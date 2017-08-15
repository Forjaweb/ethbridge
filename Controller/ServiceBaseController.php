<?php

namespace Forjaweb\EthbridgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Forjaweb\EthbridgeBundle\Request\RpcRequest;


class ServiceBaseController extends Controller
{
	protected $method  = 'rpc';
	protected $request = null;
	
	public function __construct() {
		if($this->method == 'rpc') {
			$this->request = new RpcRequest();
		}
	}
	
	public function getRequest() {
		return $this->request;
	}
}
