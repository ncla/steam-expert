<?php

use Illuminate\Database\Migrations\Migration;

class AddImageAndInspectLinks extends Migration
{

	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('gameitems', function ($table) {
			$table->string('image_url', 191)->nullable();
			$table->string('inspect_url', 191)->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('gameitems', function ($table) {
			$table->dropColumn('image_url');
			$table->dropColumn('inspect_url');
		});
	}

}
