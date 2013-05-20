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

class cli_updateCorporations implements cliCommand
{
	public function getDescription()
	{
		return "Updates the corporation Name and IDs. |g|Usage: updateCorporations <type>";
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
				self::updateCorporations();
	}

	private static function updateCorporations()
	{
		CLI::out("|g|Updating corporations");
		Db::execute("delete from zz_corporations where corporationID = 0");
		Db::execute("insert ignore into zz_corporations (corporationID) select executorCorpID from zz_alliances where executorCorpID > 0");
		$result = Db::query("select corporationID, name, memberCount, ticker from zz_corporations where (memberCount is null or memberCount > 0 or lastUpdated = 0)  and corporationID >= 1000001 order by lastUpdated limit 100", array(), 0);
		foreach($result as $row) {
			$id = $row["corporationID"];
			$oMemberCount = $row["memberCount"];
			$oName = $row["name"];
			$oTicker = $row["ticker"];

			$pheal = Util::getPheal();
			$pheal->scope = "corp";
			try {
				$corpInfo = $pheal->CorporationSheet(array("corporationID" => $id));
				$name = $corpInfo->corporationName;
				$ticker = $corpInfo->ticker;
				$memberCount = $corpInfo->memberCount;
				$ceoID = $corpInfo->ceoID;
				if ($ceoID == 1) $ceoID = 0;
				$dscr = $corpInfo->description;
				CLI::out("|g|$id|n| $name");
				if ($name != "") Db::execute("update zz_corporations set name = :name, ticker = :ticker, memberCount = :memberCount, ceoID = :ceoID, description = :dscr, lastUpdated = now() where corporationID = :id",
						array(":id" => $id, ":name" => $name, ":ticker" => $ticker, ":memberCount" => $memberCount, ":ceoID" => $ceoID, ":dscr" => $dscr));

			} catch (Exception $ex) {
				Db::execute("update zz_corporations set lastUpdated = now() where corporationID = :id", array(":id" => $id));
				if ($ex->getCode() != 503) Log::log("ERROR Validating Corp $id: " . $ex->getMessage());
			}
			usleep(100000); // Try not to spam the API servers (pauses 1/10th of a second)
		}
	}
}
