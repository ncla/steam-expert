<?php

namespace App\Http\Controllers\Api;

use App\GameItem;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\PriceHistory;
use Carbon\Carbon;
use DB;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\Input;

/**
 * @Resource("Prices")
 */
class PricesController extends Controller
{
	use Helpers;

	/**
	 * Price archive
	 * Get latest item prices
	 * @Post("/items/prices/archive")
	 * @Request({"appid": 730,
	 *          "items": [
	 *               "CS:GO Case Key"
	 *          ]
	 * })
	 */
	public function getPrices()
	{
		$maxPriceCount = 2500;

		$postItems = Input::get('items');
		$appID = Input::get('appid');

		if (is_string($postItems)) {
			$postItems = json_decode($postItems, true);
		}

		if (!isset($postItems, $appID) || !is_array($postItems) || !$postItems || !is_numeric($appID)) {
			return $this->response()->errorBadRequest('Incorrect or missing parameters "appid" or "items"');
		}

		if (count($postItems) > $maxPriceCount) {
			return $this->response()->errorBadRequest('Too many entries requested');
		}

		$return = ['data' => []];
		$r = &$return['data'];

		foreach ($postItems as $postItem) {
			if (!is_string($postItem)) {
				return $this->response()->errorBadRequest('Items array contains invalid values');
			}
			$r[ $postItem ] = [
				'price' => null,
				'date'  => null
			];
		}

		/** @var  GameItem[] $gameitems */
		$gameitems = DB::table('gameitems')->selectRaw('name, updated_at as date, price')
			->where('appId', $appID)
			->whereIn('name', $postItems)
			->get();

		$requestedItems = array_flip($postItems);

		foreach ($gameitems as $item) {
			if (!isset($requestedItems[ $item->name ])) {
				continue;
			}
			$r[ $item->name ] = [
				'price' => $item->price,
				'date'  => $item->date
			];
			unset($requestedItems[ $item->name ]);
		}

		foreach ($requestedItems as $name => $trash) {
			$r[ $name ] = [
				'price' => null,
				'date'  => null
			];
		}

		return $return;
	}

