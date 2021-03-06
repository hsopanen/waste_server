<?php
require_once('Commons.php');

class API {

	function __construct($root, $handler) {
		$this->init();
		$this->handler = $handler;
		$this->root = $root;
	}
	
	#
	# Private functions.
	#
	
	private function init() {
		$this->startTime = microtime(true);
	}
	
	private function toJSON($data, $state) {
		$response = array(
			'method' => $this->method,
			'call' => $this->call,
			'state' => $state,
			'message' => Commons::getStateMessage($state),
			'response' => $data,
			'server_time' => round(($this->endTime-$this->startTime)*1000, 2)
		);
		if (property_exists($this, 'sid')) $response['sid'] = $this->sid;
		$this->handler->log($response);
		return Commons::getParam('callback', null, null) != null ? Commons::getParam('callback', null, null)."(".json_encode($response).")" : json_encode($response);
	}
	
	#
	# API core.
	#
	
	function process() {
		$this->prepare();
		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: *");
		header("Content-Type: application/json");
		$response = NULL;
		$state = 200;
		if ($this->call != 'register' && !$this->handler->authenticate($response, $this)) {
			$state = 401;
		} else if (!method_exists($this->handler, $this->call)) {
			$state = 405;
			$response = array("error" => "Invalid method called");
		} else {
			$response = $this->handler->{$this->call}($state, $this);
		}
		$this->endTime = microtime(true);
		echo $this->toJSON($response, $state);
	}
	
	private function prepare() {
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->uri = urldecode($_SERVER['REQUEST_URI']);
		$uri_parts = explode('?', $this->uri, 2);
		$this->path = str_replace($this->root, "", $uri_parts[0]);

		$this->path_parts = explode('/', $this->path);
		$this->call = Commons::getParam('method', null, null) ? Commons::getParam('method', null, null) : $this->path_parts[1];
	}
	
}

?>
