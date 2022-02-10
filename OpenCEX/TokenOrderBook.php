<?php
final class OpenCEX_TokenOrderBook extends OpenCEX_OrderBook{
	private readonly OpenCEX_L1_context $l1ctx;
	private readonly OpenCEX_token $primary;
	private readonly OpenCEX_token $secondary;
	private $buy_prices = null;
	private $sell_prices = null;
	private int $last_rep = 1;
	
	//NOTE: Order cancellations are handled by the request manager, not the matching engine.
	public function __construct(OpenCEX_L1_context $l1ctx, OpenCEX_token $primary, OpenCEX_token $secondary, bool $allow_buy = false, bool $allow_sell = false){
		parent::__construct($l1ctx->get_safety_checker());
		
		$this->ctx->check_safety_2($GLOBALS["OpenCEX_orders_table_unlk"] || $GLOBALS["OpenCEX_ledger_unlk"], "Tables not properly locked!");
		
		$this->ctx->usegas(1);
		
		$this->l1ctx = $l1ctx;
		$this->primary = $primary;
		$this->secondary = $secondary;
		
		if($allow_buy){
			$this->sell_prices = $this->permit($l1ctx->safe_prepare("SELECT Price, Amount, InitialAmount, TotalCost, Id, PlacedBy FROM Orders WHERE Pri = ? AND Sec = ? AND Buy = 0 ORDER BY Price ASC, Id ASC FOR UPDATE;"));
		}
		
		if($allow_sell){
			$this->buy_prices = $this->permit($l1ctx->safe_prepare("SELECT Price, Amount, InitialAmount, TotalCost, Id, PlacedBy FROM Orders WHERE Pri = ? AND Sec = ? AND Buy = 1 ORDER BY Price DESC, Id ASC FOR UPDATE;"));
		}
	}
	
	protected function refund_order_cancellation(OpenCEX_Order $order){
		$this->ctx->usegas(1);
		$token;
		if($order->buy){
			$token = $this->primary;
		} else{
			$token = $this->secondary;
		}
		$token->creditordebit($order->placed_by, $order->initial_amount->sub($order->total_cost), true, false);
	}

	private function permit($prepared){
		$this->ctx->usegas(1);
		$name1 = $this->primary->name;
		$name2 = $this->secondary->name;
		$prepared->bind_param("ss", $name1, $name2);
		return $this->l1ctx->safe_execute_prepared($prepared);
	}
	
	//We defer loading extra orders for as long as possible.
	protected function get_more_orders(bool $buy){
		$this->ctx->usegas(1);
		$prepared;
		$raw_result = null;
		$buy2;
		if($buy){
			$this->ctx->check_safety_2(is_null($this->buy_prices), "Selling is not supported by this order book!");
			$raw_result = $this->buy_prices;
			$buy2 = 1;
		} else{
			$this->ctx->check_safety_2(is_null($this->sell_prices), "Buying is not supported by this order book!");
			$raw_result = $this->sell_prices;
			$buy2 = 0;
		}
		
		if($raw_result->num_rows == 0){
			return;
		}
		
		$ret;
		if($buy){
			$ret = &$this->buy_orders;
		} else{
			$ret = &$this->sell_orders;
		}
		
		for($i = 0; $i < $this->last_rep; $i++){
			$raw_order = $raw_result->fetch_assoc();
			if(is_null($raw_order)){
				break;
			} else{
				$price = OpenCEX_uint::init($this->ctx, $this->ctx->convcheck2($raw_order, "Price"));
				$amount = OpenCEX_uint::init($this->ctx, $this->ctx->convcheck2($raw_order, "Amount"));
				$initial_amount = OpenCEX_uint::init($this->ctx, $this->ctx->convcheck2($raw_order, "InitialAmount"));
				$total_cost = OpenCEX_uint::init($this->ctx, $this->ctx->convcheck2($raw_order, "TotalCost"));
				$id = $this->ctx->convcheck2($raw_order, "Id");
				$placed_by = $this->ctx->convcheck2($raw_order, "PlacedBy");
				array_push($ret, new OpenCEX_Order($this->ctx, $price, $amount, $initial_amount, $total_cost, $id, $placed_by, $buy));
			}
		}
		++$this->last_rep;
		
		
	}
	
	//Flush order book to database
	public function flush(bool $flush_ledgers = true){
		$this->ctx->usegas(1);
		//Append new orders
		$noremove = [];
		$prepared = $this->l1ctx->safe_prepare("INSERT INTO Orders (Pri, Sec, Price, Amount, InitialAmount, TotalCost, Id, PlacedBy, Buy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);");
		foreach($this->appended_orders as $order){
			if(in_array($order->id, $this->removed_orders)){
				array_push($noremove, $order->id);
			} else{
				$buy = 0;
				if($order->buy){
					$buy = 1;
				}
				$name1 = $this->primary->name;
				$name2 = $this->secondary->name;
				$price2 = strval($order->price);
				$amount2 = strval($order->amount);
				$ia2 = strval($order->initial_amount);
				$cost2 = strval($order->total_cost);
				$id2 = $order->id;
				$placed_by2 = $order->placed_by;
				$prepared->bind_param("sssssssii", $name1, $name2, $price2, $amount2, $ia2, $cost2, $id2, $placed_by2, $buy);
				$this->l1ctx->safe_execute_prepared($prepared);
			}
			
		}
		
		//Delete old orders
		$noupdate = [];
		$prepared = $this->l1ctx->safe_prepare("DELETE FROM Orders WHERE Id = ?;");
		foreach($this->removed_orders as $removed_order_id){
			if(!in_array($removed_order_id, $noremove)){
				array_push($noupdate, $removed_order_id);
				$prepared->bind_param("s", $removed_order_id);
				$this->l1ctx->safe_execute_prepared($prepared);
			}
		}
		
		//Update modded orders
		$prepared = $this->l1ctx->safe_prepare("UPDATE Orders SET Amount = ?, TotalCost = ? WHERE Id = ?");
		foreach($this->modded_orders as $order){
			if(!in_array($order->id, $noupdate)){
				$buy = 0;
				if($order->buy){
					$buy = 1;
				}
				$a2 = strval($order->amount);
				$i2 = $order->id;
				$tctc = $order->total_cost;
				$prepared->bind_param("sss", $a2, $tctc, $i2);
				$this->l1ctx->safe_execute_prepared($prepared);
			}
		}
		
		//Clear caches
		$this->removed_orders = [];
		$this->appended_orders = [];
		$this->modded_orders = [];
		
		//Flush ledgers
		if($flush_ledgers){
			$this->primary->flush();
			$this->secondary->flush();
		}
	}
	
	protected function order_execution_handler(OpenCEX_order $order, OpenCEX_uint $cost, OpenCEX_uint $output, bool $refund = true){
		$this->ctx->usegas(1);
		$token1;
		$token2;
		if($order->buy){
			$token1 = $this->primary;
			$token2 = $this->secondary;
		} else{
			$token1 = $this->secondary;
			$token2 = $this->primary;
		}
		
		if($refund && $order->amount->comp($this->safe_zero) == 0){
			//If the order is filled at a better-than-expected price, credit the diffrence
			//back to the account that placed the order
			$token1->creditordebit($order->placed_by, $order->initial_amount->sub($order->total_cost), true, false);
			//NOTE: this refund doesn't occour for immediate or cancel orders, since it's already done by
			//the matching engine.
		}
		
		$token2->creditordebit($order->placed_by, $output, true, false);
	}
}
?>