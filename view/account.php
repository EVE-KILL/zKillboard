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

if (!User::isLoggedIn()) {
	$app->render("login.html");
	die();
}

$userID = User::getUserID();
$key = "me";
$error = "";

if(isset($req))
	$key = $req;

if($_POST)
{
	// Post is just generic, we'll figure out what the user wants, based on what is set
	$keyid = "";
	$vcode = "";
	$label = null;
	$theme = "";
	$viewtheme = "";
	$deletekeyid = "";
    $deleteentity = "";
	$orgpw = "";
	$password = "";
	$password2 = "";
	$defaultcommentcharacter = "";
	$timeago = "";
	$entity = "";
	$entitytype = "";
	$ddcombine = "";
  $ddmonthyear = ""; 
    $deleteentityid = "";
    $deleteentitytype = "";
    $entitymetadata = "";
    $deletedomainentityid = "";
    $deletedomainentitytype = "";
    $subdomain = "";
    $domainname = "";
    $deletedomainid = "";

    if(isset($_POST["deletedomainid"]))
    	$deletedomainid = $_POST["deletedomainid"];
    if(isset($_POST["deletedomainentityid"]))
    	$deletedomainentityid = $_POST["deletedomainentityid"];
    if(isset($_POST["deletedomainentitytype"]))
    	$deletedomainentitytype = $_POST["deletedomainentitytype"];
    if(isset($_POST["subdomain"]))
    	$subdomain = $_POST["subdomain"];
    if(isset($_POST["domainname"]))
    	$domainname = $_POST["domainname"];
	if(isset($_POST["keyid"]))
		$keyid = trim($_POST["keyid"]);
	if(isset($_POST["vcode"]))
		$vcode = trim($_POST["vcode"]);
	if(isset($_POST["label"]))
		$label = $_POST["label"];
	if(isset($_POST["viewtheme"]))
		$viewtheme = $_POST["viewtheme"];
	if(isset($_POST["theme"]))
		$theme = $_POST["theme"];
	if(isset($_POST["deletekeyid"]))
		$deletekeyid = $_POST["deletekeyid"];
	if(isset($_POST["deleteentity"]))
		$deleteentity = $_POST["deleteentity"];
	if(isset($_POST["orgpw"]))
		$orgpw = $_POST["orgpw"];
	if(isset($_POST["password"]))
		$password = $_POST["password"];
	if(isset($_POST["password2"]))
		$password2 = $_POST["password2"];
	if(isset($_POST["defaultcommentcharacter"]))
		$defaultcommentcharacter = $_POST["defaultcommentcharacter"];
	if(isset($_POST["timeago"]))
		$timeago = $_POST["timeago"];
	if(isset($_POST["addentitybox"]))
		$entity = $_POST["addentitybox"];
	if(isset($_POST["entitymetadata"]))
		$entitymetadata = $_POST["entitymetadata"];
	if(isset($_POST["ddcombine"]))
		$ddcombine = $_POST["ddcombine"];
  if(isset($_POST["ddmonthyear"]))
    $ddmonthyear = $_POST["ddmonthyear"];
    if(isset($_POST["deleteentityid"]))
        $deleteentityid = $_POST["deleteentityid"];
    if(isset($_POST["deleteentitytype"]))
        $deleteentitytype = $_POST["deleteentitytype"];
     
    // Delete an entity from a domain
    if($deletedomainentitytype && $deletedomainentitytype)
    {
    	Domains::deleteUserTrackerEntity($reqid, $deletedomainentityid, $deletedomainentitytype);
    	$app->redirect("/account/subdomains/$reqid/");
    }

    // Add an entity to a domain
    if($entitymetadata && $subdomain)
    {
    	$json = json_decode($entitymetadata, true);
    	$id = $json["id"];
    	$name = $json["name"];
    	$type = $json["type"];
    	Domains::addUserTrackerEntity($reqid, $id, $type, $name);
    	$app->redirect("/account/subdomains/$reqid/");
    }

    // Add a domain name
    if($domainname)
    {
    	Domains::addUserTrackerDomain($userID, $domainname);
    }

    // Delete a domain name
    if($deletedomainid)
    {
    	Domains::deleteUserTrackerDomain($userID, $deletedomainid);	
    }
    
	// Apikey stuff
	if($keyid || $vcode)
	{
		$check = Api::checkAPI($keyid, $vcode);
		if($check == "success")
		{
			$error = Api::addKey($keyid, $vcode, $label);
		}
		else
		{
			$error = $check;
		}
	}

	// Delete an apikey
	if($deletekeyid && !$deleteentity)
	{
		$error = Api::deleteKey($deletekeyid);
	}

    if($deletekeyid && $deleteentity)
    {
        $error = Domains::deleteEntity($deletekeyid, $deleteentity);
    }
    
	// Theme stuff
	if($viewtheme)
	{
		UserConfig::set("viewtheme", $viewtheme);
		$app->redirect($_SERVER["REQUEST_URI"]);
	}
	
	if($theme)
		UserConfig::set("theme", $theme);
	
	// Password
	if($orgpw && $password && $password2)
	{
		if($password != $password2)
			$error = "Passwords don't match, try again";
		elseif(Password::checkPassword($orgpw) == true)
		{
			Password::updatePassword($password);
			$error = "Password updated";
		}
		else
			$error = "Original password is wrong, please try again";
	}

	// Default comment character
	if($defaultcommentcharacter > 0) 
		UserConfig::set("defaultCommentCharacter", $defaultcommentcharacter);

	if($timeago)
		UserConfig::set("timeago", $timeago);

	// Tracker
    if($deleteentityid && $deleteentitytype)
    {
        $q = UserConfig::get($deleteentitytype);
        foreach($q as $k => $ent)
        {
            if($ent["id"] == $deleteentityid)
            {
                unset($q[$k]);
                $error = $ent["name"]." has been removed";
            }
        }
        UserConfig::set($deleteentitytype, $q);
    }

    // Tracker
	if($entity && $entitymetadata)
	{
		$entitymetadata = json_decode($entitymetadata, true);
		$entities = UserConfig::get($entitymetadata['type']);
		$entity = array('id' => $entitymetadata['id'], 'name' => $entitymetadata['name']);
		
		if(empty($entities) || !in_array($entity, $entities))
		{
			$entities[] = $entity;
			UserConfig::set($entitymetadata['type'], $entities);
			$error = "{$entitymetadata['name']} has been added to your tracking list";
		}
		else
			 $error = "{$entitymetadata['name']} is already being tracked";
	}

	if($ddcombine)
		UserConfig::set("ddcombine", $ddcombine);
  if($ddmonthyear)
    UserConfig::set("ddmonthyear",$ddmonthyear);
}

$data["domains"] = Domains::getUserTrackerDomains($userID);
$data["domainEntities"] = Domains::getUserTrackerEntities($reqid);
$data["entities"] = Account::getUserTrackerData();
$data["themes"] = array("default", "amelia", "cerulean", "cosmo", "cyborg", "journal", "readable", "simplex", "slate", "spacelab", "spruce", "superhero", "united");
$data["viewthemes"] = array("bootstrap", "edk");
$data["apiKeys"] = Api::getKeys($userID);
$data["apiChars"] = Api::getCharacters($userID);
$charKeys = Api::getCharacterKeys($userID);
$charKeys = Info::addInfo($charKeys);
$data["apiCharKeys"] = $charKeys;
$data["cmtChars"] = Api::getCharacters($userID);
$data["cmtChars"][] = array("characterID" => 0, "characterName" => "Anonymous");
$data["userInfo"] = User::getUserInfo();
$data["currentTheme"] = UserConfig::get("theme", "default");
$data["timeago"] = UserConfig::get("timeago");
$data["ddcombine"] = UserConfig::get("ddcombine");
$data["ddmonthyear"] = UserConfig::get("ddmonthyear");

$app->render("account.html", array("data" => $data, "message" => $error, "key" => $key, "reqid" => $reqid));
