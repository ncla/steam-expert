<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePricehistoryTable extends Migration
{

	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('pricehistory', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->integer('id')->unsigned();

			$table->date('date');
			$table->float('soldfor');
			$table->integer('amountsold');
			$table->timestamps();

			$table->foreign('id')->references('id')->on('gameitems');

			$table->unique(['id', 'date']);
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::drop('pricehistory');
	}

}
