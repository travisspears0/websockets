<?php

namespace WebSockets\Server;

class Connection {

	private $socket,
			$handShaked,
			$id,
			$name;

	public function __construct($socket,$id=0,$name="userrrrr") {
		$this->socket = $socket;
		$this->handShaked = false;
		$this->id = $id;
		$this->name = $name;
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

	public function getName() {
		return $this->name;
	}

	public function getId() {
		return $this->id;
	}

}