<?php
require_once($GLOBALS["OpenCEX_common_impl"]);

//PHP port of OpenZeppelin's SafeMath
final class OpenCEX_uint{
	private readonly OpenCEX_safety_checker $ctx;
	private readonly bool $gmp;
	private $value;
	private readonly ?OpenCEX_uint $maxvalue;
	private function __construct(OpenCEX_safety_checker $ctx, $value, bool $gmp, ?OpenCEX_uint $maxvalue, string $exceed_message = "SafeMath: Initialization Overflow!"){
		$ctx->usegas(1);
		if(!$gmp){
			$ctx->check_safety(is_string($value), "SafeMath: BCMath value must be string!");			
		}
		$this->ctx = $ctx;
		$this->value = $value;
		$this->gmp = $gmp;
		$this->maxvalue = $maxvalue;
		if(is_null($maxvalue)){
			$ctx->covmark(41);
		} else{
			$ctx->check_safety_2($this->comp($maxvalue) == 1, $exceed_message, 40);
		}
	}
	public static function init(OpenCEX_safety_checker $ctx, string $value, OpenCEX_uint $maxvalue = NULL, string $exceed_message = "SafeMath: Initialization Overflow!"){
		$ctx->usegas(1);
		$hex = substr($value, 0, 2) == "0x";
		$firstchar = ord($value);
		$ctx->check_safety_2($firstchar == 45, "SafeMath: Negative values not supported!");
		if($hex){
			$value = strtolower($value);
		} else{
			$ctx->check_safety(is_numeric($value), "SafeMath: invalid value!");
			$ctx->check_safety_2($firstchar == 48 && $value != "0" && !$hex, "SafeMath: Octal values not accepted!");
			$ctx->check_safety_2(is_int(stripos($value, "e")), "SafeMath: Exponential form not supported!");
			$ctx->check_safety_2(is_int(stripos($value, "E")), "SafeMath: Exponential form not supported!");
		}
		
		$ctx->check_safety_2(is_int(stripos($value, ".")), "SafeMath: Decimals are not supported!");
		
		
		
		
		$lookup = $value;
		if(is_null($maxvalue)){
			$lookup = $lookup . "_inf";
			$ctx->covmark(42);
		} else{
			$ctx->covmark(43);
			if($maxvalue->gmp){
				$lookup = $lookup . "_gmp_";
			} else{
				$lookup = $lookup . "_bcmath_";
			}
			$lookup = $lookup . strval($maxvalue);
		}
		
		
		
		if(function_exists("gmp_add")){
			$lookup = hash("sha256", "gmp_" . $lookup);
			$temp;
			if(array_key_exists($lookup, $ctx->SafeMathDedupCache)){
				$ctx->covmark(44);
				$temp = $ctx->SafeMathDedupCache[$lookup];
			} else{
				$ctx->covmark(45);
				$temp = new OpenCEX_uint($ctx, gmp_init($value, $hex ? 16 : 10), true, $maxvalue, $exceed_message);
				$ctx->appendSafeMathCache($lookup, $temp);
			}
			return $temp;
		} else if(function_exists("bcadd")){
			if($hex){
				$ret = "0";
				$value = str_split($value);
				$value[1] = "0";
				
				foreach($value as $digit){
					switch($digit){
						case "a":
							$digit = "10";
							break;
						case "b":
							$digit = "11";
							break;
						case "c":
							$digit = "12";
							break;
						case "d":
							$digit = "13";
							break;
						case "e":
							$digit = "14";
							break;
						case "f":
							$digit = "15";
							break;
						default:
							$ctx->check_safety(is_numeric($digit), "SafeMath: Invalid hexadecimal value!");
							break;
					}
					$ret = bcadd(bcmul($ret, "16"), $digit);
				}
				
				return OpenCEX_uint::init($ctx, $ret, $maxvalue, $exceed_message);
				
			} else{
				$lookup = hash("sha256", "bcmath_" . $lookup);
				$temp;
				if(array_key_exists($lookup, $ctx->SafeMathDedupCache)){
					$ctx->covmark(44);
					$temp = $ctx->SafeMathDedupCache[$lookup];
				} else{
					$ctx->covmark(45);
					$temp = new OpenCEX_uint($ctx, $value, false, $maxvalue, $exceed_message);
					$ctx->appendSafeMathCache($lookup, $temp);
				}
				return $temp;
			}
		} else{
			$ctx->die2("SafeMath: Neither GMP or BCMath is available!");
		}
	}
	
