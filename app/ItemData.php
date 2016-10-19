<?php

namespace App;

use App\GameItem;
use App\Helpers\ArrayHelpers;
use App\Helpers\Log;
use App\Helpers\MemoryHelpers;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Class ItemData
 * General class for calculating various item data
 */
class ItemData
{

	const CHUNK_SIZE = 1375;

	protected $tableName = 'saleshistory';

	protected $selectColumns = ['item_id', 'date', 'median_price', 'amount_sold'];

	protected $columnsNullify = [
		'avg_median_7', 'avg_median_30', 'avg_median_90', 'median_7', 'median_30', 'median_90',
		'trend_7', 'trend_30', 'trend_90', 'total_sold_7', 'total_sold_30', 'total_sold_90'
	];

	protected $updatedItemsCalculate = 0;

	public function calculate($days = 90, $startTimeOverride = false, $itemIdExceptions = [])
	{
		$t = microtime(true);
		Log::msg('Calculating item data for all items')->info();

		$formatted_date = Carbon::now()->subDays($days)->toDateString();
		$startTime = Carbon::now()->toDateTimeString();

		// Here we are storing only one items history at the time
		$oneItemSalesHistory = null;

		$salesHistoryPosition = 0;

		// Array of item calculations that will be ready to saved in DB
		$results = [];
		$i = 0;

		$lastGameItemId = Gameitem::max('id');
		do {
			Log::msg('Processing new chunk, from ' . $salesHistoryPosition . ' to ' . ($salesHistoryPosition + self::CHUNK_SIZE))->echo();

			$salesHistory = DB::table($this->tableName)
				->select($this->selectColumns)
				->where('date', '>=', $formatted_date)
				->where('item_id', '>', $salesHistoryPosition)
				->where('item_id', '<=', ($salesHistoryPosition + self::CHUNK_SIZE))
				->whereNotIn('item_id', $itemIdExceptions)
				->orderBy('item_id')
				->orderBy('date')
				->get();

			$salesHistoryCount = count($salesHistory);

			// If no price history records are found, increment the position to next chunk
			if (!$salesHistoryCount) {
				Log::msg('No saleshistory rows in chunk ' . $salesHistoryPosition . ' to ' . ($salesHistoryPosition + self::CHUNK_SIZE))->echo()->info();
				$salesHistoryPosition += self::CHUNK_SIZE;
				$oneItemSalesHistory = null;
			}

			// This is how we process chunks of sales history. All price history points are fetched for x amount of items,
			// determined by CHUNK_SIZE. The $salesHistory is ordered by ID, so that we are going one item at time, and
			// when we have reached new item, we can process new unique item. $salesHistory will only have from past 90 days,
			// the data from those days is processed by self::process90DaySalesHistory appropriately.

			// You can specify what items you do not want to update by giving list of item IDs in $itemIdExceptions
			foreach ($salesHistory as $history) {
				// Here we check if we have gotten a new item by comparing the new $history item ID with previously processed $history
				if ($history->item_id !== $salesHistoryPosition) {
					// If we are here, that means we got a new item!
					// We temporarily store the previous items ID
					$oldItemId = $salesHistoryPosition;

					// Ignore the zero, because that's the initial one
					if ($oldItemId !== 0 && $oneItemSalesHistory) {
						// Parse the price history and append data to $results
						$results[ $oldItemId ] = $this->processHistory($oneItemSalesHistory);

						$this->updatedItemsCalculate++;

						// It will be a new item in next cycle, so we set it to null
						$oneItemSalesHistory = null;
					}

					$oneItemSalesHistory[] = $history;

					// Set the item ID we looped through
					$salesHistoryPosition = $history->item_id;
				}
				// If not we just keep continue adding to the $oneItemSalesHistory array, which is self-explanatory.
				// The array will only have one item's history at a time
				else {
					$oneItemSalesHistory[] = $history;
				}
			}

			Log::msg('Memory usage ' . MemoryHelpers::convert(memory_get_usage()))->info()->echo(true);
			++$i;

			self::insertItemCalculations($results);
			$results = [];

			Log::msg('Processed a chunk with ' . $salesHistoryCount . ' saleshistory rows')->echo();

			$firstCondition = $salesHistory || $lastGameItemId >= $salesHistoryPosition;

			$finalCondition = true;
			// Check if any price history rows are still left unparsed
			if (!$firstCondition) {
				$finalCondition = DB::table($this->tableName)
					->where('date', '>=', $formatted_date)
					->where('item_id', '>', $salesHistoryPosition - self::CHUNK_SIZE)
					->whereNotIn('item_id', $itemIdExceptions)
					->count();
			}
		} while ($finalCondition);

		if ($results) {
			Log::msg('All chunks have been processed, processing left overs')->info()->echo();

			// Lets not forget about last item sales history
			$results[ $oneItemSalesHistory[0]->item_id ] = $this->processHistory($oneItemSalesHistory);
			$oneItemSalesHistory = null;
			self::insertItemCalculations($results);
			//$this->updatedItemsCalculate++;
		} else {
			Log::msg('All chunks have been processed in one go')->info()->echo();
		}

		$columnsNullify = [];

		foreach ($this->columnsNullify as $column) {
			$columnsNullify[ $column ] = null;
		}

		// Clear old calculations
		DB::table('gameitems')
			->where('calculations_updated_at', '<', ($startTimeOverride === false ? $startTime : $startTimeOverride))
			->update($columnsNullify);
		Log::msg('Old calculations have been cleared')->info();

		var_dump($this->updatedItemsCalculate);

		$total = microtime(true) - $t;

		$data = ['calculation_time' => round($total, 3), 'updated_item_count' => $this->updatedItemsCalculate];
		Log::array($data)->slack()->perflog()->echo();
	}

