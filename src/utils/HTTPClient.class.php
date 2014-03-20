<?php

if (!defined('MEMCACHE_ENABLED')) {
	define('MEMCACHE_ENABLED', true);
}

class HTTPClient {

	private $header = null;

	const CURL_ENABLED = true;

	const CACHE_ENABLED = MEMCACHE_ENABLED;

	const TTL_1_MINUTE = 60;
	const TTL_1_HOUR = 3600;
	const TTL_24_HOURS = 86400;
	const TTL_1_WEEK = 604800;

	private $referer = 'http://www.google.fr/';
	//private $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)';
	private $userAgent = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:11.0) Gecko/20100101 Firefox/11.0';
	private $lastHttpResponseCode;
	private $timeout=null;

	public function setReferer($referer){
		$this->referer = $referer;
	}

	public function setTimeout($t){
		$this->timeout = $t;
	}

	public function setUserAgent($userAgent){
		$this->userAgent = $userAgent;
	}

	public function addHeader($header){
		if(!$this->header){
			$this->header = array();
		}
		$this->header[] = $header;
	}

	public function request($url, $ttl=0){
		if(self::CACHE_ENABLED && $ttl > 0 && class_exists('Memcache')){
			$memcache = new Memcache;
			$cachekey = hash("md5", $url);
			try{
				$memcache->connect('localhost', 11211);
				$result = $memcache->get($cachekey);
			}
			catch (Exception $e){
				$result = null;
			}
			if($result == null){
				$result = $this->doHTTPRequest($url);
				if($result){
					try{
						$memcache->set($cachekey,$result,0, $ttl);
					}catch (Exception $e){
					}
				}
			}
			return $result;
		}
		else{
			$result = $this->doHTTPRequest($url);
			return $result;
		}
	}

	public function clearUrlCache($url){
		if(self::CACHE_ENABLED && class_exists('Memcache')){
			$memcache = new Memcache;
			$cachekey = hash("md5", $url);
			try{
				$memcache->connect('localhost', 11211);
				$memcache->delete($cachekey);
			}
			catch (Exception $e){
			}
		}
	}

	private function doHTTPRequest($url){

		if (self::CURL_ENABLED && function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_VERBOSE, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
			//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			//curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			if($this->referer){
				curl_setopt($ch, CURLOPT_REFERER, $this->referer);
			}
			if($this->userAgent){
				curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent );
			}
			if($this->header){
				curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header );
			}
			if($this->timeout){
				curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
			}

			curl_setopt($ch,CURLOPT_ENCODING, '');
			curl_setopt($ch, CURLOPT_URL, $url);
			$result=curl_exec ($ch);
			$this->lastHttpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close ($ch);
		}
		else {
			$result= file_get_contents($url);
		}
		//echo("############# http request end ".time()."\n");
		return $result;
	}

	public function postRequest($url, $body, $waitForResponse=true, $ttl=0, $cachekey=null){
		if(self::CACHE_ENABLED && $ttl > 0 && class_exists('Memcache')){
			$memcache = new Memcache;
			if(!$cachekey){
				$cachekey = hash("md5", $url)."-".hash("md5", $body);
			}
			try{
				$memcache->connect('localhost', 11211);
				$result = $memcache->get($cachekey);
			}
			catch (Exception $e){
				$result = null;
			}
			if($result == null){
				$result = $this->doPostRequest($url, $body, $waitForResponse);
				if($result){
					try{
						$memcache->set($cachekey,$result,0, $ttl);
					}catch (Exception $e){
					}
				}
			}
			return $result;
		}else{
			$result = $this->doPostRequest($url, $body, $waitForResponse);
			return $result;
		}
	}

	private function doPostRequest($url, $body, $waitForResponse=true){
		if (self::CURL_ENABLED && function_exists('curl_init')) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_VERBOSE, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
			curl_setopt ($ch, CURLOPT_POST, true);
			curl_setopt ($ch, CURLOPT_POSTFIELDS, $body);
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			if($this->referer){
				curl_setopt($ch, CURLOPT_REFERER, $this->referer);
			}
			if($this->userAgent){
				curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent );
			}
			if($this->header){
				curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header );
			}
			if(!$waitForResponse){
				curl_setopt($ch, CURLOPT_TIMEOUT, 1);
			}else if($this->timeout){
				curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
			}
			curl_setopt($ch, CURLOPT_URL, $url);
			$result=curl_exec ($ch);

			$this->lastHttpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close ($ch);
			return $result;
		}
		else{
			$params = array('http' => array(
					'method' => 'POST',
					'content' => $body
			));
			if($this->header){
				$params['http']['header'] = $this->header;
			}
			$ctx = stream_context_create($params);
			$fp = @fopen($url, 'rb', false, $ctx);
			if (!$fp) {
				throw new Exception("Problem with $url");
			}
			$response = @stream_get_contents($fp);
			$responses = $this->parse_http_response_header($http_response_header);
			$this->lastHttpResponseCode = $responses[0]['status']['code']; // last status code
			return $response;
		}
		return null;
	}

	/**
	 * parse_http_response_header
	 *
	 * @param array $headers as in $http_response_header
	 * @return array status and headers grouped by response, last first
	 */
	function parse_http_response_header(array $headers)
	{
		$responses = array();
		$buffer = NULL;
		foreach ($headers as $header)
		{
			if ('HTTP/' === substr($header, 0, 5))
			{
				// add buffer on top of all responses
				if ($buffer) array_unshift($responses, $buffer);
				$buffer = array();

				list($version, $code, $phrase) = explode(' ', $header, 3) + array('', FALSE, '');

				$buffer['status'] = array(
						'line' => $header,
						'version' => $version,
						'code' => (int) $code,
						'phrase' => $phrase
				);
				$fields = &$buffer['fields'];
				$fields = array();
				continue;
			}
			list($name, $value) = explode(': ', $header, 2) + array('', '');
			// header-names are case insensitive
			$name = strtoupper($name);
			// values of multiple fields with the same name are normalized into
			// a comma separated list (HTTP/1.0+1.1)
			if (isset($fields[$name]))
			{
				$value = $fields[$name].','.$value;
			}
			$fields[$name] = $value;
		}
		unset($fields); // remove reference
		array_unshift($responses, $buffer);

		return $responses;
	}

	public function downloadFile($url, $out) {
		if (self::CURL_ENABLED && function_exists('curl_init')) {
			$fp = fopen (".$out", 'w+');
			$ch = curl_init($url);

			curl_setopt($ch, CURLOPT_TIMEOUT, 6);

			// Save the returned data to a file
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_VERBOSE, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, false);
			//curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			//curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
			if($this->referer){
				curl_setopt($ch, CURLOPT_REFERER, $this->referer);
			}
			if($this->userAgent){
				curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent );
			}
			if($this->header){
				curl_setopt($ch, CURLOPT_HTTPHEADER, $this->header );
			}

			curl_setopt($ch, CURLOPT_URL, $url);
			$result=curl_exec ($ch);
			$this->lastHttpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close ($ch);
		}
		else {
			$result= file_get_contents($url);
		}
		return $result;
	}

	public function getLastHttpResponseCode(){
		return $this->lastHttpResponseCode;
	}

	public function download($url, $path) {
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$data = curl_exec($ch);

		curl_close($ch);

		return file_put_contents($path, $data);
	}
}
?>