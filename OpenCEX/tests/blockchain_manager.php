<?php
$OpenCEX_common_impl = "tests/stubs.php";
require_once("../blockchain_manager.php");

$ctx = new OpenCEX_safety_checker(new OpenCEX_L2_context(0, 0));

$blockchain_manager = new OpenCEX_BlockchainManager($ctx, 137, "https://speedy-nodes-nyc.moralis.io/41590f438df3f8018a1e84b1/polygon/mainnet");
$batch_manager = new OpenCEX_BatchRequestManager($ctx);
$wrapper = new OpenCEX_BlockchainManagerWrapper($ctx, $batch_manager);
$wrapper->net_version();
echo($batch_manager->execute($blockchain_manager)[0]);

?>