	public function __toString(){
		$this->ctx->usegas(1, 46);
		if($this->gmp){
			return gmp_strval($this->value, 10);
		} else{
			return $this->value;
		}
	}
	
	public function comp(OpenCEX_uint $other){
		$this->ctx->usegas(1, 47);
		if($other->gmp == $this->gmp){
			if($this->gmp){
				return gmp_sign(gmp_sub($this->value, $other->value));
			} else{
				$tmp = bcsub($this->value, $other->value);
				if(substr($tmp, 0, 1) == "-"){
					return -1;
				} else if($tmp == "0"){
					return 0;
				} else{
					return 1;
				}
			}
		} else{
			if($this->gmp){
				return gmp_sign(gmp_sub($this->value, gmp_init($other->value, 10)));
			} else{
				return gmp_sign(gmp_sub(gmp_init($this->value, 10), $other->value));
			}
		}
	}
	
	public function check2(OpenCEX_uint $other){
		$this->ctx->usegas(1, 48);
		
		//We don't mark this one, since it's proven to be safe in previous tests.
		if(is_null($this->maxvalue)){
			$this->ctx->check_safety(is_null($other->maxvalue), "SafeMath: Value ceilings do not match!");
		} else{
			$this->ctx->check_safety_2(is_null($other->maxvalue), "SafeMath: Value ceilings do not match!");
			$this->ctx->check_safety($this->maxvalue->comp($other->maxvalue) == 0, "SafeMath: Value ceilings do not match!");
		}
	}
	
	public function add(OpenCEX_uint $other){
		$this->ctx->usegas(1, 49);
		$this->check2($other);
		if($other->gmp == $this->gmp){
			if($this->gmp){
				return new OpenCEX_uint($this->ctx, gmp_add($this->value, $other->value), true, $this->maxvalue, "SafeMath: Addition Overflow!");
			} else{
				return new OpenCEX_uint($this->ctx, bcadd($this->value, $other->value), false, $this->maxvalue, "SafeMath: Addition Overflow!");
			}
		} else{
			if($this->gmp){
				return new OpenCEX_uint($this->ctx, gmp_add($this->value, gmp_init($other->value, 10)), true, $this->maxvalue, "SafeMath: Addition Overflow!");
			} else{
				return new OpenCEX_uint($this->ctx, gmp_add(gmp_init($this->value, 10), $other->value), true, $this->maxvalue, "SafeMath: Addition Overflow!");
			}
		}
	}
	
	public function mul(OpenCEX_uint $other){
		$this->ctx->usegas(1, 50);
		$this->check2($other);
		if($other->gmp == $this->gmp){
			if($this->gmp){
				return new OpenCEX_uint($this->ctx, gmp_mul($this->value, $other->value), true, $this->maxvalue, "SafeMath: Multiplication Overflow!");
			} else{
				return new OpenCEX_uint($this->ctx, bcmul($this->value, $other->value), false, $this->maxvalue, "SafeMath: Multiplication Overflow!");
			}
		} else{
			if($this->gmp){
				return new OpenCEX_uint($this->ctx, gmp_mul($this->value, gmp_init($other->value, 10)), true, $this->maxvalue, "SafeMath: Multiplication Overflow!");
			} else{
				return new OpenCEX_uint($this->ctx, gmp_mul(gmp_init($this->value, 10), $other->value), true, $this->maxvalue, "SafeMath: Multiplication Overflow!");
			}
		}
	}
	
