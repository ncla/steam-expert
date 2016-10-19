<?php

namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;

class ItemTransformer extends TransformerAbstract
{
	protected $availableIncludes = [
		'saleshistory'
	];

	public function transform(\App\GameItem $item)
	{
		$itemArray = [
			'id'               => $item->id,
			'market_hash_name' => $item->name,
			'appid'            => $item->appid,
			'subappid'         => $item->subappid,
			'quantity'         => $item->quantity,
			'price'            => $item->price,
			'description'      => $item->description,
			'updated_at'       => $item->getOriginal('updated_at'),
			'image_url'        => $item->image_url,
			'inspect_url'      => $item->inspect_url,
			'calculations'     => [
				'week'        => [
					'median'         => $item->median_7,
					'average_median' => $item->avg_median_7,
					'total_sold'     => $item->total_sold_7,
					'trend'          => $item->trend_7,
					'average_opskins' => $item->avg_opskins_7,
					'total_sold_opskins' => $item->total_sold_opskins_7
				],
				'month'       => [
					'median'         => $item->median_30,
					'average_median' => $item->avg_median_30,
					'total_sold'     => $item->total_sold_30,
					'trend_month'    => $item->trend_30,
					'average_opskins' => $item->avg_opskins_30,
					'total_sold_opskins' => $item->total_sold_opskins_30
				],
				'threemonths' => [
					'median'         => $item->median_90,
					'average_median' => $item->avg_median_90,
					'total_sold'     => $item->total_sold_90,
					'trend'          => $item->trend_90
				],
				'updated_at'  => $item->calculations_updated_at
			]
		];

		return $itemArray;
	}

	public function includeSalesHistory(\App\GameItem $item, $params)
	{
		$saleshistory = $item->saleshistory()->orderBy('date', 'desc')->get();

		return $this->collection($saleshistory, new SalesHistoryTransformer, false);
	}
}
