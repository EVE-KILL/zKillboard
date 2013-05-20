#!/usr/bin/env php
<?php

if(php_sapi_name() != "cli")
    die("This is a cli script!");

if(!extension_loaded('pcntl'))
    die("This script needs the pcntl extension!");

$base = __DIR__;
require_once "$base/init.php";

interface cliCommand {
    public function getDescription();
    public function getAvailMethods();
    public function execute($parameters);
}

$curTime = time();
$cronInfo = array();

$files = scandir("$base/cli");
foreach($files as $file)
{
    if(!preg_match("/^cli_(.+)\\.php$/", $file, $match))
        continue;

    $command = $match[1];
    $className = "cli_$command";
    require_once "$base/cli/$file";

    if(!is_subclass_of($className, "cliCommand"))
        continue;

    if(!method_exists($className, "getCronInfo"))
        continue;

    $class = new $className();
    $cronInfo[$command] = $class->getCronInfo();
    unset($class);
}

if(file_exists("$base/cron.overrides"))
{
    $overrides = file_get_contents("$base/cron.overrides");
    $overrides = json_decode($overrides, true);

    foreach($overrides as $command => $info)
        $cronInfo[$command] = $info;
}

foreach($cronInfo as $command => $info)
{
    foreach($info as $interval => $arguments)
    {
        runCron($command, $interval, $arguments);
    }
}

function runCron($command, $interval, $args)
{
    global $base, $curTime;

    if(is_array($args))
        array_unshift($args, $command);
    else if($args != "")
        $args = explode(" ", "$command $args");
    else
        $args = array($command);

    $cronName = implode(".", $args);
    $locker = "lastCronRun.$cronName";
    $lastRun = (int)Storage::retrieve($locker, 0);

    $dateFormat = "D M j G:i:s T Y";
    if($curTime - $lastRun < $interval)
    {
        echo "Cron $cronName last run at ".date($dateFormat, $lastRun)."\n";
        return;
    }

    echo "Cron $cronName running at ".date($dateFormat, $curTime)."\n";

    Storage::store($locker, $curTime);

    $pid = pcntl_fork();
    if($pid < 0)
    {
        Storage::store($locker, $lastRun);
        return;
    }

    if($pid != 0)
        return;

    putenv("SILENT_CLI=1");
    pcntl_exec("$base/cliLock.sh", $args);
    Storage::store($locker, $lastRun);
    die("Executing $command failed!");
}
