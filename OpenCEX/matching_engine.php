<?php
//OpenCEX advanced-technology high-performance matching engine

$OpenCEX_orders_table_unlk = true;

require_once($GLOBALS["OpenCEX_common_impl"]);
require_once("SafeMath.php");


final class OpenCEX_order{
	private readonly OpenCEX_safety_checker $ctx;
	public readonly OpenCEX_uint $price;
	public OpenCEX_uint $amount;
	public readonly OpenCEX_uint $initial_amount;
	public OpenCEX_uint $total_cost;
	public readonly string $id;
	public readonly bool $buy;
	public readonly int $placed_by;
	private readonly OpenCEX_uint $safe_zero;
	private readonly OpenCEX_uint $safe_divisor;
	
	function __construct(OpenCEX_safety_checker $ctx, OpenCEX_uint $price, OpenCEX_uint $amount, OpenCEX_uint $initial_amount, OpenCEX_uint $total_cost, string $id, int $placed_by, bool $buy){
		$ctx->usegas(1, 17);
		$this->ctx = $ctx;
		$this->price = $price;
		$this->amount = $amount;
		$this->initial_amount = $initial_amount;
		$this->total_cost = $total_cost;
		$this->id = $id;
		$this->buy = $buy;
		$this->placed_by = $placed_by;
		$this->safe_zero = OpenCEX_uint::init($ctx, "0");
		$this->safe_divisor = OpenCEX_uint::init($ctx, "1000000000000000000");
	}
	

	function get_matched_amount(OpenCEX_order $other){
		$this->ctx->usegas(1, 1);
		
		$this->ctx->check_safety_2($this->buy == $other->buy, "Order matching error: attempted to match two orders of the same type!");
		$minprice = $other->price->min($this->price);
		if($this->buy){
			if($other->price->comp($this->price) == 1){
				$this->ctx->covmark(2);
				return $this->safe_zero;
			} else{
				$tmp = $this->amount;
				$tmp = $tmp->min($other->amount);
				$tmp = $tmp->min($this->initial_amount->sub($this->total_cost)->mul($minprice)->div($this->safe_divisor));
				$tmp = $tmp->min($other->initial_amount->sub($other->total_cost));
				if($tmp->comp($this->safe_zero) == 0){
					$this->ctx->covmark(3);
					return $this->safe_zero;
				} else{
					$this->ctx->covmark(54);
					$this->amount = $this->amount->sub($tmp);
					$other->amount = $other->amount->sub($tmp);
					$other->total_cost = $other->total_cost->add($tmp);
					$this->ctx->check_safety_2($other->total_cost->comp($other->initial_amount) == 1, "Maximum order cost exceeded (sell order)!");
					$this->total_cost = $this->total_cost->add($tmp->mul($minprice)->div($this->safe_divisor));
					$this->ctx->check_safety_2($this->total_cost->comp($this->initial_amount) == 1, "Maximum order cost exceeded (buy order)!" . strval($this->id));
					return $tmp;
				}
				
				
			}
		} else{
			$this->ctx->covmark(4);
			return $other->get_matched_amount($this)->mul($minprice)->div($this->safe_divisor);
		}
	}
	
	function comp(OpenCEX_order $other){
		$this->ctx->usegas(1, 5);
		$this->ctx->check_safety($this->buy == $other->buy, "Order matching error: attempted to compare a buy order with a sell order!");
		$tmp = $this->price->comp($other->price);
		$specialswitch2 = false;
		if($tmp == 0){
			$this->ctx->covmark(6);
			$tmp = strcmp($this->id, $other->id);
			if($tmp == 0){
				$this->ctx->covmark(34);
			} else{
				$this->ctx->covmark(7);
				$specialswitch2 = true;
				$tmp = $tmp / abs($tmp);
			}
		} else{
			$this->ctx->covmark(33);
		}
		
		if($specialswitch2){
			$this->ctx->covmark(14);
			return $tmp;
		} else if($this->buy){
			$this->ctx->covmark(8);
			return 0 - $tmp;
		} else{
			$this->ctx->covmark(9);
			return $tmp;
		}
	}
}

abstract class OpenCEX_OrderBook{
	protected $buy_orders = [];
	protected $sell_orders = [];
	protected $removed_orders = [];
	protected $appended_orders = [];
	protected $modded_orders = [];
	protected readonly OpenCEX_uint $safe_zero;
	protected readonly OpenCEX_safety_checker $ctx;
	private readonly OpenCEX_uint $safe_divisor;
	public function __construct(OpenCEX_safety_checker $ctx){
		$ctx->usegas(1, 10);
		$this->ctx = $ctx;
		$this->safe_zero = OpenCEX_uint::init($ctx, "0");
		$this->safe_divisor = OpenCEX_uint::init($ctx, "1000000000000000000");
	}
	
