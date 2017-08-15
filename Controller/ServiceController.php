<?php

namespace Forjaweb\EthbridgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ServiceController extends Controller
{
	
    public function createWallet($pass = null)
    {
		return $this->container->get('fw.wallet')->createWallet($pass);
    }
    
    public function checkWalletBalance($wallet) {
		return $this->container->get('fw.wallet')->checkWalletBalance($wallet);
	}
	
	public function toWei($q) {
		return $this->container->get('fw.wallet')->toWei($q);
	}
	
	public function toEth($q) {
		return $this->container->get('fw.wallet')->toEth($q);
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
