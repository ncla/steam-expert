<?php namespace App\Http\Middleware;

use App\ApiKey;
use Closure;

class AuthApiKey
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
		$apikeysCount = ApiKey::all()->count();

		// Don't bother if no keys set up
		if ($apikeysCount === 0) {
			return $next($request);
		} else {
			$key = $request->input('key');
			if (ApiKey::where('key', '=', $key)->first() === null) {
				return response()->json(['error' => 'Unauthorized'])->setStatusCode(401);
			} else {
				return $next($request);
			}
		}
	}

}