	public function append_order(OpenCEX_order $order, int $fill_mode = 0){
		$this->ctx->usegas(1);
		$this->ctx->check_safety_2($fill_mode < 0, "Invalid order fill mode!");
		$this->ctx->check_safety($fill_mode < 3, "Invalid order fill mode!");
		//Order fill modes
		//0 - limit order, 1 - immediate or cancel, 2 - fill or kill
		
		$old_amount = $order->amount;
		$counter;
		$friend;
		if($order->buy){
			$this->ctx->covmark(12);
			$counter = &$this->sell_orders;
			$friend = &$this->buy_orders;
		} else{
			$this->ctx->covmark(13);
			$counter = &$this->buy_orders;
			$friend = &$this->sell_orders;
		}
		usort($counter, function($x, $y){
			//We move all the nulls to the end of the array
			//to optimize order execution performance. Nulls
			//are removed during serialization.
			if(is_null($x) || is_null($y)){
				$this->ctx->covmark(17);
				return 0;
			} else{
				$this->ctx->covmark(18);
				return $x->comp($y);
			}
		});

		$limit = count($counter);
		if($limit == 0){
			$this->ctx->covmark(19);
			$this->get_more_orders(!$order->buy);
			$limit = count($counter);
		} else{
			$this->ctx->covmark(20);
		}
		
		$old_total_cost = $order->total_cost;
		$total_output = $this->safe_zero;
		for($i = 0; $i < $limit; ){
			//Execute order
			$other = $counter[$i];
			if(is_null($other)){
				$this->ctx->covmark(21);
				$i++;
				
				//NOTE: Order book defragmentation is done
				//during serialization, not during execution.
				continue;
			} else{
				$this->ctx->covmark(11);
			}
			$old_other_amount = $other->amount;
			$old_other_total_cost = $other->total_cost;
			$output = $order->get_matched_amount($other);
			
			if($other->amount->comp($this->safe_zero) == 0){
				$this->ctx->covmark(22);
				$counter[$i] = NULL;
				array_push($this->removed_orders, $other->id);
			} else if($output->comp($this->safe_zero) == 0){
				$this->ctx->covmark(23);
				break;
			} else{
				$this->ctx->covmark(24);
				array_push($this->modded_orders, $other);
			}
			$total_output = $total_output->add($output);
			$minprice2 = $order->price->min($other->price);
			if($order->buy){
				$this->ctx->covmark(25);
				$this->order_execution_handler($other, $old_other_amount->sub($other->amount), $output->mul($minprice2)->div($this->safe_divisor));
			} else{
				$this->ctx->covmark(26);
				$this->order_execution_handler($other, $old_other_amount->sub($other->amount), $old_other_amount->sub($other->amount));
			}
			if(++$i == $limit){
				$this->ctx->covmark(27);
				$this->get_more_orders(!$order->buy);
				$limit = count($counter);
			} else{
				$this->ctx->covmark(14);
			}
		}
		
		
		if($order->amount->comp($this->safe_zero) == 1){
			//Atomic orders, such as fill or kill orders, are never admitted to the order book.
			if($fill_mode == 0){
				$this->ctx->covmark(28);
				array_push($friend, $order);
				array_push($this->appended_orders, $order);
			} else if($fill_mode == 1){
				$this->ctx->covmark(29);
				$this->refund_order_cancellation($order);
			} else{
				$this->ctx->die2("Fill or kill order canceled due to insufficent liquidity!");
				return;
			}
		} else{
			$this->ctx->covmark(35);
		}
		
		$total_cost = $order->total_cost->sub($old_total_cost);
		if($total_output->add($total_cost)->comp($this->safe_zero) == 1){
			$this->ctx->covmark(31);
			$this->order_execution_handler($order, $total_cost, $total_output, $fill_mode != 1);
		} else{
			$this->ctx->covmark(32);
		}
		
		$this->ctx->covmark(36 + $fill_mode);
	}
	
	protected abstract function get_more_orders(bool $buy);
	protected abstract function order_execution_handler(OpenCEX_order $order, OpenCEX_uint $cost, OpenCEX_uint $output, bool $refund = true);
	protected abstract function refund_order_cancellation(OpenCEX_order $order);
}


?>