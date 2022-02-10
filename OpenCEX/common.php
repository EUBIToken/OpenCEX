<?php
require_once("config.php");
require_once("assert_exception.php");

//The L1 context contains raw SQL query methods. It hides anything that
//we don't need from the L2 context, to maximize security
final class OpenCEX_L1_context{
	private ?mysqli $sql;
	private ?OpenCEX_L2_context $container;
	private int $itx_count = 0;
	public function destroy(bool $commit = true){
		//Commit or revert transaction
		if($commit){
			$this->container->check_safety_2(is_null($this->container), "L1 context already closed! (should not reach here)");
			$deferredthrow = null;
			try{
				$this->safe_query("COMMIT;");
			} catch (Exception $e){
				$deferredthrow = $e;
			}
			$this->container = null;
			$this->sql->close();
			if(!is_null($deferredthrow)){
				throw $deferredthrow;
			}
		} else{
			//no success checks since the transaction
			//would be reverted anyways after closing connection.
			$this->sql->query("ROLLBACK;");
			
			//Destroy MySQL connection
			if(!is_null($this->container)){
				//NOTE: if transaction commitment fails, we can end up calling
				//close on the SQL connection twice. So, we only call close if
				//the container is not disassociated from the L1 context.
								
				//Close SQL connection
				$this->sql->close();
				
				//Disassociate from container
				$this->container = null;
			}
		}
	}
	
	public function __construct(OpenCEX_L2_context $container){
		//Comsume an extra 100 gas, for the cost of database connection establishment.
		$container->usegas(100);
		$this->container = $container;
		$temp_sql = new mysqli($GLOBALS["OpenCEX_sql_servername"], $GLOBALS["OpenCEX_sql_username"], $GLOBALS["OpenCEX_sql_password"]);
		if ($temp_sql->connect_error) {
			$this->die2("MySQL connection error: " . $temp_sql->connect_error);
			return;
		} else{
			$this->sql = $temp_sql;
			
			try{
				$temp_sql->select_db("OpenCEX");
			} catch(Exception $e){
				if($e->getMessage() == "Unknown database 'opencex'"){
					//Refund gas for database setup
					$container->usegas(-500);
					$this->safe_query("CREATE DATABASE OpenCEX;");
					$temp_sql->select_db("OpenCEX");
					$this->safe_query("CREATE TABLE Accounts (UserID BIGINT NOT NULL AUTO_INCREMENT UNIQUE, Username VARCHAR(255) NOT NULL UNIQUE, Passhash VARCHAR(255) NOT NULL, DepositPrivateKey VARCHAR(64) UNIQUE NOT NULL, PRIMARY KEY (UserID));");
					$this->safe_query("CREATE TABLE Sessions (SessionTokenHash VARCHAR(64) NOT NULL UNIQUE, UserID BIGINT NOT NULL, Expiry BIGINT NOT NULL);");
					$this->safe_query("CREATE TABLE Balances (UserID BIGINT NOT NULL, Coin VARCHAR(255) NOT NULL, Balance VARCHAR(255) NOT NULL DEFAULT('0'));");
					$this->safe_query("CREATE TABLE Orders (Pri VARCHAR(255) NOT NULL, Sec VARCHAR(255) NOT NULL, Price VARCHAR(255) NOT NULL, Amount VARCHAR(255) NOT NULL, InitialAmount VARCHAR(255) NOT NULL DEFAULT '0', TotalCost VARCHAR(255) NOT NULL, Id BIGINT NOT NULL UNIQUE, PlacedBy BIGINT NOT NULL, Buy BIT NOT NULL);");
					$this->safe_query("CREATE TABLE Misc (Kei VARCHAR(255) NOT NULL UNIQUE, Val VARCHAR(255) NOT NULL);");
				} else{
					throw $e;
				}
			}
			
			//Setup database if not exist
			if($temp_sql->error == "Unknown database 'opencex'"){

			} else{
				$container->check_safety($temp_sql->error == "", "Unable to select MySQL database: " . htmlspecialchars($temp_sql->error));
			}
			
			//Begin MySQL transaction
			$container->check_safety($this->safe_query("START TRANSACTION;") === true, "MySQL BEGIN returned invalid status!");
		}
	}
	
