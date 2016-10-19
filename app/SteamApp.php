<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SteamApp extends Model
{
	public $timestamps = false;
	protected $table = 'applications';
	protected $fillable = ['appid', 'name', 'marketable'];
}
