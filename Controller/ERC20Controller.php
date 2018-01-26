<?php

namespace Forjaweb\EthbridgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Forjaweb\EthbridgeBundle\Controller\ServiceBaseController;
use Forjaweb\EthbridgeBundle\Request\RpcRequest;

/**
 * -------------------
 * ERC 20 standard bridge from Symfony/php to JSON RPC ethereum node
 * Currently this file is for testing, not for real use.
 * No documentation and no support.
 * -------------------
 * ERC 20 standard defined here:
 * https://theethereum.wiki/w/index.php/ERC20_Token_Standard
 * 
 * JSON RPC documentation:
 * https://github.com/ethereum/wiki/wiki/JSON-RPC
 * 
 * Solidity data types:
 * http://solidity.readthedocs.io/en/develop/types.html
 * */
class ERC20Controller extends ServiceBaseController
{	
	// Smart contract address for the token
	private $sc_address = '';
	
	// Set the contract address before calling any other function
	// Ex: $this->get('fw.erc20')->contract('0x...')->balanceOf('0x...');
	public function contract($address) {
		$this->sc_address = $address;
		return $this;
	}
	
	/** 
	 * Balance of an address
	 * https://theethereum.wiki/w/index.php/ERC20_Token_Standard#Token_Balance
	 * 
	 * Receives an address in hex format and returns the result of a call to balanceOf(address)
	 * Example usage:
	 *     $erc20 = $this->get('fw.erc20')->contract($contract_address);
	 * 	   $balance = $erc20->balanceOf($address);
	 *     print_r($erc20->hexToEth($balance->result));
	 **/
    public function balanceOf($address)
    {
		// Remove 0x from address
		if(strlen($address) == 42 && substr($address, 0, 2) == '0x') {
			$address = substr($address, 2);
		}

		// Params
		$c = new \stdClass();
		$c->from = '0x7ee760b17ce5cd95b9752f261bfb9b91c2babdba';
		$c->to   = $this->sc_address;
		$c->data = '0x70a08231000000000000000000000000' . $address;
				
		$params = [$c, "latest"];
		
		// Perform request (request->request->... ugly!)
		$data = $this->request->request('eth_call', $params);
				
		return $data;
    }
    
    // Transfer of an address
    // transfer(address,uint) => SHA3 = 0x6cb927d8
	// https://theethereum.wiki/w/index.php/ERC20_Token_Standard#Transfer_Token_Balance
    public function transfer($from, $to, $amount, $rawamount = false)
    {				
		// Remove 0x from address
		if(strlen($from) == 42 && substr($from, 0, 2) == '0x') {
			// Use testHex to check for hex format
			$short_from = substr($this->testHex($from), 2);
		}
		
		// Remove 0x to address
		if(strlen($to) == 42 && substr($to, 0, 2) == '0x') {
			// Use testHex to check for hex format
			$short_to = substr($this->testHex($to), 2);
		}
		
		// If rawamount is true, it means that the amount comes in hex format, 32 bytes long
		// and can be used directly. Otherwise, you need to convert from uint32 to hex32, fill with 0 on left
		if(!$rawformat) {
			// Decimal to hex
			$hex = dechex($amount);
			
			// Add 0 to the left
			$l = strlen($hex);
			$z = '';
			// TODO: something more efficient
			for($l; $l < 64; $l++)
				$z .= '0';
			$amount = $z . $hex;
		}
		

		// Params
		$c = new \stdClass();
		$c->from = $from;
		$c->to   = $this->sc_address;
		$c->data = '0x70a08231000000000000000000000000' . $short_to . $amount;
		
		$params = [$c, "latest"];
		
		// Perform request
		$data = $this->request->request('eth_call', $params);
				
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
	
	private function testHex($input) {
		// Remove the 0x
		if(substr($input, 0, 2) == '0x')
			$hex = substr($input, 2);
		else
			$hex = $input;
		
		// Perform test
		if(preg_match('/[a-f0-9]+/', $hex))
			return $input;
		else {
			// TODO: not sure about the best option for value to return here...
			return '0';
		}
	}
}
