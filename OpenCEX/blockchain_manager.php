<?php
require_once("SafeMath.php");
require_once($GLOBALS["OpenCEX_common_impl"]);

interface OpenCEX_BlockchainRequestConsumer{
	public function consume_object($object, bool $inject = true);
}

//The full blockchain manager throws when requests are
//appended to it, similarly to the /dev/full pseudo-file in linux
final class OpenCEX_FullBlockchainManager implements OpenCEX_BlockchainRequestConsumer{
	public function consume_object($object, bool $inject = true){
		throw new OpenCEX_assert_exception("Request sent to full blockchain manager!");
	}
}

//The null blockchain manager discards all requests that are
//appended to it, similarly to the /dev/null pseudo-file in linux
final class OpenCEX_NullBlockchainManager implements OpenCEX_BlockchainRequestConsumer{
	public function consume_object($object, bool $inject = true){
		
	}
}

final class OpenCEX_BatchRequestManager implements OpenCEX_BlockchainRequestConsumer{
	private $requests = [];
	private int $id = 0;
	private readonly OpenCEX_safety_checker $ctx;
	function __construct(OpenCEX_safety_checker $ctx){
		$ctx->usegas(1);
		$this->ctx = $ctx;
	}
	public function consume_object($object, bool $inject = true){
		$this->ctx->usegas(1);
		if($inject){
			$object["jsonrpc"] = "2.0";
			$object["id"] = strval($this->id++);
		}
		
		$this->ctx->check_safety(array_key_exists("method", $object), "Missing request method in batch request!");
		array_push($this->requests, $object);
		return null;
	}
	public function execute(OpenCEX_BlockchainRequestConsumer $blockchain_manager){
		$this->ctx->usegas(1);
		$returns = $blockchain_manager->consume_object($this->requests, false);
		foreach($returns as $miniret){
			$this->ctx->check_safety(array_key_exists("id", $miniret), "Response message id missing!");
		}
		usort($returns, function($x, $y){
			return $x["id"] - $y["id"];
		});
		$this->ctx->check_safety(is_array($returns), "Batch request must return array!");
		$count2 = count($this->requests);
		$this->ctx->check_safety($count2 == count($returns), "Batch return array length must be equal to request array length!");
		$formatted_returns = [];
		$request_checker = new OpenCEX_BlockchainManagerWrapper($this->ctx, new OpenCEX_FullBlockchainManager());
		for($i = 0; $i < $count2; $i++){
			array_push($formatted_returns, $request_checker->validateRequestReturns($this->requests[$i]["method"], $returns[$i]));
		}
		$this->requests = [];
		$this->id = 0;
		return $formatted_returns;
	}
}

final class OpenCEX_BlockchainManager implements OpenCEX_BlockchainRequestConsumer{
	public readonly OpenCEX_safety_checker $ctx;
	public readonly int $chainid;
	public readonly string $rpc_url;
	function __construct(OpenCEX_safety_checker $ctx, int $chainid, string $rpc_url){
		$ctx->usegas(1);
		$this->ctx = $ctx;
		$this->chainid = $chainid;
		$this->rpc_url = $rpc_url;
	}
	
	private function post(string $content){
		$this->ctx->usegas(1);
		$header = array(
			"Content-Type: application/json",
			"Content-Length: ". strlen($content)
		);
		$options = array(
			'http' => array(
				'method' => 'POST',
				'content' => $content,
				'header' => implode("\r\n", $header)
			)
		);
		$ret = file_get_contents($this->rpc_url, false, stream_context_create($options));
		$this->ctx->check_safety_2($ret === false, "Node request failed!");
		$ret = json_decode($ret, true);
		$this->ctx->check_safety_2(is_null($ret), "Node returned invalid response!");
		return $ret;
	}
	
	public function consume_object($object, bool $inject = true){
		$this->ctx->usegas(100);
		if($inject){
			$object["jsonrpc"] = "2.0";
			$object["id"] = "0";
		}
		return $this->post(json_encode($object));
	}
}

final class OpenCEX_BlockchainManagerWrapper{
	private readonly OpenCEX_BlockchainRequestConsumer $output;
	private readonly OpenCEX_safety_checker $ctx;
	private bool $invalid = false;
	
	function __construct(OpenCEX_safety_checker $ctx, OpenCEX_BlockchainRequestConsumer $output){
		$ctx->usegas(1);
		$this->ctx = $ctx;
		$this->output = $output;
	}
	
	//Since the ability to send blockchain transactions is a sensitive scope
	//we generate blockchain access tokens and invalidate them when we are
	//done with them.
	public function invalidate(){
		$this->ctx->usegas(1);
		$this->invalid = true;
	}
	
