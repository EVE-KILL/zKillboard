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
class Moderator
{
  public static function getUserInfo($userID){
    $info = Db::query("SELECT * FROM zz_users WHERE id = :id", array(":id" => $userID),0); // should this be star
    return $info;
  }
  public static function setEmail($userID,$email){
    Db::execute("UPDATE zz_users SET email = :email WHERE id = :id",array(":id" => $userID,":email" => $email));
  }
  public static function setPassword($userID){
    $plaintext = "test";
    $hpassword = Password::genPassword($plaintext);
    Db::execute("UPDATE zz_users set password = :password WHERE id = :id",array(":id" => $userID,":password"=> $hpassword));
    return $plaintext;
  }
  public static function setUnRevoked($userID){
  Db::execute("UPDATE zz_users SET revoked = :access WHERE id = :id", array(":id" => $userID, ":access" => 0));
  }
  public static function setRevoked($userID,$reason){
    Db::execute("UPDATE zz_users SET revoked = :access WHERE id = :id", array(":id" => $userID, ":access" => 1));
    Db::execute("UPDATE zz_users SET revoked_reason = :reason WHERE id = :id", array(":id" => $userID, ":reason" => $reason));
  }
  public static function getUsers(){
  $users = Db::query("SELECT * FROM zz_users order by username", array(), 0); 
  return $users;
  }

}
