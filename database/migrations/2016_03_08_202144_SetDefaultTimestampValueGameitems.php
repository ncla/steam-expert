<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class SetDefaultTimestampValueGameitems extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			DB::statement("ALTER TABLE `gameitems` MODIFY `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;");
			DB::statement("ALTER TABLE `gameitems` MODIFY `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;");
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			// `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
			DB::statement("ALTER TABLE `gameitems` MODIFY `created_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';");
			DB::statement("ALTER TABLE `gameitems` MODIFY `updated_at` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00';");
		});
	}
}
