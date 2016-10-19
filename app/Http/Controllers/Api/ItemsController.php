<?php

namespace App\Http\Controllers\Api;

use App\GameItem;
use App\Http\Controllers\Controller;
use App\Http\DataSerializers\CustomResourceKey;
use App\Http\Requests;
use App\Http\Transformers\ItemDbTransformer;
use App\Http\Transformers\ItemTransformer as GameItemTransformer;
use Carbon\Carbon;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\DB;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

/**
 * @Resource("Items")
 */
class ItemsController extends Controller
{
	use Helpers;

	/**
	 * Market item data
	 *
	 * @param  string $name
	 *
	 * @return \Illuminate\Http\Response
	 * @Parameters({
	 *      @Parameter("saleshistory", description="Include sales history in the response.", default=0),
	 *      @Parameter("appid", description="If there are multiple results from different applications, you can query by specific application ID.", default=0),
	 *      @Parameter("subappid", description="SubAppId is used only for Steam items (emoticons, wallpapers etc.), currently only 753 application utilizes a sub app ID.", default=0)
	 * })
	 * @Get("/items/{identifier}/{identifierValue}")
	 * @Versions({"v1"})
	 */
	public function getItem($identifier, $identifierValue)
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

		if ($item) {
			$manager = new Manager();
			$manager->setSerializer(new CustomResourceKey());
			$resource = new Item($item, new GameItemTransformer(), 'data');

			if ((boolean)\Request::query('saleshistory')) {
				$manager->parseIncludes('saleshistory');
			}

			return $manager->createData($resource)->toArray();
		} else {
			return $this->response()->errorNotFound('No item found with this ID, name, appID or subAppID');
		}
	}

	public function getPrice($identifier, $identifierValue)
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

		if ($item) {
			// https://github.com/ncla/steamexpert/issues/82
			return [
				'data' => [
					'price'      => $item->price,
					'updated_at' => $item->getOriginal('updated_at')
				]
			];
		} else {
			return $this->response()->errorNotFound('No item found with this ID, name, appID or subAppID');
		}
	}

	public function getItemsListByAppId($appID)
	{
		$appID = intval($appID);

		$items = DB::table('gameitems')
			->select([
						 'id', 'name AS market_hash_name', 'appid', 'subappid', 'quantity', 'price', 'updated_at',
						 'median_7 AS median_week', 'median_30 AS median_month',
						 'avg_median_7 AS average_median_week', 'avg_median_30 AS average_median_month',
						 'trend_7 AS trend_week', 'trend_30 AS trend_month',
						 'total_sold_7 AS total_sold_week', 'total_sold_30 AS total_sold_month'
					 ])
			->where('appid', $appID)
			->get();

		if (count($items) === 0) {
			return $this->response()->errorNotFound('No items found for this appID');
		}

		// TODO: Fucking figure out how to feed arrays into transformers, since by default Dingo/Fractal requires a Collection
		return ['data' => $items];
	}

	public function getItemsListLoungeDestroyer()
	{
		$minimumFreshnessTimestamp = Carbon::now()->subDays(14)->toDateTimeString();

		$items = DB::table('gameitems')
			->select(['name', 'price', 'appid'])
			->whereIn('appid', ['730', '570'])
			->where('updated_at', '>', $minimumFreshnessTimestamp)
			->get();

		if (count($items) === 0) {
			return $this->response()->errorNotFound('No items found for this appID');
		}

		$list = [];

		foreach ($items as $item) {
			$list[ $item->appid ][ $item->name ] = $item->price;
		}

		return ['data' => $list];
	}
}
