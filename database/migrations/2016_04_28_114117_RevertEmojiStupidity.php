<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class RevertEmojiStupidity extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('gameitems', function ($table) {
			$table->dropUnique('gameitems_name_appid_subappid_unique');
		});

		Schema::table('gameitems', function ($table) {
			$table->string('name', 512)->change();
		});

		// Because of the 767 byte limitation on the index keys, need to trim name column
		// There is another workaround which involves changing InnoDB config and changing table structure
		DB::statement('ALTER TABLE `gameitems` ADD UNIQUE `gameitems_name_appid_subappid_unique_stripped`(name(186), appid, subappid);');

		Schema::table('games', function ($table) {
			$table->string('name', 255)->change();
		});

		Schema::table('api_keys', function ($table) {
			$table->string('description', 255)->change();
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			$table->dropUnique('gameitems_name_appid_subappid_unique_stripped');
			$table->string('name', 191)->change();
			$table->unique(['name', 'appid', 'subappid']);
		});

		Schema::table('games', function ($table) {
			$table->string('name', 191)->change();
		});

		Schema::table('api_keys', function ($table) {
			$table->string('description', 191)->change();
		});
	}
}
