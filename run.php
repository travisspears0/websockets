<?php

	require_once __DIR__ . "/vendor/autoload.php" ;

	use WebSockets\Telnet\Server;

	set_time_limit(0);

	(new Server())->run();
