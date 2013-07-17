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
/**
 * Various Moderator Actions
 *
 */
class Moderator
{
  /**
   * Gets the User info
   *
   * @static
   * @param $userID the userid of the user to query
   * @return The array with the userinfo in it 
   */
  public static function getUserInfo($userID){
    $info = Db::query("SELECT * FROM zz_users WHERE id = :id", array(":id" => $userID),0); // should this be star
    return $info;
  }
  /**
   * Unrevokes the users access 
   *
   * @static
   * @param $userID the userid to change
   */
  public static function setUnRevoked($userID){
  Db::execute("UPDATE zz_users SET revoked = :access WHERE id = :id", array(":id" => $userID, ":access" => 0));
  }
  /**
   * Revokes the users acces
   *
   * @static
   * @param $userID the userid to change
   * @param $reason the reason why the access was revoked
   */
  public static function setRevoked($userID,$reason){
    Db::execute("UPDATE zz_users SET revoked = :access WHERE id = :id", array(":id" => $userID, ":access" => 1));
    Db::execute("UPDATE zz_users SET revoked_reason = :reason WHERE id = :id", array(":id" => $userID, ":reason" => $reason));
  }
  public static function getUsers(){
  $users = Db::query("SELECT * FROM zz_users order by username", array(), 0); 
  return $users;
  }

}
