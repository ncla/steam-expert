<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCsglitemsTable extends Migration
{

	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('csgolounge_items', function (Blueprint $table) {
			// To possibly fix the errors with foreigns keys when inserting/updating
			$table->engine = 'InnoDB';

			$table->integer('id')->unsigned();

			$table->float('value');
			$table->timestamps();

			$table->foreign('id')->references('id')->on('gameitems');
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::drop('csgolounge_items');
	}

}
