<?php

namespace Forjaweb\EthbridgeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Forjaweb\EthbridgeBundle\Controller\ServiceBaseController;
use Forjaweb\EthbridgeBundle\Request\RpcRequest;

/**
 * -------------------
 * General smart contract controller
 * -------------------
 * */
class ContractController extends ServiceBaseController
{   
    // Smart contract address for the token
    private $sc_address = '';
    
    // Contract ABI
    private $abi = '';
    
    // Contract functions helper
    private $func = array();
    
    
    private $sizeof = array(
        'uint8'   => 1,
        'uint16'  => 2,
        'uint24'  => 3,
        'uint32'  => 4,
        'uint40'  => 5,
        'uint48'  => 6,
        'uint56'  => 7,
        'uint64'  => 8,
        'uint72'  => 9,
        'uint80'  => 10,
        'uint88'  => 11,
        'uint96'  => 12,
        'uint104' => 13,
        'uint112' => 14,
        'uint120' => 15,
        'uint128' => 16,
        'uint136' => 17,
        'uint144' => 18,
        'uint152' => 19,
        'uint160' => 20,
        'uint168' => 21,
        'uint176' => 22,
        'uint184' => 23,
        'uint192' => 24,
        'uint200' => 25,
        'uint208' => 26,
        'uint216' => 27,
        'uint224' => 28,
        'uint232' => 29,
        'uint240' => 30,
        'uint248' => 31,
        'uint256' => 32,
        'address' => 8,
        'uint'    => 32,
        'int'     => 32,
        'bool'    => 32,
        'string'  => 0,
        'bytes8'  => 8,
        'bytes16' => 16,
        'bytes24' => 24,
        'bytes32' => 32,
        'bytes'   => 0,
    );
    
    private $args   = array();
    private $inputs = array();
    
    private $header = '';
    private $data   = '';
    
    // Set the contract address before calling any other function
    // Ex: $this->get('fw.erc20')->contract('0x...')->function('...');
    public function contract($address) {
        $this->sc_address = $address;
        return $this;
    }
    
    public function setAbi($abi) {
        // Convert from JSON
        if(gettype($abi) === 'string')
            $abi = json_decode($abi);
    
        // Prepare ABi //
        // 1 Get each function codification
        // 2 Some internal structures for 'easy' access
        foreach($abi as $func) {
            // Full name of the function, without parameter names
            $full = $func->name . '(';
            foreach($func->inputs as $input) {
                $this->_sanitizeInput($input);
                $full .= $input->type . ',';
            }
            $full = substr($full, 0, strlen($full)-1);
            $full .= ')';
            
            // Hash table using func name as key
            // Pre generated function bytes for contract call
            $this->func[$func->name]['k256'] = '0x' . substr(\kornrunner\Keccak::hash($full, 256), 0, 8);
            
            // Rest of function information in Obj format
            $this->func[$func->name]['obj']  = $func;
        }
        
        // Save the ABI
        $this->abi = $abi;
        return $this;
    }
     
    /** 
     * Calls a function
     * You must set the ABI before calling this function
     **/
    public function func($func, $args = [])
    {
        // Start with first 4 bytes of keccak-256 codification of function name and parameters
        $data = $this->func[$func]['k256'];
        $obj  = $this->func[$func]['obj'];
        
        // TODO: maybe return an error object instead?
        // Check if number of params is right
        if(count($args) != count($obj->inputs)) {
            throw new \Exception();
        }
        
        // Next to the function name, the parameters data
        $data .= $this->processParameters($obj, $args);
        
        // Store complete data
        $this->data = $data;
        
        // Lets call the smart contract
        // The 'constant' parameter tells us if we can call the node or we need to make a full 
        // transaction to the network
        if($obj->constant)
            return $this->callNode();
        else
            return $this->txNode();
            
    }
    
    /**
     * This function will call a node internally, without creating
     * a transaction in the Ethereum node
     * */
    public function callNode()
    {
        // Params
        $c = new \stdClass();
        
        // Address to send, data and value
        $c->to   = $this->sc_address;
        $c->data = $this->data;
        $c->value = '0x0';
                
        $params = [$c, "latest"];
        
        // Perform request (request->request->... ugly!)
        $data = $this->request->request('eth_call', $params);

        return $data;
    }
    
    /**
     * This function creates a transaction and publishes it in the
     * Ethereum network
     * */
    public function txNode()
    {
        // Params
        $c = new \stdClass();
        
        // TODO: change From address...
        $c->from = '0xAeE0050f244fCc1Ef2F87586d2Ab8f4D053E35AD';
        $c->to   = $this->sc_address;
        $c->data = $this->data;
        $c->value = '0x0';
                
        $params = [$c];
        
        // Perform request (request->request->... ugly!)
        $data = $this->request->request('eth_sendTransaction', $params);

        return $data;
    }
    
