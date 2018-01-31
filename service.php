<?php

class Credito extends Service
{
	/**
	 * Function executed when the service is called
	 *
	 * @param Request
	 * @return Response
	 */
	public function _main(Request $request)
	{
		$utils = new Utils();
		$amount = false;
		$receiver = false;

		// do not allow blank searches
		if(empty($request->query))
		{
			// get the person's credit
			$person = $utils->getPerson($request->email);
			$credit = number_format($person->credit, 2);

			$sql = "SELECT transfer.transfer_time, transfer.amount, inventory.name
				FROM transfer INNER JOIN inventory
				ON transfer.inventory_code = inventory.code
				WHERE transfer.sender = '{$request->email}'
				AND transfer.transfered = '1'
				ORDER BY transfer.transfer_time DESC
				LIMIT 0,50;";

			$connection = new Connection();
			$r = $connection->query($sql);
			if (!isset($r[0])) $r = false;

			$response = new Response();
			$response->setResponseSubject("Su credito");
			$response->createFromTemplate("home.tpl", array("credit"=>$credit, "items" => $r));

			return $response;
		}

		// get the email and the amount to send
		foreach(explode(" ", $request->query) as $value)
		{
			// check if it is a valid @username or email
			$temp = $utils->getEmailFromUsername($value);
			if($temp) $value = $temp;
			if(filter_var($value, FILTER_VALIDATE_EMAIL)) $receiver = $value;

			// check if it is a valid money amount
			$number = str_replace(",", ".", $value);
			if(preg_match("/^-?[0-9]+(?:\.[0-9]{1,2})?$/", $number)) $amount = $number;
		}

		// return error response if the receiver or the amount are wrong
		if(empty($amount) || empty($receiver))
		{
			if(empty($receiver)) $message = "El email o @username de la persona a recibir no es correcto. Puede que usted halla escrito el email mal por error.";
			else $message = "La cantidad insertada no es correcta, parece que usted inserto un n&uacute;mero que no es v&aacute;lido.";

			// send response to the user
			$responseContent = array("message" => $message, "query" => $request->query);
			$response = new Response();
			$response->subject = "El email o la cantidad a transferir son incorrectas";
			$response->createFromTemplate("invalid.tpl", $responseContent);
			return $response;
		}

		// check if the person exist. If not, message the requestor
		if( ! $utils->personExist($receiver))
		{
			$responseContent = array("email" => $receiver);
			$response = new Response();
			$response->subject = "La persona no existe";
			$response->createFromTemplate("inexistent.tpl", $responseContent);
			return $response;
		}

		// check if you have enough credit to transfer
		$profile = $utils->getPerson($request->email);
		if($profile->credit < $amount)
		{
			// send response to the user
			$responseContent = array("amount" => $amount, "credit" => $profile->credit, "email" => $receiver);
			$template = "nocredit.tpl";

			if($request->subject == "PURCHASE")
			{
				$template = "nocreditPurchase.tpl";
				$responseContent = array_merge($responseContent, array("nameOfItemToPurchase" => $request->body));
			}

			$response = new Response();
			$response->subject = "Usted no tiene suficiente credito";
			$response->createFromTemplate($template, $responseContent);
			return $response;
		}

		// save the transfer intention in the database
		$confirmationHash = $utils->generateRandomHash();
		$inventory_code = $request->subject == "PURCHASE" ? $request->name : "NULL";
		$query = "INSERT INTO transfer(sender,receiver,amount,confirmation_hash,inventory_code) VALUES ('{$request->email}', '$receiver', '$amount', '$confirmationHash', '$inventory_code')";
		$connection = new Connection();
		$connection->query($query);

		// create the variables for the view
		$template = "confirmation.tpl";
		$responseContent = array("amount" => $amount, "receiver" => $this->utils->getUsernameFromEmail($receiver), "hash" => $confirmationHash);

		if($request->subject == "PURCHASE")
		{
			$template = "confirmPurchase.tpl";
			$responseContent = array_merge($responseContent, array("nameOfItemToPurchase" => $request->body));
		}

		// email the confirmation to transfer the credits
		$response = new Response();
		$response->subject = "Necesitamos su confirmacion para continuar";
		$response->createFromTemplate($template, $responseContent);
		return $response;
	}

