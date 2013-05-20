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

class cli_calculateRecentStatsAndRanks implements cliCommand
{
	public function getDescription()
	{
		return "Calculates the recent stats and ranks for all the types on the board. |g|Usage: recentStatsAndRanks <type>";
	}

	public function getAvailMethods()
	{
		return "ranks stats all"; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(
			86400 => "stats"
		);
	}

	public function execute($parameters)
	{
		if (sizeof($parameters) == 0 || $parameters[0] == "") CLI::out("Usage: |g|recentStatsAndRanks <type>|n| To see a list of commands, use: |g|methods recentStatsAndRanks", true);
		$command = $parameters[0];

		switch($command)
		{
			case "all":
				self::ranks();
				self::stats();
			break;

			case "ranks":
				self::ranks();
			break;

			case "stats":
				self::stats();
			break;

		}
	}

	private static function ranks()
	{
		CLI::out("|g|Ranks calculation started");
		Db::execute("drop table if exists zz_ranks_temporary");
		Db::execute("create table zz_ranks_temporary like zz_ranks_recent");

		$types = array("faction", "alli", "corp", "pilot", "ship", "group", "system", "region");
		$indexed = array();

		foreach($types as $type) {
			CLI::out("Calculating ranks for $type");
			Log::log("Calcing ranks for $type");
			Db::execute("truncate zz_ranks_temporary");
			$exclude = $type == "corp" ? "and typeID > 1100000" : "";
			Db::execute("insert into zz_ranks_temporary select * from (select type, typeID, sum(destroyed) shipsDestroyed, null sdRank, sum(lost) shipsLost, null slRank, null shipEff, sum(pointsDestroyed) pointsDestroyed, null pdRank, sum(pointsLost) pointsLost, null plRank, null pointsEff, sum(iskDestroyed) iskDestroyed, null idRank, sum(iskLost) iskLost, null ilRank, null iskEff, null overallRank from zz_stats_recent where type = '$type' $exclude group by type, typeID) as f");

			if ($type == "system" or $type == "region") {
				Db::execute("update zz_ranks_temporary set shipsDestroyed = shipsLost, pointsDestroyed = pointsLost, iskDestroyed = iskLost");
				Db::execute("update zz_ranks_temporary set shipsLost = 0, pointsLost = 0, iskLost = 0");
			}

			// Calculate efficiences
			Db::execute("update zz_ranks_temporary set shipEff = (100*(shipsDestroyed / (shipsDestroyed + shipsLost))), pointsEff = (100*(pointsDestroyed / (pointsDestroyed + pointsLost))), iskEff = (100*(iskDestroyed / (iskDestroyed + iskLost)))");

			// Calculate Ranks for each type
			$rankColumns = array();
			$rankColumns[] = array("shipsDestroyed", "sdRank", "desc");
			$rankColumns[] = array("shipsLost", "slRank", "asc");
			$rankColumns[] = array("pointsDestroyed", "pdRank", "desc");
			$rankColumns[] = array("pointsLost", "plRank", "asc");
			$rankColumns[] = array("iskDestroyed", "idRank", "desc");
			$rankColumns[] = array("iskLost", "ilRank", "asc");
			foreach($rankColumns as $rankColumn) {
				$typeColumn = $rankColumn[0];
				$rank = $rankColumn[1];
				$rankOrder = $rankColumn[2];

				if (!in_array($typeColumn, $indexed)) {
					$indexed[] = $typeColumn;
					Db::execute("alter table zz_ranks_temporary add index($typeColumn, $rank)");
				}

				Db::execute("insert into zz_ranks_temporary (type, typeID, $rank) (SELECT type, typeID, @rownum:=@rownum+1 AS $rank FROM (SELECT type, typeID FROM zz_ranks_temporary ORDER BY $typeColumn desc, typeID ) u, (SELECT @rownum:=0) r) on duplicate key update $rank = values($rank)");

				$dupRanks = Db::query("select * from (select $typeColumn n, min($rank) r, count(*) c from zz_ranks_temporary where type = '$type' group by 1) f where c > 1");
				foreach($dupRanks as $dupRank) {
					$num = $dupRank["n"];
					$newRank = $dupRank["r"];
					CLI::out("|g|$type |r|$typeColumn |g|$num $rank |n|->|g| $newRank");
					Db::execute("update zz_ranks_temporary set $rank = $newRank where $typeColumn = $num and type = '$type'");
				}
			}

			// Overall ranking
			Db::execute("update zz_ranks_temporary set shipEff = 0 where shipEff is null");
			Db::execute("update zz_ranks_temporary set pointsEff = 0 where pointsEff is null");
			Db::execute("update zz_ranks_temporary set iskEff = 0 where iskEff is null");
			Db::execute("insert into zz_ranks_temporary (type, typeID, overallRank) (SELECT type, typeID, @rownum:=@rownum+1 AS overallRanking FROM (SELECT type, typeID, (if (shipsDestroyed = 0, 10000000000000, ((shipsDestroyed / (pointsDestroyed + 1)) * (sdRank + idRank + pdRank))) * (1 + (1 - ((shipEff + pointsEff + iskEff) / 300)))) k, (slRank + ilRank + plRank) l from zz_ranks_temporary order by 3, 4 desc, typeID) u, (SELECT @rownum:=0) r) on duplicate key update overallRank = values(overallRank)");
			Db::execute("delete from zz_ranks_recent where type = '$type'");
			Db::execute("insert into zz_ranks_recent select * from zz_ranks_temporary");
		}
		Db::execute("drop table zz_ranks_temporary");
	}

