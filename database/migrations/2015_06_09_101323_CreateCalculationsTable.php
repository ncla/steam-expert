<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCalculationsTable extends Migration
{

	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('item_calculations', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->integer('id')->unsigned()->unique();

			$table->float('avg7')->nullable();
			$table->float('avg30')->nullable();
			$table->float('avg90')->nullable();

			$table->float('trend7')->nullable();
			$table->float('trend30')->nullable();
			$table->float('trend90')->nullable();

			$table->integer('totalsold7')->unsigned()->nullable();
			$table->integer('totalsold30')->unsigned()->nullable();
			$table->integer('totalsold90')->unsigned()->nullable();

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
		Schema::drop('item_calculations');
	}

}
