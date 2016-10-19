<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddSubAppID extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			$table->integer('subappid')->after('appid')->nullable();
			$table->dropUnique('gameitems_name_appid_unique');
			$table->unique(['name', 'appid', 'subappid']);
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::table('gameitems', function (Blueprint $table) {
			$keyExists = DB::select(
				DB::raw(
					'SHOW KEYS
                    FROM gameitems
                    WHERE Key_name=\'gameitems_name_appid_subappid_unique\''
				)
			);

			if ($keyExists) {
				$table->dropUnique('gameitems_name_appid_subappid_unique');
			}

			if (Schema::hasColumn('gameitems', 'subappid')) {
				$table->dropColumn('subappid');
			}

			DB::raw('ALTER IGNORE TABLE gameitems ADD UNIQUE INDEX gameitems_name_appid_unique (name, appid);');
		});
	}
}
