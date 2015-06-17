<?php

namespace WebSockets\Http;

use WebSockets\Interfaces\ServerInterface;

class Server implements ServerInterface { 

	/*
	 * @description: 
 	 * @type: constant integer
	 */
	const MAX_CONNECTIONS = 1;

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
	 * @description: connections on the server
 	 * @type: array
	 */
	private $connections;

	/*
	 * @description: connections which state changed(used in listening function socket_select())
 	 * @type: array
	 */
	private $read;

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
		define("ALL_BUT_ONE","ALL_BUT_ONE");

		$this->host = $host;
		$this->port = $port;

		$this->connections = array();
		$this->read = array();

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

		while(true) {
			//reset $read array
			$this->read = array();
			//add master socket to $read
			$this->read[0] = $socket;
			//add all the connections to $read
			for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
				if( isset($this->connections[$i]) ) {
					$this->read[$i+1] = $this->connections[$i]->getSocket();
				}
			}
			//blocking function listening to changes on sockets and writing changes to $read array
			if(socket_select($this->read , $write , $except , null) === false)
		    {
		        $errorcode = socket_last_error();
		        $errormsg = socket_strerror($errorcode);
		     
		        throw new \Exception("Could not listen on socket : [$errorcode] $errormsg!");
		    }
		    //there is new message or, if there's not, there is a new connection
		    if( !$this->onMessage() ) {
		    	$this->connect($socket);
		    }
		}
	}

	/*
	 * description: function called when a message from one of the sockets is received
	 * @params: -
	 * @return: (boolean) 
	 *				true - there was new message
	 *				false - there was no new messages
	 */
	public function onMessage() {
		for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
	    	if( isset($this->connections[$i]) && in_array($this->connections[$i]->getSocket(), $this->read) ) {
	    		$message="";
	    		//read whole message
				/*while( socket_recv($connections[$i]->getSocket(), $out, 2*1024,MSG_DONTWAIT) > 0 ) {
					if( $out != null ) {
						$message .= $out; 
					}
				}*/
				socket_recv($this->connections[$i]->getSocket(), $message, 2*1024,MSG_DONTWAIT);
				//try handshake
	    		if( !($this->connections[$i]->isHandshaked()) ) {
	    			//if( $this->handshake($connections[$i]->getSocket(),$message) ) {
	    			if( $this->connections[$i]->handshake($message) ) {
		    			Server::write("User [". $this->connections[$i]->getSocket() ."] HANDSHAKED!");
		    		} else {
		    			Server::write("User [". $this->connections[$i]->getSocket() ."] couldn't perform Handshake! Disconnecting...");
	    				$this->disconnect($i);
		    		}
					return true;
	    		}
	    		$message = $this->unmask($message);
	    		//empty message = disconnect
	    		//message including only end of text(ascii = 3) = disconnect
	    		//(in fact first codition should be replaced/removed)
	    		if( strlen($message) === 0 || ord($message) === 3 ) {
	    			$response = "USER [" . $this->connections[$i]->getSocket() . "] disconnected!";
					Server::write($response);
					$this->sendMessage($response,SERVER,ALL_BUT_ONE,$i);
	    			$this->disconnect($i);
					return true;
	    		}
	    		//display user's message on server console
				$this->writeFromUser($message,$this->connections[$i]->getSocket());
				//send user message to all other users
				$this->sendMessage($message,$this->connections[$i]->getParam('name'),ALL_USERS,$i);
				//$this->sendMessageToAll($connections,$message,$i);//change
				return true;
	    	}
	    }
	    return false;
	}

	/*
	 * description: translates messages sent by users for the server(TCP protocol)
	 * @params:
	 *		$text: (string) text to be translated
	 * @return: (string) translated text
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
	 * @return: (string) translated text
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
	 *		$index: (mixed)
	 *			(integer)index of socket to be removed
	 *			(socket resource)single socket not yet registered in server to be disconnected
	 * @return: -
	 */
	public function disconnect($index) {

		if( gettype($index) === "integer" ) {
			socket_close($this->connections[$index]->getSocket());
			unset($this->connections[$index]);
			unset($this->read[$index+1]);
			return;
		}

		socket_close($index);
	}

	/*
	 * description: connects new socket to the server
	 * @params:
	 *		$socket: (socket resource)master socket
	 * @return: (bool)
	 *		true - successfuly connected
	 *		false - connection failure
	 */
	public function connect($socket) {
    	$connection = socket_accept($socket);
    	//reserving first empty slot in connections array
    	for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
    		if( !isset($this->connections[$i]) ) {
    			$this->connections[$i] = new Connection($connection);
    			$this->connections[$i]->setParam('name',substr(md5($i), 0, 5));
    			$response = "USER [" . $this->connections[$i]->getSocket() . "] connected";
				Server::write($response);
    			$this->sendMessage("new user connected",SERVER,ALL_BUT_ONE,$i);
    			return true;
    		}
    	}
    	Server::write("New connection has not been accepted due to connections limit which has been reached!");
    	$this->disconnect($connection);
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
	 *		$author: (mixed)
	 *			(string)name of the author of the message[DEFAULT='SERVER']
	 *		$sendTo: (mixed)
	 *			(FLAG)
	 *				ONE_USER - send message only to one user
	 *				ALL_USERS - send message to all users
	 *				SOME_USERS - send message to multiple users
	 *				ALL_BUT_ONE - send message to all users except one
	 *			(socket resource)socket which might receive a message. In that case $dest don't matter
	 *		$dest: (mixed)
 	 *			$sendTo==ONE_USER: (integer)index in $connections array
 	 *			$sendTo==ALL_USERS: doesn't matter
 	 *			$sendTo==SOME_USERS: (array of integers)array of indexes in $connections array
 	 *			$sendTo==ALL_BUT_ONE: (integer)index in $connections array
	 * @return: -
	 */
	public function sendMessage($message,$author=SERVER,$sendTo=ALL_USERS,$dest=-1) {
		$date = date("Y-m-d H:i:s");
		$message = array(	"date"=>$date,
							"author"=>$author,
							"message"=>$message);
		$message = json_encode($message);
		$message = $this->mask($message);

		if( gettype($sendTo) !== 'string' ) {
			socket_write($sendTo,$message,strlen($message));
			return;
		}
		
		switch ($sendTo) {
			case ONE_USER: {
				$socket = $this->connections[$dest]->getSocket();
				socket_write($socket,$message,strlen($message));
				break;
			}
			case SOME_USERS: {
				foreach ( $dest as $index ) {
					if( !isset($this->connections[$index]) ) {
						continue;
					}
					$socket = $this->connections[$index]->getSocket();
					socket_write($socket,$message,strlen($message));
				}
				break;
			}
			case ALL_USERS: {
				for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
					if( isset($this->connections[$i]) ) {
						$socket = $this->connections[$i]->getSocket();
						socket_write($socket,$message,strlen($message));
					}
				}
				break;
			}
			case ALL_BUT_ONE: {
				for( $i=0 ; $i<Server::MAX_CONNECTIONS ; ++$i ) {
					if( isset($this->connections[$i]) && $i !== $dest ) {
						$socket = $this->connections[$i]->getSocket();
						socket_write($socket,$message,strlen($message));
					}
				}
				break;
			}
		}
	}

}