	public function safe_query(string $query){
		$this->container->usegas(100);
		$sqlresult = null;
		try{
			$sqlresult = $this->sql->query($query);
		} catch(Exception $e){
			$this->container->die2("MySQL query error: " . $e->getMessage());
		}
		$this->container->check_safety($this->sql->error == "", "MySQL query error: " . $this->sql->error);
		$this->container->check_safety_2(is_null($sqlresult), "SQL Query returned invalid result!");
		return $sqlresult;
	}
	
	public function safe_execute_prepared($prepared){
		$this->container->usegas(100);
		try{
			$prepared->execute();
		} catch(Exception $e){
			$this->container->check_safety($this->sql->error == "", "MySQL prepared query error: " . $e);
		}
		
		$this->container->check_safety($this->sql->error == "", "MySQL prepared query error: " . $this->sql->error);
		return $prepared->get_result();
	}
	
	public function safe_prepare(string $template){
		$this->container->usegas(1);
		$temp = $this->sql->prepare($template);
		$this->container->check_safety($temp, "Unable to prepare MySQL query!");
		return $temp;
	}
	
	public function keepalive(){
		if(!is_null($this->container)){
			$this->container->usegas(1);
		}
	}
	
	public function get_safety_checker(){
		$this->container->usegas(1);
		return new OpenCEX_safety_checker($this->container);
	}
	
	//If we forget to commit or revert transaction, revert execution with error
	function __destruct(){
		if(!is_null($this->container)){
			$this->container->die2("MySQL connection still open after L1 context destruction!");
		}
	}
}

//Since many methods asks for a reference to the L2 context just to
//destroy it in case of failure, we created this simple wrapper to
//enforce the principle of least privilidge.
final class OpenCEX_safety_checker{
	private readonly OpenCEX_L2_context $underlying;
	public function __construct(OpenCEX_L2_context $underlying){
		$this->underlying = $underlying;
	}
	public function check_safety($predicate, string $message = "", int $id = 0){
		$this->underlying->usegas(1);
		if(!$predicate){
			$this->underlying->die2($message);
		}
	}
	
	public function check_safety_2($predicate, string $message = "", int $id = 0){
		$this->underlying->usegas(1);
		if($predicate){
			$this->underlying->die2($message);
		}
	}
	
	public function die2(string $message = ""){
		$this->underlying->die2($message);
	}
	
	public function usegas(int $amount, int $id = 0){
		$this->underlying->usegas($amount);
	}
	
	public function convcheck2($result, $key, int $id = 0){
		$this->usegas(1);
		$this->check_safety($result, "SQL Query returned invalid result!");
		$this->check_safety_2(is_null($key), "SQL Query returned invalid result!");
		$this->check_safety(is_array($result), "SQL Query returned invalid result!");
		$this->check_safety(array_key_exists($key, $result), "SQL Query returned invalid result!");
		$result = $result[$key];
		$this->check_safety_2(is_null($result), "SQL Query returned invalid result!");
		return $result;
	}
	
	public function destroy(){
		$this->underlying->finish_emitting(false);
	}
	
