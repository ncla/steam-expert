<?php

namespace App\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;

class CORS
{
	private $headers = [
		'Access-Control-Allow-Origin'      => '*',
		'Access-Control-Allow-Headers'     => 'DNT,X-Mx-ReqToken,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type',
		'Access-Control-Allow-Methods'     => 'GET, POST, OPTIONS',
		'Access-Control-Allow-Credentials' => 'true'
	];

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
		/** @var Response $response */
		$response = $next($request);
		$response->headers->add($this->headers);

		return $response;
	}
}
