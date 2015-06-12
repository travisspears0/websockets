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
	 *		$...
	 * @return: (bool)
	 */
	public function connect();

	/*
	 * description: disconnects a socket from the server
	 * @params: 
	 *		$...
	 * @return: (bool)
	 */
	public function disconnect();

	/*
	 * description: function called each time a message is received from one of the sockets
	 * @params: -
	 * @return: -
	 */
	public function onMessage();

	/*
	 * description: sends message to socket(s)
	 * @params: 
	 *		$...
	 * @return: -
	 */
	public function sendMessage();

}