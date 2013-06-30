<?php

$base = dirname(__FILE__);
require_once "$base/../init.php";

out("|g|Starting maintenance mode...|n|");
Db::execute("replace into zz_storage values ('maintenance', 'true')");
out("|b|Waiting 60 seconds for all executing scripts to stop...|n|");
sleep(60);

// Now install the db structure
try {
    $sqlFiles = scandir("$base/sql");
    foreach($sqlFiles as $file) {
        if (Util::endsWith($file, ".sql")) {
            $table = str_replace(".sql", "", $file);
            out("Updating table |g|$table|n| ... ", false, false);
            $sqlFile = "$base/sql/$file";
            loadFile($sqlFile, $table);
            out("|w|done|n|");
        }   
    }   
} catch (Exception $ex) {
    out("|r|Error!|n|");
    throw $ex;
}

out("|g|Unsetting maintenance mode|n|");
Db::execute("delete from zz_storage where locker = 'maintenance'");
out("All done, enjoy your update!");

function loadFile($file, $table) {
    if (Util::endsWith($file, ".gz")) $handle = gzopen($file, "r");
    else $handle = fopen($file, "r");
  if(Db::queryRow("SHOW TABLES LIKE'$table'")!= null){ //Check to see if we are adding new tables
	  if (Util::startsWith($table, "ccp_")) {
		  Db::execute("drop table $table");
	  } else {
		  Db::execute("alter table $table rename old_$table");
	  }
  }


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
  if (Db::queryRow("SHOW TABLES LIKE 'old_$table'")!= null){ // Check again to see if the old_table is there
	  if (!Util::startsWith($table, "ccp_")) {
		  Db::execute("insert into $table select * from old_$table");
		  Db::execute("drop table old_$table");
	  }
  }
}

function out($message, $die = false, $newline = true)
{
    $colors = array(
        "|w|" => "1;37", //White
        "|b|" => "0;34", //Blue
        "|g|" => "0;32", //Green
        "|r|" => "0;31", //Red
        "|n|" => "0" //Neutral
        );

    foreach($colors as $color => $value)
        $message = str_replace($color, "\033[".$value."m", $message);

    if($newline)
        echo $message.PHP_EOL;
    else
        echo $message;
}
