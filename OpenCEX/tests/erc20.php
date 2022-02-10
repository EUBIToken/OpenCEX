<?php
$OpenCEX_common_impl = "tests/stubs.php";
require_once("../SafeMath.php");
require_once("../abi_encoder.php");

$ctx = new OpenCEX_safety_checker(new OpenCEX_L2_context(0, 0));
$encoder = new OpenCEX_abi_encoder($ctx);

echo($encoder->encode_erc20_transfer("0xc098b1bb56b497beae71dd3c07d3e6991c124cf7", OpenCEX_uint::init($ctx, "0xab45c3a")) . "\n");
echo("0xa9059cbb000000000000000000000000c098b1bb56b497beae71dd3c07d3e6991c124cf7000000000000000000000000000000000000000000000000000000000ab45c3a");

?>