<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class SupplyAndPriceHistoryTable extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('supplyhistory', function (Blueprint $table) {
			$table->unsignedMediumInteger('item_id');
			$table->decimal('listing_price', 6, 2);
			$table->unsignedInteger('units');
			$table->timestamp('recorded_at');
			$table->unique(['item_id', 'recorded_at']);
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('supplyhistory');
	}
}
