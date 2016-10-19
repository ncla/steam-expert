<?php

use Illuminate\Database\Migrations\Migration;

class TableOptimization extends Migration
{

	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('gameitems', function ($table) {
			$table->index('name');
			$table->index('price');
		});
		Schema::table('pricehistory', function ($table) {
			$table->index('id');
			$table->index('date');
		});
		Schema::table('item_calculations', function ($table) {
			$table->index(['avg7', 'trend7', 'totalsold7']);
			$table->index(['avg30', 'trend30', 'totalsold30']);
			$table->index(['avg90', 'trend90', 'totalsold90']);
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('gameitems', function ($table) {
			$table->dropIndex('gameitems_name_index');
			$table->dropIndex('gameitems_price_index');
		});
		Schema::table('item_calculations', function ($table) {
			$table->dropIndex('item_calculations_avg7_trend7_totalsold7_index');
			$table->dropIndex('item_calculations_avg30_trend30_totalsold30_index');
			$table->dropIndex('item_calculations_avg90_trend90_totalsold90_index');
		});
		Schema::table('pricehistory', function ($table) {
			$table->dropIndex('pricehistory_id_index');
			$table->dropIndex('pricehistory_date_index');
		});
	}

}
