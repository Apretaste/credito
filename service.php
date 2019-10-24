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

		// clean the transfers array
		foreach ($transfers as $t) {
			unset($t->sender_id);
			unset($t->sender_username);
			unset($t->receiver_id);
			unset($t->receiver_username);
			unset($t->reason);
			unset($t->inventory_code);
			unset($t->inventory_name);
			$t->datetime = date("d/m/Y g:ia", strtotime($t->datetime));
		}

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
		$username = $request->input->data->username;
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
				"button" => ["href" => "CREDITO ENVIAR", "caption" => "Transferir"]
			]);
		}

		// return ok message
		return $response->setTemplate('message.ejs', [
			"header" => "Crédito enviado",
			"icon" => "pan_tool",
			"text" => "¡Chócala! Usted ha enviado §$amount a @{$person->username} correctamente. Esta transfencia se mostrará en sus transacciones.",
			"button" => ["href" => "CREDITO", "caption" => "Transacciones"]
		]);
	}
}