	/**
	 * Prices history archive
	 * Get item sales average for range of items in specific dates
	 * @Post("/items/history/archive")
	 * @Request({"appid": 730,
	 *          "items": {"2016-03-10": [
	 *               "CS:GO Case Key"
	 *          ]
	 *      }
	 * })
	 */
	public function getPricesByDatesAndNames()
	{
		$maxPriceCount = 2500;
		$maxPriceAge = 30; //days

		$postItems = Input::get('items');
		$appID = Input::get('appid');

		if (is_string($postItems)) {
			$postItems = json_decode($postItems, true);
		}

		if (!isset($postItems, $appID) || !is_array($postItems) || !$postItems || !is_numeric($appID)) {
			return $this->response()->errorBadRequest('Incorrect or missing parameters "appid" or "items"');
		}

		$gameitemDates = [];
		$return = ['data' => []];
		$r = &$return['data'];
		$requestedItemCount = 0;

		foreach ($postItems as $date => $items) {
			if (strlen($date) < 10) {
				return $this->response()->errorBadRequest('Incorrect date passed, date format should be YYYY-MM-DD');
			}
			if (!isset($r[ $date ])) {
				$r[ $date ] = [];
			}
			if (!is_array($items)) {
				return $this->response()->errorBadRequest('Incorrect items array passed, each value of items should be an array');
			}
			foreach ($items as $itemName) {
				$gameitemDates[ $itemName ][ $date ] = $date;
				++$requestedItemCount;
			}
		}

		if ($requestedItemCount > $maxPriceCount) {
			return $this->response()->errorBadRequest('Too many entries requested');
		}

		$gameitemNames = array_keys($gameitemDates);

		/** @var GameItem[] $gameitems */
		$gameitems = DB::table('gameitems')->select('name', 'id')
			->where('appId', $appID)
			->whereIn('name', $gameitemNames)->get();

		if ($gameitems) {
			// Reindex gameitems
			$temp = [];
			foreach ($gameitems as $item) {
				$temp[ $item->name ] = $item->id;
			}
			$gameitems = $temp;
			unset($temp);

			$queriedItems = 0;
			$salesHistory = DB::table('saleshistory')->select('item_id', 'date', 'median_price');
			$salesHistory->where(function ($query) use ($gameitems, $gameitemNames, $gameitemDates, &$queriedItems, $return, &$r) {
				$temp = $gameitems;
				$lastItemId = end($temp);
				$itemsgame = array_flip($gameitems);
				while (!isset($isLastItemTrimmed) || !$isLastItemTrimmed) {
					$isLastItemTrimmed = isset($itemsgame[ $lastItemId ], $gameitemDates[ $itemsgame[ $lastItemId ] ]);
					if ($isLastItemTrimmed) {
						break;
					}
					if ($lastItemId === false) {
						return;
					}
					$lastItemId = prev($temp);
				}
				$isFirstItem = true;
				foreach ($gameitems as $itemName => $id) {
					if (!isset($gameitemDates[ $itemName ])) {
						continue;
					}
					++$queriedItems;

					$curItemDates = $gameitemDates[ $itemName ];
					if ($isFirstItem) {
						$query->where('item_id', $id);
						$isFirstItem = false;
					} else {
						$query->orWhereRaw('(item_id=?', [$id]);
					}
					$query->whereIn('date', $curItemDates);
					if ($id != $lastItemId) {
						$query->whereRaw('item_id)');
					}
				}
			});

			if ($queriedItems) {
				/** @var PriceHistory[] $salesHistory */
				$salesHistory = $salesHistory->get();

				$gameitemNames = array_flip($gameitems);
				// Ready data for return
				foreach ($salesHistory as $i => $item) {
					$r[ $item->date ][ $gameitemNames[ $item->item_id ] ] = $item->median_price;
					unset($gameitemDates[ $gameitemNames[ $item->item_id ] ][ $item->date ]);
				}
			} else {
				$this->nullValues($gameitemDates, $r);

				return $return;
			}
		}

		$dateCache = [];
		$sanitizedDates = [];
		$queries = [];
		foreach ($gameitemDates as $itemName => $dates) {
			if (!$dates || !isset($gameitems[ $itemName ])) {
				continue;
			}
			foreach ($dates as $date) {
				if (!isset($dateCache[ $date ])) {
					$cDate = Carbon::createFromFormat('Y-m-d', $date);
					$sanitizedDates[ $date ] = $cDate->toDateString();
					$dateCache[ $date ] = [
						$cDate->subDays($maxPriceAge)->toDateString(),
						$cDate->addDays($maxPriceAge * 2)->toDateString()
					];
				}

				$queries[] = DB::table('saleshistory')->selectRaw('median_price, item_id, ? as date', [$date])
					->where('item_id', $gameitems[ $itemName ])
					->whereBetween('date', $dateCache[ $date ])
					->orderByRaw('abs(DATEDIFF(date,?))', [$sanitizedDates[ $date ]])
					->take(1);
			}
		}

		if ($queries) {
			$query = $queries[0];
			$c = count($queries);
			for ($i = 1; $i < $c; $i++) {
				$query->union($queries[ $i ]);
			}
			$results = $query->get();
			foreach ($results as $result) {
				$itemName = $gameitemNames[ $result->item_id ];
				$r[ $result->date ][ $itemName ] = $result->median_price;
				unset($gameitemDates[ $itemName ][ $result->date ]);
			}
		}

		$this->nullValues($gameitemDates, $r);

		return $return;
	}

	private function nullValues($gameitemDates, &$r)
	{
		foreach ($gameitemDates as $itemName => $dates) {
			foreach ($dates as $date) {
				$r[ $date ][ $itemName ] = null;
			}
		}
	}
}
