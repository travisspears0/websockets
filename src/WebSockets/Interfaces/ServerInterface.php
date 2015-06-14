<?php
	
namespace WebSockets\Interfaces;

interface ServerInterface {

	/*
	 * description: main function of the Server. Sets the infinite loop used to listen to connections.
	 * @params: -
	 * @return: -
	 */
	public function run();

	/*
	 * description: connects new socket to the server
	 * @params:
	 *		&$connections: (array reference)array with server connections
	 *		$socket: (socket resource)socket to be connected
	 * @return: (bool)
	 *		true - successfuly connected
	 *		false - connection failure
	 */
	public function connect(&$connections,$socket);

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
	public function disconnect($index,&$connections=null,&$read=null);

	/*
	 * description: function called when a message from one of the sockets is received
	 * @params:
	 *		$text: (string) text to be translated
	 * @return: (boolean) 
	 *				true - there was new message
	 *				false - there was no new messages
	 */
	public function onMessage(&$connections,&$read);

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
	public function sendMessage($message,$connections,$author=SERVER,$sendTo=ALL_USERS,$dest=-1);

}