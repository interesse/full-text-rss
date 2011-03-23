<?php
/**
 * Humble HTTP Agent
 * 
 * This class is designed to take advantage of parallel HTTP requests
 * offered by PHP's PECL HTTP extension. For environments which 
 * do not have this extension, it reverts to standard sequential 
 * requests (using file_get_contents())
 * 
 * @version 2010-10-19
 * @see http://php.net/HttpRequestPool
 * @author Keyvan Minoukadeh
 * @copyright 2010 Keyvan Minoukadeh
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPL v3
 */

class HumbleHttpAgent
{
	protected $requests = array();
	protected $requestOptions;
	protected $parallelSupport;
	protected $maxParallelRequests = 5;
	protected $cache = null;
	protected $httpContext;
	protected $minimiseMemoryUse = false;
	protected $debug = false;
	
	//TODO: prevent certain file/mime types
	//TODO: set max file size
	//TODO: normalise headers
	
	function __construct($requestOptions=null) {
		$this->parallelSupport = class_exists('HttpRequestPool');
		$this->requestOptions = array(
			'timeout' => 10,
			'redirect' => 5
			// TODO: test onprogress?
		);
		if (is_array($requestOptions)) {
			$this->requestOptions = array_merge($this->requestOptions, $requestOptions);
		}
		$this->httpContext = stream_context_create(array(
			'http' => array(
				'timeout' => $this->requestOptions['timeout'],
				'max_redirects' => $this->requestOptions['redirect'],
				'header' => "User-Agent: PHP/5.2\r\n".
                    "Accept: */*\r\n"
				)
			)
		);		
	}
	
	protected function debug($msg) {
		if ($this->debug) {
			$mem = round(memory_get_usage()/1024, 2);
			$memPeak = round(memory_get_peak_usage()/1024, 2);
			echo '* ',$msg;
			echo ' - mem used: ',$mem," (peak: $memPeak)\n";	
			ob_flush();
			flush();
		}
	}
	
	public function enableDebug($bool=true) {
		$this->debug = (bool)$bool;
	}
	
	public function minimiseMemoryUse($bool = true) {
		$this->minimiseMemoryUse = $bool;
	}
	
	public function setMaxParallelRequests($max) {
		$this->maxParallelRequests = $max;
	}
	
	/**
	 * Set cache object.
	 * The cache object passed should implement Zend_Cache_Backend_Interface
	 * @param Zend_Cache_Backend_Interface
	 */
	public function useCache($cache) {
		$this->cache = $cache;
	}
	
	public function validateUrl($url) {
		//TODO: run sanitize filter first!
		$test = filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
		// deal with bug http://bugs.php.net/51192 (present in PHP 5.2.13 and PHP 5.3.2)
		if ($test === false) {
			$test = filter_var(strtr($url, '-', '_'), FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED);
		}
		if ($test !== false && $test !== null && preg_match('!^https?://!', $url)) {
			return filter_var($url, FILTER_SANITIZE_URL);
		} else {
			return false;
		}
	}
	
	public function isCached($url) {
		if (!isset($this->cache)) return false;
		return ($this->cache->test(md5($url)) !== false);
	}
	
	public function getCached($url) {
		$cached = $this->cache->load(md5($url));
		$cached['fromCache'] = true;
		return $cached;
	}
	
	public function cache($url) {
		if (isset($this->cache) && !isset($this->requests[$url]['fromCache']) && isset($this->requests[$url]['body'])) {
			$this->debug("Saving to cache ($url)");
			$res = $this->cache->save($this->requests[$url], md5($url));
			//$res = @file_put_contents($this->cacheFolder.'/'.md5($url).'.txt', serialize($this->requests[$url]));
			return ($res !== false);
		}
		return false;
	}	
	
	public function cacheAll() {
		if (isset($this->cache)) {
			foreach (array_keys($this->requests) as $url) {
				$this->cache($url);
			}
			return true;
		}
		return false;
	}
	
