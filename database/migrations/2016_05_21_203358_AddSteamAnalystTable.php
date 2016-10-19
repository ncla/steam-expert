<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddSteamAnalystTable extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('steamanalyst', function (Blueprint $table) {
			$table->integer('item_id');
			$table->string('name', 512);
			$table->float('price', 6, 2);
			$table->float('average_price', 6, 2);
		});

		DB::statement('ALTER TABLE `steamanalyst` ADD UNIQUE `steamanalyst_name_item_id_unique` (name(180), item_id);');
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('steamanalyst');
	}
}
