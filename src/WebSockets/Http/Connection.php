<?php

namespace WebSockets\Http;

use WebSockets\Interfaces\ConnectionInterface;

class Connection implements ConnectionInterface {
	
	/*
	 * @description: socket
 	 * @type: socket resource
	 */
	private $socket;
	/*
	 * @description: determines whether socket handshaked with the server
 	 * @type: boolean
	 */
	private $handshaked;
	/*
	 * @description: unique socket id on server
 	 * @type: integer
	 */
	private $id;
	/*
	 * @description: array of miscellaneous parameters of the client connection
 	 * @type: array
	 */
	private $params;

	/*
	 * description: constructor
	 * @params:
	 *		$socket (socket resource) socket
	 *		$id (integer) unique socket id on server
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
	 * description: specifies if conection handshaked with the server
	 * @params: -
	 * @return:
	 *		-true - connection handshaked
	 *		-false - connection hasn't handshaked yet
	 */
	public function isHandshaked() {
		return $this->handshaked;
	}

	/*
	 * description: sets $handshaked variable
	 * @params: 
	 *		$handshaked (boolean) 
	 * @return: -
	 */
	public function setHandshaked($handshaked) {
		$this->handshaked = $handshaked;
	}
}