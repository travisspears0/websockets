<?php

namespace WebSockets\Server;

class Server {

	const MAX_CONNECTIONS = 2;

	private $host,
			$port;

	public function __construct($host="127.0.0.1",$port="8080") {
		$this->host = $host;
		$this->port = $port;

		/* */
		set_error_handler(function($errno, $errstr) { 
			throw new \Exception("ERROR => [$errno] $errstr");
		});
		/* */
	}

	public function run() {
		Server::write("Server is running...");

		if(!($socket = socket_create(AF_INET, SOCK_STREAM, 0)))
		{
		    $errorcode = socket_last_error();
		    $errormsg = socket_strerror($errorcode);

		    Server::write("Couldn't create socket: [$errorcode] $errormsg");
		}

		echo Server::write("Socket created");

		if( !socket_bind($socket, $this->host , $this->port) )
		{
		    $errorcode = socket_last_error();
		    $errormsg = socket_strerror($errorcode);

		    Server::write("Could not bind socket : [$errorcode] $errormsg");
		}

		Server::write("Socket bind OK");

		if(!socket_listen ($socket , Server::MAX_CONNECTIONS))
		{
		    $errorcode = socket_last_error();
		    $errormsg = socket_strerror($errorcode);
		     
		    Server::write("Could not listen on socket : [$errorcode] $errormsg");
		}

		Server::write("Socket listen OK");

		Server::write("Waiting for incoming connections...");

		$connections = array();

		while(true) {

			$read = array();
			$read[0] = $socket;
			for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
				if( isset($connections[$i]) ) {
					$read[$i+1] = $connections[$i]->getSocket();
				}
			}
			if(socket_select($read , $write , $except , null) === false)
		    {
		        $errorcode = socket_last_error();
		        $errormsg = socket_strerror($errorcode);
		     
		        throw new \Exception("Could not listen on socket : [$errorcode] $errormsg!");
		    }
		    $newConn = true;
		    for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
		    	//received new message
		    	if( isset($connections[$i]) && in_array($connections[$i]->getSocket(), $read) ) {
		    		$newConn = false;
		    		$message = socket_read($connections[$i]->getSocket(), 2*1024);
		    		//empty message = disconnect
		    		/*if( strlen($message) === 0 && false ) {
						Server::write("USER [" . $connections[$i]->getSocket() . "] disconnected!");
		    			socket_close($connections[$i]->getSocket());
		    			unset($connections[$i]);
		    			unset($read[$i+1]);
		    			break;
		    		}*/
		    		if( !($connections[$i]->isHandShaked()) ) {
		    			if( $this->handshake($connections[$i]->getSocket(),$message,$socket) ) {
			    			$connections[$i]->handShake();
			    			//socket_write($connections[$i]->getSocket(),"Connection accepted!");
			    			Server::write("User [". $connections[$i]->getSocket() ."] HANDSHAKED!");
			    		} else {
			    			Server::write("User [". $connections[$i]->getSocket() ."] couldn't perform Handshake! Disconnecting...");
			    			socket_close($connections[$i]->getSocket());
			    			unset($connections[$i]);
			    			unset($read[$i+1]);
			    		}
			    		break;
		    		}
					Server::write("User [" . $connections[$i]->getSocket() . "]: $message" );
					//send user message to all other users
					for( $j=0 ; $j<Server::MAX_CONNECTIONS ; ++$j ) {
						if( isset($connections[$j]) ){//&& $j !== $i ) {
							try {
								$buff = "USER [". $connections[$j]->getSocket() ."]: $message";
								socket_write($connections[$j]->getSocket(), $buff, strlen($buff));
							} catch(\Exception $e) {
				    			Server::write("ERROR ". $connections[$i]->getSocket() ." disconnected!");
				    			socket_close($connections[$i]->getSocket());
				    			unset($connections[$i]);
				    			unset($read[$i+1]);
							}
						}
					}
					break;
		    	}
		    }
		    //there was no messages so it has to be new connection
		    if( $newConn ) {
		    	$newConn = false;
		    	$connection = socket_accept($socket);
		    	//reserving first empty slot in connections array
		    	for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
		    		if( !isset($connections[$i]) ) {
		    			$connections[$i] = new Connection($connection);//$connection;
						Server::write("USER [" . $connections[$i]->getSocket() . "] connected");
		    			$newConn = true;
		    			break;
		    		}
		    	}
		    	//there are no empty slots, disconnecting
		    	if( !$newConn ) {
					Server::write("New connection has not been accepted due to connections limit which has been reached!");
					$buff = "Server is full!";
					socket_write($connection, $buff,strlen($buff));
					socket_close($connection);
		    	}
		    }
		}
	}

	private function handshake($client, $headers, $socket) {

		if(preg_match("/Sec-WebSocket-Version: (.*)\r\n/", $headers, $match))
			$version = $match[1];
		else {
			Server::write("The client doesn't support WebSocket");
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

			socket_write($client, $upgrade, strlen($upgrade));
			return true;
		}
		else {
			Server::write("WebSocket version 13 required (the client supports version {$version})");
			return false;
		}
	}

	static function write($message,$author="SERVER") {
		echo "[". date('Y-m-d H:i:s') ."][$author]: $message\n" ;
	}

	static function sendMessage($socket,$message) {
		socket_write($socket, $message, strlen($message));//json...
	}

}