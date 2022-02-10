<?php
$OpenCEX_common_impl = "tests/stubs.php";
require_once("../wallet_manager.php");
require_once("../blockchain_manager.php");
require_once("stubs.php");
$ctx = new OpenCEX_safety_checker(new OpenCEX_L2_context(0, 0));
$wallet = new OpenCEX_WalletManager($ctx, new OpenCEX_BlockchainManagerWrapper($ctx, new OpenCEX_BlockchainManager($ctx, 137, "https://speedy-nodes-nyc.moralis.io/41590f438df3f8018a1e84b1/polygon/mainnet")));
echo($wallet->balanceOf("0x2791bca1f2de4661ed88a30c99a7a9449aa84174"));


?>