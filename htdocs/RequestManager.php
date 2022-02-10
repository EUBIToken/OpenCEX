<?php
set_time_limit(0);
$leaked_ctx = null;
$OpenCEX_anything_locked = false;
//Treat warnings as errors
function OpenCEX_error_handler($errno, string $message, string $file, int $line, $context = NULL)
{
	//NOTE: we treat all warnings as errors!
	$temp_leaked_context = null;
	if(array_key_exists("leaked_ctx", $GLOBALS)){
		$temp_leaked_context = $GLOBALS["leaked_ctx"];
	}
	if(!is_null($temp_leaked_context)){
		$temp_leaked_context->destroy();
	}
	die('{"status": "error", "reason": "Unexpected internal server error at ' . escapeJsonString($file) . ' line ' . strval($line) . ': ' . escapeJsonString($message) . '"}');
	return true;
}


set_error_handler("OpenCEX_error_handler");

//Safety checking
if(!array_key_exists("OpenCEX_request_body", $_POST)){
	die('{"status": "error", "reason": "Missing request body!"}');
}

if(strlen($_POST["OpenCEX_request_body"]) > 65536){
	die('{"status": "error", "reason": "Excessively long request body!"}');
}

$decoded_request = json_decode($_POST["OpenCEX_request_body"], true);

if(is_null($decoded_request)){
	die('{"status": "error", "reason": "Invalid request!"}');
}

$requests_count = 1;
if(is_array($decoded_request)){
	//shortcut
	$requests_count = count($decoded_request);
	if($requests_count == 0){
		die('{"status": "success", "returns": []}');
	}
} else{
	//put request in array, if it's not an array
	$decoded_request = [$decoded_request];
}

$OpenCEX_common_impl = "common.php";

require_once("../OpenCEX/" . $GLOBALS["OpenCEX_common_impl"]);
require_once("../OpenCEX/matching_engine.php");
require_once("../OpenCEX/TokenOrderBook.php");
require_once("../OpenCEX/SafeMath.php");
require_once("../OpenCEX/tokens.php");
require_once("../OpenCEX/wallet_manager.php");
require_once("../OpenCEX/blockchain_manager.php");

abstract class Request{
	public abstract function execute(OpenCEX_L3_context $ctx, $args);
	public function captcha_required(){
		return false;
	}
	
	//Requests that result in external interaction are not batchable
	public function batchable(){
		return true;
	}
}

function check_safety_3($ctx, $args, $exception = NULL){
	$ctx->check_safety(is_array($args), "Arguments must be array!");
	foreach($args as $key => $value){
		$ctx->check_safety(is_string($key), "Key must be string!");
		if($key !== $exception){
			$ctx->check_safety(is_string($value), "Value must be string!");
		}
	}
}

