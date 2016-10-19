<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class MoveItemcalculationsToGameItemsTable extends Migration
{
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up()
	{
		Schema::table('gameitems', function ($table) {
			$table->float('avg_median_7')->nullable();
			$table->float('avg_median_30')->nullable();
			$table->float('avg_median_90')->nullable();

			$table->float('trend_7')->nullable();
			$table->float('trend_30')->nullable();
			$table->float('trend_90')->nullable();

			$table->integer('total_sold_7')->unsigned()->nullable();
			$table->integer('total_sold_30')->unsigned()->nullable();
			$table->integer('total_sold_90')->unsigned()->nullable();

			$table->timestamp('calculations_updated_at');
		});

		DB::table('item_calculations')->chunk(1000, function ($items) {
			foreach ($items as $item) {
				DB::table('gameitems')
					->where('id', $item->id)
					->update([
								 'avg_median_7'            => $item->avg7,
								 'avg_median_30'           => $item->avg30,
								 'avg_median_90'           => $item->avg90,
								 'trend_7'                 => $item->trend7,
								 'trend_30'                => $item->trend30,
								 'trend_90'                => $item->trend90,
								 'total_sold_7'            => $item->totalsold7,
								 'total_sold_30'           => $item->totalsold30,
								 'total_sold_90'           => $item->totalsold90,
								 'calculations_updated_at' => $item->updated_at
							 ]);
			}
		});

		Schema::dropIfExists('item_calculations');
	}

	/**
	 * Reverse the migrations.
	 * @return void
	 */
	public function down()
	{
		Schema::create('item_calculations', function (Blueprint $table) {
			$table->engine = 'InnoDB';

			$table->integer('id')->unsigned()->unique();

			$table->float('avg7')->nullable();
			$table->float('avg30')->nullable();
			$table->float('avg90')->nullable();

			$table->float('trend7')->nullable();
			$table->float('trend30')->nullable();
			$table->float('trend90')->nullable();

			$table->integer('totalsold7')->unsigned()->nullable();
			$table->integer('totalsold30')->unsigned()->nullable();
			$table->integer('totalsold90')->unsigned()->nullable();

			$table->timestamps();

			$table->foreign('id')->references('id')->on('gameitems');

			$table->index(['avg7', 'trend7', 'totalsold7']);
			$table->index(['avg30', 'trend30', 'totalsold30']);
			$table->index(['avg90', 'trend90', 'totalsold90']);
		});

		DB::table('gameitems')->chunk(1000, function ($items) {
			foreach ($items as $item) {
				DB::table('item_calculations')
					->insert([
								 'id'          => $item->id,
								 'avg7'        => $item->avg_median_7,
								 'avg30'       => $item->avg_median_30,
								 'avg90'       => $item->avg_median_90,
								 'trend7'      => $item->trend_7,
								 'trend30'     => $item->trend_30,
								 'trend90'     => $item->trend_90,
								 'totalsold7'  => $item->total_sold_7,
								 'totalsold30' => $item->total_sold_30,
								 'totalsold90' => $item->total_sold_90,
								 'created_at'  => $item->calculations_updated_at,
								 'updated_at'  => $item->calculations_updated_at
							 ]);
			}
		});

		Schema::table('gameitems', function ($table) {
			$table->dropColumn('avg_median_7');
			$table->dropColumn('avg_median_30');
			$table->dropColumn('avg_median_90');

			$table->dropColumn('trend_7');
			$table->dropColumn('trend_30');
			$table->dropColumn('trend_90');

			$table->dropColumn('total_sold_7');
			$table->dropColumn('total_sold_30');
			$table->dropColumn('total_sold_90');

			$table->dropColumn('calculations_updated_at');
		});

	}
}
