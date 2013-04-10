<?php

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
}
// Force all warnings into errors
set_error_handler("exception_error_handler");

$base = dirname(__FILE__);

if (file_exists("$base/../config.php")) {
	die("Your config.php is already setup, if you want to reinstall please delete it.");
}

echo "
We will prompt you with a few questions.  If at any time you are unsure and want to
back out of the installation hit CTRL+C.

Questions will always have a default answer specified in []'s.  Example:
What is 1+1? [2] 

Hitting enter will let you select the default answer.

Some database questions:
";

$settings = array();
$settings["dbuser"] = prompt("Database username?", "zkillboard");
$settings["dbpassword"] = prompt("Database password?", "zkillboard");
$settings["dbname"] = prompt("Database name?", "zkillboard");
$settings["dbhost"] = prompt("Database server?", "localhost");

echo "\nSome memcache questions:\n";

$settings["memcache"] = prompt("Memcache server?", "localhost");
$settings["memcacheport"] = prompt("Memcache port?", "11211");

echo "\nAnd now what is the address of your server?  Just use the domain name!  e.g. zkillboard.com\n";
$settings["baseaddr"] = prompt("Domain name?", "zkillboard.com");

$settings["logfile"] = prompt("Log file location?", "/var/log/zkb.log");

$configFile = file_get_contents("$base/config.new.php");

foreach($settings as $key=>$value) {
	$configFile = str_replace("%$key%", $value, $configFile);
}

// Save the file and then attempt to load and initialize from that file
$configLocation = "$base/../config.php";
if (file_put_contents($configLocation, $configFile) === false) die("Unable to write configuration file at $configLocation\n");

try {
	echo "Config file written, now attempting to initialize settings\n";
	require_once "$base/../init.php";
	echo "Settings initialized, now attempting to connect to the database and memcached\n";
	$one = Db::queryField("select 1 one from dual", "one", array(), 1);
	if ($one != "1") throw new Exception("We were able to connect but the database did not return the expected '1' for: select 1 one from dual;");
	
	echo "\n\nSuccess!\n\n";
} catch (Exception $ex) {
	echo "\n\nError!  Removing configuration file.\n";
	unlink($configLocation);
	throw $ex;
}

// Now install the db structure
try {
	$sqlFiles = scandir("$base/sql");
	foreach($sqlFiles as $file) {
		if (Util::endsWith($file, ".sql.gz")) {
			$table = str_replace(".sql.gz", "", $file);
			echo "Adding table $table ... ";
			$sqlFile = "$base/sql/$file";
			loadFile($sqlFile);
			echo "done\n";
		}
	}
} catch (Exception $ex) {
	echo "\n\nError!  Removing configuration file.\n";
	unlink($configLocation);
	throw $ex;
}

function loadFile($file) {
	if (Util::endsWith($file, ".gz")) $handle = gzopen($file, "r");
	else $handle = fopen($file, "r");

	$query = "";
	while ($buffer = fgets($handle)) {
		$query .= $buffer;
		if (strpos($query, ";") !== false) {
			$query = str_replace(";", "", $query);
			Db::execute($query);
			$query = "";
		}
	}
	fclose($handle);
}


function prompt($prompt, $default = "") {
	echo "$prompt [$default] ";
	$answer = trim(fgets(STDIN));
	if (strlen($answer) == 0) return $default;
	return $answer;
}


// Password prompter kindly borrowed from http://stackoverflow.com/questions/187736/command-line-password-prompt-in-php
function prompt_silent($prompt = "Enter Password:") {
	$command = "/usr/bin/env bash -c 'echo OK'";
	if (rtrim(shell_exec($command)) !== 'OK') {
		trigger_error("Can't invoke bash");
		return;
	}
	$command = "/usr/bin/env bash -c 'read -s -p \""
		. addslashes($prompt)
		. "\" mypassword && echo \$mypassword'";
	$password = rtrim(shell_exec($command));
	echo "\n";
	return $password;
}
