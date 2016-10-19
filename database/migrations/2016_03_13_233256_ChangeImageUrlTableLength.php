<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class ChangeImageUrlTableLength extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			DB::statement("ALTER TABLE `gameitems` MODIFY `image_url` VARCHAR(2048);");
			DB::statement("ALTER TABLE `gameitems` MODIFY `inspect_url` VARCHAR(2048);");
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			DB::statement("ALTER TABLE `gameitems` MODIFY `image_url` VARCHAR(191);");
			DB::statement("ALTER TABLE `gameitems` MODIFY `inspect_url` VARCHAR(191);");
		});
	}
}
