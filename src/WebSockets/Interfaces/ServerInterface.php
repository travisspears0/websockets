<?php
	
namespace WebSockets\Interfaces;

interface ServerInterface {

	/*
	 * description: function called after message was received from one of the users
	 * @params:
	 *		$message (string)message text
	 *		$connection (socket resource)socket which is the author of the message
	 * @return: -
	 */
	public function onMessage($message,$connection);

	/*
	 * description: function called after new user connected
	 * @params:
	 *		$connection (socket resource)socket which just connected
	 * @return: -
	 */
	public function onConnect($connection);

	/*
	 * description: function called after user disconnected
	 * @params:
	 *		$connection (socket resource)socket which disconnected
	 * @return: -
	 */
	public function onDisconnect($connection);

}