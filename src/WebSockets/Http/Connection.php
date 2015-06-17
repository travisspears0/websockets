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
	 * description: performs handshake between user and server. Basically receives message from user and decides whether it passes requirements to establish connection properly
	 * @params:
	 *		$headers: (string)text sent by user
	 * @return: bool:
 	 *				true: handshake success
 	 *				false: handshake failure
	 */
	public function handshake($headers) {

		if(preg_match("/Sec-WebSocket-Version: (.*)\r\n/", $headers, $match))
			$version = $match[1];
		else {
			Server::write("The client doesn't support WebSocket");
			$this->handshaked = false;
			return false;
		}

		if($version == 13) {
			// Extract header variables
			if(preg_match("/GET (.*) HTTP/", $headers, $match))
				$root = $match[1];
			if(preg_match("/Host: (.*)\r\n/", $headers, $match))
				$host = $match[1];
			if(preg_match("/Origin: (.*)\r\n/", $headers, $match))
				$origin = $match[1];
			if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $match))
				$key = $match[1];

			$acceptKey = $key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
			$acceptKey = base64_encode(sha1($acceptKey, true));

			$upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
					   "Upgrade: websocket\r\n".
					   "Connection: Upgrade\r\n".
					   "Sec-WebSocket-Accept: $acceptKey".
					   "\r\n\r\n";

			socket_write($this->socket, $upgrade, strlen($upgrade));
			$this->handshaked = true;
			return true;
		}
		else {
			Server::write("WebSocket version 13 required (the client supports version {$version})");
			$this->handshaked = false;
			return false;
		}
	}
}