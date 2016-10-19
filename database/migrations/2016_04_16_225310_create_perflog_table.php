<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePerflogTable extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('perflogs', function (Blueprint $table) {
			$table->string('stat', 30);
			$table->index('stat');
			$table->dateTime('added');
			$table->binary('data');
			$table->primary(['stat', 'added']);
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::drop('perflogs');
	}
}