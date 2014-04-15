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

class Social
{

	public static function findConversations()
	{
		$locker = "Social:lastSocialTime";
		$lastSocialTime = Storage::retrieve($locker, null);
		if ($lastSocialTime == null)
			$result = Db::query("select killID, insertTime from zz_killmails where killID > 0 and processed = 1 and insertTime >= date_sub(now(), interval 10 minute)", array(), 0);
		else 
			$result = Db::query("select killID, insertTime from zz_killmails where killID > 0 and processed = 1 and insertTime >= :last", array(":last" => $lastSocialTime), 0);
		foreach ($result as $row) {
			$lastSocialTime = $row["insertTime"];
			self::beSocial($row["killID"]);
		}
		Storage::store($locker, $lastSocialTime);
	}

	public static function beSocial($killID)
	{
		if ($killID < 0) return;
		$ircMin = 5000000000;
		$twitMin = 10000000000;

		$count = Db::queryField("select count(*) count from zz_social where killID = :killID", "count", array(":killID" => $killID), 0);
		if ($count != 0) return;

		// Get victim info
		$victimInfo = Db::queryRow("select * from zz_participants where killID = :killID and isVictim = 1", array(":killID" => $killID));
		if ($victimInfo == null) return;
		$totalPrice = $victimInfo["total_price"];

		$worthIt = false;
		$worthIt |= $totalPrice >= $ircMin;
		$worthIt |= $victimInfo["corporationID"] == 1000197 && $totalPrice >= 100000000 && $victimInfo["shipTypeID"] != 670;
		if (!$worthIt) return;

		$tweetIt = false;
		$tweetIt |= $totalPrice >= $twitMin;
		$tweetIt |= $victimInfo["corporationID"] == 1000197 && $totalPrice >= 100000000 && $victimInfo["shipTypeID"] != 670;
		
		Info::addInfo($victimInfo);

		$url = "https://zkillboard.com/detail/$killID/";
		if ($totalPrice >= $twitMin) $url = Twit::shortenUrl($url);
		$message = "|g|" . $victimInfo["shipName"] . "|n| worth |r|" . Util::formatIsk($totalPrice) . " ISK|n| was destroyed! $url";
		if (!isset($victimInfo["characterName"])) $victimInfo["characterName"] = $victimInfo["corporationName"];
		if (strlen($victimInfo["characterName"]) < 25) {
			$name = $victimInfo["characterName"];
			if (Util::endsWith($name, "s")) $name .= "'";
			else $name .= "'s";
			$message = "$name $message";
		}
		if ($victimInfo["corporationID"] == 1000197) $message = "[Live Event] $message";

		Db::execute("insert into zz_social (killID) values (:killID)", array(":killID" => $killID));

		Log::irc("$message");
		$message = Log::stripIRCColors($message);

		if ($tweetIt) {
			$message .= " #tweetfleet #eveonline";
			$return = Twit::sendMessage($message);
			$twit = "https://twitter.com/zkillboard/status/" . $return->id;
			Log::irc("Message was also tweeted: |g|$twit");
		}	
	}
}