$request_methods = ["non_atomic" => new class extends Request{
	//This special request indicates that we are doing a non-atomic request.
	public function execute(OpenCEX_L3_context $ctx, $args){
		
	}
}, "create_account" => new class extends Request{
	public function execute(OpenCEX_L3_context $ctx, $args){
		//Safety checks
		check_safety_3($ctx, $args);
		$ctx->check_safety(count($args) === 3, "Account creation requires 3 arguments!");
		$ctx->check_safety(array_key_exists("username", $args), "Account creation error: missing username!");
		$ctx->check_safety(array_key_exists("password", $args), "Account creation error: missing password!");
		
		//More safety checks
		$username = $args["username"];
		$password = $args["password"];
		$ctx->check_safety(strlen($username) < 256, "Account creation error: username too long!");
		$ctx->check_safety(strlen($username) > 3, "Account creation error: username too short!");
		$ctx->check_safety(strlen($password) > 8, "Account creation error: password too short!");
		
		//Do some work
		$ctx->session_create($ctx->emit_create_account($username, $password), true);
	}
	public function captcha_required(){
		return true;
	}
}, "login" => new class extends Request{
	public function execute(OpenCEX_L3_context $ctx, $args){
		//Safety checks
		check_safety_3($ctx, $args, "renember");
		$ctx->check_safety(count($args) === 4, "Login requires 4 arguments!");
		$ctx->check_safety(is_bool($args["renember"]), "Login error: renember flag must be boolean!");
		
		//Process user login
		$ctx->login_user($args["username"], $args["password"], $args["renember"]);
	}
	public function captcha_required(){
		return true;
	}
}, "flush" => new class extends Request{
	public function execute(OpenCEX_L3_context $ctx, $args){
		$ctx->flush_outstanding();
	}
}, "client_name" => new class extends Request{
	public function execute(OpenCEX_L3_context $ctx, $args){
		return $ctx->get_cached_username();
	}
}, "logout" => new class extends Request{
	public function execute(OpenCEX_L3_context $ctx, $args){
		return $ctx->destroy_active_session();
	}
}, "get_test_tokens" => new class extends Request{
	public function execute(OpenCEX_L3_context $ctx, $args){
		$ctx->borrow_sql(function(OpenCEX_L1_context $l1ctx, int $userid2){
			//NOTE: We use nested transactions to ensure that the lock is properly released!
			(new OpenCEX_pseudo_token($l1ctx, "shitcoin"))->creditordebit($userid2, "1000000000000000000", true);
			(new OpenCEX_pseudo_token($l1ctx, "scamcoin"))->creditordebit($userid2, "1000000000000000000", true);
		}, $ctx->get_cached_user_id());
	}
	function batchable(){
		return false;
	}
}, "place_order" => new class extends Request{
	//TODO: Require captcha for order creation in production
	public function execute(OpenCEX_L3_context $ctx, $args){
		//Safety checks
		$ctx->check_safety(is_int($args["fill_mode"]), "Order placement error: order filling mode must be int!");
		$fill_mode = intval($args["fill_mode"]);
		unset($args["fill_mode"]);
		check_safety_3($ctx, $args, "buy");
		$ctx->check_safety(count($args) === 5, "Order placement requires 5 arguments!");
		$ctx->check_safety(array_key_exists("primary", $args), "Order placement error: missing primary token!");
		$ctx->check_safety(array_key_exists("secondary", $args), "Order placement error: missing secondary token!");
		$ctx->check_safety(array_key_exists("price", $args), "Order placement error: missing price!");
		$ctx->check_safety(array_key_exists("amount", $args), "Order placement error: missing amount!");
		$ctx->check_safety(array_key_exists("buy", $args), "Order placement error: missing order type!");
		$ctx->check_safety(is_bool($args["buy"]), "Order placement error: order type must be boolean!");
		$ctx->check_safety(in_array(implode([$args["primary"], "_", $args["secondary"]]), $GLOBALS["OpenCEX_whitelisted_pairs"]), "Order placement error: nonexistant pair!");
		$ctx->check_safety_2($fill_mode < 0, "Invalid order fill mode!");
		$ctx->check_safety($fill_mode < 3, "Invalid order fill mode!");
		
		//Initialize SafeMath's large unsigned integers
		$safe = new OpenCEX_safety_checker($ctx);
		$price = OpenCEX_uint::init($safe, $args["price"]);
		$amount;
		$real_amount = OpenCEX_uint::init($safe, $args["amount"]);
		$chk_token;
		if($args["buy"]){
			$amount = $real_amount->mul($price)->div(OpenCEX_uint::init($safe, "1000000000000000000"));
			$chk_token = $args["primary"];
		} else{
			$amount = $real_amount;
			$chk_token = $args["secondary"];
		}
		if($fill_mode == 0){
			$real_amount->sub(OpenCEX_uint::init($safe, $GLOBALS["OpenCEX_minimum_limit"][$chk_token]), "Order size is smaller than the minimum limit order size!");
		}
		
		$ctx->borrow_sql(function(OpenCEX_L1_context $l1ctx, int $userid2, OpenCEX_safety_checker $safe2, OpenCEX_uint $price2, OpenCEX_uint $amount2, OpenCEX_uint $real_amount2, $args2, int $fill_mode2){
			//LOCK TABLES
			$GLOBALS["OpenCEX_orders_table_unlk"] = false;
			$GLOBALS["OpenCEX_ledger_unlk"] = false;
			$GLOBALS["OpenCEX_anything_locked"] = true;
			$l1ctx->safe_query("LOCK TABLES Balances WRITE, Orders WRITE, Misc WRITE;");
			
			//Increment orders counter
			$result = $l1ctx->safe_query("SELECT Val FROM Misc WHERE Kei = 'OrderCounter';");
			if($result->num_rows == 0){
				$result = OpenCEX_uint::init($safe2, "0");
				$l1ctx->safe_query("INSERT INTO Misc (Kei, Val) VALUES ('OrderCounter', '1')");
			} else{
				$safe2->check_safety($result->num_rows == 1, "Multiple order counters found!");
				$result = OpenCEX_uint::init($safe2, $safe2->convcheck2($result->fetch_assoc(), "Val"));
				$l1ctx->safe_query(implode(["UPDATE Misc SET Val = '", strval($result->add(OpenCEX_uint::init($safe2, "1"))), "' WHERE Kei = 'OrderCounter';"]));
			}
			
			//Initialize database of balances 
			$primary = new OpenCEX_pseudo_token($l1ctx, $args2["primary"]);
			$secondary = new OpenCEX_pseudo_token($l1ctx, $args2["secondary"]);
			
			//Debit from user balance
			if($args2["buy"]){
				$primary->creditordebit($userid2, $amount2, false, false);
			} else{
				$secondary->creditordebit($userid2, $amount2, false, false);
			}
			
			//Initialize matching engine
			$orders = new OpenCEX_TokenOrderBook($l1ctx, $primary, $secondary, $args2["buy"], !$args2["buy"]);
			
			//Call matching engine
			$orders->append_order(new OpenCEX_order($safe2, $price2, $real_amount2, $amount2, OpenCEX_uint::init($safe2, "0"), strval($result), $userid2, $args2["buy"]), $fill_mode2);
			
			//Flush order book to database
			$orders->flush();
		}, $ctx->get_cached_user_id(), $safe, $price, $amount, $real_amount, $args, $fill_mode);
	}
	function batchable(){
		return false;
	}
}, "balances" => new class extends Request{
	public function execute(OpenCEX_L3_context $ctx, $args){
		return $ctx->borrow_sql(function(OpenCEX_L1_context $l1ctx, int $userid2){
			$l1ctx->safe_query("LOCK TABLE Balances READ;");
			$result = $l1ctx->safe_query(implode(["SELECT Coin, Balance FROM Balances WHERE UserID = ", strval($userid2), " ORDER BY Coin;"]));
			$l1ctx->safe_query("UNLOCK TABLES;");
			$ret = [];
			$found_coins = [];
			if($result->num_rows > 0){
				$checker = $l1ctx->get_safety_checker();
				while($row = $result->fetch_assoc()) {
					$coin2 = $checker->convcheck2($row, "Coin");
					array_push($found_coins, $coin2);
					array_push($ret, [$coin2, $checker->convcheck2($row, "Balance")]);
				}
			}
			foreach($GLOBALS["OpenCEX_tokens"] as $token){
				if(!in_array($token, $found_coins, true)){
					array_push($ret, [$token, "0"]);
				}
			}
			return $ret;
		}, $ctx->get_cached_user_id());
	}
	function batchable(){
		return false;
	}
}, "eth_deposit_address" => new class extends Request{
	public function execute(OpenCEX_L3_context $ctx, $args){
		$safe = new OpenCEX_safety_checker($ctx);
		return (new OpenCEX_WalletManager($safe, new OpenCEX_BlockchainManagerWrapper($safe, new OpenCEX_FullBlockchainManager()), $ctx->cached_eth_deposit_key()))->address;
	}
}, "withdraw" => new class extends Request{
	public function execute(OpenCEX_L3_context $ctx, $args){
		$ctx->check_safety(count($args) == 3, "Withdrawal requires 3 arguments!");
		check_safety_3($ctx, $args, null);
		$ctx->check_safety(array_key_exists("token", $args), "Withdrawal must specify token!");
		$ctx->check_safety(array_key_exists("amount", $args), "Withdrawal must specify amount!");
		$ctx->check_safety(array_key_exists("address", $args), "Withdrawal must specify recipient address!");
		$userid = $ctx->get_cached_user_id();
		
		$safe = new OpenCEX_safety_checker($ctx);
		switch($args["token"]){
			case "MATIC":
				$blockchain = new OpenCEX_BlockchainManager($safe, 137, "https://polygon-rpc.com");
				break;
			case "MintME":
				$blockchain = new OpenCEX_BlockchainManager($safe, 24734, "https://node1.mintme.com:443");
				break;
			default:
				$ctx->die2("Unsupported token!");
				break;
		}
		$wallet = new OpenCEX_SmartWalletManager($safe, $blockchain);
		$token = $ctx->borrow_sql(function(OpenCEX_L1_context $l1ctx, OpenCEX_SmartWalletManager $wallet2, string $name2){
			return new OpenCEX_native_token($l1ctx, $name2, $wallet2, $name2);
		}, $wallet, $args["token"]);
		$ctx->usegas(-1000);
		$token->send($userid, $args["address"], OpenCEX_uint::init($safe, $args["amount"]));
		$ctx->usegas(1000);
	}
	function batchable(){
		return false;
	}
}, "deposit" => new class extends Request{
	public function execute(OpenCEX_L3_context $ctx, $args){
		$ctx->check_safety(count($args) == 1, "Deposit must specify one argument!");
		$ctx->check_safety(array_key_exists("token", $args), "Deposit must specify token!");
		$ctx->check_safety(is_string($args["token"]), "Token must be string!");
		$safe = new OpenCEX_safety_checker($ctx);
		$blockchain;
		switch($args["token"]){
			case "MATIC":
				$blockchain = new OpenCEX_BlockchainManager($safe, 137, "https://polygon-rpc.com");
				break;
			case "MintME":
				$blockchain = new OpenCEX_BlockchainManager($safe, 24734, "https://node1.mintme.com:443");
				break;
			default:
				$ctx->die2("Unsupported token!");
				break;
		}
		$wallet = new OpenCEX_SmartWalletManager($safe, $blockchain, $ctx->cached_eth_deposit_key());
		$token = $ctx->borrow_sql(function(OpenCEX_L1_context $l1ctx, OpenCEX_SmartWalletManager $manager, string $token2){
			return new OpenCEX_native_token($l1ctx, $token2, $manager);
		}, $wallet, $args["token"]);
		$token->sweep($ctx->get_cached_user_id());
	}
}];

