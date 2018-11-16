<?php

namespace Forjaweb\EthbridgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use \Forjaweb\EthbridgeBundle\Controller\WalletController;

class ServiceController extends Controller
{
  private $wallet;
  private $contract;
  
  private $address = '';
  private $pass    = '';
  
  public function __construct(WalletController $wallet, ContractController $contract) {
    $this->wallet   = $wallet;
    $this->contract = $contract;
  }
    
  public function createWallet($pass = null) {
    return $this->wallet->createWallet($pass);
  }
    
  public function checkWalletBalance($wallet, $format = null) {
    return $this->wallet->checkWalletBalance($wallet, $format);
  }
  
  public function hexToWei($q) {
    return $this->wallet->hexToWei($q);
  }
  
  public function hexToEth($q) {
    return $this->wallet->hexToEth($q);
  }
  
  public function sendTransaction($from, $to, $value, $pass, $gas = 90000, $gasPrice = 1, $data = '') {
    return $this->wallet->sendTransaction($from, $to, $value, $pass, $gas, $gasPrice, $data);
  }
  
  public function getReceipt($receipt) {
    return $this->wallet->getReceipt($receipt);
  }
  
  public function getTransaction($hash) {
    return $this->wallet->getTransaction($hash);
  }
  
  public function deployContract($data, $abi, $params) {
    $this->contract->setAddress($this->address);
    $this->contract->setPass($this->pass);
    if(substr($data, 0, 2) !== '0x')
      $data = '0x' . $data;
      
    return $this->contract->deployContract($data, $abi, $params);
  }
  
  public function callContractFunction($contract_address, $abi, $func, $args = []) {
    $this->contract->setAddress($this->address);
    $this->contract->setPass($this->pass);
    $this->contract->contract($contract_address);
    
    $this->contract->setAbi($abi);
    
    return $this->contract->func($func, $args);
  }
  
  public function checkReceipt($receipt) {
    return $this->contract->checkReceipt($receipt);
  }
  
  public function setAddress($address) {
    $this->address = $address;
  }
  
  public function setPass($pass) {
    $this->pass = $pass;
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
