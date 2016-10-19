<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateProxyTable extends Migration
{

	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('proxies', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->increments('id');

			$table->string('ip', 191);
			$table->integer('port');
			$table->float('response_time_ms')->default(0);
			$table->boolean('healthy')->default(false);
			$table->timestamps();

			// Not sure if we want only IP to be unique
			$table->unique('ip');
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::drop('proxies');
	}

}
