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

class UserGlobals extends Twig_Extension
{
	public function getName()
	{
		return "UserGlobals";
	}

	public function getGlobals()
	{
		$result = array();
		if (isset($_SESSION["loggedin"])) {
			$u = User::getUserInfo();
			$this->addGlobal($result, "sessionrevoked", User::isRevoked());
			$this->addGlobal($result, "sessionrevokereason", User::getRevokeReason());
			$this->addGlobal($result, "sessionusername", $u["username"]);
			$this->addGlobal($result, "sessionuserid", $u["id"]);
			$this->addGlobal($result, "sessionadmin", (bool)$u["admin"]);
			$this->addGlobal($result, "sessionmoderator", (bool)$u["moderator"]);
			$this->addGlobal($result, "sessionpilots", UserConfig::get("character"));
			$this->addGlobal($result, "sessioncorps", UserConfig::get("corporation"));
			$this->addGlobal($result, "sessionalliances", UserConfig::get("alliance"));
			$this->addGlobal($result, "sessionfactions", UserConfig::get("faction"));
			$this->addGlobal($result, "sessionships", UserConfig::get("ship"));
			$this->addGlobal($result, "sessionsystems", UserConfig::get("system"));
			$this->addGlobal($result, "sessionregions", UserConfig::get("region"));
			$this->addGlobal($result, "sessionviewtheme", UserConfig::get("viewtheme"), "bootstrap");
			if(UserConfig::get("viewtheme") == "edk")
				$this->addGlobal($result, "sessiontheme", "cyborg");
			else
				$this->addGlobal($result, "sessiontheme", UserConfig::get("theme"), "cyborg");
			$this->addGlobal($result, "defaultCommentCharacter", UserConfig::get("defaultCommentCharacter"));
			$this->addGlobal($result, "sessiontimeago", UserConfig::get("timeago"));
			$this->addGlobal($result, "sessionddcombined", UserConfig::get("ddcombine"));
		  $this->addGlobal($result, "sessionddmonthyear",UserConfig::get("ddmonthyear"));
    }
		$this->addGlobal($result, "killsLastHour", Storage::retrieve("KillsLastHour", 0));
		return $result;
	}

	private function addGlobal(&$array, $key, $value, $defaultValue = null)
	{
		if ($value == null && $defaultValue == null) return;
		else if ($value == null) $array[$key] = $defaultValue;
		else $array[$key] = $value;
	}
}
