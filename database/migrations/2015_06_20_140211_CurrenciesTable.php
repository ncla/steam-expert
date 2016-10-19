<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CurrenciesTable extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('currencies', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->string('abbreviation', 191)->unique();
			$table->double('rate');
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::drop('currencies');
	}
}
