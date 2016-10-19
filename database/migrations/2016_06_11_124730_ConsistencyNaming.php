<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ConsistencyNaming extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::rename('pricehistory', 'saleshistory');

		Schema::table('saleshistory', function(Blueprint $table) {
			$table->renameColumn('amountsold', 'amount_sold');

			$table->dropIndex('pricehistory_id_index');
			$table->dropIndex('pricehistory_date_index');
			$table->dropForeign('pricehistory_id_foreign');
			$table->dropUnique('pricehistory_id_date_unique');

			$table->renameColumn('id', 'item_id');

			$table->index('item_id');
			$table->index('date');
			$table->unique(['item_id', 'date']);
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::rename('saleshistory', 'pricehistory');

		Schema::table('pricehistory', function(Blueprint $table) {
			$table->renameColumn('amount_sold', 'amountsold');

			$table->dropIndex('saleshistory_item_id_index');
			$table->dropIndex('saleshistory_date_index');
			$table->dropUnique('pricehistory_item_id_date_unique');

			$table->renameColumn('item_id', 'id');

			$table->index('id');
			$table->index('date');
			$table->unique(['id', 'date']);
		});
	}
}
