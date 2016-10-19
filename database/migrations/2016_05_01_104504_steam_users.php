<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class SteamUsers extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropUnique('users_email_unique');
			$table->string('username');
			$table->string('avatar');
			$table->string('steamid', 19)->unique();
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('username');
			$table->dropColumn('avatar');
			$table->dropColumn('steamid');
		});
	}
}
