<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesHistoryThirdParty extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('saleshistory_3rdparty', function (Blueprint $table) {
			$table->integer('item_id');
			$table->decimal('average_price', 6, 2);
			$table->unsignedInteger('amount_sold');
			$table->date('date');

			$table->unique(['item_id', 'date']);
        });
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::drop('saleshistory_3rdparty');
	}
}
