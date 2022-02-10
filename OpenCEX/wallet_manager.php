<?php
require_once("signer/autoload.php");
require_once("abi_encoder.php");
require_once($GLOBALS["OpenCEX_common_impl"]);
require_once("SafeMath.php");
require_once("tokens.php");
require_once("blockchain_manager.php");
use kornrunner\Ethereum\Address as OpenCEX_Ethereum_Address;
use kornrunner\Ethereum\Transaction as OpenCEX_Ethereum_Transaction;

final class OpenCEX_WalletManager{
	private readonly string $private_key;
	public readonly string $address;
	private readonly string $chke20balance;
	private readonly OpenCEX_abi_encoder $encoder;
	private readonly OpenCEX_safety_checker $ctx;
	private readonly OpenCEX_BlockchainManagerWrapper $blockchain_manager;
	
	function __construct(OpenCEX_safety_checker $ctx, OpenCEX_BlockchainManagerWrapper $blockchain_manager, string $private_key = ""){
		$ctx->usegas(1);
		if($private_key == ""){
			require("wallet.php");
			$this->private_key = $OpenCEX_wallet_key;
		} else{
			$this->private_key = $private_key;
		}
		$this->blockchain_manager = $blockchain_manager;
		$this->ctx = $ctx;
		$this->address = "0x" . (new OpenCEX_Ethereum_Address($this->private_key))->get();
		$this->chke20balance = "0x70a08231000000000000000000000000" . $this->address;
		$this->encoder = new OpenCEX_abi_encoder($ctx);
	}
	
	public function balanceOf(string $token_addy){
		$this->ctx->usegas(1);
		$this->encoder->chkvalidaddy($token_addy, false);
		return $this->blockchain_manager->eth_call(["from" => "0x0000000000000000000000000000000000000000", "to" => $token_addy, "data" => $this->chke20balance]);
	}
	
	public function nativeBalance($token_addy){
		$this->ctx->usegas(1);
		return $this->blockchain_manager->eth_getBalance($this->address);
	}
	
	public function sendTransactionIMPL(OpenCEX_Ethereum_Transaction $transaction, int $chainid){
		$this->ctx->usegas(1);
		return $this->blockchain_manager->eth_sendRawTransaction("0x" . $transaction->getRaw($this->private_key, $chainid));
	}
}

final class OpenCEX_SmartWalletManager{
	private readonly OpenCEX_safety_checker $ctx;
	private readonly OpenCEX_WalletManager $wallet;
	private readonly OpenCEX_BlockchainManager $blockchain_manager;
	private readonly OpenCEX_BatchRequestManager $batch_manager;
	private readonly OpenCEX_BlockchainManagerWrapper $manager_wrapper;
	public readonly string $address;
	function __construct(OpenCEX_safety_checker $ctx, OpenCEX_BlockchainManager $blockchain_manager, string $key = ""){
		$this->ctx = $ctx;
		$ctx->usegas(1);
		$this->blockchain_manager = $blockchain_manager;
		$this->batch_manager = new OpenCEX_BatchRequestManager($ctx);
		$this->manager_wrapper = new OpenCEX_BlockchainManagerWrapper($ctx, $this->batch_manager);
		$this->wallet = new OpenCEX_WalletManager($ctx, $this->manager_wrapper, $key);
		$this->address = $this->wallet->address;
	}
	
	public function borrow($callback, ...$args){
		$this->ctx->usegas(1);
		$temp = new OpenCEX_BlockchainManagerWrapper($this->ctx, $this->batch_manager);
		$ret = $callback($temp, ...$args);
		$temp->invalidate();
		return [$ret, $this->batch_manager->execute($this->blockchain_manager)];
	}
	
	public function sendTransactionIMPL(OpenCEX_Ethereum_Transaction $transaction){
		$this->ctx->usegas(1);
		$this->wallet->sendTransactionIMPL($transaction, $this->blockchain_manager->chainid);
		return $this->batch_manager->execute($this->blockchain_manager)[0];
	}
	
	public function reconstruct(string $key = ""){
		return new OpenCEX_SmartWalletManager($this->ctx, $this->blockchain_manager, $key);
	}
}
final class OpenCEX_native_token extends OpenCEX_token{
	private readonly OpenCEX_abi_encoder $encoder;
	private readonly OpenCEX_SmartWalletManager $manager;
	public function __construct(OpenCEX_L1_context $l1ctx, string $name, OpenCEX_SmartWalletManager $manager){
		parent::__construct($l1ctx, $name);
		$this->ctx = $l1ctx->get_safety_checker();
		$this->ctx->usegas(1);
		$this->encoder = new OpenCEX_abi_encoder($this->ctx);
		$this->manager = $manager;
	}
	public function send(int $from, string $address, OpenCEX_uint $amount, bool $sync = true){
		$this->ctx->usegas(1);
		
		//Prepare transaction
		$this->encoder->chkvalidaddy($address, false);
		$transaction = ["from" => $this->manager->address, "to" => $address, "value" => $amount->tohex()];
		
		//Get gas price, gas estimate, and transaction nonce
		$chainquotes = $this->manager->borrow(function(OpenCEX_BlockchainManagerWrapper $wrapper, string $address2, OpenCEX_uint $amount2, $transaction2, string $address3){
			$wrapper->eth_getTransactionCount($address3);
			$wrapper->eth_estimateGas($transaction2);
			$wrapper->eth_gasPrice();
		}, $address, $amount, $transaction, $this->manager->address)[1];
		$this->creditordebit($from, $amount->add($chainquotes[1]->mul($chainquotes[2])), false, $sync);
		
		$this->manager->sendTransactionIMPL(new OpenCEX_Ethereum_Transaction($chainquotes[0]->tohex(), $chainquotes[2]->tohex(), $chainquotes[1]->tohex(), $address, $transaction["value"]));
		
	}
	public function sweep(int $from){		
		$chainquotes = $this->manager->borrow(function(OpenCEX_BlockchainManagerWrapper $wrapper, string $address3){
			$wrapper->eth_getTransactionCount($address3);
			$wrapper->eth_gasPrice();
			$wrapper->eth_getBalance($address3);
		}, $this->manager->address)[1];
		//$this->ctx->die2(strval($chainquotes[0]) . ", " . strval($chainquotes[1]) . ", " . strval($chainquotes[2]));
		$remains = $chainquotes[2]->sub($chainquotes[1]->mul(OpenCEX_uint::init($this->ctx, "21000")), "Amount not enough to cover blockchain fee!");
		//$this->ctx->die2(implode(",", [strval($chainquotes[0]), strval($chainquotes[1]), "21000", $this->manager->reconstruct()->address, strval($remains)]));
		$this->manager->sendTransactionIMPL(new OpenCEX_Ethereum_Transaction($chainquotes[0]->tohex(), $chainquotes[1]->tohex(), "0x5208", $this->manager->reconstruct()->address, $remains->tohex()));
		$this->creditordebit($from, $remains, true, true);
	}
}
?>