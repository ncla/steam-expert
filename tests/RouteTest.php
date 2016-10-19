<?php

use Carbon\Carbon;
use Illuminate\Support\Str;

class RouteTest extends TestCase
{
	public function testHomepage()
	{
		$this->visit('/')
			->see('STEAM.EXPERT');
	}

	public function testItemEndpoint()
	{
		$this->visit('/api/items/id/1')
			->seeJsonStructure(
				[
					'data' => [
						'id',
						'market_hash_name',
						'appid',
						'subappid',
						'quantity',
						'price',
						'description',
						'updated_at',
						'image_url',
						'inspect_url',
						'calculations' => [
							'week',
							'month',
							'threemonths'
						]
					]
				]
			);
	}

	public function testAppIdForcingForItemEndpoint()
	{
		$this->visit('/api/items/name/Name%20Tag?appid=730')
			->seeJson(
				[
					'appid' => 730
				]
			);

		$this->visit('/api/items/name/Name%20Tag?appid=440')
			->seeJson(
				[
					'appid' => 440
				]
			);

		$this->visit('/api/items/name/Name%20Tag?appid=570')
			->seeJson(
				[
					'appid' => 570
				]
			);
	}

	public function testNotFoundExceptionForItemEndpoint()
	{
		$this->json('GET', '/api/items/id/' . random_int(90000000, 90000000000000000))
			->seeJson(
				[
					'message'     => 'No item found with this ID, name, appID or subAppID',
					'status_code' => 404
				]
			)->assertResponseStatus(404);

		$this->json('GET', '/api/items/name/' . Str::random(100))
			->seeJson(
				[
					'message'     => 'No item found with this ID, name, appID or subAppID',
					'status_code' => 404
				]
			)->assertResponseStatus(404);
	}

	public function testIncorrectItemIdentifier()
	{
		$this->json('GET', '/api/items/idd/1')
			->seeJson(
				[
					'message'     => '404 Not Found',
					'status_code' => 404
				]
			)->assertResponseStatus(404);

		$this->json('GET', '/api/items/namme/Name%20Tag')
			->seeJson(
				[
					'message'     => '404 Not Found',
					'status_code' => 404
				]
			)->assertResponseStatus(404);
	}

	public function testItemsPriceOnlyEndpoint()
	{
		$this->json('GET', '/api/items/id/1/price')
			->seeJsonStructure(
				[
					'data' => [
						'price'
					]
				]
			);

		$this->json('GET', '/api/items/name/Name%20Tag/price')
			->seeJsonStructure(
				[
					'data' => [
						'price'
					]
				]
			);
	}

	public function testAllItemsApiEndpoint()
	{
		$this->json('GET', '/api/items/all/730')
			->seeJsonStructure(
				[
					'data' => [
						'*' => [
							'id',
							'market_hash_name',
							'appid',
							'subappid',
							'price',
							'updated_at',
							'median_week',
							'median_month',
							'average_median_week',
							'average_median_month',
							'trend_week',
							'trend_month',
							'total_sold_week',
							'total_sold_month'
						]
					]
				]
			);

		$this->json('GET', '/api/items/all/133742069')
			->assertResponseStatus(404);
	}

	public function testItemResponseWithHistory()
	{
		$this->json('GET', '/api/items/id/1/?history=1')
			->seeJsonStructure(
				[
					'data' => [
						'history'
					]
				]
			);

		$this->json('GET', '/api/items/id/1/history/')
			->seeJsonStructure(
				[
					'data' => [
						'*' => [
							'date',
							'median_price',
							'sales_amount',
							'updated_at'
						]
					]
				]
			);
	}

	public function testItemsHistorySpecificDate()
	{
		$date = Carbon::now();
		$date = $date->subDays(7)->toDateString();

		// Not guaranteed to have a sales history at that date :-(
		$this->json('GET', '/api/items/id/1/history/' . $date);
	}

