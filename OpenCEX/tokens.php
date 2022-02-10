<?php

$OpenCEX_ledger_unlk = true;
require_once($GLOBALS["OpenCEX_common_impl"]);
require_once("SafeMath.php");

abstract class OpenCEX_token{
	private OpenCEX_L1_context $ctx;
	protected OpenCEX_safety_checker $safety_checker;
	public readonly string $name;
	private $prepared_query;
	private $prepared_update;
	private $prepared_insert;
	public function __construct(OpenCEX_L1_context $ctx, string $name){
		$this->safety_checker = $ctx->get_safety_checker();
		$this->safety_checker->usegas(1);
		$this->ctx = $ctx;
		$this->name = $name;
		if($GLOBALS["OpenCEX_ledger_unlk"]){
			$ctx->safe_query("LOCK TABLE Balances WRITE;");
			$GLOBALS["OpenCEX_ledger_unlk"] = false;
			$GLOBALS["OpenCEX_anything_locked"] = true;
		}
		$this->prepared_query = $this->ctx->safe_prepare("SELECT Balance FROM Balances WHERE Coin = ? AND UserID = ?;");
		$this->prepared_update = $this->ctx->safe_prepare("UPDATE Balances SET Balance = ? WHERE Coin = ? AND UserID = ?;");
		$this->prepared_insert = $this->ctx->safe_prepare("INSERT INTO Balances (Balance, Coin, UserID) VALUES (?, ?, ?);");
		
		
	}
	
	private $cached_balances = [];
	
	public function balance2(int $userid){
		$this->safety_checker->usegas(1);
		if(array_key_exists($userid, $this->cached_balances)){
			return $this->cached_balances[$userid];
		} else{
			$name2 = $this->name;
			$this->prepared_query->bind_param("si", $name2, $userid);
			$result = $this->ctx->safe_execute_prepared($this->prepared_query);
			$len = intval($result->num_rows);
			$this->safety_checker->check_safety($len < 2, "Balance database corrupted!");
			$ret2;
			if($len == 0){
				$ret2 = ["0", true];
			} else{
				$tmpval = $this->safety_checker->convcheck2($result->fetch_assoc(), "Balance");
				$this->chksafevalue($tmpval);
				$ret2 = [$tmpval, false];
			}
			$this->cached_balances[$userid] = $ret2;
			return $ret2;
		}
		
	}
	
	private function chksafevalue(string $value){
		$this->safety_checker->usegas(1);
		$this->safety_checker->check_safety(is_numeric($value), "Invalid value!");
		$this->safety_checker->check_safety_2(is_int(stripos($value, ".")), "Decimal not supported!");
		$this->safety_checker->check_safety_2(substr($value, 0, 1) == "-", "Negative values not supported!");
	}
	
	public function creditordebit(int $userid, string|OpenCEX_uint $amount, bool $credit, bool $sync = true){
		$this->safety_checker->usegas(1);
		
		//We don't verify amount, since SafeMath would do it anyways!
		if(is_string($amount)){
			$amount = OpenCEX_uint::init($this->safety_checker, $amount);
		}
		
		$bal = $this->balance2($userid);
		$new_balance = OpenCEX_uint::init($this->safety_checker, $bal[0]);
		if($credit){
			$new_balance = $new_balance->add($amount);
		} else{
			//Sufficent balance checks are already performed by SafeMath.
			$new_balance = $new_balance->sub($amount, "Insufficent balance!");
		}
		
		$new_balance = strval($new_balance);
		
		if($sync){
			$action;
			if($bal[1]){
				$action = $this->prepared_insert;
			} else{
				$action = $this->prepared_update;
			}
			$this->cached_balances[$userid] = [$new_balance, false, false];
		
			$name2 = $this->name;
			$action->bind_param("isi", $new_balance, $name2, $userid);
			$this->ctx->safe_execute_prepared($action);
		} else{
			$this->cached_balances[$userid] = [$new_balance, $bal[1], true];
		}
	}
	
	public function flush(bool $clear = false){
		foreach($this->cached_balances as $userid => &$balance){
			//Optimization: we only flush dirty balances.
			if($balance[2]){
				$action;
				if($balance[1]){
					$action = $this->prepared_insert;
				} else{
					$action = $this->prepared_update;
				}
				$name2 = $this->name;
				$new_balance = $balance[0];
				$userid = strval($userid);
				$action->bind_param("isi", $new_balance, $name2, $userid);
				$this->ctx->safe_execute_prepared($action);
				$balance[2] = false;
			}
		}
		if($clear){
			$this->cached_balances = [];
		}
	}
	
	public abstract function send(int $from, string $address, OpenCEX_uint $amount);
	public abstract function sweep(int $from);
	
}

final class OpenCEX_pseudo_token extends OpenCEX_token{
	public function send(int $from, string $address, OpenCEX_uint $amount, bool $sync = true){
		$this->creditordebit($from, $amount, false, $sync);
	}
	public function sweep(int $from){
		$this->safety_checker->die2("Test tokens are not depositable!");
	}
}

?>