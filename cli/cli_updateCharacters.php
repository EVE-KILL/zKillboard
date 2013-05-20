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

class cli_updateCharacters implements cliCommand
{
	public function getDescription()
	{
		return "Updates character Name and IDs. |g|Usage: updateCharacters <type>";
	}

	public function getAvailMethods()
	{
		return "";
	}

	public function getCronInfo()
	{
		return array(
			60 => ""
		);
	}

	public function execute($parameters)
	{
		self::updateCharacters();
	}

	private static function updateCharacters()
	{
		CLI::out("|g|Updating characters");
		$minute = (int) date("i");
		if ($minute == 0) {
			Db::execute("insert ignore into zz_characters (characterID) select ceoID from zz_corporations");
			Db::execute("insert ignore into zz_characters (characterID) select characterID from zz_api_characters where characterID != 0");
		}
		Db::execute("delete from zz_characters where characterID < 9000000");
		Db::execute("update zz_characters set lastUpdated = now() where characterID >= 30000000 and characterID <= 31004590");
		Db::execute("update zz_characters set lastUpdated = now() where characterID >= 40000000 and characterID <= 41004590");
		$result = Db::query("select characterID, name from zz_characters where lastUpdated < date_sub(now(), interval 7 day) and corporationID != 1000001 order by lastUpdated limit 600", array(), 0);
		foreach ($result as $row) {
			$id = $row["characterID"];
			$oName = $row["name"];
			Db::execute("update zz_characters set lastUpdated = now() where characterID = :id", array(":id" => $id));

			$pheal = Util::getPheal();
			$pheal->scope = "eve";
			try
			{
				$charInfo = $pheal->CharacterInfo(array("characterid" => $id));
				$name = $charInfo->characterName;
				$corpID = $charInfo->corporationID;
				$alliID = $charInfo->allianceID;
				CLI::out("|g|$name|n| $id $corpID $alliID");
				if ($name != "") Db::execute("update zz_characters set name = :name, corporationID = :corpID, allianceID = :alliID where characterID = :id", array(":id" => $id, ":name" => $name, ":corpID" => $corpID, ":alliID" => $alliID));
			}
			catch (Exception $ex)
			{
				// Is this name even a participant?
				$count = Db::queryField("select count(*) count from zz_participants where characterID = :id", "count", array(":id" => $id));
				if ($count == 0)
					Db::execute("delete from zz_characters where characterID = :id", array(":id" => $id));
				elseif ($ex->getCode() != 503)
					Log::log("ERROR Validating Character $id" . $ex->getMessage());
			}
			usleep(100000); // Try not to spam the API servers (pauses 1/10th of a second)
		}
	}
}
