<?php

	require_once __DIR__ . "/vendor/autoload.php" ;

	use WebSockets\Telnet\Server;

	set_time_limit(0);

	class ServerImplementation extends Server {

		/*
		 * description: function called after message was received from one of the users
		 * @params: -
		 * @return: -
		 */
		public function onMessage($message,$connection) {
			$this->sendMessage($message,$connection->getParam('name'),ALL_BUT_ONE,$connection->getId());
		}

		/*
		 * description: function called after user connected and handshaked successfuly
		 * @params: -
		 * @return: -
		 */
		public function onConnect($connection) {
			$this->sendMessage("Welcome to websockets chat! Type '!exit' to disconnect, have fun!\n",SERVER,ONE_USER,$connection->getId());
	    	$this->sendMessage("User [" . $connection->getParam('name') . "] connected!\n",SERVER,ALL_BUT_ONE,$connection->getId());
		}

		/*
		 * description: function called after user disconnected
		 * @params: -
		 * @return: -
		 */
		public function onDisconnect($connection) {
			$this->sendMessage("User [" . $connection->getParam('name') . "] disconnected!\n",SERVER,ALL_BUT_ONE,$connection->getId());
		}

	}

	(new ServerImplementation())->run();
