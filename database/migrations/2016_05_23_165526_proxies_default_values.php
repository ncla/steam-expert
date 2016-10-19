<?php

use Illuminate\Database\Migrations\Migration;

class ProxiesDefaultValues extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		DB::statement('ALTER TABLE `proxies` CHANGE `created_at` `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		DB::statement('ALTER TABLE `proxies` CHANGE `created_at` `created_at` DATETIME NOT NULL DEFAULT "0000-00-00 00:00:00"');
	}
}
