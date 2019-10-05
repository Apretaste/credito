<?php

class Service
{
	/**
	 * Main function
	 */
	public function _main(Request $request, Response $response)
	{
		// get all transfers
		$transfers = MoneyNew::transactions($request->person->id);

		// create response data
		$content = [
			'credit' => $request->person->credit,
			'items' => $transfers
		];

		// send response
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
	 * @return Response
	 */
	public function _enviar(Request $request, Response $response)
	{
		$response->setCache("year");
		$response->setTemplate('enviar.ejs', ["credit" => $request->person->credit]);
	}

	/**
	 * Execute a transfer
	 *
	 * @param Request
	 * @return Response
	 */
	public function _transfer(Request $request, Response $response)
	{
		// get params for the transfer 
		$amount = (float)$request->input->data->price;
		$username = trim($request->input->data->username, "@");
		$reason = $request->input->data->reason;

		// get the person who will receive the funds
		$person = Utils::getPerson($username);
 
		// send the transfer
		try {
			MoneyNew::send($request->person->id, $person->id, $amount, $reason);
		} catch (Exception $e) {
			return $response->setTemplate('message.ejs', [
				"header" => "Error inesperado",
				"icon" => "sentiment_very_dissatisfied",
				"text" => "Encontramos un error inesperado transfiriendo su crédito. Por favor intente nuevamente.",
				"button" => ["href" => "CREDITO TRANSFERIR", "caption" => "Transferir"]
			]);
		}

		// return ok message
		return $response->setTemplate('message.ejs', [
			"header" => "Crédito enviado",
			"icon" => "pan_tool",
			"text" => "¡Chócala! Usted ha enviado §$amount a @$username correctamente. Esta transfencia se mostrará en sus transacciones.",
			"button" => ["href" => "CREDITO", "caption" => "Transacciones"]
		]);
	}

	/**
	 * Execute a payment
	 *
	 * @param Request
	 * @return Response
	 */
	public function _purchase(Request $request, Response $response)
	{
		// get params for the purchase
		$buyer = $request->person->id;
		$inventory = strtoupper($request->input->data->item);

		// send the transfer
		try {
			$pay = MoneyNew::buy($buyer, $inventory);
		} catch (Exception $e) {
			return $response->setTemplate('message.ejs', [
				"header" => "Error inesperado",
				"icon" => "sentiment_very_dissatisfied",
				"text" => "Encontramos un error en su compra. Puede que los productos se hallan acabado o el vendedor no este activo en este momento. Por favor intente nuevamente y si el problema persiste, consulte al soporte.",
				"button" => ["href" => "CREDITO", "caption" => "Ver crédito"]
			]);
		}

		// return ok message
		return $response->setTemplate('message.ejs', [
			"header" => "Compra realizada",
			"icon" => "pan_tool",
			"text" => "¡Chócala! Usted ha canjeado §{$pay->price} por {$pay->name} correctamente. Esta transfencia se mostrará en sus transacciones.",
			"button" => ["href" => "CREDITO", "caption" => "Transacciones"]
		]);
	}

	/**
	 * ALIAS for _purchase, for backwards compatibility.
	 * NOTE: Delete when all other services call _purchase
	 *
	 * @param Request
	 * @param Response
	 */
	public function _procesar(Request $request, Response $response)
	{
		return $this->_purchase($request, $response);
	}
}
