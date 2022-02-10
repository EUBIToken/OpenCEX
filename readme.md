# OpenCEX: Open-source cryptocurrency exchange

As part of our transparency policy, we let customers view the source code of our cryptocurrency exchange.

### NOTE: This code can't be used, since it lacks critical configuration files. Here's what those files are supposed to contain

````OpenCEX/config.php````

````php
<?php
//MySQL server config
$OpenCEX_sql_servername = "localhost";
$OpenCEX_sql_username = "root";
$OpenCEX_sql_password = "";

//Cookies config
$OpenCEX_host = "localhost";
$OpenCEX_secure = false;

//Captcha config
$OpenCEX_raincaptcha_secret = "lXcqY6puVt1tbqr1f7_gjy8WVRgl_nDB";
$OpenCEX_localhost_override = "138.199.21.199";

//Trading config
$OpenCEX_whitelisted_pairs = ["shitcoin_scamcoin"];

//NOTE: minimum limit order don't apply to immediate or cancel and fill or kill orders.
$OpenCEX_minimum_limit = ["shitcoin" => "1000000000000000000", "scamcoin" => "1000000000000000000"];

$OpenCEX_tokens = ["shitcoin", "scamcoin", "MATIC", "MintME"];
?>

````

````OpenCEX/wallet.php````

````php
<?php
//NOTE: Wallet config is seperate from other configs.
$OpenCEX_wallet_key = "9dba2524076fa5be4c7c0904366a6edf074589f874812ba16e9b9df321df02a3";
?>
````
