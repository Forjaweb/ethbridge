<?php

namespace Forjaweb\EthbridgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ServiceController extends Controller
{
	
    public function createWallet($pass = null)
    {
		return $this->container->get('fw.wallet')->createWallet($pass);
    }
    
    public function checkWalletBalance($wallet, $format = null) {
		return $this->container->get('fw.wallet')->checkWalletBalance($wallet, $format);
	}
	
	public function hexToWei($q) {
		return $this->container->get('fw.wallet')->hexToWei($q);
	}
	
	public function hexToEth($q) {
		return $this->container->get('fw.wallet')->hexToEth($q);
	}
	
	public function sendTransaction($from, $to, $value, $pass, $gas = 90000, $gasPrice = 1, $data = '') {
		return $this->container->get('fw.wallet')->sendTransaction($from, $to, $value, $pass, $gas, $gasPrice, $data);
	}
	
	public function getReceipt($receipt) {
		return $this->container->get('fw.wallet')->getReceipt($receipt);
	}
	
	public function getTransaction($hash) {
		return $this->container->get('fw.wallet')->getTransaction($hash);
	}
	
	// Convert ETH 0x.. format to plain HEX
	// This function was taken from https://github.com/btelle/ethereum-php/blob/master/ethereum.php
	// @author btelle
	public function decode_hex($input)
	{
		// Remove the 0x
		if(substr($input, 0, 2) == '0x')
			$input = substr($input, 2);
		
		// Check hex format
		if(preg_match('/[a-f0-9]+/', $input))
			return hexdec($input);
			
		return $input;
	}
}
