<?php
require_once("../assert_exception.php");
class OpenCEX_L2_context{
	private readonly int $min;
	private readonly int $max;
	private readonly int $cov_envelope;
	public function __construct(int $min = 0, int $max = 0){
		$this->min = $min;
		$this->max = $max;
		$this->check_safety_2($max < $min, "Maximum id must be bigger than or equal to minimum id!");
		$this->cov_envelope = $max - $min;
	}
	public function check_safety($predicate, string $message = "", int $id = 0){
		$this->covmark($id);
		if(!$predicate){
			$this->die2($message);
		}
	}
	
	public function check_safety_2($predicate, string $message = "", int $id = 0){
		$this->covmark($id);
		if($predicate){
			$this->die2($message);
		}
	}
	
	public function die2(string $message = ""){
		throw new OpenCEX_assert_exception($message);
	}
	
	private int $remaining_gas = 2147483647;
	public function usegas(int $amount, int $id = 0){
		$this->check_safety($this->remaining_gas > $amount, "Insufficent gas!", $id);
		$this->remaining_gas -= $amount;
	}
	
	public function cleargas(){
		$this->remaining_gas = 0;
	}
	
	public function convcheck2($result, $key, int $id = 0){
		$this->usegas(1, $id);
		$this->check_safety($result, "SQL Query returned invalid result!");
		$this->check_safety_2(is_null($key), "SQL Query returned invalid result!");
		$this->check_safety(is_array($result), "SQL Query returned invalid result!");
		$this->check_safety(array_key_exists($key, $result), "SQL Query returned invalid result!");
		$result = $result[$key];
		$this->check_safety_2(is_null($result), "SQL Query returned invalid result!");
		return $result;
	}
	
	private $caught_ids = [];
	
	public function covmark(int $id = 0){
		if($id != 0){
			if($id < $this->min || $id > $this->max || in_array($id, $this->caught_ids, true)){
				return;
			} else {
				array_push($this->caught_ids, $id);
				$cov = count($this->caught_ids);
				echo("Code coverage: " . strval(floor(intdiv(100 * $cov, $this->cov_envelope))) . "%, current id: " . strval($id) . "\n");
				if($cov == $this->cov_envelope){
					die("Test completed!");
				}
			}
		}
	}
}
final class OpenCEX_L3_context extends OpenCEX_L2_context{
	
}
final class OpenCEX_safety_checker{
	private readonly OpenCEX_L2_context $underlying;
	public function __construct(OpenCEX_L2_context $underlying){
		$this->underlying = $underlying;
	}
	public function check_safety($predicate, string $message = "", int $id = 0){
		$this->underlying->usegas(1, $id);
		if(!$predicate){
			$this->underlying->die2($message);
		}
	}
	
	public function check_safety_2($predicate, string $message = "", int $id = 0){
		$this->underlying->usegas(1, $id);
		if($predicate){
			$this->underlying->die2($message);
		}
	}
	
	public function die2(string $message = ""){
		$this->underlying->die2($message);
	}
	
	public function covmark(int $id = 0){
		$this->underlying->covmark($id);
	}
	
	public function usegas(int $amount, int $id = 0){
		$this->underlying->usegas($amount, $id);
	}
	
	public function convcheck2($result, $key, int $id = 0){
		$this->usegas(1, $id);
		$this->check_safety($result, "SQL Query returned invalid result!");
		$this->check_safety_2(is_null($key), "SQL Query returned invalid result!");
		$this->check_safety(is_array($result), "SQL Query returned invalid result!");
		$this->check_safety(array_key_exists($key, $result), "SQL Query returned invalid result!");
		$result = $result[$key];
		$this->check_safety_2(is_null($result), "SQL Query returned invalid result!");
		return $result;
	}
	
	//SafeMath values deduplication cache
	public $SafeMathDedupCache = [];
	private $SafeMathDedupCacheOrder = [];
	private int $SafeMathDedupCacheSize = 0;
	public function appendSafeMathCache(string $index, $value){
		if($this->SafeMathDedupCacheSize == 1000){
			unset($this->SafeMathDedupCache[array_shift($this->SafeMathDedupCacheOrder)]);
		} else{
			$this->SafeMathDedupCacheSize++;
		}
		$this->SafeMathDedupCache[$index] = $value;
		array_push($this->SafeMathDedupCacheOrder, $index);
	}
}
?>