    /**
     * Process Parameters to be sent to the contract
     * Official documentation from Ethereum:
     * https://github.com/ethereum/wiki/wiki/Ethereum-Contract-ABI#function-selector-and-argument-encoding
     * The process is done in 3 steps:
     * * Pre-process
     * * Pass 1 -> generate the content
     * * Pass 2 -> generate the header
     * TODO: This is a ugly function, with too much 'test and error' that needs a complete re-build
     * TODO: Need coherence within $input, $this->args and $this->inputs. Should use one $this->args or 
     * similar to unify all this information
     * */
    private function processParameters($func_obj, $args) {
        
        $ret  = '';
        $post = array();
        $total = 0;
        
        // Dynamic variables will use a Head->Content structure
        // We are using 2 passes
        // First, we need to 'render' de content and calculate the size of each parameter
        
        // Pass 1: Calculate total size and content codification
        foreach($func_obj->inputs as $input) {
            $name = $input->name;
            $type = $input->type;
            
            // Is the parameter an Array?
            // TODO: move to a new private function
            $aux = explode('[', $input->type);
            $type = $aux[0];
            $input->isArray = false;
            if(count($aux) > 1) {
                $input->isArray = true;
                if($aux[1] == ']')
                    $input->isArrayDynamic = true;
                else
                    $input->isArrayDynamic = false;
            }
            
            // Support for Dynamic Arrays
            // TODO: add support for static arrays
            if($input->isArray) {
                if($input->isArrayDynamic) {
                    $c = count($args[$name]);
                    $this->args[$name] = array();
                    $this->args[$name]['content'] = '';
                    $this->args[$name]['size']    = 0;
                    
                    for($i = 0; $i < $c; $i++) {
                        $aux = $this->getArgumentContent($type, $args[$name][$i]);
                        
                        $this->args[$name]['content'] .= $aux['content'];
                        $this->args[$name]['size']    += $aux['size'];
                        $this->args[$name]['type']     = $aux['type'];
                    }
                }
                else {
                    // Static arrays processed here...
                }
            }
            else {
                // Not an array
                $this->args[$name] = $this->getArgumentContent($type, $args[$name]);
                // TODO: Size / 2... seriously ?
                $this->args[$name]['size'] /= 2;
            }
            
            // When dynamic content, we need to provide first the size
            // TODO: use something like if($input->isDynamic)
            if($input->isArray || $this->sizeof[$this->args[$name]['type']] == 0) {
                $this->data .= str_pad(dechex($this->args[$name]['size']), 64, '0', STR_PAD_LEFT) . $this->args[$name]['content'];
            }
            
            // TODO: Unify this information, see the function comments
            $this->inputs[] = $input;
        }
        
        // Pass 2: Generate the header
        $head = '';
        $c    = count($func_obj->inputs);
        for($i = 1; $i <= $c; $i++) {
            $input = $this->inputs[$i-1];
            $head .= $this->getArgumentHeader($input, $i);
        }
        
        // Return header + content
        return $head . $this->data;
    }
    
    /**
     * Calculates the header
     * This is used for dynamic variables, and this function
     * will print the location of the content (in bytes)
     * TODO: I think that $salt is not calculated correctly and may cause error in some cases
     * test it with several smart contracts
     * */
    private function getArgumentHeader($input, $pos) {
        $name = $input->name;
        $arg  = $this->args[$name];
        
        // Calculate header
        
        // First, non-dynamic parameters
        // TODO: change this for something like if(!$input->isDynamic)
        if(!$input->isArray && $this->sizeof[$arg['type']] > 0) {
            return $arg['content'];
        }
        
        // If Dynamic, add content to the top
        $c    = count($this->args);
        
        // First, add the size of the header from current position
        $salt = ($c - $pos + 1) * 32;
        
        // Then add the size of each argument prior to this one
        for($i = 0; $i < $pos - 1; $i++) {
            $salt += strlen($this->args[$this->inputs[$i]->name]['content']);
        }
        
        // Ok, return it, padded
        return(str_pad(dechex($salt), 64, '0', STR_PAD_LEFT));
    }

    /**
     * Returns the content of an argument, codified, padded, etc
     * https://github.com/ethereum/wiki/wiki/Ethereum-Contract-ABI#function-selector-and-argument-encoding
     * */
    private function getArgumentContent($type, $arg) {
        $content = '';
        $size    = '';
        $ret     = array();
        
        // Convert to Hex if needed
        // TODO: maybe we can use something like $arg->isHex or $input->isHex
        // and generate isHex value in the 'processParameters' step
        if($type != 'string' && $type != 'address' && substr($type, 0, 5) != 'bytes') {
            $content = dechex($arg);
        }
        else {
            if($type == 'string')
                $content = bin2hex($arg);
            else
                $content = $arg;
        }
        
        // Calculate size
        $size = strlen($content);
        
        // TODO: not sure about this
        if($type == 'bool') $size = 2;
        
        // Pad zeroes
        // TODO: here we are assuming bytes and string content always <= 32 bytes
        // Add support to true dynamic strings and bytes
        if($type == 'string' || substr($type, 0, 5) == 'bytes') {
            $ret['content'] = str_pad($content, 64, '0', STR_PAD_RIGHT);
        }
        else {
            $ret['content'] = str_pad($content, 64, '0', STR_PAD_LEFT);
        }
        
        // Return data
        $ret['size'] = $size;
        $ret['type'] = $type;
        
        return $ret;
    }
    
    /**
     * Function to test if a content is in Hex format
     * */
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
    
    /** 
     * Some pre-processor for input types
     * TODO: change function name, add more types
     * */
    private function _sanitizeInput(&$input) {
        switch($input->type) {
            case 'uint':
                $input->type = 'uint256';
            break;
            case 'uint[]':
                $input->type = 'uint256[]';
            break;
        }
    }
}
