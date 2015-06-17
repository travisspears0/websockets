<?php

	require_once __DIR__ . "/vendor/autoload.php" ;

	use WebSockets\Http\Server;

	set_time_limit(0);

	$server = new Server();
	$server->run();
