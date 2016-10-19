<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class ChangeDefaultValuesGameItems extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			$table->float('price')->nullable(true)->default(null)->change();

			$table->integer('quantity')->default(0)->change();
		});

		// Change existing entries, price 0.00 to NULL
		DB::table('gameitems')->where('price', '=', 0.00)->update(['price' => null]);
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			$table->float('price')->nullable(false)->change();
			$table->integer('quantity')->nullable(false)->change();
		});

		DB::statement('ALTER TABLE `gameitems` ALTER `quantity` DROP DEFAULT;');

		DB::table('gameitems')->where('price', '=', null)->update(['price' => 0.00]);
	}
}
