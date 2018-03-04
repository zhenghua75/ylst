<?php
namespace Extend;
class HttpClient
{
	static 	private $_model;
	protected $ch;
	protected $method;
	protected $config = array(
			"CURLOPT_FAILONERROR" => 1,
			 "CURLOPT_FOLLOWLOCATION" => 1, 
			 "CURLOPT_AUTOREFERER" => 1, 
			 "CURLOPT_ENCODING" => 'gzip, deflate', 
			 "CURLOPT_SSL_VERIFYPEER" => 1, 
			 "CURLOPT_HEADER" => 1, 
			 "CURLOPT_USERAGENT" => 'kmdx/v1.0', 
			 "CURLOPT_SSLVERSION" => 1
		 );

	private function __clone()
	{
	}

	private function __construct()
	{
		$this->init();
	}

	static public function getInstance()
	{
		if (!self::$_model instanceof self) {
			self::$_model = new self();
		}

		return self::$_model;
	}

	public function init()
	{
		$this->ch = curl_init();
		curl_setopt_array($this->ch, $this->config);
	}

	private function _request($url, $params)
	{
		$params["form"] = ($params["form"] ? $params["form"] : "");

		if ($params["timeout"]) {
			$params["timeout"] = 10000;
		}

		curl_setopt($this->ch, CURLOPT_URL, $url);

		if ("get" == $this->method) {
			curl_setopt($this->ch, CURLOPT_HTTPGET, true);
		}

		if ("post" == $this->method) {
			curl_setopt($this->ch, CURLOPT_POST, true);

			if ("data" !== $params["form"]) {
				$params["post"] = (is_array($params["post"]) ? http_build_query($params["post"]) : $params["post"]);
			}

			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $params["post"]);
		}

		if (!$params["header"]) {
			curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->_setHeader($params["header"]));
		}

		curl_setopt($this->ch, CURLOPT_TIMEOUT_MS, $params["timeout"]);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($this->ch);

		if ($this->hasError()) {
			return false;
		}

		return $result;
	}

	private function _setHeader($headers)
	{
		if (is_string($headers)) {
			$header[] = $headers;
		}
		else {
			foreach ($headers as $key => $value ) {
				$header[] = (is_numeric($key) ? $value : $key . ": " . $value);
			}
		}

		return $header;
	}

	public function get($url, $params)
	{
		$this->method = "get";
		return $this->_request($url, $params);
	}

	public function post($url, $params)
	{
		$this->method = "post";
		return $this->_request($url, $params);
	}

	public function setOption($option, $value)
	{
		curl_setopt($this->ch, $option, $value);
		return $this;
	}

	public function getHttpCode()
	{
		return $this->getInfo(CURLINFO_HTTP_CODE);
	}

	public function getInfo($option)
	{
		return $option ? curl_getinfo($this->ch) : curl_getinfo($this->ch, $option);
	}

	public function hasError()
	{
		return curl_errno($this->ch) != 0 ? true : false;
	}

	public function getErrorMsg()
	{
		return "CURL ERROR #" . curl_errno($this->ch) . ": " . curl_error($this->ch);
	}

	public function close()
	{
		curl_close($this->ch);
	}

	public function __destruct()
	{
		$this->close();
	}
}


?>
