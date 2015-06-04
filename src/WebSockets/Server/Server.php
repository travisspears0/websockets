<?php

namespace WebSockets\Server;

class Server {

	const MAX_CONNECTIONS = 2;

	private $host,
			$port;

	public function __construct($host="127.0.0.1",$port="8080") {
		$this->host = $host;
		$this->port = $port;
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
		$read = array();

		while(true) {
			$read[0] = $socket;

			for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
				if( isset($connections[$i]) && $connections[$i] != null ) {
					$read[$i+1] = $connections[$i];
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
		    	if( isset($connections[$i]) && in_array($connections[$i], $read) ) {
		    		$newConn = false;
		    		$message = socket_read($connections[$i], 1024);
		    		if( strlen($message) === 0 ) {
						Server::write("USER [" . $connections[$i] . "] disconnected!");
		    			socket_close($connections[$i]);
		    			unset($connections[$i]);
		    			break;
		    		}
					Server::write("User [" . $connections[$i] . "]: $message" );
					for( $j=0 ; $j<Server::MAX_CONNECTIONS ; ++$j ) {
						if( isset($connections[$j]) && $j !== $i ) {
							socket_write($connections[$j], "USER [". $connections[$j] ."]: $message");
						}
					}
					break;
		    	}
		    }
		    if( $newConn ) {
		    	$newConn = false;
		    	$connection = socket_accept($socket);
		    	for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
		    		if( !isset($connections[$i]) ) {
		    			$connections[$i] = $connection;
						Server::write("USER [" . $connections[$i] . "] connected");
		    			$newConn = true;
		    			break;
		    		}
		    	}
		    	if( !$newConn ) {
					Server::write("New connection has not been accepted due to connections limit which has been reached!");
					socket_write($connection, "Server is full!");
					socket_close($connection);
		    	}
		    }
		}
	}

	static function write($message,$author="SERVER") {
		echo "[$author]: $message\n" ;
	}

	static function sendMessage($socket,$message) {
		socket_write($socket, $message);//json...
	}

}