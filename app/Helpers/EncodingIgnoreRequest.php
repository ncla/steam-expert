<?php

namespace App\Helpers;

use MultiRequest\Request;

/**
 * Modified class for handleCurlResult method to ignore invalid encoding for responses
 * @see https://github.com/barbushin/multirequest
 * @author Barbushin Sergey http://linkedin.com/in/barbushin
 */
class EncodingIgnoreRequest extends Request
{

	public function handleCurlResult()
	{
		$curlHandle = $this->getCurlHandle();
		$this->curlInfo = curl_getinfo($curlHandle);
		$this->error = curl_error($curlHandle);
		$responseData = curl_multi_getcontent($curlHandle);

		// fix bug? https://bugs.php.net/bug.php?id=63894
		preg_match_all('/.*Content-Length: (\d+).*/mi', $responseData, $matches);

		$contentLength = array_pop($matches[1]);

		// HTTP/1.0 200 Connection established\r\nProxy-agent: Kerio WinRoute Firewall/6.2.2 build 1746\r\n\r\nHTTP
		if (stripos($responseData, "HTTP/1.0 200 Connection established\r\n\r\n") !== false) {
			$responseData = str_ireplace("HTTP/1.0 200 Connection established\r\n\r\n", '', $responseData);
		}

		if (is_null($contentLength) || $contentLength == 0) {
			$this->responseHeaders = mb_substr($responseData, 0, curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE));
			$this->responseContent = mb_substr($responseData, curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE));

		} else {
			$this->responseHeaders = mb_substr($responseData, 0, mb_strlen($responseData) - $contentLength);
			$this->responseContent = mb_substr($responseData, mb_strlen($responseData) - $contentLength);
		}

		$clientEncoding = $this->detectClientCharset($this->getResponseHeaders());
		if ($clientEncoding && $clientEncoding != $this->serverEncoding) {
			self::$clientsEncodings[ $this->getDomain() ] = $clientEncoding;
			try {
				$this->responseContent = mb_convert_encoding($this->responseContent, $this->serverEncoding, $clientEncoding);
			} catch (\ErrorException $e) {
				$this->responseContent = '';
			}
		}
		if ($curlHandle && is_resource($curlHandle)) {
			curl_close($curlHandle);
		}
	}
}
