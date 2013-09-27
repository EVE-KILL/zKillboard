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
class Util
{
	public static function setSubdomainGlobals($key, $row, $type)
	{
		global $subDomainKey, $subDomainRow;
		$subDomainKey = $key;
		$row["type"] = $type;
		$subDomainRow = $row;
		return true;
	}

	public static function isValidSubdomain($subDomain)
	{
		if ($subDomain === null || trim($subDomain) == "") return true;
		$subDomain = str_replace("_", " ", $subDomain);
		$array = array(":subDomain" => $subDomain);
		/*$row = Db::queryRow("select factionID, name from zz_factions where ticker = :subDomain", $array, 3600);
		if ($row != null) return Util::setSubdomainGlobals("factionID", $row, "faction");
		$row = Db::queryRow("select allianceID, name from zz_alliances where ticker = :subDomain order by memberCount desc limit 1", $array, 3600);
		if ($row != null) return Util::setSubdomainGlobals("allianceID", $row, "alliance");
		$row = Db::queryRow("select corporationID, name from zz_corporations where ticker = :subDomain order by memberCount desc limit 1", $array, 3600);
		if ($row != null) return Util::setSubdomainGlobals("corporationID", $row, "corporation");*/
		$row = Db::queryRow("select * from zz_domains where domain = :subDomain", $array, 300);
		if ($row) {
			$entities = Db::query("SELECT * FROM zz_domains_entities WHERE domainID = :domainID", array(":domainID" => $row["domainID"]));
			foreach($entities as $entity) {
				$ent = array();
				$ent["type"] = $entity["entityType"];
				$ent[$entity["entityType"] . "ID"] = $entity["entityID"];
				$ent["name"] = $entity["entityName"];
				return Util::setSubdomainGlobals($entity["entityType"] . "ID", $ent, $entity["entityType"]);
			}
		}
		return false;
	}

	public static function isMaintenanceMode()
	{
		return "true" == Db::queryField("select contents from zz_storage where locker = 'maintenance'", "contents", array(), 0);
	}


	public static function getPheal($keyID = null, $vCode = null)
	{
		global $phealCacheLocation, $apiServer, $baseAddr;

		PhealConfig::getInstance()->http_method = "curl";
		PhealConfig::getInstance()->http_user_agent = "API Fetcher for http://$baseAddr";
		PhealConfig::getInstance()->http_post = false;
		PhealConfig::getInstance()->http_keepalive = true; // default 15 seconds
		PhealConfig::getInstance()->http_keepalive = 10; // KeepAliveTimeout in seconds
		PhealConfig::getInstance()->http_timeout = 30;
		PhealConfig::getInstance()->cache = new PhealFileCache($phealCacheLocation);
		PhealConfig::getInstance()->log = new PhealLogger();
		PhealConfig::getInstance()->api_customkeys = true;
		PhealConfig::getInstance()->api_base = $apiServer;

			if ($keyID != null && $vCode != null) $pheal = new Pheal($keyID, $vCode);
			else $pheal = new Pheal();
		return $pheal;
	}

	public static function pluralize($string)
	{
		if (!Util::endsWith($string, "s")) return $string . "s";
		else return $string . "es";
	}

	public static function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	public static function endsWith($haystack, $needle)
	{
		return substr($haystack, -strlen($needle)) === $needle;
	}

	public static function firstUpper($str)
	{
		if (strlen($str) == 1) return strtoupper($str);
		$str = strtolower($str);
		return strtoupper(substr($str, 0, 1)) . substr($str, 1);
	}

	public static function getKillHash($killID = null, $kill = null)
	{
		if ($killID != null) {
			$json = Db::queryField("select kill_json from zz_killmails where killID = :killID", "kill_json", array(":killID" => $killID), 0);
			if ($json === null) throw new Exception("Cannot find kill $killID");
			$kill = json_decode($json);
			if ($kill === null) throw new Exception("Cannot json_decode $killID");
		}
		if ($kill === null) throw new Exception("Can't hash an empty kill");

		$hashStr = "";
		$hashStr .= ":$kill->killTime:$kill->solarSystemID:$kill->moonID:";
		$victim = $kill->victim;
		$hashStr .= ":$victim->characterID:$victim->shipTypeID:$victim->damageTaken:";

		return hash("sha256", $hashStr);
	}

