<?php
	require_once("../config.php");
	//NOTE: this is the back-up mean of logging out, if normal logout fails
	setcookie("OpenCEX_session", "", 1, "", $GLOBALS["OpenCEX_host"], $GLOBALS["OpenCEX_secure"], true);
?>