	/**
	 * Function executed when the subservice is called
	 *
	 * @param Request
	 * @return Response
	 */
	public function _aceptar(Request $request)
	{
		$hash = $request->query;

		// get the transfer details, ensure the transfer is valid
		$connection = new Connection();
		$transfer = $connection->query("SELECT * FROM transfer WHERE confirmation_hash = '$hash' && transfered = 0");

		// error if the hash was not valid or the transaction was used already
		if(empty($hash) || empty($transfer))
		{
			$response = new Response();
			$response->subject = "Error procesando su transferencia o pago";
			$response->createFromText("Tuvimos un error procesando su transferencia, o puede que esta transferencia ya se halla cobrado. Su cr&eacute;dito no ha sido afectado. Por favor intente nuevamente.");
			return $response;
		}

		// get the elapsed time since the transfer was requested
		$transferRow = $transfer[0];
		$seconds = time() - strtotime($transferRow->transfer_time);
		$elapsedTimeInHours = $seconds / 60 / 60;

		// error if the transfer is out of date
		if($elapsedTimeInHours > 1)
		{
			$response = new Response();
			$response->subject = "Su transferencia o pago ha expirado";
			$responseContent = array("amount" => $transferRow->amount, "receiver" => $this->utils->getUsernameFromEmail($transferRow->receiver));
			$response->createFromTemplate("expired.tpl", $responseContent);
			return $response;
		}

		// check if you still have enough credit to transfer
		$utils = new Utils();
		$profile = $utils->getPerson($transferRow->sender);
		if($profile->credit < $transferRow->amount)
		{
			// send response to the user
			$responseContent = array("amount" => $transferRow->amount, "credit" => $profile->credit, "email" => $this->utils->getUsernameFromEmail($transferRow->receiver));
			$response = new Response();
			$response->subject = "No tiene suficiente credito";
			$response->createFromTemplate("nocredit.tpl", $responseContent);
			return $response;
		}

		// make the transfer
		$connection->query("
			START TRANSACTION;
			UPDATE person SET credit=credit-{$transferRow->amount} WHERE email='{$request->email}';
			UPDATE person SET credit=credit+{$transferRow->amount} WHERE email='{$transferRow->receiver}';
			UPDATE transfer SET transfered=1 WHERE id='{$transferRow->id}';
			COMMIT;");

		// if it is a transfer
		if($transferRow->inventory_code == "NULL")
		{
			$template = "receipt.tpl";
			$itemBought = ""; // empty for transfers
			$subject = "Su transferencia ha sido realizada correctamente";
		}
		// if it is a sell, execute the selling code
		else
		{
			// get the transfer row
			$inventory = $connection->query("SELECT * FROM inventory WHERE code = '{$transferRow->inventory_code}'")[0];
			$serviceName = strtolower($inventory->service);

			// include the service
			include_once $utils->getPathToService($serviceName) . "/service.php";
			$object = new $serviceName();

			// if the object has a method payment
			if(method_exists($object, "payment"))
			{
				// create the payment object
				$payment = new Payment();
				$payment->code = $inventory->code;
				$payment->price = $inventory->price;
				$payment->name = $inventory->name;
				$payment->seller = $inventory->seller;
				$payment->buyer = $request->email;
				$payment->transfer = $transferRow;
				// call the payment function
				$object->payment($payment);
			}

			$template = "receiptPurchase.tpl";
			$subject = "Su pago se ha efectuado correctamente";
			$itemBought = $inventory->name;
		}

		// send a notification to the receiver
		$msg = "Usted ha recibido §{$transferRow->amount} de credito";
		$this->utils->addNotification($transferRow->receiver, 'Credito', $msg, 'CREDITO', 'IMPORTANT');

		// create response to send to the user
		$responseContent = array(
			"amount" => $transferRow->amount,
			"receiver" => $this->utils->getUsernameFromEmail($transferRow->receiver),
			"itemBought" => $itemBought // used only for payment
		);

		// send the receipt to the sender
		$response = new Response();
		$response->subject = $subject;
		$response->createFromTemplate($template, $responseContent);
		return $response;
	}

	/**
	 * Function executed when the subservice is called
	 *
	 * @param Request
	 * @return Response
	 */
	public function _comprar(Request $request)
	{
		$code = $request->query;

		// get the payment details
		$connection = new Connection();
		$inventory = $connection->query("SELECT * FROM inventory WHERE code = '$code' && active = 1");

		// error if the code was not valid or the inventory item cannot be found or its not active
		if(empty($code) || empty($inventory))
		{
			$article = empty($code) ? "" : ", $code, ";
			$response = new Response();
			$response->subject = "Articulo incorrecto o temporalmente agotado";
			$response->createFromText("El articulo que usted pidi&oacute; comprar{$article}no existe o se encuentra temporalmente agotado. Por favor compruebe el c&oacute;digo e intente nuevamente.");
			return $response;
		}

		// get the element from the inventory
		$inventory = $inventory[0];

		// start a new transfer
		$r = new Request();
		$r->subject = "PURCHASE";
		$r->name = $inventory->code;
		$r->body = $inventory->name;
		$r->email = $request->email;
		$r->query = $inventory->price . " " . $inventory->seller;
		return $this->_main($r);
	}
}
