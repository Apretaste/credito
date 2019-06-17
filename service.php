<?php

class Service
{

	/**
	 * Main function
	 *
	 * @param Request
	 *
	 * @return Response
	 */
	public function _main(Request $request, Response $response)
	{
		// get latest purchases
		$items = Connection::query("
			SELECT A.transfer_time, A.amount, B.name, C.username 
			FROM transfer A 
			LEFT JOIN inventory B 
			ON A.inventory_code = B.code 
			JOIN person C 
			ON A.receiver = C.email  
			WHERE A.sender_id = '{$request->person->id}'
			AND A.transfered = '1' 
			ORDER BY A.transfer_time DESC 
			LIMIT 50");

		// prepare data for the view
		$content = [
			"credit" => $request->person->credit,
			"items"  => $items,
		];

		// send data to the template
		$response->setTemplate("home.ejs", $content);
	}

	/**
	 * Show the list of ways to obtain credits
	 *
	 * @param Request
	 * @param Response
	 */
	public function _obtener(Request $request, Response $response)
	{
		$response->setCache("month");
		$response->setTemplate('obtain.ejs');
	}

	/**
	 * Starts a new transfer
	 *
	 * @param Request
	 *
	 * @return Response
	 */
	public function _transferir(Request $request, Response $response)
	{
		$response->setCache("year");
		$response->setTemplate('transfer.ejs', ["credit" => $request->person->credit]);
	}

	/**
	 * Starts a new transfer OR sale
	 * for TRANSFER data:{username:@USERNAME, price:NUMBER}
	 * for SALE data:{item:INVENTARY_CODE}
	 *
	 * @param Request
	 * @param Response
	 *
	 * @return \Response
	 */
	public function _procesar(Request $request, Response $response)
	{
		// inicialize params
		$person = $price = $code = $article = $sale = "";

		// if this is a purchase, get params from the item
		if (isset($request->input->data->item)) {
			$sale = true;
			$code = strtoupper($request->input->data->item);
			$item = Connection::query("SELECT name, price, seller FROM inventory WHERE code = '$code'");
			if ($item) {
				$price   = $item[0]->price;
				$article = $item[0]->name;
				$person  = Utils::getPerson($item[0]->seller);
			}
		}

		// if this is a transfer, get params from the params
		if (isset($request->input->data->username) && isset($request->input->data->price)) {
			$sale     = false;
			$price    = floatval($request->input->data->price);
			$username = trim($request->input->data->username, "@");
			$person   = Utils::getPerson($username);
		}

		// do not let pass invalid information or invalid credit
		if (empty($person) || empty($price) || $request->person->credit < $price) {
			return $response->setTemplate('message.ejs', [
				"header" => "Datos incorrectos",
				"icon"   => "sentiment_very_dissatisfied",
				"text"   => "Hay un error con el @username o email de la persona a recibir o con la cantidad a enviar. Puede que la persona no exista en Apretaste o que la cantidad no sea válida. Por favor verifique los datos e intente nuevamente.",
				"button" => ["href" => "CREDITO TRANSFERIR", "caption" => "Transferir"],
			]);
		}

		// save the transfer intention in the database
		$confirmationHash = Utils::generateRandomHash();
		Connection::query("
			INSERT INTO transfer (sender,sender_id, receiver, receiver_id, amount,confirmation_hash,inventory_code)
			VALUES ('{$request->person->email}',{$request->person->id}, '{$person->email}',{$person->id},'$price','$confirmationHash','$code')");

		// create the variables for the view
		$content = [
			"price"    => $price,
			"receiver" => $person,
			"article"  => $article,
			"sale"     => $sale,
			"hash"     => $confirmationHash,
		];

		// email the confirmation to transfer the credits
		$response->setTemplate("confirmation.ejs", $content);
	}

	/**
	 * Accepts the details of the transfer and submit
	 *
	 * @param Request
	 *
	 * @return Response
	 */
	public function _aceptar(Request $request, Response $response)
	{
		// get the transfer details to ensure the transfer is valid
		$transfer = Connection::query("
			SELECT * FROM transfer 
			WHERE confirmation_hash = '{$request->input->data->hash}' 
			AND transfer_time > (NOW()-INTERVAL 1 HOUR)
			AND transfered = 0");
		$transfer = empty($transfer) ? false : $transfer[0];

		// error if the hash is invalid or the transaction was used already
		if (!$transfer || $transfer->amount > $request->person->credit) {
			return $response->setTemplate('message.ejs', [
				"header" => "Error inesperado",
				"icon"   => "sentiment_very_dissatisfied",
				"text"   => "Tuvimos un error procesando su transferencia, o puede que esta transferencia halla expirado o ya se halla cobrado. Su crédito no ha sido afectado.",
				"button" => ["href" => "CREDITO", "caption" => "Mis Transferencias"],
			]);
		}

		// get the Person objects for the receiver
		$receiver = Utils::getPerson($transfer->receiver);

		// if is a purchase, execute the service method
		$item = false;
		if ($transfer->inventory_code) {
			// get the transfer row
			$item = Connection::query("SELECT * FROM inventory WHERE code = '{$transfer->inventory_code}'")[0];

			// create the payment object
			$payment         = new Payment();
			$payment->code   = $item->code;
			$payment->price  = $item->price;
			$payment->name   = $item->name;
			$payment->seller = $receiver;
			$payment->buyer  = $request->person;

			// update the amount
			$transfer->amount = $item->price;

			// include and call the payment function
			include_once Utils::getPathToService(strtolower($item->service)) . "/functions.php";
			$paymentResult = payment($payment);

			// let the user know if there is the product is out of stock
			if (!$paymentResult) {
				return $response->setTemplate('message.ejs', [
					"header" => "Producto no disponible",
					"icon"   => "sentiment_very_dissatisfied",
					"text"   => "Encontramos un problema procesando su transferencia. Lo más posible es que el producto se halla agotado temporalmente. Su crédito no ha sido afectado.",
					"button" => ["href" => $item->service, "caption" => "Ir al servicio"],
				]);
			}
		}

		// transfer the credit and mark as DONE
		Connection::query("
			START TRANSACTION;
			UPDATE person SET credit=credit-{$transfer->amount} WHERE id = '{$request->person->id}';
			UPDATE person SET credit=credit+{$transfer->amount} WHERE id = '{$transfer->receiver_id}';
			UPDATE transfer SET transfered=1 WHERE id='{$transfer->id}';
			COMMIT;");

		// send a notification to the receiver
		Utils::addNotification($receiver->id, "Usted ha recibido §{$transfer->amount} de crédito", '{"command":"CREDITO"}', "attach_money");

		// create response to send to the user
		$content = [
			"amount"   => $transfer->amount,
			"receiver" => $receiver,
			"item"     => $item,
		];

		// send the receipt to the sender
		$response->setTemplate("receipt.ejs", $content);
	}
}