	public static function calcX($slot, $size)
	{
		$angle = $slot * (360 / 32) - 4;
		$rad = deg2rad($angle);
		$radius = $size / 2;
		return (int)(($radius * cos($rad)));
	}

	public static function calcY($slot, $size)
	{
		$angle = $slot * (360 / 32) - 4;
		$rad = deg2rad($angle);
		$radius = $size / 2;
		return (int)(($radius * sin($rad)));
	}

	private static $formatIskIndexes = array("", "k", "m", "b", "t", "tt", "ttt");

	public static function formatIsk($value)
	{
		$numDecimals = (((int)$value) == $value) && $value < 10000 ? 0 : 2;
		if ($value == 0) return number_format(0, $numDecimals);
		if ($value < 10000) return number_format($value, $numDecimals);
		$iskIndex = 0;
		while ($value > 999.99) {
			$value /= 1000;
			$iskIndex++;
		}
		return number_format($value, $numDecimals) . self::$formatIskIndexes[$iskIndex];
	}

	public static function convertUriToParameters($additionalParameters = array())
	{
		$parameters = array();
		@$uri = $_SERVER["REQUEST_URI"];
		$split = explode("/", $uri);
		if ($additionalParameters != null) foreach($additionalParameters as $key=>$value) {
			if (strpos($key, "ID") !== false) {
				$split[] = $key;
				$split[] = $value;
			}
		}
		//$split = array_merge($additionalParameters, $split);
		$currentIndex = 0;
		foreach ($split as $key) {
			$value = $currentIndex + 1 < sizeof($split) ? $split[$currentIndex + 1] : null;
			switch ($key) {
				case "kills":
				case "losses":
				case "w-space":
				case "solo":
					$parameters[$key] = true;
					break;
				case "character":
				case "characterID":
				case "corporation":
				case "corporationID":
				case "alliance":
				case "allianceID":
				case "faction":
				case "factionID":
				case "ship":
				case "shipID":
				case "shipTypeID":
				case "group":
				case "groupID":
				case "system":
				case "solarSystemID":
				case "systemID":
				case "region":
				case "regionID":
					if ($value != null) {
						if (strpos($key, "ID") === false) $key = $key . "ID";
						if ($key == "systemID") $key = "solarSystemID";
						else if ($key == "shipID") $key = "shipTypeID";
						$exploded = explode(",", $value);
						if (sizeof($exploded) > 10) throw new Exception("Too many IDs! Max: 10");
						$parameters[$key] = $exploded;
					}
					break;
				case "page":
					$value = (int)$value;
					if ($value < 1) throw new Exception("page must be greater than or equal to 1");
					$parameters[$key] = $value;
					break;
				case "orderDirection":
					if (!($value == "asc" || $value == "desc")) throw new Exception("Invalid orderDirection!  Allowed: asc, desc");
					$parameters[$key] = "desc";
					$parameters[$key] = $value;
					break;
				case "pastSeconds":
					$value = (int) $value;
					if (($value / 86400) > 7) throw new Exception("pastSeconds is limited to a max of 7 days");
					$parameters[$key] = $value;
					break;
				case "startTime":
				case "endTime":
					$time = strtotime($value);
					if($time < 0) throw new Exception("$value is not a valid time format");
					$parameters[$key] = $value;
					break;
				case "limit":
					$value = (int) $value;
					if ($value < 200) $parameters["limit"] = $value;
					break;
				case "xml":
					$value = true;
				default:
					if (is_numeric($value) && $value < 0) throw new Exception("$value is not a valid entry for $key");
					if ($key != "" && $value != "") $parameters[$key] = $value;
			}
			$currentIndex++;
		}
		if (isset($parameters["page"]) && $parameters["page"] > 10 && isset($parameters["api"])) {
			// Verify that the request is for a character, corporation, or alliance
			// This will prevent scrape attempts against regions, ships, systems, etc. which
			// are very hard against the database
			$legitEntities = array("characterID", "corporationID", "allianceID");
			$legit = false;
			foreach ($legitEntities as $entity) {
				$legit |= in_array($entity, array_keys($parameters));
			}
			if (!$legit) throw new Exception("page > 10 not allowed for this modifier type, please see API documentation");
		}
		return $parameters;
	}

	public static function shortString($string, $maxLength = 8)
	{
		if (strlen($string) <= $maxLength) return $string;
		return substr($string, 0, $maxLength - 3) . "...";
	}

