<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Yadakhov\InsertOnDuplicateKey;

class SupplyHistory extends Model
{
	use InsertOnDuplicateKey;

	protected $table = 'supplyhistory';

	protected $primaryKey = 'item_id'; // Not sure about this one, we don't really have PK?
}
