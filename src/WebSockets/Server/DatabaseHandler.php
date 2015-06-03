<?php

namespace WebSockets\Server;

use Symfony\Component\Yaml\Yaml;

class DatabaseHandler {

	private $host,
			$dbname,
			$username,
			$password;

	public function __construct($parametersFile) {
		if( !file_exists($parametersFile) ) {
			throw new \Exception("File does not exist!");
		}
		$parameters = Yaml::parse(file_get_contents($parametersFile));
		$this->host = $parameters["parameters"]["database_host"];
		$this->dbname = $parameters["parameters"]["database_name"];
		$this->username = $parameters["parameters"]["database_user"];
		$this->password = $parameters["parameters"]["database_password"];
		//echo "HOST: $this->host\nDBNAME: $this->dbname\nUSER: $this->username\nPASS: $this->password\n" ;
	}

	public function login($username,$password) {
		$password = hash('sha512',$password);
		$result = $this->executeQuery("SELECT COUNT(*) FROM `sockets`.`Users` WHERE `username`='$username' AND `password`='$password'");
		return ( (int)$result[0][0] === 1 ) ? true : false ;
	}

	private function executeQuery($query) {
		try {
            $db = new \PDO("mysql:host=$this->host;dbname=$this->dbname;charset=utf8", $this->username, $this->password, array(\PDO::ATTR_EMULATE_PREPARES => false, \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
            $res = $db->prepare($query);
            $res->execute();
            $res = $res->fetchAll();
            return $res;
        } catch(PDOException $ex) {
            die("There was an error with database query...") ;
        }
	}

}