	public function testItemsHistoryArchive()
	{
		$date = Carbon::now();
		$date = $date->subDays(7)->toDateString();

		$this->json('POST', '/api/items/prices/archive', [])
			->assertResponseStatus(400);

		$itemsDifferentData = [
			[], true, '', [
				'2015-5-01' => [
					'Name Tag'
				]
			]
		];

		foreach ($itemsDifferentData as $value) {
			$this->json('POST', '/api/items/prices/archive', [
				'items' => $value,
				'appid' => 730
			])->assertResponseStatus(400);
		}

		// It is OK to have random appID
		$this->json('POST', '/api/items/prices/archive', [
			'items' => [
				$date => [
					'Name Tag'
				]
			],
			'appid' => 730133742069
		])->assertResponseStatus(200);

		$this->json('POST', '/api/items/prices/archive', [
			'items' => [
				$date => [
					'Name Tag'
				]
			],
			'appid' => 'u w0t m8'
		])->assertResponseStatus(400);

		$this->json('POST', '/api/items/prices/archive', [
			'items' => [
				$date => [
					'Name Tag'
				]
			]
		])->assertResponseStatus(400);

		$this->json('POST', '/api/items/prices/archive', [
			'appid' => 730
		])->assertResponseStatus(400);

		$this->json('POST', '/api/items/prices/archive', [
			'items' => [
				$date => [
					'Name Tag'
				]
			],
			'appid' => 730
		])->seeJsonStructure(
			[
				'data' => [
					$date => [
						'Name Tag'
					]
				]
			]
		)->assertResponseStatus(200);


		// Testing too many entries
		$randomItemData = [];

		for ($i = 0; $i < 3000; $i++) {
			$date = Carbon::now();
			$date = $date->subDays(random_int(1, 1000))->format('Y-m-d');


			$rndItemName = Str::random(100);

			if (!isset($randomItemData[ $date ])) {
				$randomItemData[ $date ] = [];
			}

			$randomItemData[ $date ][] = $rndItemName;
		}

		$this->json('POST', '/api/items/prices/archive', [
			'items' => $randomItemData,
			'appid' => 730
		])->seeJson(
			[
				'message' => 'Too many entries requested'
			]
		)->assertResponseStatus(400);

		// Edge case test for untrimmed items
		$whitespaceItems = DB::table('gameitems')->select('name', 'price')
			->whereRaw('CHAR_LENGTH(name) != CHAR_LENGTH(TRIM(name))')
			->orderByRaw('RAND()')
			->limit(50)
			->get();

		$expectedReturn = [
			'data' => []
		];

		$e = &$expectedReturn['data'];

		$randomItemData = [];
		for ($i = 0; $i < 5; ++$i) {
			$date = Carbon::now();
			$date = $date->subDays(random_int(1, 1000))->format('Y-m-d');

			if (!isset($randomItemData[ $date ])) {
				$e[ $date ] = [];
				for ($j = 0; $j < 5; ++$j) {
					$t = $whitespaceItems[ rand(0, 19) ]->name;
					$randomItemData[ $date ][] = $t;
					$e[ $date ][] = $t;
				}
			}
		}

		$this->json('POST', '/api/items/prices/archive', [
			'items' => $randomItemData,
			'appid' => 730
		])->seeJsonStructure($expectedReturn)->assertResponseStatus(200);

		// Now with non-existent items
		foreach ($randomItemData as $date => $items) {
			foreach ($items as $i => $item) {
				$randomItemName = '  ' . Str::random(60) . (rand() ? '' : '        ');
				$randomItemData[ $date ][ $i ] = $randomItemName;
				$e[ $date ][ $i ] = $randomItemName;
			}
		}

		$this->json('POST', '/api/items/prices/archive', [
			'items' => $randomItemData,
			'appid' => 730
		])->seeJsonStructure($expectedReturn)->assertResponseStatus(200);

		$date = Carbon::now();
		$date = $date->subDays(7)->toDateString();

		$this->json('POST', '/api/items/prices/archive', [
			'items' => [
				$date => [
					'Name Tag ',
					'Name Tag',
					'Black dog shit'
				]
			],
			'appid' => 730
		])->assertResponseStatus(200);

		$this->json('POST', '/api/items/prices/archive', [
			'items' => [
				$date => [
					'Name Tag ',
					'Name Tag     '
				]
			],
			'appid' => 730
		])->assertResponseStatus(200);
	}
	
	public function testBatchPricesEndpoint()
	{
		$this->json('GET', '/api/items/prices')
			->assertResponseStatus(400);

		$itemsDifferentData = [
			[], true, '', [false]
		];

		foreach ($itemsDifferentData as $value) {
			$this->json('POST', '/api/items/prices', [
				'items' => $value,
				'appid' => 730
			])->assertResponseStatus(400);
		}

		$this->json('POST', '/api/items/prices', [
			'items'    => [
				'Name Tag'
			], 'appid' => 730133742069
		])->assertResponseStatus(200);

		// Testing too many entries
		$randomItemData = [];

		for ($i = 0; $i < 3000; $i++) {
			$rndItemName = Str::random(100);

			$randomItemData[] = $rndItemName;
		}

		$this->json('POST', '/api/items/prices', [
			'items' => $randomItemData,
			'appid' => 730
		])->seeJson(
			[
				'message' => 'Too many entries requested'
			]
		)->assertResponseStatus(400);

		$this->json('POST', '/api/items/prices', [
			'items' => [
				'Name Tag'
			],
			'appid' => 730
		])->seeJsonStructure(
			[
				'data' => [
					'Name Tag' => [
						'price',
						'date'
					]
				]
			]
		);

		$this->json('POST', '/api/items/prices', [
			'appid' => 730
		])->assertResponseStatus(400);
	}
}
