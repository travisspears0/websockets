<?php

	require_once __DIR__ . "/vendor/autoload.php" ;

	use WebSockets\Server\Server;

	$server = Server::run();
