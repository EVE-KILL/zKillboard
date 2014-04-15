<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

$key = $input[0];
if (!isset($input[1])) $app->redirect("/");
$id = $input[1];
$pageType = @$input[2];

$validPageTypes = array("overview", "kills", "losses", "top", "topalltime", "solo", "history");
if ($key == "alliance")
{
	$validPageTypes[] = "api";
	$validPageTypes[] = "corpstats";
}
if (!in_array($pageType, $validPageTypes)) $pageType = "overview";

$map = array(
		"corporation"   => array("column" => "corporation", "id" => "Info::getCorpId", "details" => "Info::getCorpDetails", "mixed" => true),
		"character"     => array("column" => "character", "id" => "Info::getCharId", "details" => "Info::getPilotDetails", "mixed" => true),
		"alliance"      => array("column" => "alliance", "id" => "Info::getAlliId", "details" => "Info::getAlliDetails", "mixed" => true),
		"faction"       => array("column" => "faction", "id" => "Info::getFactionId", "details" => "Info::getFactionDetails", "mixed" => true),
		"system"        => array("column" => "solarSystem", "id" => "Info::getSystemId", "details" => "Info::getSystemDetails", "mixed" => true),
		"region"        => array("column" => "region", "id" => "Info::getRegionId", "details" => "Info::getRegionDetails", "mixed" => true),
		"group"			=> array("column" => "group", "id" => "Info::getGroupIDFromName", "details" => "Info::getGroupDetails", "mixed" => true),
		"ship"          => array("column" => "shipType", "id" => "Info::getShipId", "details" => "Info::getShipDetails", "mixed" => true),
		);
if (!array_key_exists($key, $map)) $app->notFound();

if (!is_numeric($id))
{
	$function = $map[$key]["id"];
	$id = call_user_func($function, $id);
	if ($id > 0) $app->redirect("/" . $key . "/" . $id . "/", 301);
	else $app->notFound();
}

if ($id <= 0) $app->notFound();

$parameters = Util::convertUriToParameters();
@$page = max(1, $parameters["page"]);
global $loadGroupShips; // Can't think of another way to do this just yet
$loadGroupShips = $key == "group";

$limit = 50;
$parameters["limit"] = $limit;
$parameters["page"] = $page;
$detail = call_user_func($map[$key]["details"], $id, $parameters);
//$totalKills = isset($detail["shipsDestroyed"]) ? $detail["shipsDestroyed"] : 0;
//$totalLosses = isset($detail["shipsLost"]) ? $detail["shipsLost"] : 0;
$pageName = isset($detail[$map[$key]["column"] . "Name"]) ? $detail[$map[$key]["column"] . "Name"] : "???";
$columnName = $map[$key]["column"] . "ID";
$mixedKills = $pageType == "overview" && $map[$key]["mixed"] && UserConfig::get("mixKillsWithLosses", true);

$mixed = $pageType == "overview" ? Kills::getKills($parameters) : array();
$kills = $pageType == "kills"    ? Kills::getKills($parameters) : array();
$losses = $pageType == "losses"  ? Kills::getKills($parameters) : array();

if ($pageType != "solo" || $key == "faction") {
	$soloKills = array();
	//$soloCount = 0;
} else {
	$soloParams = $parameters;
	if (!isset($parameters["kills"]) || !isset($parameters["losses"])) $soloParams["mixed"] = true;
	$soloKills = Kills::getKills($soloParams);
	//$soloCount = Db::queryField("select count(killID) count from zz_participants where " . $map[$key]["column"] . "ID = :id and isVictim = 1 and number_involved = 1", "count", array(":id" => $id), 3600);
}
//$soloPages = ceil($soloCount / $limit);
$solo = Kills::mergeKillArrays($soloKills, array(), $limit, $columnName, $id);

