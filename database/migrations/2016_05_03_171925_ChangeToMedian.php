<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class ChangeToMedian extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('pricehistory', function (Blueprint $table) {
			$table->renameColumn('soldfor', 'median_price');
		});

		Schema::table('gameitems', function (Blueprint $table) {
			$table->float('median_7')->after('avg_median_90')->nullable();
			$table->float('median_30')->after('median_7')->nullable();
			$table->float('median_90')->after('median_30')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('pricehistory', function (Blueprint $table) {
			$table->renameColumn('median_price', 'soldfor');
		});

		Schema::table('gameitems', function (Blueprint $table) {
			$table->dropColumn(['median_7', 'median_30', 'median_90']);
		});
	}
}