	//Only used in testing
	public function covmark(int $id = 0){
		
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

//The L2 context contain methods that need to make SQL queries
abstract class OpenCEX_L2_context{
	//Abstracts help isolate methods, enforcing the principle of least privilidge
	public abstract function get_active_session_token();
	public abstract function check_safety($predicate, string $message = "", int $id = 0);
	public abstract function check_safety_2($predicate, string $message = "", int $id = 0);
	public abstract function convcheck2($result, $key, int $id = 0);
	public abstract function usegas(int $amount, int $id = 0);
	public abstract function cleargas();
	public abstract function get_cached_user_id();
	
	private ?OpenCEX_L1_context $ctx = null;
	private bool $lock = false;
	
	public function begin_emitting(){
		$this->usegas(1);
		$this->check_safety_2($this->ctx, "MySQL server already connected!");
		$this->ctx = new OpenCEX_L1_context($this);
	}
	
	//Only used in testing
	public function covmark(int $id = 0){
		
	}
	
	public function finish_emitting(bool $commit = true){
		if($this->ctx){
			$this->check_safety_2($commit & $this->lock, "Commit not allowed with context locked!");
			$ctx = $this->ctx;
			$this->ctx = null;
			$this->lock = false;
			$ctx->destroy($commit);
			$ctx->keepalive();
		} else{
			//Reverting without SQL connected should be treated as a no-op
			$this->check_safety_2($commit, "MySQL server not connected!");
		}
	}
	
	//Commits all outstanding database changes
	public function flush_outstanding(){
		$this->usegas(1);
		$this->safe_query("COMMIT;");
		$this->safe_query("START TRANSACTION;");
	}
	
	//Reverts all outstanding database changes
	public function clear_outstanding(){
		$this->usegas(1);
		$this->safe_query("ROLLBACK;");
		$this->safe_query("START TRANSACTION;");
	}
	
	public function die2(string $msg = ""){
		//Set remaining gas to 0, so any further operations would
		//result in failure.
		$this->cleargas();
		//Destroy L1 context
		$this->finish_emitting(false);
		
		//Print error and stop processing request
		if($msg == ""){
			throw new OpenCEX_assert_exception("OpenCEX ran into an unknown error while processing your request!");
		} else{
			throw new OpenCEX_assert_exception(htmlspecialchars($msg));
		}
	}
	
	private function require_sql(){
		$this->usegas(1);
		$this->check_safety_2($this->lock, "MySQL queries disabled!");
		$this->check_safety($this->ctx, "MySQL server not connected!");
	}
	
	private function safe_query(string $query){
		//NOTE: We don't use gas here. It's already handled by the L1 context.
		$this->require_sql();
		return $this->ctx->safe_query($query);
	}
	
	private function safe_execute_prepared($prepared){
		//NOTE: We don't use gas here. It's already handled by the L1 context.
		$this->require_sql();
		return $this->ctx->safe_execute_prepared($prepared);
	}
	
	private function safe_prepare(string $template){
		//NOTE: We don't use gas here. It's already handled by the L1 context.
		$this->require_sql();
		return $this->ctx->safe_prepare($template);
	}
	
	public function borrow_sql($callable, ...$args){
		$this->usegas(1);
		$this->require_sql();
		
		$return = null;
		$deferredthrow = null;
		
		//NOTE: We disable SQL queries from the L2 context while exclusive
		//SQL access is granted to another function.
		$this->lock = true;
		
		try{
			$return = $callable($this->ctx, ...$args);
		} catch(Exception $e){
			$deferredthrow = $e;
		}
		if($this->lock){
			$this->lock = false;
		} else{
			//If the context gets unlocked by the borrower, and no exceptions are thrown, we assert.
			$this->check_safety_2(is_null($deferredthrow), "L2 context unlocked by SQL borrower (should not reach here)!");
		}
		if(is_null($deferredthrow)){
			return $return;
		} else{
			throw $deferredthrow;
		}
	}
	
	private string $cached_deposit_key = "";
	public function cached_eth_deposit_key(){
		if($this->cached_deposit_key == ""){
			$query = $this->safe_query("SELECT DepositPrivateKey FROM Accounts WHERE UserID = " . strval($this->get_cached_user_id()) . ";");
			$this->check_safety($query->num_rows == 1, "Corrupted user wallet database!");
			$this->cached_deposit_key = $this->convcheck2($query->fetch_assoc(), "DepositPrivateKey");
		}		
		return $this->cached_deposit_key;
	}
	
	
	public function emit_create_account(string $username, string $password){
		$this->usegas(1);
		
		//Create user account
		$prepared = $this->safe_prepare("INSERT INTO Accounts (Username, Passhash, DepositPrivateKey) VALUES (?, ?, ?)");
		$password = password_hash($password, PASSWORD_DEFAULT);
		$rng = str_pad(bin2hex(random_bytes(32)), 64, "0", STR_PAD_LEFT);
		$prepared->bind_param("sss", $username, $password, $rng);
		$this->safe_execute_prepared($prepared);
		
		
		//Get user ID
		$prepared = $this->safe_prepare("SELECT UserID FROM Accounts WHERE Username = ?");
		$prepared->bind_param("s", $username);
		$result = $this->safe_execute_prepared($prepared);
		
		//Safety checking
		$this->check_safety(intval($result->num_rows) == 1, "Account creation error: database corrupted!");
		$result = $this->convcheck2($result->fetch_assoc(), "UserID");		
		$intuserid = intval($result);
		$this->check_safety_2($intuserid == 0, "Account creation error: user id must be a non-zero integer!");
				
		//Return user id
		return $intuserid;
	}
	
	public function check_valid_account(int $userid){
		$this->usegas(1);
		if($userid === 0){
			return false;
		} else{				
			//No risk of SQL injection, since the only argument is an integter
			$result = $this->safe_query(implode(["SELECT COUNT(UserID) FROM Accounts WHERE UserID = '", strval($userid), "';"]));
			$result = $this->convcheck2($result->fetch_assoc(), "COUNT(UserID)");
			
			return $result === "1";
		}
	}
	
	//Partially-privilidged function stub
	protected function session_create_query(string $session_token, int $userid, int $expiry){
		$this->usegas(1);
		//No risk of SQL injection, since we are only using trusted data
		//NOTE: According to OWASP, we don't store session tokens in our database
		//instead, we store the hash of session tokens in our database!
		$this->safe_query(implode(["INSERT INTO Sessions (SessionTokenHash, UserID, Expiry) VALUES ('", hash("sha256", $session_token), "', ", strval($userid), ", ", strval($expiry), ");"]));
	}
	
	public function get_active_session($session = null){
		$this->usegas(1);
		if(!$session){
			$session = $this->get_active_session_token();
		}

		if($session){
			//Authenticate session token
			$result = $this->safe_query(implode(["SELECT UserID, Expiry FROM Sessions WHERE SessionTokenHash = '", hash("sha256", $session), "';"]));
			
			//Safety checking
			if(intval($result->num_rows) != 1){
				return 0;
			}
			
			$result = $result->fetch_assoc();				
			
			//Check if account is valid (not deleted)
			$userid = intval($this->convcheck2($result, "UserID"));
			if(!$this->check_valid_account($userid)){
				return 0;
			}
			//Check if session is valid (not expired)
			if(time() > intval($this->convcheck2($result, "Expiry"))){
				return 0;
			}
			
			return $userid;
		}
		
		return 0;
	}
	
	public function try_destroy_all_other_sessions(){
		$this->usegas(1);
		$session = $this->get_active_session_token();
		$userid = strval($this->get_active_session($session));
		
		if($session && $userid !== "0"){
			//Destroy all other sessions on server side only, NOTE: no risk of SQL injection
			$result = $this->safe_query(implode(["DELETE FROM Sessions WHERE UserID = ", $userid, " AND SessionTokenHash != '", hash("sha256", $session), "';"]));
		}
	}
	
	public function destroy_active_session(){
		$this->usegas(1);
		$session = $this->get_active_session_token();
		
		if($session){
			//Destroy session token on server side, NOTE: no risk of SQL injection
			$result = $this->safe_query(implode(["DELETE FROM Sessions WHERE SessionTokenHash = '", hash("sha256", $session), "';"]));
		}
		
		//Destroy session token on client side
		//NOTE: no failure checking, since the session would be destroyed on the server side anyways!
		setcookie("OpenCEX_session", "", 1, "", $GLOBALS["OpenCEX_host"], $GLOBALS["OpenCEX_secure"], true);
	}
	
	protected abstract function finalize_logon($prepared, string $password, bool $persistent);
	
	public function login_user(string $username, string $password, bool $persistent = false){
		$this->usegas(1);
		$prepared = $this->safe_prepare("SELECT UserID, Passhash FROM Accounts WHERE Username = ?;");
		$prepared->bind_param("s", $username);
		$this->finalize_logon($this->safe_execute_prepared($prepared), $password, $persistent);
	}
	
	protected function query_user_name(int $userid){
		$this->usegas(1);
		$result = $this->safe_query(implode(["SELECT Username FROM Accounts WHERE UserID = ", $userid, ";"]));
		
		//Safety checking
		$this->check_safety(intval($result->num_rows) === 1, "User does not exist!");
		
		return $this->convcheck2($result->fetch_assoc(), "Username");
	}
	
	public function unlock_tables(){
		$this->usegas(1);
		$result = $this->safe_query("UNLOCK TABLES;");
	}
}

//L3 context contains methods that doesn't need to access
//the database directly. It have the least privilidge when
//compared to the L1 and L2 contexts.
final class OpenCEX_L3_context extends OpenCEX_L2_context {
	//We use an Ethereum-like system for DOS protection.
	private int $remaining_gas = 2000;
	public function usegas(int $amount, int $id = 0){
		$this->check_safety($this->remaining_gas > $amount, "Insufficent gas!");
		$this->remaining_gas -= $amount;
	}
	public function cleargas(){
		$this->remaining_gas = 0;
	}
	
	protected function finalize_logon($prepared, string $password, bool $persistent){
		$this->usegas(1);			
		//Safety checking
		$this->check_safety(intval($prepared->num_rows) == 1, "User does not exist!");
		$prepared = $prepared->fetch_assoc();

		//Check if session is valid (not expired)
		if(!password_verify($password, $this->convcheck2($prepared, "Passhash"))){
			$this->die2("Incorrect password!");
		}
		
		$this->session_create(intval($this->convcheck2($prepared, "UserID")), $persistent);
	}
	
	public function get_active_session_token(){
		$this->usegas(1);
		if(array_key_exists("OpenCEX_session", $_COOKIE)){
			return base64_decode($_COOKIE["OpenCEX_session"], true);
		} else{
			return false;
		}
	}
	
	//These safety checking functions uses gas in a way that is slightly funny
	//They don't use gas, unless called via the safety wrapper.
	public function check_safety($predicate, string $message = "", int $id = 0){
		if(!$predicate){
			$this->die2($message);
		}
	}
	
	public function check_safety_2($predicate, string $message = "", int $id = 0){
		if($predicate){
			$this->die2($message);
		}
	}
	
	public function convcheck2($result, $key, int $id = 0){
		$this->usegas(1);
		$this->check_safety($result, "SQL Query returned invalid result!");
		$this->check_safety_2(is_null($key), "SQL Query returned invalid result!");
		$this->check_safety(is_array($result), "SQL Query returned invalid result!");
		$this->check_safety(array_key_exists($key, $result), "SQL Query returned invalid result!");
		$result = $result[$key];
		$this->check_safety_2(is_null($result), "SQL Query returned invalid result!");
		return $result;
	}
	
	public function session_create(int $userid, bool $persistent = false){
		$this->usegas(1);
		$this->check_safety($this->check_valid_account($userid), "Invalid user id!");
		
		$session_token = random_bytes(64);
		$expiry = intval(time() + 86400);
		
		//Persistent sessions last 1 month, while transient sessions last 24 hours
		if($persistent){
			$expiry += 2505600;
		}
		
		//Call privilidged method
		$this->session_create_query($session_token, $userid, $expiry);
		
		if(!$persistent){
			$expiry = 0;
		}
		
		$this->check_safety(setcookie("OpenCEX_session", base64_encode($session_token), $expiry, "", $GLOBALS["OpenCEX_host"], $GLOBALS["OpenCEX_secure"], true), "Unable to set session cookie!");
	}
	
	private int $cached_user_id = -1;
	public function get_cached_user_id(){
		$this->usegas(1);
		if($this->cached_user_id == -1){
			$this->cached_user_id = $this->get_active_session();
		}
		return $this->cached_user_id;
	}
	
	private string $cached_user_name = "";
	public function get_cached_username(){
		$this->usegas(1);
		if($this->cached_user_name === ""){
			$userid = $this->get_cached_user_id();
			if($userid > 0){
				$this->cached_user_name = $this->query_user_name($userid);
			}
		}
		return $this->cached_user_name;
	}
}
?>