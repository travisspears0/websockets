<?php

namespace WebSockets\Interfaces;

interface ConnectionInterface {

	/*
	 * description: returns connection's id
	 * @params: -
	 * @return: connection's id
	 */
	public function getId();

	/*
	 * description: returns connection's socket resource
	 * @params: -
	 * @return: (socket resource)connection's socket resource
	 */
	public function getSocket();

	/*
	 * description: returns one of the connection's param
	 * @params: 
	 *		(string)$key - key of the param
	 * @return: (mixed)connection's param. Type isn't specified.
	 */
	public function getParam($key);

	/*
	 * description: sets one of the connection's param
	 * @params: 
	 *		(string)$key - key of the param
	 *		(mixed)$newValue - new value for the param
	 * @return: (boolean)
	 *		true - param exists and have been set
	 *		false - there's no such param
	 */
	public function setParam($key,$newValue);

}