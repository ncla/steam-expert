<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Currencies extends Model
{

	public $timestamps = false;
	protected $fillable = ['abbreviation', 'rate'];

}
