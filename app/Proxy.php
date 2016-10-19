<?php namespace App;

use App\Helpers\EncodingIgnoreRequest as Request;
use App\Helpers\Log;
use App\Helpers\Python;
use App\Helpers\Slacker;
use Carbon\Carbon;
use Curl\Curl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use MultiRequest\Handler;

class Proxy extends Model
{

	protected $table = 'proxies';

	protected $fillable = ['ip', 'port', 'response_time_ms'];

	protected $guardable = ['id', 'created_at'];

	protected $appends = ['ip_port'];

	public function getIpPortAttribute()
	{
		return $this->attributes['ip:port'] = $this->attributes['ip'] . ':' . $this->attributes['port'];
	}

	public function scrape()
	{
		$data = Python::run(Python::ProxyScraper);

		if (!empty($data['proxy_count'])) {
			$data = [
				'start_time'  => $data['start_time'], 'duplicates' => $data['duplicates'],
				'finish_time' => $data['finish_time'], 'proxy_count' => $data['proxy_count']
			];

			Log::array($data)->info()->perflog()->slack()->echo();
		} else {
			Slacker::log('[update:proxies] Failed to scrape proxies');
		}

		if (!empty($data['errors'])) {
			Slacker::log('[update:proxies] Scraper returned errors: ' . json_encode($data['errors']));
		}

	}

	public function testAllProxies()
	{
		$ipValidationSites = ['http://checkip.amazonaws.com/', 'http://ip.gwhois.org', 'http://icanhazip.com', 'http://ident.me/'];
		$curl = new Curl();
		$ipSite = '';

		foreach ($ipValidationSites as $site) {
			$curl->get($site);
			if (filter_var($curl->response, FILTER_VALIDATE_IP) && $curl->httpStatusCode == 200) {
				$ipSite = $site;
				break;
			}
		}

		if (!$ipSite) {
			$msg = 'All IP apis are down, not testing proxies';
			Log::msg($msg)->critical()->slack()->echo();
			return;
		}

		// get unhealthiest proxies first
		$proxies = Proxy::orderBy('last_healthy')->get()->toArray();

		$totalGood = 0;

		$results = [];

		$mrHandler = new Handler();
		// give me all the connections :^)
		$mrHandler->setConnectionsLimit(1500);

		$mrHandler->onRequestComplete(function (Request $request) use (&$totalGood, &$results) {
			if (!\App::environment('production')) {
				echo 'Request complete: ' . $request->getUrl() . ' Code: ' . $request->getCode() . ' Time: ' . $request->getTime() . PHP_EOL;
				echo 'Real IP length ' . strlen($request->getContent()) . ', Expected Proxy IP length ' . strlen($request->getCurlOptions()[ CURLOPT_PROXY ]) . PHP_EOL;
			}

			$proxyIP = explode(':', $request->getCurlOptions()[ CURLOPT_PROXY ])[0];

			$result = [
				'ip'               => $proxyIP,
				'response_time_ms' => $request->getTime(),
				'healthy'          => 1
			];

			// Response has to be identical to the proxy IP, otherwise we are getting something fucked up ;>
			if ($proxyIP == $request->getContent() && $request->getCode() == 200) {
				$totalGood = $totalGood + 1;
			} else {
				$result['healthy'] = 0;
			}

			$results[] = $result;

		});
		$mrHandler->requestsDefaults()->addCurlOptions([
														   CURLOPT_TIMEOUT        => 30,
														   CURLOPT_CONNECTTIMEOUT => 10
													   ]);

		$Session = new \MultiRequest\Session($mrHandler, '/tmp');

		$Session->start();

		foreach ($proxies as $proxy) {
			$request = new Request($ipSite);
			$request->addCurlOptions([CURLOPT_PROXY => ($proxy['ip'] . ':' . $proxy['port'])]);
			$mrHandler->pushRequestToQueue($request);
		}

		$mrHandler->start();

		Log::msg('Good proxies: ' . $totalGood)->echo()->slack()->cs(ComponentState::GOOD_PROXY_COUNT, $totalGood);

		$updateTime = Carbon::now()->toDateTimeString();
		foreach ($results as $proxy) {
			$data = [
				'response_time_ms' => $proxy['response_time_ms'],
				'healthy'          => $proxy['healthy'],
				'updated_at'       => $updateTime
			];

			if ($data['healthy']) {
				$data['last_healthy'] = $updateTime;
			}
			Proxy::where('ip', $proxy['ip'])
				->update($data);
		}

		$earliestUnhealthyDate = Carbon::now()->subDays(5)->toDateTimeString();
		DB::table('proxies')
			->whereNotNull('last_healthy')
			->where('updated_at', '>', $earliestUnhealthyDate)
			->where('last_healthy', '<', $earliestUnhealthyDate)
			->delete();

		DB::table('proxies')
			->where('last_healthy', '0000-00-00 00:00:00')
			->where('created_at', '<', $earliestUnhealthyDate)
			->delete();
	}

}
