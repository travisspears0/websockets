<?php

	require_once __DIR__ . "/vendor/autoload.php" ;

	use WebSockets\Server\Server;

	set_time_limit(0);

	(new Server())->run();
