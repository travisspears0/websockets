<?php

namespace WebSockets\Http;

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
	private $handshaked;
	/*
	 * @description: 
 	 * @type: 
	 */
	private $id;
	/*
	 * @description: 
 	 * @type: 
	 */
	private $params;

	/*
	 * description: ...
	 * @params: ...
	 * @return: ...
	 */
	public function __construct($socket,$id=0) {
		$this->socket = $socket;
		$this->handshaked = false;
		$this->id = $id;
		$this->params = array("name"=>'');
	}

	/*
	 * description: returns one of the connection's param
	 * @params: 
	 *		(string)$key - key of the param
	 * @return: (mixed)connection's param. Type isn't specified.
	 */
	public function getParam($key) {
		return $this->params[$key];
	}

	/*
	 * description: sets one of the connection's param
	 * @params: 
	 *		(string)$key - key of the param
	 *		(mixed)$newValue - new value for the param
	 * @return: (boolean)
	 *		true - param exists and have been set
	 *		false - there's no such param
	 */
	public function setParam($key,$newValue) {
		if( !isset($this->params[$key]) ) {
			return false;
		}
		$this->params[$key] = $newValue;
		return true;
	}


	/*
	 * description: returns connection's id
	 * @params: -
	 * @return: connection's id
	 */
	public function getId() {
		return $this->id;
	}

	/*
	 * description: returns connection's socket resource
	 * @params: -
	 * @return: (socket resource)connection's socket resource
	 */
	public function getSocket() {
		return $this->socket;
	}

	/*
	 * description: ...
	 * @params: ...
	 * @return: ...
	 */
	public function isHandshaked() {
		return $this->handshaked;
	}

	/*
	 * description: ...
	 * @params: ...
	 * @return: ...
	 */
	public function setHandshaked($handshaked) {
		$this->handshaked = $handshaked;
	}
}