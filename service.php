<?php

use Apretaste\Money;
use Apretaste\Request;
use Apretaste\Response;
use Apretaste\Tutorial;
use Apretaste\Challenges;

class Service
{
	/**
	 * Display the list of transactions
	 */
	public function _main(Request $request, Response $response)
	{
		// get all transfers
		$transfers = Money::transactions($request->person->id);

		// clean the transfers array
		foreach ($transfers as $t) {
			unset($t->sender_id);
			unset($t->sender_username);
			unset($t->receiver_id);
			unset($t->receiver_username);
			unset($t->inventory_code);
			unset($t->inventory_name);
			$t->datetime = date('d/m/Y g:i A', strtotime($t->datetime));
		}

		// create response data
		$content = [
			'credit' => number_format($request->person->credit, 2),
			'items' => $transfers
		];

		// send response
		$response->setTemplate('home.ejs', $content);
	}

	/**
	 * Show the list of ways to obtain credits
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _obtener(Request $request, Response $response)
	{
		// complete the challenge
		Challenges::complete('read-how-to-obtain-credit', $request->person->id);

		// complete tutorial
		Tutorial::complete($request->person->id, 'get_credits');

		// send data to the view
		$response->setCache('month');
		$response->setTemplate('obtain.ejs');
	}
}
