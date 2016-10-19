<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddThirdPartyCalculation extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			$table->double('avg_opskins_7', 6, 2)->nullable()->after('total_sold_90');
			$table->double('avg_opskins_30', 6, 2)->nullable()->after('avg_opskins_7');
			$table->unsignedInteger('total_sold_opskins_7')->nullable()->after('avg_opskins_30');
			$table->unsignedInteger('total_sold_opskins_30')->nullable()->after('total_sold_opskins_7');
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			$table->dropColumn(['avg_opskins_7', 'avg_opskins_30', 'total_sold_opskins_7', 'total_sold_opskins_30']);
		});
	}
}