	public function sub(OpenCEX_uint $other, string $overflow_message = "SafeMath: Subtraction Overflow!"){
		$this->ctx->usegas(1, 51);
		$this->check2($other);
		if($other->gmp == $this->gmp){
			if($this->gmp){
				$tmp = gmp_sub($this->value, $other->value);
				$this->ctx->check_safety_2(gmp_sign($tmp) == -1, $overflow_message);
				return new OpenCEX_uint($this->ctx, $tmp, true, $this->maxvalue);
			} else{
				$tmp = bcsub($this->value, $other->value);
				$this->ctx->check_safety_2(substr($tmp, 0, 1) == "-", $overflow_message);
				return new OpenCEX_uint($this->ctx, $tmp, false, $this->maxvalue);
			}
		} else{
			$tmp;
			if($this->gmp){
				$tmp = gmp_sub($this->value, gmp_init($other->value, 10));
			} else{
				$tmp = gmp_sub(gmp_init($this->value, 10), $other->value);
			}
			$this->ctx->check_safety_2(gmp_sign($tmp) == -1, $overflow_message);
			return new OpenCEX_uint($this->ctx, $tmp, true, $this->maxvalue);
		}
	}
	
	public function div(OpenCEX_uint $other){
		$this->check2($other);
		$str2 = strval($other);
		$this->ctx->check_safety_2($str2 == "0", "SafeMath: Division Overflow!");
		if($str2 == "1"){
			$this->ctx->covmark(52);
			return $this;
		} else{
			$this->ctx->covmark(53);
			if($other->gmp == $this->gmp){
				if($this->gmp){
					return new OpenCEX_uint($this->ctx, gmp_div_qr($this->value, $other->value)[0], true, $this->maxvalue);
				} else{
					return new OpenCEX_uint($this->ctx, bcdiv($this->value, $other->value, 0), false, $this->maxvalue);
				}
			} else{
				if($this->gmp){
					return new OpenCEX_uint($this->ctx, gmp_div_qr($this->value, gmp_init($other->value, 10))[0], true, $this->maxvalue);
				} else{
					return new OpenCEX_uint($this->ctx, gmp_div_qr(gmp_init($this->value, 10), $other->value)[0], true, $this->maxvalue);
				}
			}
		}
	}
	
	//No codecov annotations!
	public function mod(OpenCEX_uint $other){
		$this->check2($other);
		$str2 = strval($other);
		$this->ctx->check_safety_2($str2 == "0", "SafeMath: Modulo Overflow!");
		if($str2 == "1"){
			return $this;
		} else{
			if($other->gmp == $this->gmp){
				if($this->gmp){
					return new OpenCEX_uint($this->ctx, gmp_mod($this->value, $other->value), true, $this->maxvalue);
				} else{
					return new OpenCEX_uint($this->ctx, bcmod($this->value, $other->value, 0), false, $this->maxvalue);
				}
			} else{
				if($this->gmp){
					return new OpenCEX_uint($this->ctx, gmp_mod($this->value, gmp_init($other->value, 10)), true, $this->maxvalue);
				} else{
					return new OpenCEX_uint($this->ctx, gmp_mod(gmp_init($this->value, 10), $other->value), true, $this->maxvalue);
				}
			}
		}
	}
	
	public function min(OpenCEX_uint $other){
		$this->ctx->usegas(1, 30);
		$this->check2($other);
		if($this->comp($other) == 1){
			return $other;
		} else{
			return $this;
		}
	}
	
	public function max(OpenCEX_uint $other){
		$this->check2($other);
		if($this->comp($other) == 1){
			return $this;
		} else{
			return $other;
		}
	}
	
	public function tohex(bool $prefix = true){
		$str = strval($this);
		
		//Strip maximum value constraint
		$this2 = OpenCEX_uint::init($this->ctx, $str);
		$_16 = OpenCEX_uint::init($this->ctx, "16");
		$zero = OpenCEX_uint::init($this->ctx, "0");
		
		if($str == "0"){
			return $prefix ? "0x0" : "0";
		} else{
			$stringbuilder = [];
			while($this2->comp($zero) == 1){
				$str = strval($this2->mod($_16));
				switch($str){
					case "10":
						$str = "a";
						break;
					case "11":
						$str = "b";
						break;
					case "12":
						$str = "c";
						break;
					case "13":
						$str = "d";
						break;
					case "14":
						$str = "e";
						break;
					case "15":
						$str = "f";
						break;
					
				}
				array_push($stringbuilder, $str);
				$this2 = $this2->div($_16);
			}
			if($prefix){
				array_push($stringbuilder, "0x");
			}
			return implode(array_reverse($stringbuilder));

		}
	}
}


?>