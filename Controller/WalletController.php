<?php

namespace Forjaweb\EthbridgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Forjaweb\EthbridgeBundle\Controller\ServiceBaseController;
use Forjaweb\EthbridgeBundle\Request\RpcRequest;

class WalletController extends ServiceBaseController
{	
	// Creates a new Wallet Address
    public function createWallet($pass = null)
    {	
		// Generate random password?
		if($pass == null) {
			$factory = new \RandomLib\Factory;
			$generator = $factory->getGenerator(new \SecurityLib\Strength(\SecurityLib\Strength::MEDIUM));
			$randomString = $generator->generateString(32);
			$pass = $randomString;
		}
		
		// Perform request (request->request->... ugly!)
		$data = $this->request->request('personal_newAccount', [$pass]);
		
		// Is node up?
		if(empty($data)) {
			throw new \Exception('Eth node is offline!');
		}
		else
		{
			// Check for errors
			if(isset($data->error)) {
				// TODO: some error check inside service?
				print_r('<br>Request: ' . $this->request->getRequest());
			}
		}
		
		// Return the pass
		$data->password = $pass;
		
		return $data;
    }
    
    // Obtain balance from any wallet
    // Receives wallet address and optionally format (wei, eth)
    public function checkWalletBalance($wallet, $format = null) {
		$data = $this->request->request('eth_getBalance', [$wallet, 'latest']);
		if(!isset($data->error)) {
			switch($format) {
				case 'wei':
					$data = $this->toWei($data->result);
				break;
				case 'eth':
					$data = $this->toEth($data->result);
				break;
			}
		}
		return $data;
	}
	
	// Convert HEX to WEI
	public function toWei($q) {
		return $this->get('fw.eth')->decode_hex($q);
	}
	
	// Convert HEX to ETH
	public function toEth($q) {
		return $this->get('fw.eth')->decode_hex($q) / 10000000000000000000;
	}
}
