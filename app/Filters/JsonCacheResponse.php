<?php
namespace App\Filters;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class JsonCacheResponse
{

	public function fetch(Route $route, Request $request)
	{
		$key = $this->makeCacheKey($request->url());

		if (Cache::has($key)) {
			$resp = Response(Cache::get($key), 200, ['Content-Type' => 'application/json']);

			return $resp;
		}
	}

	public function put(Route $route, Request $request, $response)
	{
		$key = $this->makeCacheKey($request->url());

		if (!Cache::has($key)) {
			Cache::put($key, $response->getContent(), 60);
		}
	}

	public function makeCacheKey($url)
	{
		return 'route_' . Str::slug($url);
	}

}