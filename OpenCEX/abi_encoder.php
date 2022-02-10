<?php
require_once("SafeMath.php");
final class OpenCEX_abi_encoder{
	private readonly OpenCEX_safety_checker $ctx;	
	function __construct(OpenCEX_safety_checker $ctx){
		$ctx->usegas(1);
		$this->ctx = $ctx;
	}
	
	public function chkvalidaddy(string $address, bool $encode = true){
		$this->ctx->usegas(1);
		$this->ctx->check_safety(strlen($address) == 42, "Address length must be 42 characters!");
		$this->ctx->check_safety(substr($address, 0, 2) == "0x", "Address must start with '0x'!");
		$address = str_split(strtolower($address));
		$address[1] = "0";
		foreach($address as $char){
			switch($char){
				case "a":
				case "b":
				case "c":
				case "d":
				case "e":
				case "f":
					break;
				default:
					$this->ctx->check_safety(is_numeric($char), "Invalid address!");
					break;
			}
		}
		if($encode){
			return "0000000000000000000000" . implode($address);
		}
	}
	public function encode_erc20_transfer(string $address, OpenCEX_uint $amount){
		$this->ctx->usegas(1);
		$amtstr = $amount->tohex(false);
		$this->ctx->check_safety(strlen($amtstr) < 65, "ERC-20 transfer amount overflow!");
		
		return implode(["0xa9059cbb", $this->chkvalidaddy($address), str_pad($amtstr, 64, "0", STR_PAD_LEFT)]);
	}
	public function encode_erc20_balanceof(string $address){
		$this->ctx->usegas(1);
		return "0x70a08231" . $this->chkvalidaddy($address);
	}
}
?>