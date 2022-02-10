<?php
$OpenCEX_common_impl = "tests/stubs.php";
require_once("../matching_engine.php");
require_once("../SafeMath.php");

$ctx = new OpenCEX_safety_checker(new OpenCEX_L2_context(1, 54));

function rand2(){
	return OpenCEX_uint::init($GLOBALS["ctx"], strval(rand(0, 2147483647)));
}
$rl = OpenCEX_uint::init($ctx, "2147483647");
$zero = OpenCEX_uint::init($ctx, "0");
$div2 = OpenCEX_uint::init($ctx, "1000000000000000000");
function rand3(){
	return rand2()->add(rand2()->mul($GLOBALS["rl"]));
}
function rand4(){
	return rand3()->add(rand3());
}


final class OpenCEX_TestOrderBook extends OpenCEX_OrderBook{
	private OpenCEX_uint $buy_price;
	private OpenCEX_uint $sell_price;
	public OpenCEX_uint $balance_primary;
	public OpenCEX_uint $balance_secondary;
	function __construct(OpenCEX_safety_checker $ctx){
		parent::__construct($ctx);
		$common = OpenCEX_uint::init($ctx, "4611686014132420609");
		$this->buy_price = $common;
		$this->sell_price = $common;
		$this->balance_primary = $GLOBALS["zero"];
		$this->balance_secondary = $GLOBALS["zero"];
	}
	protected function get_more_orders(bool $buy){
		/*
		$rnd = rand2();
		$amount = rand3();
		$price;
		$appendto;
		$initial_amount;
		if($buy){
			$this->ctx->covmark(30);
			$price = $this->buy_price->sub($rnd);
			$this->buy_price = $price;
			$appendto = &$this->buy_orders;
			$initial_amount = $amount;
			$this->balance_primary = $this->balance_primary->add($amount);
		} else{
			$this->ctx->covmark(17);
			$price = $this->sell_price->add($rnd);
			$this->sell_price = $price;
			$appendto = &$this->sell_orders;
			$initial_amount = $amount->mul($price)->div($GLOBALS["div2"]);
			$this->balance_secondary = $this->balance_secondary->add($initial_amount);
		}
		array_push($appendto, new OpenCEX_order($this->ctx, $price, $amount, $initial_amount, $GLOBALS["zero"], random_bytes(32), 0, $buy));
		*/
	}
	protected function order_execution_handler(OpenCEX_order $order, OpenCEX_uint $cost, OpenCEX_uint $output, bool $refund = true){
		$token1;
		$token2;
		if($order->buy){
			$token1 = $this->balance_primary;
			$token2 = $this->balance_secondary;
		} else{
			$token1 = $this->balance_secondary;
			$token2 = $this->balance_primary;
		}
		
		if($refund && $order->amount->comp($GLOBALS["zero"]) == 0){
			//If the order is filled at a better-than-expected price, credit the diffrence
			//back to the account that placed the order
			$token1 = $token1->sub($order->initial_amount->sub($order->total_cost), $order->buy ? "Theft of funds detected (primary token)!" : "Theft of funds detected (secondary token)!");
			//NOTE: this refund doesn't occour for immediate or cancel orders, since it's already done by
			//the matching engine.
		}
		
		$token2 = $token2->sub($output, $order->buy ? "Theft of funds detected (secondary token)!" : "Theft of funds detected (primary token)!");
		if($order->buy){
			$this->balance_primary = $token1;
			$this->balance_secondary = $token2;
		} else{
			$this->balance_primary = $token2;
			$this->balance_secondary = $token1;
		}
	}
	protected function refund_order_cancellation(OpenCEX_order $order){
		if($order->buy){
			$token = $this->balance_primary;
		} else{
			$token = $this->balance_secondary;
		}
		//$token = $token->sub($order->initial_amount->sub($order->total_cost), $order->buy ? "Theft of funds detected (primary token)!" : "Theft of funds detected (secondary token)!");
		if($order->buy){
			$this->balance_primary = $token;
		} else{
			$this->balance_secondary = $token;
		}
	}
	public function cred2(OpenCEX_uint $amt, bool $buy){
		if($buy){
			$token = $this->balance_primary;
		} else{
			$token = $this->balance_secondary;
		}
		$token = $token->add($amt);
		if($buy){
			$this->balance_primary = $token;
		} else{
			$this->balance_secondary = $token;
		}
	}
	
}

while(true){
	//echo("Creating new order book...\n");
	$order_book = new OpenCEX_TestOrderBook($ctx);
	//echo("New order book created!\n");
	for($i = 0; $i < 256; $i++){
		$buy = rand(0, 1) == 0;
		$amount = rand3();
		$price = rand4();
		$initial_amount;
		if($buy){
			$ctx->covmark(16);
			$initial_amount = $amount->mul($price)->div($div2);
		} else{
			$ctx->covmark(15);
			$initial_amount = $amount;
		}
		$order_fill_mode = rand(0, 1);
		
		$order_book->cred2($initial_amount, $buy);
		//echo(($order_fill_mode == 0 ? "Limit order: " : "Immediate or cancel order: ") . strval($price) . "=>" . strval($amount) . ($buy ? " (buy)\n" : "(sell)\n"));
		$order_book->append_order(new OpenCEX_order($ctx, $price, $amount, $initial_amount, $zero, random_bytes(32), 0, $buy), $order_fill_mode);
		//echo("Order execution completed!\n");
	}
}

?>