<?php
/**
 * Created by PhpStorm.
 * User: darksworm
 * Date: 3/25/16
 * Time: 9:08 PM
 */

namespace App\Helpers;

use App;
use Frlnc\Slack\Core\Commander;
use Frlnc\Slack\Http\CurlInteractor;
use Frlnc\Slack\Http\SlackResponseFactory;


/* @class Slacker
 * used for pushing messages to slack
 */
class Slacker
{
	const MAX_ATTEMPTS = 15;

	/**
	 * @param string $message
	 * @param string $prefix
	 *
	 * @return bool
	 */
	public static function log($message = '', $prefix = '')
	{
		static $client = null;
		static $options = null;

		if (App::environment(['local', 'testing'])) {
			echo "Intercepted Slack message: ", $message, PHP_EOL;

			return true;
		}

		if (!$client || !$options) {
			$options = (object)['api_key' => null, 'channel' => null, 'bot_name' => null, 'bot_img' => null];
			self::getOptions($options);
			if (!$options->api_key || !$options->channel) {
				return false;
			}

			$interactor = new CurlInteractor;
			$interactor->setResponseFactory(new SlackResponseFactory);
			$client = new Commander($options->api_key, $interactor);
		}

		if (!is_string($message)) {
			$message = json_encode($message);
		}

		$response = $client->execute('chat.postMessage', [
			'channel'  => $options->channel,
			'username' => $options->bot_name,
			'icon_url' => $options->bot_img,
			'text'     => $prefix . $message
		]);

		return $response->getStatusCode() == 200;
	}

	private static function getOptions(&$options)
	{
		$options->api_key = env('SLACK_API_KEY');
		$options->channel = env('SLACK_CHANNEL');
		$options->bot_img = env('SLACK_BOT_IMG', null);
		$options->bot_name = env('SLACK_BOT_NAME', 'BOT');
	}

}