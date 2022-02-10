<?php
$OpenCEX_common_impl = "tests/stubs.php";
require_once("../SafeMath.php");

$ctx = new OpenCEX_safety_checker(new OpenCEX_L2_context(0, 0));

for($i = 0; $i < 256; $i++){
	echo (OpenCEX_uint::init($ctx, strval($i))->tohex() . "\n");
}
for($i = 0; $i < 256; $i++){
	echo (OpenCEX_uint::init($ctx, "0x" . dechex($i)) . "\n");
}

?>