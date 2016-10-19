<?php

namespace App;

use App\GameItem;
use App\Helpers\ArrayHelpers;
use Carbon\Carbon;

class ItemDataOpskins extends ItemData {

	protected $tableName = 'saleshistory_3rdparty';

	protected $selectColumns = ['item_id', 'date', 'average_price', 'amount_sold'];

	protected $columnsNullify = [
		'avg_opskins_7', 'avg_opskins_30', 'total_sold_opskins_7', 'total_sold_opskins_30'
	];

	public function calculate($days = 90, $startTimeOverride = false, $itemIdExceptions = [])
	{
		parent::calculate($days, $startTimeOverride, $itemIdExceptions);
	}

	protected function processHistory($history)
	{
		return self::process30DaySalesHistoryThirdParty($history);
	}

	public static function process30DaySalesHistoryThirdParty($saleshistory)
	{
		$ph7 = $ph30 = $ph90 = [];

		$carbon7days = Carbon::now()->subDays(7);

		foreach ($saleshistory as $key => $history) {
			$historyDate = Carbon::parse($history->date);
			// 7 days sales history
			if ($historyDate->gte($carbon7days)) {
				$ph7[] = $history;
			}
			// 30 days sales history
			$ph30[] = $history;
		}

		$info7 = self::processSalesHistoryThirdParty($ph7, 7);
		$info30 = self::processSalesHistoryThirdParty($ph30, 30);

		return array_merge($info7, $info30);
	}

	public static function processSalesHistoryThirdParty($salesHistory, $days)
	{
		$total = 0;
		$averages = [];

		foreach ($salesHistory as $key => $history) {
			$total = $total + $history->amount_sold;
			$averages[] = $history->average_price;
		}

		$avg = ArrayHelpers::calculateAverage($averages);

		if ($avg === 0) {
			$avg = null;
		}

		$info = [
			'avg_opskins_' . $days => $avg,
			'total_sold_opskins_' . $days => $total,
		];

		return $info;
	}

}