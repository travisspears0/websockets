<?php

	require_once __DIR__ . "/vendor/autoload.php" ;

	use WebSockets\Http\Server;

	set_time_limit(0);

	class ServerImplementation extends Server {

		/*
		 * description: function called after message was received from one of the users
		 * @params: -
		 * @return: -
		 */
		public function onMessage($message,$connection) {
			$this->sendMessage($message,$connection->getParam('name'));
		}

		/*
		 * description: function called after user connected and handshaked successfuly
		 * @params: -
		 * @return: -
		 */
		public function onConnect($connection) {
			$users = array();
			$index = -1;
			for( $i=0 ; $i<$this->maxConnections ; ++$i ) {
				if( isset($this->connections[$i]) ) {
					if( $this->connections[$i]->getId() == $connection->getId() ) {
						$index = $i;
					}
					$users[] = array(	"id"=>$this->connections[$i]->getId(),
										"name"=>$this->connections[$i]->getParam('name'));
				}
			}
			//send list of users to newly connected user
			$this->sendMessage($users,SERVER,ONE_USER,$index,LIST_OF_USERS);
			//send ifno about new user to the rest of users
			$data = array(	"id"=>$connection->getId(),
							"name"=>$connection->getParam('name'));
	    	$this->sendMessage($data,SERVER,ALL_BUT_ONE,$index,USER_CONNECTED);
		}

		/*
		 * description: function called after user disconnected
		 * @params: -
		 * @return: -
		 */
		public function onDisconnect($connection) {
			$data = array("id"=>$connection->getId());
			$this->sendMessage($data,SERVER,ALL_BUT_ONE,$connection->getId(),USER_DISCONNECTED);
		}

	}

	(new ServerImplementation())->run();
