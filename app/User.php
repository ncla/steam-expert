<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
	/**
	 * The attributes that are mass assignable.
	 * @var array
	 */
	protected $fillable = [
		'name', 'email', 'password', 'username', 'steamid', 'avatar'
	];

	/**
	 * The attributes that should be hidden for arrays.
	 * @var array
	 */
	protected $hidden = [
		'password', 'remember_token',
	];

	public function __get($key)
	{
		if ($key == 'name' && !parent::__get('name')) {
			return parent::__get('username');
		}

		return parent::__get($key);
	}

	public function isAdmin()
	{
		return in_array($this->steamid, [
			"iamncla"     => 76561198043770492,
			"darkswormlv" => 76561198048673690
		]);
	}
}
