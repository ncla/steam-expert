<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class JsonResponseCacheBefore
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

		if (Cache::has($key)) {
			return Response(Cache::get($key), 200, ['Content-Type' => 'application/json']);
		}

		return $next($request);
	}
}
