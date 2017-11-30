<?php

namespace devinoTeleApi;
class ViberSend {
	const BASE_URL = "https://viber.devinotele.com:444",
		SEND_ACTION_URL = "/send/",
		STATUS_ACTION_URL = "/status/";

	private $login,
		$password,
		$headers = array(),
		$data,
		$url,
		$rsCurl;


	private function init() {
		$this->headers[] = 'Content-type: application/json';
		$this->headers[] = 'Authorization: Basic ' . base64_encode($this->login . ":" . $this->password);
		$this->rsCurl = curl_init();
		curl_setopt($this->rsCurl, CURLOPT_TIMEOUT, 30);
		curl_setopt($this->rsCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->rsCurl, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($this->rsCurl, CURLOPT_POST, true);
	}

	private function exec() {
		$this->init();
		curl_setopt($this->rsCurl, CURLOPT_POSTFIELDS, $this->data);
		curl_setopt($this->rsCurl, CURLOPT_URL, $this->url);
		$result = curl_exec($this->rsCurl);
		$status_code = curl_getinfo($this->rsCurl, CURLINFO_HTTP_CODE);

		var_dump($status_code);
		var_dump($result);
	}

	public function __construct(string $login, string $password) {
		$this->login = $login;
		$this->password = $password;
	}

	public function __destruct() {
		if(is_resource($this->rsCurl))
			curl_close($this->rsCurl);
	}

	public function getStatus(array $aData) {
		if(empty($aData))
			return;

		$dataArr = array(
			"messages" => $aData
		);
		$this->data = json_encode($dataArr);
		var_dump($this->data);
		$this->url = static::BASE_URL.static::STATUS_ACTION_URL;
		$this->exec();
	}




}