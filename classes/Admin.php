<?php
/*
 - getuserinfo(id)
  -array info
 - setEmail(id,email)
  - success or failure
 - setPassword(id)
  - there new password
 - setRevoked(id,reason)
  - success or failure
 - setUnRevoked(id)
  - success of failure
 - setAdmin(id,bool)
 - setModerator(id,bool)
*/
class Admin extends Moderator
{
  public static function setAdmin($userID,$bool){
    Db::execute("UPDATE zz_users SET admin = :access WHERE id = :id", array(":id" => $userID, ":access" => $bool));
  }
  public static function setMod($userID,$bool){
    Db::execute("UPDATE zz_users SET moderator = :access WHERE id = :id", array(":id" => $userID, ":access" => $bool));
  }

}