	protected function processHistory($history)
	{
		return self::process90DaySalesHistory(self::filterCheapAccidentSales($history));
	}

	public static function insertItemCalculations($results)
	{
		$currentTime = Carbon::now()->toDateTimeString();

		foreach ($results as $itemID => $data) {
			$dataToDb = $data;
			$dataToDb['calculations_updated_at'] = $currentTime;

			DB::table('gameitems')
				->where('id', $itemID)
				->update($dataToDb);
		}

		Log::msg('Inserted new data into database')->info();
	}

	public static function filterCheapAccidentSales($salesHistory)
	{
		// Calculate average
		$average = self::calculateAverageFromSalesHistory($salesHistory);

		$salesHistory = array_filter($salesHistory, function ($val) use (&$average) {
			return ($average / $val->median_price < 1000);
		});


		return $salesHistory;
	}

	/**
	 * @param $salesHistory
	 * @param bool $passingFullHistory If we are passing sales history that has all history points (and not filtered by DB query beforehand)
	 *
	 *@return mixed
	 */
	public static function process90DaySalesHistory($salesHistory, $passingFullHistory = false)
	{
		// Loop through all shit and add to its appropriate array (7/30/90 days array)
		$ph7 = $ph30 = $ph90 = [];

		$carbon7days = Carbon::now()->subDays(7);
		$carbon30days = Carbon::now()->subDays(30);
		$carbon90days = Carbon::now()->subDays(90);

		foreach ($salesHistory as $key => $history) {
			$historyDate = Carbon::parse($history->date);
			// 7 days sales history
			if ($historyDate->gte($carbon7days)) {
				$ph7[] = $history;
			}
			// 30 days sales history
			if ($historyDate->gte($carbon30days)) {
				$ph30[] = $history;
			}
			// All sales history (90 days)

			if ($passingFullHistory === true && $historyDate->gte($carbon90days)) {
				$ph90[] = $history;
			}

			if ($passingFullHistory === false) {
				$ph90[] = $history;
			}
		}

		$info7 = self::processSalesHistory($ph7, 7);
		$info30 = self::processSalesHistory($ph30, 30);
		$info90 = self::processSalesHistory($ph90, 90);

		return array_merge($info7, $info30, $info90);
	}

	public static function calculateMedianFromSalesHistory($salesHistory)
	{
		$soldFor = [];
		foreach ($salesHistory as $key => $value) {
			$soldFor[] = $value->median_price;
		}

		// Calculate median
		return ArrayHelpers::calculateMedian($soldFor);
	}

	/**
	 * Calculate average/trending %/total sold for past x days
	 *
	 * @param $salesHistory
	 * @param $days

	 *
	 * @return array
	 */
	public static function processSalesHistory($salesHistory, $days)
	{
		$total = 0;
		$medians = [];

		foreach ($salesHistory as $key => $history) {
			$total = $total + $history->amount_sold;
			$medians[] = $history->median_price;
		}

		$avgMedian = ArrayHelpers::calculateAverage($medians);
		$median = ArrayHelpers::calculateMedian($medians);

		// Yes, a one-liner, please don't hit me
		if ($avgMedian === 0) {
			$avgMedian = null;
		}
		if ($median === 0) {
			$median = null;
		}

		$info = [
			'avg_median_' . $days => $avgMedian,
			'median_' . $days     => $median,
			'total_sold_' . $days => $total,
			'trend_' . $days      => self::calculateTrendingPercentage($salesHistory),
		];

		if ($info[ 'total_sold_' . $days ] < 3) {
			$info[ 'avg_median_' . $days ] = null;
			$info[ 'median_' . $days ] = null;
		}

		return $info;
	}

	public static function calculateTrendingPercentage($salesHistory)
	{
		if (count($salesHistory) < 3) {
			return null;
		}

		$xValues = [];
		$yValues = [];

		foreach ($salesHistory as $key => $value) {
			$xValues[] = $key + 1;
			$yValues[] = $value->median_price;
		}

		// TODO: Is this acceptable for it to still be 'average'?
		$meanX = ArrayHelpers::calculateAverage($xValues);

		$meanY = ArrayHelpers::calculateAverage($yValues);

		$meanXY = $meanXSquare = 0;

		foreach ($xValues as $key => $value) {
			$meanXY = $meanXY + ($xValues[ $key ] * $yValues[ $key ]);
			$meanXSquare = $meanXSquare + pow($xValues[ $key ], 2);
		}

		$meanXY = $meanXY / count($xValues);
		$meanXSquare = $meanXSquare / count($xValues);

		$m = (($meanX * $meanY) - ($meanXY)) / (pow($meanX, 2) - $meanXSquare);

		$b = $meanY - ($m * $meanX);

		$y = $m * count($xValues) + $b;

		if ($b == 0) {
			Log::msg('Saleshistory: division by zero error')->info();

			return null;
		}

		$trend = (float) number_format(((($y / $b) * 100) - 100), 2);

		return $trend;
	}

	public static function calculateAverageFromSalesHistory($salesHistory)
	{
		$soldFor = [];

		foreach ($salesHistory as $key => $value) {
			$soldFor[] = $value->median_price;
		}

		// Calculate average
		return ArrayHelpers::calculateAverage($soldFor);
	}

}