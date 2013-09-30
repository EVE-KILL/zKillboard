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

class cli_stompReceive implements cliCommand
{
	public function getDescription()
	{
		return "Receives data from the STOMP server. |w|Beware, this is a persistent script. It's run and forget!.|n| Usage: |g|stompReceive";
	}

	public function getAvailMethods()
	{
		return ""; // Space seperated list
	}

	public function getCronInfo()
	{
		return array();
	}

	public function execute($parameters)
	{
		global $stompServer, $stompUser, $stompPassword, $baseAddr;
		$stomp = new Stomp($stompServer, $stompUser, $stompPassword);
		$stomp->setReadTimeout(10);
		$destination = "/topic/kills";
		$stomp->subscribe($destination, array("id" => "zkb-".$baseAddr, "persistent" => "true", "ack" => "client", "prefetch-count" => 1));

		Log::log("StompReceive started");

		$timer = new Timer();
		while($timer->stop() < 599000)
		{
			$frame = $stomp->readFrame();
			if(!empty($frame))
			{
				$killdata = json_decode($frame->body, true);
				if(!empty($killdata))
				{
					$killID = $killdata["killID"];
					$count = Db::queryField("SELECT count(1) AS count FROM zz_killmails WHERE killID = :killID LIMIT 1", "count", array(":killID" => $killID), 0);
					if($count == 0)
					{
						if($killID > 0)
						{
							$hash = Util::getKillHash(null, json_decode($frame->body));
							Db::execute("INSERT IGNORE INTO zz_killmails (killID, hash, source, kill_json) values (:killID, :hash, :source, :json)",
								array("killID" => $killID, ":hash" => $hash, ":source" => "stompQueue", ":json" => json_encode($killdata)));
							$stomp->ack($frame->headers["message-id"]);
							continue;
						}
						else
						{
							$stomp->ack($frame->headers["message-id"]);
							continue;
						}
					}
					else
					{
						$stomp->ack($frame->headers["message-id"]);
						continue;
					}
				}
			}
		}
	}
}