	public static function truncate($str, $length = 200, $trailing = "...")
	{
		$length -= mb_strlen($trailing);
		if (mb_strlen($str) > $length) {
			// string exceeded length, truncate and add trailing dots
			return mb_substr($str, 0, $length) . $trailing;
		}
		else
		{
			// string was already short enough, return the string
			$res = $str;
		}
		return $res;
	}

	public static function pageTimer()
	{
		global $timer;
		return $timer->stop();
	}

	public static function isActive($pageType, $currentPage, $retValue = "active")
	{
		return strtolower($pageType) == strtolower($currentPage) ? $retValue : "";
	}

	private static $months = array("", "JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC");

	public static function getMonth($month)
	{
		return self::$months[$month];
	}

	private static $longMonths = array("", "January", "February", "March", "April", "May", "June", "July", "August",
			"September", "October", "November", "December");

	public static function getLongMonth($month)
	{
		return self::$longMonths[$month];
	}

	public static function scrapeCheck()
	{
		global $app, $apiBinAttempts, $apiTimeBetweenAccess, $apiWhiteList;

		$ip = IP::get();
		if(!in_array($ip, $apiWhiteList))
		{
			// Make a pool with the IP as an MD5
			// Store a count in the cache with the md5 as hash
			// Count it up, if it hits $apiBinAttempts, he need to wait atleast $apiTimeBetweenAccess seconds before another request can be made.
			$md5 = md5("IP:$ip");
			$bin = Cache::get($md5);

			if($bin != null && count($bin) >= $apiBinAttempts)
			{
				$date = date("Y-m-d H:i:s");
				$cachedUntil = date("Y-m-d H:i:s", time() + $apiTimeBetweenAccess);
				if(stristr($_SERVER["REQUEST_URI"], "xml"))
				{
					$data = "<?xml version=\"1.0\" encoding=\"UTF-8\?" . ">"; // separating the ? and > allows vi to still color format code nicely
					$data .= "<eveapi version=\"2\" zkbapi=\"1\">";
					$data .= "<currentTime>$date</currentTime>";
					$data .= "<result>";
					$data .= "<error>You have requested data too fast, please wait $apiTimeBetweenAccess seconds..</error>";
					$data .= "</result>";
					$data .= "<cachedUntil>$cachedUntil</cachedUntil>";
					$data .= "</eveapi>";
					header("Content-type: text/xml; charset=utf-8");
				}
				else
				{
					header("Content-type: application/json; charset=utf-8");
					$data = json_encode(array("Error" => "You have requested data too fast, please wait $apiTimeBetweenAccess seconds..", "cachedUntil" => $cachedUntil));
				}
				header("Retry-After: " . $cachedUntil . " GMT");
				header("HTTP/1.1 403 Forbidden");
				header("Etag: ".(md5(serialize($data))));
				header("+".$apiTimeBetweenAccess." seconds");
				echo $data;
				die();
			}

			$bin[] = time();
			Cache::set($md5, $bin, $apiTimeBetweenAccess);
		}
	}

	public static function isValidCallback($subject)
	{
		$identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';

		$reserved_words = array('break', 'do', 'instanceof', 'typeof', 'case',
				'else', 'new', 'var', 'catch', 'finally', 'return', 'void', 'continue', 
				'for', 'switch', 'while', 'debugger', 'function', 'this', 'with', 
				'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum', 
				'extends', 'super', 'const', 'export', 'import', 'implements', 'let', 
				'private', 'public', 'yield', 'interface', 'package', 'protected', 
				'static', 'null', 'true', 'false');

		return preg_match($identifier_syntax, $subject) && ! in_array(mb_strtolower($subject, 'UTF-8'), $reserved_words);
	}

	public static function deleteKill($killID)
	{
		if($killID < 0)
		{
			// Verify the kill exists
			$count = Db::execute("select count(*) count from zz_killmails where killID = :killID", array(":killID" => $killID));
			if ($count == 0) return false;
			// Remove it from the stats
			Stats::calcStats($killID, false);
			// Remove it from the kill tables
			Db::execute("delete from zz_participants where killID = :killID", array(":killID" => $killID));
			Db::execute("delete from zz_items where killID = :killID", array(":killID" => $killID));
			// Mark the kill as deleted
			Db::execute("update zz_killmails set processed = 2 where killID = :killID", array(":killID" => $killID));
			return true;
		}
		return false;
	}
}
