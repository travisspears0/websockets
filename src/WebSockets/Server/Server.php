<?php

namespace WebSockets\Server;

class Server {

	static function run() {
		Server::write("Server is running...");
	}

	static function write($message,$author="SERVER") {
		echo "[$author]: $message\n" ;
	}

}