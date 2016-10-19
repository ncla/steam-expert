<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateGameitemsTable extends Migration
{

	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('gameitems', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->string('name', 191);
			$table->integer('appid');
			$table->integer('quantity');
			$table->float('price');
			$table->timestamps();

			$table->unique(['name', 'appid']);
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::drop('gameitems');
	}

}
