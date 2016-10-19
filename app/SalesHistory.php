<?php

namespace App;

use App\GameItem;
use Illuminate\Database\Eloquent\Model;
use Yadakhov\InsertOnDuplicateKey;

class SalesHistory extends Model
{

	use InsertOnDuplicateKey;

	protected $table = 'saleshistory';

	protected $hidden = ['created_at', 'id'];

	protected $primaryKey = 'item_id';
	
	public function gameItem()
	{
		return $this->belongsTo('App\GameItem', 'id', 'item_id');
	}

}