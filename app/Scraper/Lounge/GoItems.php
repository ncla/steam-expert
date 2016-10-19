<?php
namespace App\Scraper\Lounge;

use App\CsglItem;
use App\GameItem;

class GoItems
{
	protected $API_URL = 'http://csgolounge.com/api/schema.php';

	public function __construct()
	{
		return $this;
	}

	public function createRequestToSchema()
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->API_URL);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);

		return $response;
	}

	public function parseSchema($schema)
	{
		$jsonDecoded = json_decode($schema, true);

		$itemList = [];
		if ($jsonDecoded !== null) {
			foreach ($jsonDecoded as $item) {
				$value = floatval($item['worth']);
				$name = trim($item['name']);
				$itemList[ $name ] = $value;
			}
		}

		return $itemList;
	}

	public function insertItems($items)
	{
		//header('Content-Type: text/html; charset=utf-8');

		// We do this to avoid inserting items that violate foreign key check
		$gameItems = GameItem::where('appid', '=', '730')->get()->toArray();

		$marketItems = [];
		foreach ($gameItems as $item) {
			$marketItems[ $item['name'] ] = $item['id'];
		}
		// Doing this because of Error MassAssignmentException in Model.php line 404: id
		\Eloquent::unguard();

		// FUCK IT, WHY NOT CREATE HUNDREDS OF QUERIES?
		foreach ($items as $name => $value) {
			// Only if the item is on Steam market
			if (isset($marketItems[ $name ])) {
				//var_dump($marketItems[$name]); var_dump($value);
				CsglItem::updateOrCreate(
					['id' => $marketItems[ $name ]],
					['id' => $marketItems[ $name ], 'value' => $value]
				);
			}
		}

	}

	public function update()
	{
		$response = $this->createRequestToSchema();
		$itemList = $this->parseSchema($response);
		$this->insertItems($itemList);
	}
}