	private static function stats()
	{
		CLI::out("|g|Stats calculation started");
		Db::execute("set session wait_timeout = 600");
		if (!Util::isMaintenanceMode()) {
			Db::execute("replace into zz_storage values ('maintenance', 'true')");
			Log::log("Maitenance mode engaged");
			sleep(30); // Wait for processes to finish and cleanup
		}

		// Fix unknown group ID's
		$result = Db::query("select distinct shipTypeID, i.groupID from zz_participants p left join ccp_invTypes i on (shipTypeID = i.typeID) where shipTypeID = i.typeID and p.groupID = 0 and shipTypeID != 0");
		foreach ($result as $row) {
			$shipTypeID = $row["shipTypeID"];
			$groupID = $row["groupID"];
			Db::execute("update zz_participants set groupID = $groupID where groupID = 0 and shipTypeID = $shipTypeID");
		}

		Db::execute("create table if not exists zz_stats_recent like zz_stats");
		Db::execute("truncate zz_stats_recent");

		try {
			self::recalc('faction', 'factionID');
			self::recalc('alli', 'allianceID');
			self::recalc('corp', 'corporationID');
			self::recalc('pilot', 'characterID');
			self::recalc('group', 'groupID');
			self::recalc('ship', 'shipTypeID');
			self::recalc('system', 'solarSystemID', false);
			self::recalc('region', 'regionID', false);
		} catch (Exception $e) {
			print_r($e);
		}

		Db::execute("delete from zz_storage where locker = 'maintenance'");
		die();

	}

	private static function recalc($type, $column, $calcKills = true)
	{
			Log::log("Starting stat calculations for $type");
			echo "$type ";

			Db::execute("drop table if exists zz_stats_temporary");
			Db::execute("
					CREATE TABLE `zz_stats_temporary` (
						`killID` int(16) NOT NULL,
						`groupName` varchar(16) NOT NULL,
						`groupNum` int(16) NOT NULL,
						`groupID` int(16) NOT NULL,
						`points` int(16) NOT NULL,
						`price` decimal(16,2) NOT NULL,
						PRIMARY KEY (`killID`,`groupName`,`groupNum`,`groupID`)
						) ENGINE=InnoDB");

			$exclude = "$column != 0";

			echo " losses ";
			Db::execute("insert ignore into zz_stats_temporary select killID, '$type', $column, groupID, points, total_price from zz_participants where $exclude and isVictim = 1 and unix_timestamp(dttm) > (unix_timestamp() - 7776000)");
			Db::execute("insert into zz_stats_recent (type, typeID, groupID, lost, pointsLost, iskLost) select groupName, groupNum, groupID, count(killID), sum(points), sum(price) from zz_stats_temporary group by 1, 2, 3");

			if ($calcKills) {
				echo " kills ";
				Db::execute("truncate table zz_stats_temporary");
				Db::execute("insert ignore into zz_stats_temporary select killID, '$type', $column, vGroupID, points, total_price from zz_participants where $exclude and isVictim = 0 and unix_timestamp(dttm) > (unix_timestamp() - 7776000)");
				Db::execute("insert into zz_stats_recent (type, typeID, groupID, destroyed, pointsDestroyed, iskDestroyed) (select groupName, groupNum, groupID, count(killID), sum(points), sum(price) from zz_stats_temporary group by 1, 2, 3) on duplicate key update destroyed = values(destroyed), pointsDestroyed = values(pointsDestroyed), iskDestroyed = values(iskDestroyed)");
			}

			Db::execute("drop table if exists zz_stats_temporary");

			echo "done!\n";
			Log::log("Finished stat calculations for $type");
	}
}
