<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class JsonResponseCacheAfter
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure $next
	 *
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$key = Str::slug($request->url());

		$response = json_encode($next($request)->getOriginalContent());

		if (!Cache::has($key)) {
			Cache::put($key, $response, 60);
		}

		return $next($request);
	}
}