	public function fetchAll(array $urls) {
		$urls = array_unique($urls);
		// parallel
		if (count($urls) > 1 && $this->parallelSupport() && $this->maxParallelRequests > 1) {
			$this->debug('Starting parallel fetch');
			try {
				while (count($urls) > 0) {
					$this->debug('Processing set of '.$this->maxParallelRequests);
					$subset = array_splice($urls, 0, $this->maxParallelRequests);
					$pool = new HttpRequestPool();
					foreach ($subset as $url) {
						$this->debug("...$url");
						if (isset($this->requests[$url])) {
							$this->debug("......in memory");
						} elseif ($this->isCached($url)) {
							$this->debug("......is cached");
							if (!$this->minimiseMemoryUse) {
								$this->requests[$url] = $this->getCached($url);
							}
						} else {
							$this->debug("......adding to pool");
							$httpRequest = new HttpRequest($url, HttpRequest::METH_GET, $this->requestOptions);
							$this->requests[$url] = array('headers'=>null, 'body'=>null, 'httpRequest'=>$httpRequest);
							$pool->attach($httpRequest);
						}
					}
					// did we get anything into the pool?
					if (count($pool) > 0) {
						$this->debug('Sending request...');
						$pool->send();
						$this->debug('Received responses');
						foreach($subset as $url) {
							if (!isset($this->requests[$url]['fromCache'])) {
								$request = $this->requests[$url]['httpRequest'];
								$this->requests[$url]['headers'] = $this->headersToString($request->getResponseHeader());
								$this->requests[$url]['body'] = $request->getResponseBody();
								$this->requests[$url]['effective_url'] = $request->getResponseInfo('effective_url');
								//die($url.' -multi- '.$request->getResponseInfo('effective_url'));
								$pool->detach($request);
								unset($this->requests[$url]['httpRequest'], $request);
								if ($this->minimiseMemoryUse) {
									if ($this->cache($url)) {
										unset($this->requests[$url]);
									}
								}
							}
						}
					}
				}
			} catch (HttpException $e) {
				$this->debug($e);
				return false;
			}
		// sequential
		} else {
			$this->debug('Starting sequential fetch...');
			foreach($urls as $url) {
				$this->get($url);
			}
		}
	}
	
	protected function headersToString(array $headers, $associative=true) {
		if (!$associative) {
			return implode("\n", $headers);
		} else {
			$str = '';
			foreach ($headers as $key => $val) {
				if (is_array($val)) {
					foreach ($val as $v) $str .= "$key: $v\n";
				} else {
					$str .= "$key: $val\n";
				}
			}
			return rtrim($str);
		}
	}
	
	protected function getRedirectUrl($header) {
		if (is_array($header)) $header = implode("\n", $header);
		if (!$header || !preg_match_all('!^Location:\s*(https?://.+)!im', $header, $match, PREG_SET_ORDER)) {
			// error parsing the response
			return false;
		} else {
			$match = end($match); // get last matched element (in case of redirects)
			return $match[1];
		}			
	}
	
	public function get($url) {
		if (isset($this->requests[$url]) && isset($this->requests[$url]['body'])) {
			$this->debug("URL already fetched - in memory ($url)");
			$response = $this->requests[$url];
		} elseif ($this->isCached($url)) {
			$this->debug("URL already fetched - in disk cache ($url)");
			$response = $this->getCached($url);
			$this->requests[$url] = $response;
		} else {
			$this->debug("Fetching URL ($url)");
			if ($html = @file_get_contents($url, false, $this->httpContext)) {
				$header = $this->headersToString($http_response_header, false);
				$response = array('headers'=>$header, 'body'=>$html);
				if ($last_url = $this->getRedirectUrl($header)) {
					$response['effective_url'] = $last_url;
					//die($url .' -single- '. $response['effective_url']);
				} else {
					$response['effective_url'] = $url;
				}
				$this->requests[$url] = $response;
			} else {
				$response = false;
			}
		}
		if ($this->minimiseMemoryUse && $response) {
			$this->cache($url);
			unset($this->requests[$url]);
		}
		return $response;
	}
	
	public function parallelSupport() {
		return $this->parallelSupport;
	}
}
?>