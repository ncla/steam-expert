<?php
namespace App\Http\Transformers;

use League\Fractal\TransformerAbstract;

class SalesHistoryTransformer extends TransformerAbstract
{
	public function transform(\App\SalesHistory $saleshistory)
	{
		return [
			'date'         => $saleshistory->date,
			'median_price' => $saleshistory->median_price,
			'sales_amount' => $saleshistory->amount_sold,
			'updated_at'   => $saleshistory->getOriginal('updated_at')
		];
	}
}
