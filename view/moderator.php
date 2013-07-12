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

$info = "";
$message = "";
if (!User::isLoggedIn()) {
    $app->render("login.html");
    die();
}
$info = User::getUserInfo();
if (!User::isModerator()) $app->redirect("/");

if($_POST)
{
	$status = NULL;
	$reply = NULL;
	$revokeaccess = NULL;
	$grantaccess = NULL;
	$reason = "Default should change";
	$report = NULL;
	$delete = NULL;
	
	if(isset($_POST["status"]))
		$status = $_POST["status"];
	if(isset($_POST["reply"]))
		$reply = $_POST["reply"];
	if(isset($_POST["report"]))
		$report = $_POST["report"];
    if(isset($_POST["revokeaccess"]))
		$revokeaccess = $_POST["revokeaccess"];
    if(isset($_POST["grantaccess"]))
		$grantaccess = $_POST["grantaccess"];
	if(isset($_POST["reason"]))
		$reason = $_POST["reason"];
	if(isset($_POST["userID"]))
		$userID = $_POST["userID"];
	if(isset($_POST["delete"]))
		$delete = $_POST["delete"];
	  if(isset($_POST["userID"]))
    $userID = $_POST["userID"];
  if(isset($_POST["email"]))
    $email = $_POST["email"];
  if(isset($_POST["resetpassword"]))
    $password = 0;
  if(isset($_POST["manualpull"]))
    $manualpull = $_POST["manualpull"];
  if(isset($_POST["deleteapi"]))
    $deleteapi = $_POST["deleteapi"];
	
	if(isset($status))
	{
		Db::execute("UPDATE zz_tickets SET status = :status WHERE id = :id", array(":status" => $status, ":id" => $id));
	}
	if(isset($reply))
	{
		$name = $info["username"];
		$moderator = $info["moderator"];
		$check = Db::query("SELECT * FROM zz_tickets_replies WHERE reply = :reply AND userid = :userid", array(":reply" => $reply, ":userid" => $info["id"]), 0);
		if(!$check)
		{
			Db::execute("INSERT INTO zz_tickets_replies (userid, belongsTo, name, reply, moderator) VALUES (:userid, :belongsTo, :name, :reply, :moderator)", array(":userid" => $info["id"], ":belongsTo" => $id, ":name" => $name, ":reply" => $reply, ":moderator" => $moderator));
			$tic = Db::query("SELECT name,email FROM zz_tickets WHERE id = :id", array(":id" => $id));
			$ticname = $tic[0]["name"];
			$ticmail = $tic[0]["email"];
			$subject = "zKillboard Ticket";
			global $baseAddr;
			$message = "$ticname, there is a new reply to your ticket from $name - https://$baseAddr/tickets/view/$id/";
			Email::send($ticmail, $subject, $message);
			if(isset($report))
				$app->redirect("/moderator/reportedkills/$id/");
			$app->redirect("/moderator/tickets/$id/");
		}
	}
	if(isset($delete))
	{
		if($delete < 0)
		{
			if(Util::deleteKill($delete))
			{
				Db::execute("DELETE FROM zz_tickets WHERE id = :id", array(":id" => $id));
				Db::execute("DELETE FROM zz_tickets_replies WHERE belongsTo = :belongsTo", array(":belongsTo" => $id));
				$app->redirect("/moderator/reportedkills/");
			}
			else
				$message = "Error, kill could not be deleted";
		}
		$message = "Error, kill is positive, and thus api verified.. something is wrong!";
	}
	if(isset($grantaccess) && isset($userID))
	{
		Moderator::setUnRevoked($userID);
    $message = "User has been granted access to the site";
	}
	if(isset($revokeaccess) && isset($userID) && isset($reason))
	{
    Moderator::setRevoked($userID,$reason);
		$message = "User has had access to the site revoked";
	}
  if(isset($email) && isset($userID))
  {
    Moderator::setEmail($userID,$email);
    $message = "User has had there email changed";
  }
  if(isset($password) && isset($userID))
  {
   $password =  Moderator::setPassword($userID);
    $message = "User has had there password change to:".$password;
  }
  if(isset($manualpull) )
  {
  $message = "ah";
  }
  if(isset($deleteapi)){
    Api::deleteKey($deleteapi);
    $message = "The Api had been deleted";
  }

}

if ($req == "") {
	$app->redirect("tickets/");
	die();
}

if($req == "tickets" && $id)
{
	$info["ticket"] = Db::query("SELECT * FROM zz_tickets WHERE id = :id", array(":id" => $id), 0);
	$info["replies"] = Db::query("SELECT * FROM zz_tickets_replies WHERE belongsTo = :id", array(":id" => $id), 0);
}
elseif($req == "tickets")
{
	$info = Db::query("SELECT * FROM zz_tickets WHERE killID = 0 ORDER BY status DESC", array(),0);
	foreach($info as $key => $val)
	{
		if($val["tags"])
			$info[$key]["tags"] = explode(",", $val["tags"]);
	}
}
elseif($req == "users")
{
	$info = Moderator::getUsers();
}
elseif($req == "revokes")
{
	$info = Db::query("SELECT id, username, email, revoked_reason FROM zz_users WHERE revoked = 1 ORDER BY id DESC", array(), 0);
}
if($req == "reportedkills" && $id)
{
	$info["ticket"] = Db::query("SELECT * FROM zz_tickets WHERE id = :id", array(":id" => $id), 0);
	$info["replies"] = Db::query("SELECT * FROM zz_tickets_replies WHERE belongsTo = :id", array(":id" => $id), 0);
}
elseif($req == "reportedkills")
{
	$info = Db::query("SELECT * FROM zz_tickets WHERE killID != 0 ORDER BY status DESC", array(),0);
	foreach($info as $key => $val)
	{
		if($val["tags"])
			$info[$key]["tags"] = explode(",", $val["tags"]);
	}
}
elseif ($req == "susers"){
if(isset($id)){
    $info = Moderator::getUserInfo($id);
    if(!isset($info[0])){
      $req = "users";
      $message = "No user found with and if of".$id;
    }else{
      if( $info[0]["admin"] == 1 or $info[0]["moderator"] == 1){
        $req = "users";
        $message = "No editing other Mod/ Admins ";
        $id = NULL;
        $info = Moderator::getUsers();
      }else{
        $api =  Api::getKeys($id);
        $info["api"]=$api;
      }
    }
  }else{
  $app->redirect("/moderator/users/");

  }

}
$app->render("moderator/moderator.html", array("id" => $id, "info" => $info, "key" => $req, "url"=>"moderator", "message" => $message));
