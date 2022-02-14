# OpenCEX: Open-source cryptocurrency exchange

As part of our transparency policy, we let customers view the source code of our cryptocurrency exchange.

## Roadmap

1. Proof-of-concept test launch 1
2. Proof-of-concept test launch 2
3. Main launch

## To-do list

### Phrase 1 - before test launch 1
~~1. Core features (accounts, trading, deposits, and withdrawals)~~

### Phrase 2 - before test launch 2
1. List partner tokens
2. Price charts
3. Add new account settings
4. Add new security features

### Phrase 3 - before main launch
1. Major code clean-ups
2. Security hardening

## FAQs

### Why do you have test launches?
Our testers need to test the exchange. Also, it's a chance for you to test it as well.

### What happens if theft of funds occour?
If less than 10% of all customer funds is lost, operations will be suspended until we got the vulnerability patched. Then, we will resume trading as usual. No funds will be debited from customer accounts. This may sound scary, but here's the truth. The loss of our hot wallet would reduce our reserve ratio to 90%, while banks are allowed to have reserve ratios as small as 1%, and Defi lending protocols have a reserve ratio of 20%.

If more than 10% of all customer funds is lost, we do the same as above, but we debit the loss to customer accounts proportionally and replace it with loss marker tokens until we can recover the lost funds. We may impose or increase fees to make back this loss. For example, if we lost 20% of all customer funds, then we will debit 20% from all customer accounts, and replace it with loss marker tokens.

If we lost customer funds to blockchain or smart contract bugs, we will permanently stop trading and deposits of the affected cryptocurrency. Then, we will debit the loss to customer accounts proportionally. Customers can then withdraw whatever is left on the exchange.

We periodically move funds between the exchange hot wallet and our cold wallet. We try to keep 90% of all customer funds in our cold wallet.

### What if I forgot my password?
If you forgot your password during the test launches, your funds are lost forever, but during the main launch, you can reset your password using one of your linked account recovery modes.

### What are your listing requirements?
1. Listing fee: 50 MATIC (not required for extremely serious and partner projects such as Bitcoin and PolyEUBI).
2. Verified contract source code (not required for tokens deployed using MintME.com).
3. Legitimate and serious project (no scamcoins, memecoins, or shitcoins).

Extremely serious cryptocurrencies, such as Ethereum, may be listed without contacting the team, free of charge.

### Why no trading fees?
Because we have advertisements, and trading fees are bad for liquidity.

### Can you explain the 3 order types?
1. Limit order: An order to buy or sell at a specific price or better. This is the only order type that comes with a minimum order size, and the only order type that is ever admitted to the order book on the Jessie Lesbian Cryptocurrency Exchange.
2. Immediate or cancel: An order to buy or sell that must be executed immediately. Any portion of an immediate or cancel order that cannot be filled immediately will be cancelled. Immediate or cancel orders are useful for arbitrage trades, and they have no minimum order size.
3. Fill or kill: An order to buy or sell that must be executed immediately in its entirety; otherwise, the entire order will be cancelled. The're is no minimum order size for fill or kill orders.

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
