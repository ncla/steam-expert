<?php

namespace App;

use Illuminate\Database\Eloquent\Collection;
use MultiRequest\Request;

class ProxyList
{
	public $unusedProxies = [];

	public $badProxies = [];

	public $goodProxies = [];

	public $inUseProxies = [];

	public function __construct(Collection $proxies)
	{
		$this->unusedProxies = $proxies;

		$this->badProxies = new Collection();

		$this->goodProxies = new Collection();

		$this->inUseProxies = new Collection();
	}

	public function handleProxy(Request $request, $status)
	{
		if (isset($request->getCurlOptions()[ CURLOPT_PROXY ])) {
			$proxyIpPort = $request->getCurlOptions()[ CURLOPT_PROXY ];


			$proxy = $this->inUseProxies->get($proxyIpPort);
			$this->inUseProxies->forget($proxyIpPort);

			if ($status == 1) {
				$this->goodProxies->put(
					$proxy->ipport, $proxy
				);
			} else {
				$this->badProxies->put(
					$proxy->ipport, $proxy
				);
			}
		}
	}

	public function getProxy()
	{
		if (!$this->goodProxies->isEmpty()) {
			$proxy = $this->goodProxies->shift();
			$this->inUseProxies->put($proxy->ipport, $proxy);

			return $proxy;
		} else if (!$this->unusedProxies->isEmpty()) {
			$proxy = $this->unusedProxies->shift();
			$this->inUseProxies->put($proxy->ipport, $proxy);

			return $proxy;
		} else {
			$this->reset();

			$proxy = $this->unusedProxies->shift();
			$this->inUseProxies->put($proxy->ipport, $proxy);

			if ($proxy === null) {
				throw new \ErrorException('ProxyList ran out of assignable proxies');
			}

			return $proxy;
		}
	}

	public function reset()
	{
		$this->unusedProxies = $this->badProxies;
		$this->badProxies = new Collection();
	}

	public function getProxiesLeft()
	{
		return $this->unusedProxies->count() + $this->goodProxies->count() + $this->inUseProxies->count();
	}
}