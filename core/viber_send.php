<?php

namespace devinoTeleApi;
class ViberSend {
	const BASE_URL = "https://viber.devinotele.com:444",
		SEND_ACTION_URL = "/send/",
		STATUS_ACTION_URL = "/status/",
		E_164_REG = "/^\+?(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d| 2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]| 4[987654310]|3[9643210]|2[70]|7|1)?[1-9]\d{10,14}$/";

	private $login,
		$password,
		$headers = array(),
		$data,
		$url,
		$defaultSender,
		$validContentType = array('text'),
		$rsCurl,
		$logString = "";

	public $logging = true;
	public $logFilePath;



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
		if($this->logging === true) $this->logString .= "exec call | ";
		$this->init();
		curl_setopt($this->rsCurl, CURLOPT_POSTFIELDS, $this->data);
		curl_setopt($this->rsCurl, CURLOPT_URL, $this->url);
		$result = curl_exec($this->rsCurl);
		$status_code = curl_getinfo($this->rsCurl, CURLINFO_HTTP_CODE);
		if($this->logging === true) $this->logString .= "$result | ";
		if($status_code !== 200) {
			throw new ViberSendException("ViberSendError: ".$result);
		} else {
			return $result;
		}
	}

	public function __construct(string $login, string $password, string $defSender = "") {
		$this->login = $login;
		$this->password = $password;
		$this->defaultSender = $defSender;
	}

	public function __destruct() {
		if(is_resource($this->rsCurl))
			curl_close($this->rsCurl);
		if($this->logging === true && file_exists($this->logFilePath)){
			$logString = date("d-m-Y H:i:s") . " || " . $this->logString . "\n\n";
			file_put_contents($this->logFilePath,$logString,FILE_APPEND);
		}
	}

	public function getStatus(array $aData) {
		if($this->logging === true) $this->logString .= "getStatus call | ";
		if(empty($aData)) {
			if($this->logging === true) $this->logString .= '$aData empty | ';
			throw new ViberSendException('Arg $aData is empty');
		}


		$dataArr = array(
			"messages" => $aData
		);
		$this->data = json_encode($dataArr);
		$this->url = self::BASE_URL . self::STATUS_ACTION_URL;
		return $this->exec();
	}

	public function sendMessage(string $address,
	                            array $content,
	                            string $contentType = "text",
	                            bool $resendSms = true,
	                            string $priority = "high",
	                            int $validityPeriodSec = 60,
	                            int $smsValidityPeriodSec = 5000,
	                            $subject = false,
	                            $smsSrcAddress = false
	) {
		if($this->logging === true) $this->logString .= "sendMessage call | ";
		if(!in_array($contentType, $this->validContentType)) {
			if($this->logging === true) $this->logString .= 'Invalid $contentType | ';
			throw new ViberSendException("Invalid content type " . $contentType);
		}

		$address = trim($address);
		if(!preg_match(self::E_164_REG,$address)) {
			if($this->logging === true) $this->logString .= 'Wrong format $address | ';
			throw new ViberSendException("Recipient address has wrong format");
		} else {
			$address = str_replace("+","",$address);
		}

		$dataArr = array(
			"resendSms" => ($resendSms === true)?"true":"false",
			"messages" => array(
				array(
					"subject" => ($subject !== false)?$subject:$this->defaultSender,
					"priority" => "$priority",
					"validityPeriodSec" => $validityPeriodSec,
					"type" => "viber",
					"contentType" => $contentType,
					"content" => array(),
					"address" => $address,
					"smsSrcAddress" => ($smsSrcAddress !== false)?$smsSrcAddress:$this->defaultSender,
					"smsValidityPeriodSec" => $smsValidityPeriodSec
				)
			)
		);

		switch($contentType) {
			case 'text':
				$content["text"] = trim($content["text"]);
				if(empty($content["text"])) {
					if($this->logging === true) $this->logString .= 'key "text" empty | ';
					throw new ViberSendException('Arg $content must have not empty key "text"');
				}

				$content["smsText"] = trim($content["smsText"]);
				if(empty($content["smsText"])) {
					$content["smsText"] = $content["text"];
				}
				$dataArr["messages"][0]["content"]["text"] = $content["text"];
				$dataArr["messages"][0]["smsText"] = $content["smsText"];
				break;
		}

		if($this->logging === true) $this->logString .= '$address = ' . var_export($address,true) . ', $content = ' . var_export($content,true) . " | ";
		$this->data = json_encode($dataArr);
		$this->url = self::BASE_URL . self::SEND_ACTION_URL;
		return $this->exec();
	}
}

class ViberSendException extends \Exception {
}
