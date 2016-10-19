<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddDescriptionToGameitems extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			$table->string('description', 2048)->after('price')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			$table->dropColumn('description');
		});
	}
}
