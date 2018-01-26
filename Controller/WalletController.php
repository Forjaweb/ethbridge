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
					$data->result = (int)$this->hexToWei($data->result);
				break;
				case 'eth':
					$data->result = (float)$this->hexToEth($data->result);
				break;
			}
		}
		//print_r($data);
		return $data;
	}
	
	// Convert HEX to WEI
	public function hexToWei($q) {
		return $this->get('fw.eth')->decode_hex($q);
	}
	
	// Convert HEX to ETH
	public function hexToEth($q) {
		return $this->get('fw.eth')->decode_hex($q) / 1000000000000000000;
	}
	
	// Convert HEX to WEI
	public function weiToHex($q) {
		return '0x' . dechex($q);
	}
	
	// Send a transaction from wallet to wallet
	// Value is used in WEI
	public function sendTransaction($from, $to, $value, $pass, $gas = 90000, $gasPrice = 1, $data = '') {
		
		$ret = $this->request->request('personal_unlockAccount', [$from, $pass]);
		
		$params = array(array(
		  "from" => $from,
		  "to" => $to,
		  "value" => $this->weiToHex($value), 
		  "gas" => $this->weiToHex($gas), 
		  "gasPrice" => $this->weiToHex($gasPrice), 
		  "data" => $data
		));
		
		//print_r($params);
		
		$data = $this->request->request('eth_sendTransaction', $params);
		
		return $data;
	}
	
	
	public function getReceipt($receipt) {
		$ret = $this->request->request('eth_getTransactionReceipt', [$receipt]);
		return $ret;
	}
	
	public function getTransaction($hash) {
		$ret = $this->request->request('eth_getTransactionByHash', [$hash]);
		return $ret;
	}
}
