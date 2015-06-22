<?php
	
namespace WebSockets\Interfaces;

interface ServerInterface {

	/*
	 * description: function called after message was received from one of the users
	 * @params: -
	 * @return: -
	 */
	public function onMessage();

	/*
	 * description: function called after new user connected
	 * @params: -
	 * @return: -
	 */
	public function onConnect();

	/*
	 * description: function called after user handshaked successfuly
	 * @params: -
	 * @return: -
	 */
	public function onHandshake();

	/*
	 * description: function called after user disconnected
	 * @params: -
	 * @return: -
	 */
	public function onDisconnect();

}