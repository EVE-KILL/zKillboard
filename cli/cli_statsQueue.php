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

class cli_statsQueue implements cliCommand
{
	public function getDescription()
	{
		return "Runs statistics against processed kills in the queue.";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function getCronInfo()
	{
		return array(0 => ""); // Always run
	}

	public function execute($parameters, $db)
	{
		$timer = new Timer();
		while ($timer->stop() < 65000) {
			$processedKills = Db::query("select killID from zz_stats_queue limit 100", array(), 0);
			if (count($processedKills) == 0) {
				sleep(5);
				continue;
			}
			foreach($processedKills as $row) {
				$killID = $row["killID"];
				Stats::calcStats($killID, true);
				// Add points and total value to the json stored in the database
				$raw = Killmail::get($killID);
				$json = json_decode($raw, true);
				unset($json["_stringValue"]);
				unset($json["zkb"]);
				$stuff = Db::queryRow("select * from zz_participants where killID = :killID and isVictim = 1", array(":killID" => $killID), 0);
				if ($stuff != null) {
					$zkb = array();
					$zkb["totalValue"] = $stuff["total_price"];
					$zkb["points"] = $stuff["points"];
					$json["zkb"] = $zkb;

					$raw = json_encode($json);
					Db::execute("update zz_killmails set kill_json = :raw where killID = :killID", array(":killID" => $killID, ":raw" => $raw));
				}
				Db::execute("delete from zz_stats_queue where killID = :killID", array(":killID" => $killID));
				Social::beSocial($killID);
			}
		}
	}
}
