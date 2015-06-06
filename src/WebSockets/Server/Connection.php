<?php

namespace WebSockets\Server;

class Connection {

	private $socket,
			$handShaked,
			$params;//...username etc...

	public function __construct($socket) {
		$this->socket = $socket;
		$this->handShaked = false;
	}

	public function handShake() {
		$this->handShaked = true;
	}

	public function isHandShaked() {
		return $this->handShaked;
	}

	public function getSocket() {
		return $this->socket;
	}

}