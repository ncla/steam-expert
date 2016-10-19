<?php

namespace App\Http\Controllers\Api;

use App\GameItem;
use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Transformers\HistoryTransformer;
use Dingo\Api\Routing\Helpers;

/**
 * @Resource("Items history")
 */
class ItemsHistoryController extends Controller
{
	use Helpers;

	/**
	 * Market sales history
	 *
	 * @param  string $name
	 *
	 * @return \Illuminate\Http\Response
	 * @Parameters({
	 *      @Parameter("appid", description="If there are multiple results from different applications, you can query by specific application ID.", default=0),
	 *      @Parameter("subappid", description="SubAppId is used only for Steam items (emoticons, wallpapers etc.), currently only 753 application utilizes a sub app ID.", default=0)
	 * })
	 * @Get("/items/{identifier}/{identifierValue}/saleshistory/{optionalDate}")
	 * @Versions({"v1"})
	 */
	public function getItemHistory($identifier, $identifierValue, $date = null)
	{
		if ($identifier !== 'id' && $identifier !== 'name') {
			return $this->response()->errorBadRequest('Incorrect identifier specified, use "id" or "name" as identifier');
		}

		if ($identifier === 'id') {
			$item = GameItem::where('id', (int)$identifierValue);
		}

		if ($identifier === 'name') {
			$item = GameItem::where('name', $identifierValue);

			if (\Request::query('appid') !== null) {
				$item->appid((int)\Request::query('appid'));
			}

			if (\Request::query('subappid') !== null) {
				$item->subappid((int)\Request::query('subappid'));
			}
		}

		$item = $item->first();

		if (!$item) {
			return $this->response()->errorNotFound('No item found with this ID, name, appID or subAppID');
		}

		$history = $item->saleshistory;

		if (count($history) === 0) {
			return $this->response()->errorNotFound('No sales history found for this item');
		}

		if ($date !== null) {
			$history = $history->groupBy('date');
			$history = $history->get($date);

			if ($history === null) {
				return $this->response()->errorNotFound('No sales history found for this item at this date');
			}

			$history = $history->first();

			return $this->response()->item($history, new HistoryTransformer, []);
		}

		$history = $history->sortByDesc('date');

		return $this->response()->collection($history, new HistoryTransformer);
	}
}
