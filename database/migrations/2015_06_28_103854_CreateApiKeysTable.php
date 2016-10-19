<?php

use Illuminate\Database\Migrations\Migration;

class CreateApiKeysTable extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('api_keys', function ($table) {
			$table->engine = 'InnoDB';

			$table->increments('id');
			$table->string('key', 191)->unique();
			$table->string('description', 191);

			$table->index('key');
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('api_keys');
	}
}
