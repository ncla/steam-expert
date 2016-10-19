<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAuthTables extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function (Blueprint $table) {
			$table->increments('id');
			$table->string('name', 32);
			$table->string('email', 190)->unique();
			$table->string('password', 64);
			$table->rememberToken();
			$table->timestamps();
		});

		Schema::create('password_resets', function (Blueprint $table) {
			$table->string('email', 190)->index();
			$table->string('token')->index();
			$table->timestamp('created_at');
		});
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');

		Schema::drop('password_resets');
	}
}
