<?php

namespace WebSockets\Telnet;

use WebSockets\Interfaces\ConnectionInterface;

class Connection implements ConnectionInterface {
	
	/*
	 * @description: 
 	 * @type: 
	 */
	private $socket;
	/*
	 * @description: 
 	 * @type: 
	 */
	private $id;
	/*
	 * @description: 
 	 * @type: 
	 */
	private $name;

	/*
	 * description: ...
	 * @params: ...
	 * @return: ...
	 */
	public function __construct($socket,$id=0,$name="userrrrr") {
		$this->socket = $socket;
		$this->id = $id;
		$this->name = $name;
	}

	/*
	 * description: ...
	 * @params: ...
	 * @return: ...
	 */
	public function getSocket() {
		return $this->socket;
	}

	/*
	 * description: ...
	 * @params: ...
	 * @return: ...
	 */
	public function getName() {
		return $this->name;
	}

	/*
	 * description: ...
	 * @params: ...
	 * @return: ...
	 */
	public function getId() {
		return $this->id;
	}
}