	public function validateRequestReturns(string $method, $return){
		$this->ctx->usegas(1);
		$this->ctx->check_safety_2($this->invalid, "Blockchain Manager Wrapper already destroyed!");
		if(array_key_exists("error", $return)){
			$this->ctx->check_safety(array_key_exists("message", $return["error"]), "Unknown wallet error!");
			$this->ctx->die2(implode(["Wallet error: ", $return["error"]["message"], "!"]));
		}
		switch($method){
			case "eth_sendRawTransaction":
				return $this->validateRPC($return, 0);
			case "net_version":
				return $this->validateRPC($return, 1);
			case "eth_call":
				return $this->validateRPC($return, 5);
			case "eth_estimateGas":
			case "eth_gasPrice":
			case "eth_getBalance":
			case "eth_getTransactionCount":
				return $this->validateRPC($return, 3);
			default:
				$this->ctx->die2("Unsupported request method: " . $method . "!");
				return;
		}
	}
	
	public function validateRPC($obj, int $expected_return_type){
		$this->ctx->usegas(1);
		$this->ctx->check_safety_2($this->invalid, "Blockchain Manager Wrapper already destroyed!");
		if(!is_null($obj)){
			$this->ctx->check_safety(is_array($obj), "RPC request must return array!");
			$this->ctx->check_safety(array_key_exists("jsonrpc", $obj), "JSON RPC header missing from message!");
			$this->ctx->check_safety($obj["jsonrpc"] == "2.0", "JSON RPC version must be v2.0!");
			$this->ctx->check_safety(array_key_exists("result", $obj), "Result missing from message!");
			
			$obj = $obj["result"];
			switch($expected_return_type){
				case 0:
					//string
					$this->ctx->check_safety(is_string($obj), "Return type must be string!");
					return $obj;
				case 1:
					//int
					if(is_string($obj)){
						return intval($obj, 0);
					} else{
						$this->ctx->check_safety(is_int($obj), "Return type must be string or int!");
						return $obj;
					}
				case 2:
					//boolean
					$this->ctx->check_safety(is_bool($obj), "Return type must be boolean!");
					return $obj;
				case 3:
					//SafeMath unsigned integer
					if(is_int($obj)){
						$obj = strval($obj);
					} else{
						$this->ctx->check_safety(is_string($obj), "Return type must be string or int!");
					}
					return OpenCEX_uint::init($this->ctx, $obj);
				case 4:
					//null
					$this->ctx->check_safety(is_null($obj), "Return type must be null!");
					return;
				case 5:
					//wild
					return $obj;
				default:
					$this->ctx->die2("Undefined return checking mode!");
					return;
					
			}
		}
		
	}
	
	public function net_version(){
		$this->ctx->usegas(1);
		$this->ctx->check_safety_2($this->invalid, "Blockchain Manager Wrapper already destroyed!");
		return $this->validateRPC($this->output->consume_object(["method" => "net_version","params" => []]), 1);
	}
	public function eth_call($transaction){
		$this->ctx->usegas(1);
		$this->ctx->check_safety_2($this->invalid, "Blockchain Manager Wrapper already destroyed!");
		return $this->validateRPC($this->output->consume_object(["method" => "eth_call","params" => [$transaction, "latest"]]), 5);
	}
	public function eth_sendRawTransaction(string $transaction){
		$this->ctx->usegas(1);
		$this->ctx->check_safety_2($this->invalid, "Blockchain Manager Wrapper already destroyed!");
		return $this->validateRPC($this->output->consume_object(["method" => "eth_sendRawTransaction","params" => [$transaction]]), 0);
	}
	public function eth_estimateGas($transaction){
		$this->ctx->usegas(1);
		$this->ctx->check_safety_2($this->invalid, "Blockchain Manager Wrapper already destroyed!");
		return $this->validateRPC($this->output->consume_object(["method" => "eth_estimateGas","params" => [$transaction, "latest"]]), 3);
	}
	public function eth_gasPrice(){
		$this->ctx->usegas(1);
		$this->ctx->check_safety_2($this->invalid, "Blockchain Manager Wrapper already destroyed!");
		return $this->validateRPC($this->output->consume_object(["method" => "eth_gasPrice","params" => []]), 3);
	}
	
	//NOTE: Address validity checking is performed by the late-created encoder.
	private ?OpenCEX_abi_encoder $late_created_encoder = null;
	private function x(string $addy, string $meth){
		$this->ctx->usegas(1);
		$this->ctx->check_safety_2($this->invalid, "Blockchain Manager Wrapper already destroyed!");
		if($this->late_created_encoder == null){
			$this->late_created_encoder = new OpenCEX_abi_encoder($this->ctx);
		}
		$this->late_created_encoder->chkvalidaddy($addy, false);
		return $this->validateRPC($this->output->consume_object(["method" => $meth, "params" => [$addy, "latest"]]), 3);
	}
	public function eth_getBalance(string $addy){
		return $this->x($addy, "eth_getBalance");
	}
	public function eth_getTransactionCount(string $addy){
		return $this->x($addy, "eth_getTransactionCount");
	}
	
}
?>