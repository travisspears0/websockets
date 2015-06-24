<?php
	
namespace WebSockets\Interfaces;

interface ServerInterface {

	/*
	 * description: function called after message was received from one of the users
	 * @params: -
	 * @return: -
	 */
	public function onMessage($message,$connection);

	/*
	 * description: function called after new user connected
	 * @params: -
	 * @return: -
	 */
	public function onConnect($connection);

	/*
	 * description: function called after user disconnected
	 * @params: -
	 * @return: -
	 */
	public function onDisconnect($connection);

}