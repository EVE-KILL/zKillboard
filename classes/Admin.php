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
 * extends Moderator
 * Various Admin only functions 
 */
class Admin extends Moderator
{
  /**
   * Sets/Removes the user admin rights
   *
   * @static
   * @param $userID the userid to change
   * @param $bool 1= set admin 0 = remove admin
   */
  public static function setAdmin($userID,$bool){
    if (!User::isAdmin()) throw new Exception("Invalid Access!");
    Db::execute("UPDATE zz_users SET admin = :access WHERE id = :id", array(":id" => $userID, ":access" => $bool));
  }
  /**
   * Sets/Removes the user moderator rights
   *
   * @static
   * @param $userID the userid to change
   * @param $bool 1 = set admin 0 = remove admin
   */
  public static function setMod($userID,$bool){
  if (!User::isAdmin()) throw new Exception("Invalid Access!");
    Db::execute("UPDATE zz_users SET moderator = :access WHERE id = :id", array(":id" => $userID, ":access" => $bool));
  }
 /**
   * Sets the users Email
   *
   * @static
   * @param $userID the userid of the user 
   * @param $email, the new email address
   */
  public static function setEmail($userID,$email){
    if (!User::isAdmin()) throw new Exception("Invalid Access!");
    Db::execute("UPDATE zz_users SET email = :email WHERE id = :id",array(":id" => $userID,":email" => $email));
  }

}
