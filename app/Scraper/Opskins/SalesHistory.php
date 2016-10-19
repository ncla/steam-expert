<?php

namespace App\Scraper\Opskins;

use App\ItemDataOpskins;
use Curl\Curl;
use App\SalesHistoryThirdParty;
use Illuminate\Support\Facades\DB;
use App\Helpers\Log;

class SalesHistory
{

	public function update()
	{
		Log::msg('Fetching data from URL..')->echo();

		$response = $this->fetch('https://opskins.com/pricelist/730.json');

		if ($response !== null) {
			$this->save($response);
		}
	}

	protected function fetch($url)
	{
		$curl = new Curl();
		$curl->get($url);

		if ($curl->error) {
			Log::msg('Error: ' . $curl->errorCode . ': ' . $curl->errorMessage)->error()->echo();
		} else {
			return $curl->response;
		}

		return null;
	}

	protected function save($items)
	{
		$i = 0;
		$batchSave = [];

		foreach ($items as $itemName => $item) {
			$i++;

			$itemDb = DB::table('gameitems')
				->select(['id', 'name'])
				->where('name', '=', $itemName)
				->where('appid', '=', 730)
				->first();

			if ($itemDb) {
				foreach ($item as $date => $salesData) {
					$batchSave[] = [
						'item_id' => $itemDb->id,
						'average_price' => $salesData->price / 100,
						'amount_sold' => $salesData->count,
						'date' => $date
					];
				}
			} else {
				Log::msg('Item ' . $itemName . ' missing from gameitems table')->echo();
			}

		}

		Log::msg('Saving 60 day OPSkins sales history in DB for ' . $i . ' items, ' . count($batchSave) . ' rows!')->echo();

		for ($i = 0; $i < count($batchSave); $i = $i + 10000) {
			Log::msg('Saving chunk ' . $i . ' to ' . ($i + 10000))->echo();

			$batchSaveChunk = array_slice($batchSave, $i, 10000);

			SalesHistoryThirdParty::insertOnDuplicateKey($batchSaveChunk, ['average_price', 'amount_sold']);
		}

		Log::msg('Done.')->echo();

		$calculator = new ItemDataOpskins();
		$calculator->calculate(30);

	}

}