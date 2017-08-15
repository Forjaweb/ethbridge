<?php

namespace Forjaweb\EthbridgeBundle\Request;

use Forjaweb\EthbridgeBundle\Request\RequestInterface;

class RpcRequest implements RequestInterface
{
	protected $response;
	protected $request;
	private   $id = 1;
	
	// URL of rpc server
	// TODO: set this in config parameter
	private $url = 'http://127.0.0.1:8000';
	
	// Performs a RPC request using function name and arguments as array
    public function request($function, $args) {
		$this->request = '{"jsonrpc":"2.0","method":"'. $function .'", "params": ' . json_encode($args) . ', "id": '. $this->id .'}';
		
		// RPC request
        $ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->request);

		// Get response and close connection
		$this->response = curl_exec($ch);
		curl_close($ch);
		
		$this->id++;
		
		return json_decode($this->response);
	}
	
	public function getRequest() {
		return $this->request;
	}
}
