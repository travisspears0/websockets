<?php

namespace WebSockets\Telnet;

use WebSockets\Interfaces\ServerInterface;

class Server implements ServerInterface {

	/*
	 * @description: 
 	 * @type: constant integer
	 */
	protected $maxConnections;

	/*
	 * @description: server's ip
 	 * @type: string
	 */
	protected $host;

	/*
	 * @description: port on which server will listen to connections
 	 * @type: string
	 */
	protected $port;

	/*
	 * @description: connections on the server
 	 * @type: array
	 */
	protected $connections;

	/*
	 * @description: connections which state changed(used in listening function socket_select())
 	 * @type: array
	 */
	protected $read;

	/*
	 * description: Server constructor, initializes crucial variables
	 * @params:
	 *		$host
	 *		$port
	 * @return: -
	 */
	public function __construct($host="127.0.0.1",$port="1234",$maxConnections=10) {

		define("SERVER","SERVER");
		/*
		 * Flags for to whom send a message
		 */
		define("ONE_USER","ONE_USER");
		define("ALL_USERS","ALL_USERS");
		define("SOME_USERS","SOME_USERS");
		define("ALL_BUT_ONE","ALL_BUT_ONE");

		/*
		 * Flags for types of messages to be sent to users
		 */
		define("MESSAGE","MESSAGE");
		define("USER_CONNECTED","USER_CONNECTED");
		define("USER_DISCONNECTED","USER_DISCONNECTED");
		define("LIST_OF_USERS","LIST_OF_USERS");

		$this->host = $host;
		$this->port = $port;
		$this->maxConnections = $maxConnections;

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

		if(!socket_listen ($socket , $this->maxConnections))
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
			for( $i=0 ; $i<$this->maxConnections ; ++$i ) {
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
		    if( !$this->onData() ) {
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
	private function onData() {
		for( $i=0 ; $i<$this->maxConnections ; ++$i ) {
			//catches changed socket
	    	if( isset($this->connections[$i]) && in_array($this->connections[$i]->getSocket(), $this->read) ) {
	    		$message="";
	    		//get the data
				socket_recv($this->connections[$i]->getSocket(), $message, 2*1024,MSG_DONTWAIT);
	    		//exit = disconnect
	    		if( preg_match('/!exit/', $message) && strlen($message) === 7 ) {
	    			$response = "User [" . $this->connections[$i]->getParam('name') . "] disconnected!";
					Server::write($response);
					$this->onDisconnect($this->connections[$i]);
	    			$this->disconnect($i);
					return true;
	    		}
				Server::write($message,$this->connections[$i]->getSocket());
				$this->onMessage($message,$this->connections[$i]);
				return true;
	    	}
	    }
	    return false;
	}

	/*
	 * description: disconnects socket and removes it from server
	 * @params:
	 *		$index: (mixed)
	 *			(integer)index of socket to be removed
	 *			(socket resource)single socket not yet registered in server to be disconnected
	 * @return: -
	 */
	private function disconnect($index) {

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
	 * @return: (integer)
	 *		>= 0 - successfuly connected
	 *		-1 - connection failure
	 */
	private function connect($socket) {
    	$connection = socket_accept($socket);
    	//reserving first empty slot in connections array
    	for( $i=0 ; $i<$this->maxConnections ; ++$i ) {
    		if( !isset($this->connections[$i]) ) {
    			$this->connections[$i] = new Connection($connection,$i);
//REMOVE BELOW!!!
    			$names = array('one','two','three','four','five','six','seven','eight','nine','ten');
//REMOVE ABOVE!!!
    			$this->connections[$i]->setParam('name',$names[$i]);
				Server::write("USER [" . $this->connections[$i]->getParam('name') . "] connected");
				$this->onConnect($this->connections[$i]);
    			return $i;
    		}
    	}
    	Server::write("New connection has not been accepted due to connections limit which has been reached!");
    	$this->disconnect($connection);
    	return -1;
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
	 * description: sends a message to user(s) encoded in json
	 * @params:
	 *		$message: (mixed)
	 *			(string)message to be sent
	 *			(array)if($type!==MESSAGE) it's an array with state for users to pass
	 *		$author: (string)name of the author of the message[DEFAULT='SERVER']
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
	protected function sendMessage($message,$author=SERVER,$sendTo=ALL_USERS,$dest=-1) {
		$date = date("Y-m-d H:i:s");
		$message = "[$date][$author] $message";
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
				for( $i=0 ; $i<$this->maxConnections ; ++$i ) {
					if( isset($this->connections[$i]) ) {
						$socket = $this->connections[$i]->getSocket();
						socket_write($socket,$message,strlen($message));
					}
				}
				break;
			}
			case ALL_BUT_ONE: {
				for( $i=0 ; $i<$this->maxConnections ; ++$i ) {
					if( isset($this->connections[$i]) && $i !== $dest ) {
						$socket = $this->connections[$i]->getSocket();
						socket_write($socket,$message,strlen($message));
					}
				}
				break;
			}
		}
	}

	/*
	 *
	 * INTERFACE METHODS
	 *
	 */

	/*
	 * description: function called after message was received from one of the users
	 * @params:
	 *		$message (string)message text
	 *		$connection (socket resource)socket which is the author of the message
	 * @return: -
	 */
	public function onMessage($message,$connection) {}

	/*
	 * description: function called after new user connected
	 * @params:
	 *		$connection (socket resource)socket which just connected
	 * @return: -
	 */
	public function onConnect($connection) {}

	/*
	 * description: function called after user disconnected
	 * @params:
	 *		$connection (socket resource)socket which disconnected
	 * @return: -
	 */
	public function onDisconnect($connection) {}

}