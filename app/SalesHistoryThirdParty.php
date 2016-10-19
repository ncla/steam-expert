<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Yadakhov\InsertOnDuplicateKey;

class SalesHistoryThirdParty extends Model
{
	use InsertOnDuplicateKey;

	protected $table = 'saleshistory_3rdparty';

	protected $primaryKey = 'item_id';
}
