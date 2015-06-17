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
	 *		$socket: (socket resource)master socket
	 * @return: (bool)
	 *		true - successfuly connected
	 *		false - connection failure
	 */
	public function connect($socket);

	/*
	 * description: disconnects socket and removes it from server
	 * @params:
	 *		$index: (mixed)
	 *			(integer)index of socket to be removed
	 *			(socket resource)single socket not yet registered in server to be disconnected
	 * @return: -
	 */
	public function disconnect($index);

	/*
	 * description: function called when a message from one of the sockets is received
	 * @params: -
	 * @return: (boolean) 
	 *				true - there was new message
	 *				false - there was no new messages
	 */
	public function onMessage();

	/*
	 * description: sends a message to user(s) encoded in json
	 * @params:
	 *		$message: (mixed)
	 *			(string)message to be sent
	 *			(socket resource)socket which might receive a message. In that case all further params don't matter
	 *		$author: (string)name of the author of the message[DEFAULT='SERVER']
	 *		$sendTo: (FLAG)
	 *			ONE_USER - send message only to one user
	 *			ALL_USERS - send message to all users
	 *			SOME_USERS - send message to multiple users
	 *			ALL_BUT_ONE - send message to all users except one
	 *		$dest: (mixed)
 	 *			$sendTo==ONE_USER: (integer)index in $connections array
 	 *			$sendTo==ALL_USERS: doesn't matter
 	 *			$sendTo==SOME_USERS: (array of integers)array of indexes in $connections array
 	 *			$sendTo==ALL_BUT_ONE: (integer)index in $connections array
	 * @return: -
	 */
	public function sendMessage($message,$author=SERVER,$sendTo=ALL_USERS,$dest=-1);

}