<?php

use Illuminate\Database\Seeder;

class GamesTableSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 * @return void
	 */
	public function run()
	{
		DB::table('applications')->insert(
			[
				'appid'      => 730,
				'name'       => 'Counter-Strike: Global Offensive',
				'marketable' => 1
			]
		);
		DB::table('applications')->insert(
			[
				'appid'      => 570,
				'name'       => 'Dota 2',
				'marketable' => 1
			]
		);
	}
}
