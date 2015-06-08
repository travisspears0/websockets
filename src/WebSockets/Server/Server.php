<?php

namespace WebSockets\Server;

class Server {

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
		    $newConn = true;
		    //loop through connections
		    for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
		    	//received new message
		    	if( isset($connections[$i]) && in_array($connections[$i]->getSocket(), $read) ) {
		    		$newConn = false;
		    		$message="";
		    		//read whole message
    				while( socket_recv($connections[$i]->getSocket(), $out, 2*1024,MSG_DONTWAIT) > 0 ) {
    					if( $out != null ) {
    						$message .= $out; 
    					}
    				}
    				//try handshake
		    		if( !($connections[$i]->isHandShaked()) ) {
		    			if( $this->handshake($connections[$i]->getSocket(),$message,$socket) ) {
			    			$connections[$i]->handShake();
			    			Server::write("User [". $connections[$i]->getSocket() ."] HANDSHAKED!");
			    		} else {
			    			Server::write("User [". $connections[$i]->getSocket() ."] couldn't perform Handshake! Disconnecting...");
		    				$this->disconnect($i,$connections,$read);
			    		}
			    		break;
		    		}
		    		//empty message = disconnect
		    		if( strlen($this->unmask($message)) === 0 ) {
		    			$response = "USER [" . $connections[$i]->getSocket() . "] disconnected!";
						Server::write($response);
						//$this->sendMessage(	$connections[$i]->getSocket(),"disconnecting...");
						$this->sendMessageToAll($connections,$response);
		    			$this->disconnect($i,$connections,$read);
		    			break;
		    		}
		    		//display user's message on server console
					$this->writeFromUser($message,$connections[$i]->getSocket());
					//send user message to all other users
					//last argument to true/false according to if it comes back to author or not
					$this->sendMessageToAll($connections,$message,$i,true);
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
		}
	}

	/*
	 * description: translates messages sent by users for the server(TCP protocol)
	 * @params:
	 *		$text: (string) text to be translated
	 * @return: $text: (string) translated text
	 */
	private function unmask($text) {
		$length = ord($text[1]) & 127;
		if($length == 126) {
			$masks = substr($text, 4, 4);
			$data = substr($text, 8);
		}
		elseif($length == 127) {
			$masks = substr($text, 10, 4);
			$data = substr($text, 14);
		}
		else {
			$masks = substr($text, 2, 4);
			$data = substr($text, 6);
		}
		$text = "";
		for ($i = 0; $i < strlen($data); ++$i) {
			$text .= $data[$i] ^ $masks[$i%4];
		}
		return $text;
	}

	/*
	 * description: translates messages to be sent to users from the server(TCP protocol)
	 * @params:
	 *		$text: (string) text to be translated
	 * @return: $text: (string) translated text
	 */
	private function mask($text)
	{
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($text);
		
		if($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);
		return $header.$text;
	}

	/*
	 * description: disconnects socket and removes it from server
	 * @params:
	 *		$index: (integer)index of socket (in $connections array) to be removed 
	 *		&$connections: (array reference)array with server connections
	 *		&$read: (array reference)array with changes(from socket_select())
	 *
	 *		$socket: (socket resource)single socket not yet registered in server to be disconnected
	 * @return: -
	 */
	private function disconnect($index,&$connections=null,&$read=null) {

		if(func_num_args() === 1) {
			socket_close($index);
			return;
		}

		socket_close($connections[$index]->getSocket());
		unset($connections[$index]);
		unset($read[$index+1]);
	}

	/*
	 * description: performs handshake between user and server. Basically receives message from user and decides whether it passes requirements to establish connection properly
	 * @params:
	 *		$client: (socket resource)socket asking for handshake
	 *		$headers: (string)text sent by user
	 *		$socket: (socket resource)master socket
	 * @return: bool:
 	 *				true: handshake success
 	 *				false: handshake failure
	 */
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
		$message = $this->unmask($message);
		echo "[". date('Y-m-d H:i:s') ."][$author]: $message\n" ;
	}

	/*
	 * description: sends a message to user encoded in json
	 * @params:
	 *		$socket: (socket resource)user for which the message is
	 *		$message: (string)message to be sent
	 *		$author: (string)name of the author of the message[DEFAULT='SERVER']
	 *		$unmask: (boolean)set to true only if it comes directly from another user[DEFAULT=false]
	 * @return: -
	 */
	private function sendMessage($socket,$message,$author='SERVER',$unmask=false) {
		if( $unmask ) {
			$message = $this->unmask($message);
		}
		$message = array(	"date"=>date("Y-m-d H:i:s"),
							"author"=>$author,
							"message"=>$message);
		$message = json_encode($message);
		$message = $this->mask($message);
		socket_write($socket,$message,strlen($message));
	}

	/*
	 * description: sends a message to all connected users. Leave 2 last args empty if it's a message from server to users
	 * @params:
	 *		$connections: (array)array of current connections on server
	 *		$message: (string)message to be sent
	 *		$index: (integer)index of author in $connections array. Equals to 0 if they're not registered as connection[DEFAULT=0]
	 *		$themselves: (boolean)determines if message is supposed to be sent to the author as well[DEFAULT=false]
	 * @return: -
	 */
	private function sendMessageToAll($connections, $message, $index=0, $themselves=false) {
		for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
			if( isset($connections[$i]) && ($i !== $index || $themselves) ) {
				if( $index === 0 ) {
					$this->sendMessage(	$connections[$i]->getSocket(),
										$message);
					continue;
				}
				$this->sendMessage(	$connections[$i]->getSocket(),
									$message,
									$connections[$index]->getName(),
									true);
			}
		}
	}
}