<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Yadakhov\InsertOnDuplicateKey;

class SteamAnalyst extends Model
{
	use InsertOnDuplicateKey;

	protected $table = 'steamanalyst';

	protected $primaryKey = 'item_id';
}