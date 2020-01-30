<?php

use Apretaste\Money;
use Apretaste\Person;
use Apretaste\Request;
use Apretaste\Response;
use Apretaste\Challenges;
use Apretaste\Level;

class Service
{
	/**
	 * Main function
	 */
	public function _main(Request $request, Response &$response)
	{
		// get all transfers
		$transfers = Money::transactions($request->person->id);

		// clean the transfers array
		foreach ($transfers as $t) {
			unset($t->sender_id);
			unset($t->sender_username);
			unset($t->receiver_id);
			unset($t->receiver_username);
			unset($t->reason);
			unset($t->inventory_code);
			unset($t->inventory_name);
			$t->datetime = date('d/m/Y g:ia', strtotime($t->datetime));
		}

		// create response data
		$content = [
				'credit' => $request->person->credit,
				'items' => $transfers,
				'canTransfer' => $request->person->level >= Level::TOPACIO,
		];

		// send response
		$response->setTemplate('home.ejs', $content);
	}

	/**
	 * Show the list of ways to obtain credits
	 *
	 * @param Request
	 * @param Response
	 *
	 * @throws \Exception
	 */
	public function _obtener(Request $request, Response &$response)
	{
		$response->setCache('month');
		$response->setTemplate('obtain.ejs');

		Challenges::complete('read-how-to-obtain-credit', $request->person->id);
	}

	/**
	 * Starts a new transfer
	 *
	 * @param \Apretaste\Request $request
	 * @param \Apretaste\Response $response
	 *
	 * @throws \Framework\Alert
	 */
	public function _enviar(Request $request, Response &$response)
	{
		// error if you do not have enought level to transfer
		if ($request->person->level < Level::TOPACIO) {
			$response->setTemplate('message.ejs', [
					'header' => 'Nivel insuficiente',
					'icon' => 'sentiment_very_dissatisfied',
					'text' => '¡Hola! Usted aún no es nivel Topacio, por lo cual no podrá realizar una tranferencia de crédito. Siga usando la app para subir de nivel.',
					'button' => ['href' => 'PERFIL NIVELES', 'caption' => 'Ver mi nivel']
			]);
			return;
		}

		$response->setCache('year');
		$response->setTemplate('enviar.ejs', ['credit' => $request->person->credit]);
	}

	/**
	 * Execute a transfer
	 *
	 * @param \Apretaste\Request $request
	 * @param \Apretaste\Response $response
	 *
	 * @return void
	 * @throws \Framework\Alert
	 */
	public function _transfer(Request $request, Response &$response)
	{
		// error if you do not have enought level to transfer
		if ($request->person->level < Level::TOPACIO) {
			$response->setTemplate('message.ejs', [
					'header' => 'Nivel insuficiente',
					'icon' => 'sentiment_very_dissatisfied',
					'text' => '¡Hola! Usted aún no es nivel Topacio, por lo cual no podrá realizar una tranferencia de crédito. Siga usando la app para subir de nivel.',
					'button' => ['href' => 'PERFIL NIVELES', 'caption' => 'Ver mi nivel']
			]);
			return;
		}

		// get params for the transfer
		$amount = (float) $request->input->data->price;
		$username = $request->input->data->username;
		$reason = $request->input->data->reason;

		// get the person who will receive the funds
		$person = Person::find($username);

		if ($person === false) {
			$response->setTemplate('message.ejs', [
					'header' => 'Usuario no encontrado',
					'icon' => 'sentiment_very_dissatisfied',
					'text' => 'El usuario al cual quiere transferir no existe en el sistema. Por favor intente nuevamente.',
					'button' => ['href' => 'CREDITO ENVIAR', 'caption' => 'Transferir']
			]);
			return;
		}

		// send the transfer
		try {
			Money::send($request->person->id, $person->id, $amount, $reason);
		} catch (Exception $e) {
			$response->setTemplate('message.ejs', [
					'header' => 'Error inesperado',
					'icon' => 'sentiment_very_dissatisfied',
					'text' => 'Encontramos un error inesperado transfiriendo su crédito. Por favor intente nuevamente.',
					'button' => ['href' => 'CREDITO ENVIAR', 'caption' => 'Transferir']
			]);
			return;
		}

		// return ok message
		$response->setTemplate('message.ejs', [
				'header' => 'Crédito enviado',
				'icon' => 'pan_tool',
				'text' => "¡Chócala! Usted ha enviado §$amount a @{$person->username} correctamente. Esta transfencia se mostrará en sus transacciones.",
				'button' => ['href' => 'CREDITO', 'caption' => 'Transacciones']
		]);
	}
}
