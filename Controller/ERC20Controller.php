<?php

namespace Forjaweb\EthbridgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Forjaweb\EthbridgeBundle\Controller\ServiceBaseController;
use Forjaweb\EthbridgeBundle\Request\RpcRequest;

class ERC20Controller extends ServiceBaseController
{	
	// Smart contract address for the token
	private $sc_address = '';
	
	public function contract($address) {
		$this->sc_address = $address;
		return $this;
	}
	
	// Balance of an address
	// https://theethereum.wiki/w/index.php/ERC20_Token_Standard#Token_Balance
    public function balanceOf($address)
    {
		// Remove 0x from address
		if(strlen($address) == 42) {
			$address = substr($address, 2);
		}

		// Params
		$c = new stdClass();
		$c->from = '0x7ee760b17ce5cd95b9752f261bfb9b91c2babdba';
		$c->to   = $this->sc_address;
		$c->data = '0x70a08231000000000000000000000000' . $address;
				
		$params = [$c, "latest"];
		
		// Perform request (request->request->... ugly!)
		$data = $this->request->request('eth_call', $params);
				
		return $data;
    }
    
    // Convert HEX to WEI
	public function hexToWei($q) {
		return $this->get('fw.eth')->decode_hex($q);
	}
	
	// Convert HEX to ETH
	public function hexToEth($q) {
		return $this->get('fw.eth')->decode_hex($q) / 10000000000000000000;
	}
	
	// Convert HEX to WEI
	public function weiToHex($q) {
		return '0x' . dechex($q);
	}
}