//Continue validating request
$not_multiple_requests = $requests_count < 2;
$captcha_caught = false;
$non_atomic = false;
foreach($decoded_request as $singular_request){
	if(!array_key_exists("method", $singular_request)){
		die('{"status": "error", "reason": "Request method missing!"}');
	}
	
	if(!array_key_exists($singular_request["method"], $request_methods)){
		die('{"status": "error", "reason": "Request method not defined!"}');
	}
	
	if($singular_request["method"] == "non_atomic"){
		$non_atomic = true;
	}
	
	if(!($not_multiple_requests || $request_methods[$singular_request["method"]]->batchable() || $non_atomic)){
		die('{"status": "error", "reason": "Request not batchable!"}');
	}
	
	if($request_methods[$singular_request["method"]]->captcha_required()){
		if($captcha_caught){
			die('{"status": "error", "reason": "Multiple captcha-protected requests in batch!"}');
		}
		$captcha_caught = true;
	}
}

function escapeJsonString($value) { # list from www.json.org: (\b backspace, \f formfeed)
	$escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
	$replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
	$result = str_replace($escapers, $replacements, $value);
	return $result;
}

//Begin request execution
$return_array = [];
try{
	//Setup context
	$ctx = new OpenCEX_L3_context();
	$leaked_ctx = new OpenCEX_safety_checker($ctx);
	$ctx->begin_emitting();
	
	//Gas refund for standard batches
	if($non_atomic || $not_multiple_requests){
		$ctx->usegas(-1000);
	}
	
	//Execute requests
	foreach($decoded_request as $singular_request){
		$data = null;
		if(array_key_exists("data", $singular_request)){
			$data = $singular_request["data"];
		} else{
			$data = [];
		}
		if($request_methods[$singular_request["method"]]->captcha_required()){
			$ip_addr = $_SERVER['REMOTE_ADDR'];
			if($ip_addr == "::1"){
				$ip_addr = $GLOBALS["OpenCEX_localhost_override"];
			}
			try{
				$ctx->check_safety(array_key_exists('captcha', $data), "Captcha required!");
				if ((new SoapClient('https://raincaptcha.com/captcha.wsdl'))->send($GLOBALS["OpenCEX_raincaptcha_secret"], $data["captcha"], $ip_addr)->status === 1) {
					$captcha_solved = true;
				} else {
					$ctx->die2("Captcha required!");
				}
			} catch (OpenCEX_assert_exception $e){
				throw $e;
			} catch (Exception $e){
				$ctx->die2("Captcha required!");
			}
		}
		
		array_push($return_array, $request_methods[$singular_request["method"]]->execute($ctx, $data));
		
		
		if($OpenCEX_anything_locked){
			$ctx->unlock_tables();
			$OpenCEX_anything_locked = false;
			$GLOBALS["OpenCEX_ledger_unlk"] = true;
			$GLOBALS["OpenCEX_orders_table_unlk"] = true;
		}
		
		
		//In an atomic batch, if 1 request fails, all previous requests are reverted.
		//In a standard batch, if 1 request fails, the results of previous requests are preserved.
		//We need to flush outstanding changes in a standard batch after each request.
		if($non_atomic){
			$ctx->flush_outstanding();
		}
	}
	
	//Flush and destroy context
	$leaked_ctx = null;
	$ctx->finish_emitting();
} catch (OpenCEX_assert_exception $e){
	die('{"status": "error", "reason": "' . escapeJsonString($e) . '"}');
} catch (Exception $e){
	//NOTE: if we fail due to unexpected exception, we must destroy the context!
	//Automatic context destruction only occours for OpenCEX_assert_exceptions.
	if(!is_null($leaked_ctx)){
		$leaked_ctx->destroy();
	}
		
	$fail_message = '{"status": "error", "reason": "Unexpected internal server error: ' . escapeJsonString($e->getMessage()) . '"}';
	die($fail_message);
}

die(implode(['{"status": "success", "returns": ', json_encode($return_array), "}"]));

?>