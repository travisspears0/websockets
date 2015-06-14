<?php

namespace WebSockets\Telnet;

use WebSockets\Interfaces\ServerInterface;

class Server implements ServerInterface { 

	/*
	 * @description: 
 	 * @type: constant integer
	 */
	const MAX_CONNECTIONS = 2;

	/*
	 * @description: server's ip
 	 * @type: string
	 */
	private $host;

	/*
	 * @description: port on which server will listen to connections
 	 * @type: string
	 */
	private $port;

	/*
	 * description: Server constructor, initializes crucial variables
	 * @params:
	 *		$host
	 *		$port
	 * @return: -
	 */
	public function __construct($host="127.0.0.1",$port="1234") {

		define("SERVER","SERVER");
		define("ONE_USER","ONE_USER");
		define("ALL_USERS","ALL_USERS");
		define("SOME_USERS","SOME_USERS");

		$this->host = $host;
		$this->port = $port;

		/* *
		set_error_handler(function($errno, $errstr) { 
			throw new \Exception("ERROR => [$errno] $errstr");
		});
		/* */
	}

	/*
	 * description: main function of the Server. Sets the infinite loop used to listen to connections.
	 * @params: -
	 * @return: -
	 */
	public function run() {
		Server::write("Server is running on host=[$this->host], port=[$this->port]...");

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
			//reset $read array
			$read = array();
			//add master socket to $read
			$read[0] = $socket;
			//add all the connections to $read
			for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
				if( isset($connections[$i]) ) {
					$read[$i+1] = $connections[$i]->getSocket();
				}
			}
			//blocking function listening to changes on sockets and writing changes to $read array
			if(socket_select($read , $write , $except , null) === false)
		    {
		        $errorcode = socket_last_error();
		        $errormsg = socket_strerror($errorcode);
		     
		        throw new \Exception("Could not listen on socket : [$errorcode] $errormsg!");
		    }
		    //there is new message or, if there's not, there is a new connection
		    if( !$this->onMessage($connections,$read) ) {
		    	$this->connect($connections,$socket);
		    }
		}
	}

	/*
	 * description: function called when a message from one of the sockets is received
	 * @params:
	 *		$text: (string) text to be translated
	 * @return: (boolean) 
	 *				true - there was new message
	 *				false - there was no new messages
	 */
	public function onMessage(&$connections,&$read) {
		for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
	    	if( isset($connections[$i]) && in_array($connections[$i]->getSocket(), $read) ) {
	    		$message="";
	    		//read whole message
				while( socket_recv($connections[$i]->getSocket(), $out, 2*1024,MSG_DONTWAIT) > 0 ) {
					if( $out != null ) {
						$message .= $out; 
					}
				}
				//try handshake
	    		/*if( !($connections[$i]->isHandshaked()) ) {
	    			//if( $this->handshake($connections[$i]->getSocket(),$message) ) {
	    			if( $connections[$i]->handshake($message) ) {
		    			Server::write("User [". $connections[$i]->getSocket() ."] HANDSHAKED!");
		    		} else {
		    			Server::write("User [". $connections[$i]->getSocket() ."] couldn't perform Handshake! Disconnecting...");
	    				$this->disconnect($i,$connections,$read);
		    		}
					return true;
	    		}*/
	    		//$message = $this->unmask($message);
	    		//empty message = disconnect
	    		//message including only end of text(ascii = 3) = disconnect
	    		//(in fact first codition should be replaced/removed)
	    		if( strlen($message) === 0 || ord($message) === 3 ) {
	    			$response = "USER [" . $connections[$i]->getSocket() . "] disconnected!";
					Server::write($response);
					//$this->sendMessageToAll($connections,$response);//change...
					$this->sendMessage($response,$connections);
	    			$this->disconnect($i,$connections,$read);
					return true;
	    		}
	    		//display user's message on server console
				$this->writeFromUser($message,$connections[$i]->getSocket());
				//send user message to all other users
				$this->sendMessage($message,$connections,$connections[$i]->getName(),ALL_USERS,$i);
				//$this->sendMessageToAll($connections,$message,$i);//change
				return true;
	    	}
	    }
	    return false;
	}

	/*
	 * description: disconnects socket and removes it from server
	 * @params:
	 *		$index: (mixed/integer)index of socket (in $connections array) to be removed 
	 *		&$connections: (array reference)array with server connections
	 *		&$read: (array reference)array with changes(from socket_select())
	 *
	 *		$socket: (mixed/socket resource)single socket not yet registered in server to be disconnected
	 * @return: -
	 */
	public function disconnect($index,&$connections=null,&$read=null) {

		if(func_num_args() === 1) {
			socket_close($index);
			return;
		}

		socket_close($connections[$index]->getSocket());
		unset($connections[$index]);
		unset($read[$index+1]);
	}

	/*
	 * description: connects new socket to the server
	 * @params:
	 *		&$connections: (array reference)array with server connections
	 *		$socket: (socket resource)master socket
	 * @return: (bool)
	 *		true - successfuly connected
	 *		false - connection failure
	 */
	public function connect(&$connections,$socket) {
		$newConn = false;
    	$connection = socket_accept($socket);
    	//reserving first empty slot in connections array
    	for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
    		if( !isset($connections[$i]) ) {
    			$connections[$i] = new Connection($connection);
    			$response = "USER [" . $connections[$i]->getSocket() . "] connected";
				Server::write($response);
    			$newConn = true;
    			break;
    		}
    	}
    	//there are no empty slots, disconnecting
    	if( !$newConn ) {
			Server::write("New connection has not been accepted due to connections limit which has been reached!");
			$buff = "Server is full!";
//NOT WORKING!!
			//$this->sendMessage($connection,$buff);
			//socket_write($connection, $buff,strlen($buff));
			$this->disconnect($connection);
    	} else {
//NOT WORKING!!
    		//$this->sendMessageToAll($connections,"new user connected");
    	}
	}
	
	/*
	 * description: writes down a message in server's console
	 * @params:
	 *		$message: (string)text to be written
	 *		$author: (string)name of the author of the message[DEFAULT='SERVER']
	 * @return: -
	 */
	static function write($message,$author='SERVER') {
		echo "[". date('Y-m-d H:i:s') ."][$author]: $message\n" ;
	}

	/*
	 * description: writes down a message in server's console
	 * @params:
	 *		$message: (string)text to be written
	 *		$author: (string)name of the author of the message
	 * @return: -
	 */
	private function writeFromUser($message,$author) {
		echo "[". date('Y-m-d H:i:s') ."][$author]: $message\n" ;
	}

	/*
	 * description: sends a message to user(s) encoded in json
	 * @params:
	 *		$message: (string)message to be sent
	 *		$connections
	 *		$author: (string)name of the author of the message[DEFAULT='SERVER']
	 *		$sendTo: (FLAG)
	 *			ONE_USER - send message only to one user
	 *			ALL_USERS - send message to all users
	 *			SOME_USERS - send message to multiple users
	 *		$dest: (mixed)
 	 *			$sendTo==ONE_USER: (integer)index in $connections array
 	 *			$sendTo==ALL_USERS: doesn't matter
 	 *			$sendTo==SOME_USERS: (array of integers)array of indexes in $connections array
	 * @return: -
	 */
	public function sendMessage($message,$connections,$author=SERVER,$sendTo=ALL_USERS,$dest=-1) {	
		switch ($sendTo) {
			case ONE_USER: {
				$socket = $connections[$dest]->getSocket();
				socket_write($socket,$message,strlen($message));
				break;
			}
			case SOME_USERS: {
				foreach ( $dest as $index ) {
					if( !isset($connections[$index]) ) {
						continue;
					}
					$socket = $connections[$index]->getSocket();
					socket_write($socket,$message,strlen($message));
				}
				break;
			}
			case ALL_USERS: {
				for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
					if( isset($connections[$i]) ) {
						$socket = $connections[$i]->getSocket();
						socket_write($socket,$message,strlen($message));
					}
				}
				break;
			}
		}
	}

}