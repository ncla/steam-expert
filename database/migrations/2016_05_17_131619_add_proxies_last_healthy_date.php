<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddProxiesLastHealthyDate extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('proxies', function (Blueprint $table) {
			$table->dateTime('last_healthy')->default('0000-00-00 00:00:00');
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('proxies', function (Blueprint $table) {
			$table->dropColumn('last_healthy');
		});
	}
}
