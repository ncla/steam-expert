<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class CsglItem extends Model
{

	protected $table = 'csgolounge_items';

	protected $hidden = ['created_at', 'id'];

	protected $primaryKey = 'id';

	public function gameItem()
	{
		return $this->belongsTo('App\GameItem', 'id', 'id');
	}

}
