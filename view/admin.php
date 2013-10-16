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

if (!User::isAdmin()) $app->notFound();
$info=NULL;
$message = "";
$reason = "default";
if($_POST)
{
  if(isset($_POST["ircuserid"]))
    $ircuserid = $_POST["ircuserid"];
  if(isset($_POST["accessLevel"]))
    $accessLevel = $_POST["accessLevel"];
  if(issset($_POST["deleteirc"]))
    $deleteirc = $_POST["deleteirc"];
  if(isset($_POST["commandlog"]));
    $commandlog = $_POST["commandlog"];
  if(isset($_POST["grantadmin"]))
    $grantadmin = $_POST["grantadmin"];
  if(isset($_POST["grantmoderator"]))
    $grantmoderator = $_POST["grantmoderator"];
  if(isset($_POST["grantaccess"]))
    $grantaccess = $_POST["grantaccess"];
  if(isset($_POST["revokeadmin"]))
    $revokeadmin = $_POST["revokeadmin"];
  if(isset($_POST["revokemoderator"]))
    $revokemoderator = $_POST["revokemoderator"];
  if(isset($_POST["revokeaccess"]))
    $revokeaccess = $_POST["revokeaccess"];
  if(isset($_POST["reason"]))
    $reason = $_POST["reason"];
  if(isset($_POST["userID"]))
    $userID = $_POST["userID"];
  if(isset($_POST["email"]))
    $email = $_POST["email"];
  if(isset($_POST["manualpull"]))
    $manualpull = $_POST["manualpull"];
  if(isset($_POST["deleteapi"]))
    $deleteapi = $_POST["deleteapi"];
  if(isset($ircuserid) && isset($deleteirc))
  {
    Db::execute("DELETE FROM zz_irc_access WHERE id = :id", array(":id" => $ircuserid));
    $message = "User deleted";
  }
  if(isset($accessLevel) && isset($ircuserid))
  {
    Db::execute("UPDATE zz_irc_access SET accessLevel = :accessLevel WHERE id = :id", array(":accessLevel" => $accessLevel, ":id" => $ircuserid));
    $message = "User's access has been updated";
  }
	if(isset($grantadmin) && isset($userID))
	{
		Admin::setAdmin($userID,1);
    $message = "User has been granted admin access";
	}
	if(isset($revokeadmin) && isset($userID))
	{
		Admin::setAdmin($userID,0);
    $message = "User has had admin access revoked";
	}
	if(isset($grantmoderator) && isset($userID))
	{
    Admin::setMod($userID,1);
		$message = "User has been granted moderator access";
	}
	if(isset($revokemoderator) && isset($userID))
	{
    Admin::setMod($userID,0);
		$message = "User has had moderator access revoked";
	}
	if(isset($grantaccess) && isset($userID))
	{
		Admin::setUnRevoked($userID);
    $message = "User has been granted access to the site";
	}
	if(isset($revokeaccess) && isset($userID) && isset($reason))
	{
		Admin::setRevoked($userID,$reason);
    $message = "User has had access to the site revoked";
	}
  if(isset($email) && isset($userID))
  {
    Admin::setEmail($userID,$email);
    $message = "User has had there email changed";
  }
  if(isset($manualpull)  )
  {
    $message = "ah";
  }
  if(isset($deleteapi)){
    Api::deleteKey($deleteapi);
    $message = "The Api had been deleted";
  }
}
if($req == "users")
{
  $info = Admin::getUsers();
}
elseif($req == "revokes")
{
  $info = Db::query("SELECT id, username, email, revoked_reason FROM zz_users WHERE revoked = 1 ORDER BY id DESC", array(), 0);
}
elseif($req == "irc")
{
  $info = Db::query("SELECT * FROM zz_irc_access ORDER BY name DESC", array(), 0);
}
elseif($req == "commandlog")
{
  if(isset($ircuserid)){
    $info = Db::query("SELECT * FROM zz_irc_log WHERE id = :id ORDER BY date DESC", array(":id"=> $ircuserid), 0);
  }else{
    $app->redirect("/admin/irc");
  }
}
elseif($req == "email")
{
    $info = array();
}
elseif($req == "susers" )
{  
  if(isset($id)){ 
    $info = Admin::getUserInfo($id);
    if(!isset($info[0])){
      $req = "users";
      $message = "No user found with id ".$id;
      $info = Admin::getUsers(); 
    }else{
      $api =  Api::getKeys($id);    
      $info["api"]=$api;
    }
  }else{
  $app->redirect("/admin/users/");
  }
}else{
  $app->redirect("/admin/users/");
}
$app->render("admin/admin.html", array("info" => $info, "url" => "admin", "key" => $req, "message" => $message));
