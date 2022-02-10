"use strict";
let _main = async function(){
	let tempfunc;
	{
		const map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		tempfunc = function(text) {
			return text.replace(/[&<>"']/g, function(m) { return map[m]; });
		};
	}

	const escapeHTML = tempfunc;
	
	{
		tempfunc = function(text) {
			const map = {
				'"': '\\";',
				'\\': '\\\\',
				'\/': '\\/',
				'\b': '\\b',
				'\f': '\\f;',
				'\n': '\\n',
				'\r': '\\r',
				'\t': '\\t',


			};
			return text.replace(/[\"\\\/\b\f\n\r\t]/g, function(m) { return map[m]; });
		};
	}

	const escapeJSON = tempfunc;
	{
		const domcache = [];
		tempfunc = function(elem) {
			if(domcache[elem]){
				return domcache[elem];
			} else{
				const temp = document.getElementById(elem);
				domcache[elem] = temp;
				return temp;
			}
		};
	}
	const smartGetElementById = tempfunc;
	tempfunc = undefined;
	
	const toast = async function(text){
		M.toast({html: text});
	};
	
	const prepxhtp = function(){
		const xhttp = new XMLHttpRequest();
		xhttp.open("POST", "RequestManager.php", true);
		xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		xhttp.addEventListener('error', async function(){
			toast("Server connection failed!");
		});
		return xhttp;
	};
	
	//BEGIN DOM BINDINGS
	let callIfExists = async function(elem, func){
		const temp = smartGetElementById(elem);
		if(temp){
			func(temp);
		}
	};
	let bindIfExists = async function(elem, func){
		callIfExists(elem, async function(e2){
			e2.onclick = func;
		});
	};
	
	const bindResponseValidatorAndCall = async function(data, callback){
		const xhttp = prepxhtp();
		xhttp.addEventListener('load', async function(){
			let decoded_list = undefined;
			try{
				decoded_list = JSON.parse(this.responseText);
			} catch{

			}
			
			if(decoded_list){
				if(decoded_list.returns){
					callback(decoded_list.returns);
				} else{
					if(decoded_list.reason){
						toast("Server returned error: " + escapeHTML(decoded_list.reason));
					} else{
						toast("Server returned unknown error!");
					}
				}
			} else{
				toast("Server returned invalid data!");
			}
		});
		
		xhttp.send(data);
	};
	
	bindIfExists("requestShitcoinButton", async function(){
		bindResponseValidatorAndCall("OpenCEX_request_body=%5B%7B%22method%22%3A%20%22get_test_tokens%22%7D%5D", async function(){
			toast("Operation completed successfully! reloading...");
			document.location.href = document.location.href;
		});		
	});
	
	callIfExists("has_preloads", async function(){
		const registeredPreloads = [];
		const registeredPreloadFormatters = [];
		const preloadIfExists = async function(name, formatter = escapeHTML){
			callIfExists("preloaded_" + name, async function(){
				registeredPreloads.push({method: name});
				registeredPreloadFormatters.push(formatter);
			});
		};
		
		preloadIfExists("client_name", function(e){
			return ["Hi, ", escapeHTML(e), "!"].join("");
		});
		preloadIfExists("eth_deposit_address", function(e){
			return ["Please send funds to this deposit address: ", escapeHTML(e), "!"].join("");
		});
		
		if(registeredPreloads.length != 0){
			//preload everything
			bindResponseValidatorAndCall("OpenCEX_request_body=" + encodeURIComponent(JSON.stringify(registeredPreloads)), async function(decoded_list){
				for(let i = 0; i < registeredPreloads.length; i++){
					smartGetElementById("preloaded_" + registeredPreloads[i].method).innerHTML = registeredPreloadFormatters[i](decoded_list[i]);
				}
			});

		}
	});
	
	//BEGIN ACCOUNT MANAGEMENT FUNCTIONS
	{
		const registerOrLogin = async function(register){
			const password = smartGetElementById("password").value;
			let method = "login";
			let renemberExtras = "";
			if(register){
				method = "create_account";
				if(password != smartGetElementById("password2").value){
					toast("Passwords do not match!");
					return;
				}
			} else{
				renemberExtras = smartGetElementById("renemberme").value ? ', "renember": true' : ', "renember": false';
			}
			const captcha = smartGetElementById("OpenCEX-captcha-result").getElementsByTagName("textarea")[0].value;
			if(captcha == ""){
				toast("Please solve the captcha!");
				return;
			}
			
			const xhttp = prepxhtp();
			xhttp.addEventListener('load', async function(){
				let decoded_list = undefined;
				try{
					decoded_list = JSON.parse(this.responseText);
				} catch{

				}
				
				if(decoded_list){
					if(decoded_list.returns){
						toast("Operation completed successfully! redirecting to client area...");
						document.location.href = "clientarea.html";
					} else{
						if(decoded_list.reason){
							toast("Server returned error: " + escapeHTML(decoded_list.reason));
							return;
						} else{
							toast("Server returned unknown error!");
							return;
						}
					}
				} else{
					toast("Server returned invalid data!");
					return;
				}
			});
			
			xhttp.send("OpenCEX_request_body=" + encodeURIComponent(['[{"method": "', method, '", "data": {"captcha": "', escapeJSON(captcha), '", "username": "', escapeJSON(smartGetElementById("username").value), '", "password" : "', escapeJSON(password), '"', renemberExtras + '}}]'].join("")));
		};
		
		bindIfExists("accountRegistrationButton", async function(){
			registerOrLogin(true);
		});
		
		bindIfExists("loginButton", async function(){
			registerOrLogin(false);
		});
		
		bindIfExists("logoutButton", async function(){
			const xhttp = prepxhtp();
			xhttp.addEventListener('load', async function(){
				let decoded_list = undefined;
				try{
					decoded_list = JSON.parse(this.responseText);
				} catch{

				}
				
				if(decoded_list){
					if(decoded_list.returns){
						toast("Operation completed successfully! redirecting to home page...");
						document.location.href = "index.html";
					} else{
						//Alternate logout only destroys the session cookie on the client side, if the server
						//is malfunctioning. It is used if the server is not working properly.
						toast("Server returned error, attempting alternate logout...");
						const logout = new XMLHttpRequest();
						logout.open("POST", "quick_destroy_session.php", true);
						logout.addEventListener('load', async function(){
							toast("Operation completed successfully! redirecting to home page...");
							document.location.href = "index.html";
						});
						logout.addEventListener('error', async function(){
							toast("Alternate logout failed, please clear your cookies!");
						});
						logout.send();
					}
				} else{
					toast("Server returned invalid data!");
					return;
				}
			});
			
			xhttp.send('OpenCEX_request_body=%5B%7B%22method%22%3A%20%22logout%22%7D%5D');
		});
	}	
	//END ACCOUNT MANAGEMENT FUNCTIONS
	
	//BEGIN TRADING FUNCTIONS
	
	callIfExists("trading_room", async function(){
		//Unload unused Web3 modules
		const copied_web3_conv2wei = Web3.utils.toWei;
		Web3 = undefined;
		
		let selected_pri = "shitcoin";
		let selected_sec = "scamcoin";
		let bindPair = async function(primary, secondary){
			smartGetElementById(["pair_selector", primary, secondary].join("_")).onclick = async function(){
				selected_pri = primary;
				selected_sec = secondary;
			};
		};
		
		//BEGIN trading pair registrations
		
		bindPair("shitcoin", "scamcoin");
		
		//END trading pair registrations
		
		bindPair = undefined;
		
		smartGetElementById("placeOrderButton").onclick = async function(){
			bindResponseValidatorAndCall('OpenCEX_request_body=' + encodeURIComponent(['[{"method": "place_order", "data": {"primary": "', escapeJSON(selected_pri), '", "secondary": "', escapeJSON(selected_sec), '", "price": "', escapeJSON(copied_web3_conv2wei(smartGetElementById("order_price").value)), '", "amount": "', escapeJSON(copied_web3_conv2wei(smartGetElementById("order_amount").value)), '", "buy": ', smartGetElementById("buy_order_selector").checked.toString(), ', "fill_mode": ', escapeJSON(smartGetElementById("fill_mode_selector").value), '}}]'].join("")), async function(){
				toast("Order placed successfully!");
			});
		};
	});
	callIfExists("balances_manager", async function(){
		//Unload unused Web3 modules
		const copied_web3_conv2wei = Web3.utils.toWei;
		const copied_web3_conv2dec = Web3.utils.fromWei;
		Web3 = undefined;
		
		//Load user balances
		bindResponseValidatorAndCall("OpenCEX_request_body=%5B%7B%22method%22%3A%20%22balances%22%7D%5D", async function(e){
			e = e[0];
			if(e.length == 0){
				return "";
			} else{
				const temp = [];
				const depositableTokens = ["MATIC", "MintME"];
				for(let i = 0; i < e.length; i++){
					const stri = i.toString();
					let token4 = e[i][0];
					const canuse = depositableTokens.lastIndexOf(token4) > -1;
					const depositModeSelector = canuse ? 'modal-trigger" href="#depositModal' : 'disabled';
					const withdrawModeSelector = canuse ? 'modal-trigger" href="#withdrawModal' : 'disabled';
					const token3 = escapeHTML(token4);
					temp.push(['<tr class="row"><td class="col s4">', token3, '</td><td class="col s4">', escapeHTML(copied_web3_conv2dec(e[i][1])), '</td><td class="col s4 row"><button id="deposit_button_', stri, '" class="col s6 btn btn-small waves-effect ', depositModeSelector , '" data-deposit-token="', token3, '">deposit</button><button data-withdrawal-token="', token3, '" class="col s6 btn btn-small waves-effect ', withdrawModeSelector, '" id="withdraw_button_', stri, '">withdraw</button></td></tr>'].join(""));
				}
				
				smartGetElementById("preloaded_balances").innerHTML = temp.join("");
				const addy = smartGetElementById("withdraw_address");
				const amt = smartGetElementById("withdraw_amount");
				for(let i = 0; i < e.length; i++){
					const stri = i.toString();
					smartGetElementById("deposit_button_" + stri).onclick = async function(){
						const token = this.dataset.depositToken;
						const token2 = escapeJSON(token);
						
						smartGetElementById("FinalizeTokenDeposit").onclick = async function(){
							bindResponseValidatorAndCall("OpenCEX_request_body=" + encodeURIComponent(['[{"method": "deposit", "data": {"token": "', token2, '"}}]'].join("")), async function(){
								toast("deposit completed!");
							});
						};
					};
					smartGetElementById("withdraw_button_" + stri).onclick = async function(){
						const token = this.dataset.withdrawalToken;
						const token2 = escapeJSON(token);
						
						smartGetElementById("FinalizeTokenWithdrawal").onclick = async function(){
							bindResponseValidatorAndCall("OpenCEX_request_body=" + encodeURIComponent(['[{"method": "withdraw", "data": {"token": "', token2, '", "address": "', escapeJSON(addy.value), '", "amount": "', escapeJSON(copied_web3_conv2wei(amt.value)), '"}}]'].join("")), async function(){
								toast("withdrawal completed!");
							});
						};
					};
				}
			}
		});
			

	});
	
	//END TRADING FUNCTIONS
	
	//END DOM BINDINGS
	callIfExists = undefined;
	bindIfExists = undefined;
	
	M.AutoInit();
};

if (/complete|interactive|loaded/.test(document.readyState)) {
	// In case the document has finished parsing, document's readyState will
	// be one of "complete", "interactive" or (non-standard) "loaded".
	_main();
} else {
	// The document is not ready yet, so wait for the DOMContentLoaded event
	document.addEventListener('DOMContentLoaded', _main, false);
}
_main = undefined;