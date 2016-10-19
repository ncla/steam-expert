<?php namespace App\Http\Middleware;

use Closure;

class LogMiddleware
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
		var_dump($request->headers);
		die;
		if (array_has($request->headers, 'accept')) {
			//var_dump($request->headers); die;
			//Log::info('info',array('context'=>'additional info'));
		}

		return $next($request);
	}

}
