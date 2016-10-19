<?php

use Illuminate\Database\Migrations\Migration;

class CreateGamesTable extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('games', function ($table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->integer('appid')->unique();
			$table->boolean('tofetch');
			$table->string('name', 191);
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('games');
	}
}