$validAllTimePages = array("character", "corporation", "alliance");
$topLists = array();
if ($pageType == "top" || ($pageType == "topalltime" && in_array($key, $validAllTimePages))) {
	$topParameters = $parameters; // array("limit" => 10, "kills" => true, "$columnName" => $id);
	$topParameters["limit"] = 10;

	if($pageType != "topalltime")
	{
		if(!isset($topParameters["year"]))
			$topParameters["year"] = date("Y");
		if(!isset($topParameters["month"]))
			$topParameters["month"] = date("m");
	}
	if (!array_key_exists("kills", $topParameters) && !array_key_exists("losses", $topParameters)) $topParameters["kills"] = true;
	
	if($key != "character") {
		$topLists[] = array("type" => "character", "data" => Stats::getTopPilots($topParameters, true));
		$topLists[] = array("type" => "corporation", "data" => Stats::getTopCorps($topParameters, true));
		$topLists[] = array("type" => "alliance", "data" => Stats::getTopAllis($topParameters, true));
	}
	$topLists[] = array("type" => "ship", "data" => Stats::getTopShips($topParameters, true));
	$topLists[] = array("type" => "system", "data" => Stats::getTopSystems($topParameters, true));
	$topLists[] = array("type" => "weapon", "data" => Stats::getTopWeapons($topParameters, true));

	if (isset($detail["factionID"]) && $detail["factionID"] != 0 && $key != "faction") {
		$topParameters["!factionID"] = 0;
		$topLists[] = array("name" => "Top Faction Characters", "type" => "character", "data" => Stats::getTopPilots($topParameters, true));
		$topLists[] = array("name" => "Top Faction Corporations", "type" => "corporation", "data" => Stats::getTopCorps($topParameters, true));
		$topLists[] = array("name" => "Top Faction Allianes", "type" => "alliance", "data" => Stats::getTopAllis($topParameters, true));
	}
} else {
                $p = $parameters;
                $numDays = 7;
                $p["limit"] = 10;
                $p["pastSeconds"] = $numDays * 86400;
                $p["kills"] = $pageType != "losses";

		$topLists[] = Info::doMakeCommon("Top Characters", "characterID", Stats::getTopPilots($p));
		$topLists[] = Info::doMakeCommon("Top Corporations", "corporationID", Stats::getTopCorps($p));
                $topLists[] = Info::doMakeCommon("Top Alliances", "allianceID", Stats::getTopAllis($p));
}

$corpList = array();
if ($pageType == "api") $corpList = Info::getCorps($id);

$corpStats = array();
if ($pageType == "corpstats") $corpStats = Info::getCorpStats($id, $parameters);

$onlyHistory = array("character", "corporation", "alliance");
if ($pageType == "history" && in_array($key, $onlyHistory)) {
	$detail["history"] = Summary::getMonthlyHistory($columnName, $id);
} else $detail["history"] = array();

// Figure out if the character or corporation has any API keys in the database
$apiVerified = false;
if(in_array($key, array("character", "corporation")))
{
	if($key == "character")
	{
		$count = Db::queryField("SELECT count(1) count FROM zz_api_characters WHERE characterID = :characterID", "count", array(":characterID" => $id));
		$apiVerified = $count > 0 ? 1 : 0;
	}
	else
	{
		$count = Db::queryField("select count(1) count from zz_api_characters where isDirector = 'T' and corporationID = :corpID", "count", array(":corpID" => $id));
		$apiVerified = $count > 0 ? 1 : 0;
	}
}

$cnt = 0;
$cnid = 0;
$stats = array();
$totalcount = ceil(count($detail["stats"]) / 4);
foreach($detail["stats"] as $q)
{
	if($cnt == $totalcount)
	{
		$cnid++;
		$cnt = 0;
	}
	$stats[$cnid][] = $q;
	$cnt++;
}
if ($mixedKills) $kills = Kills::mergeKillArrays($mixed, array(), $limit, $columnName, $id);

$renderParams = array("pageName" => $pageName, "kills" => $kills, "losses" => $losses, "detail" => $detail, "page" => $page,
		"mixed" => $mixedKills, "key" => $key, "id" => $id, "pageType" => $pageType, "solo" => $solo, "topLists" => $topLists,
		"corps" => $corpList, "corpStats" => $corpStats, "summaryTable" => $stats, "pager" => true, "datepicker" => true, "apiVerified" => $apiVerified);

//$app->etag(md5(serialize($renderParams)));
//$app->expires("+5 minutes");
$app->render("overview.html", $renderParams);


