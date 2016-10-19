<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class RenameGamesTableAttributes extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('games', function (Blueprint $table) {
			$table->renameColumn('tofetch', 'marketable');
			$table->rename('applications');
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('applications', function (Blueprint $table) {
			$table->renameColumn('marketable', 'tofetch');
			$table->rename('games');
		});
	}
}
