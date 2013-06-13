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
 * FileCache for zKillboard (Basically copies what Memcached does)
 */
class FileCache extends AbstractCache
{
	/**
	 * @param $cacheDir the default cache dir
	 * @param $cacheTime the default cache time (5 minutes)
	 */
	
	var $cacheDir = "cache/queryCache/";
	var $cacheTime = 300;

	function __construct()
	{
		if(!is_dir($this->cacheDir))
			mkdir($this->cacheDir);
	}

	/**
	 * Gets the data
	 * 
	 * @param $key
	 * @return array
	 */
	public function get($key)
	{
		if(file_exists($this->cacheDir.sha1($key)))
		{
			$time = time();
			$data = self::getData($key);
			$age = $data["age"];
			$data = json_decode($data["data"], true);
			if($age <= $time)
			{
				@unlink($this->cacheDir.sha1($key));
				return false;	
			}
			return $data;
		}
		else
			return false;
	}

	/**
	 * Sets data
	 * 
	 * @param $key
	 * @param $value
	 * @param $timeout
	 * 
	 * return bool
	 */
	public function set($key, $value, $timeout)
	{
		return self::setData($key, $value, $timeout) !== false;
	}

	/**
	 * Replaces data
	 * 
	 * @param $key
	 * @param $value
	 * @param $timeout
	 * @return array
	 */
	public function replace($key, $value, $timeout)
	{
		if(file_exists($this->cacheDir.sha1($key)))
		{
			@unlink($this->cacheDir.sha1($key));
			if(self::setData($key, $value, $timeout) !== false)
				return true;
		}

		return false;
	}

	/**
	 * Deletes a key
	 * 
	 * @param $key
	 * @return bool
	 */
	public function delete($key)
	{
		try
		{
			@unlink($this->cacheDir.sha1($key));
		}
		catch (Exception $e)
		{
			return false;
		}
		return true;
	}

	/**
	 * Increments value
	 * 
	 * @param $key
	 * @return bool
	 */
	public function increment($key, $step = 1, $timeout = 0)
	{
		$data = self::getData($key);
		$data = json_decode($data["data"], true);

		try
		{
			@unlink($this->cacheDir.sha1($key));
			return self::setData($key, $data+$step);
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Decrements value
	 * 
	 * @param $key
	 * @return bool
	 */
	public function decrement($key, $step = 1, $timeout = 0)
	{
		$data = self::getData($key);
		$data = json_decode($data["data"], true);

		try
		{
			@unlink($this->cacheDir.sha1($key));
			return self::setData($key, $data-$step);
		}
		catch (Exception $e)
		{
			return false;
		}
	}

	/**
	 * Flushes the cache
	 */
	public function flush()
	{
		$dir = opendir($this->cacheDir);
		while($file = readdir($dir))
		{
			@unlink($this->cacheDir.$file);
		}
	}

	/**
	 * Sets data to cache file
	 * 
	 * @param $key
	 * @param $value
	 * @param $timeout
	 * 
	 * return bool
	 */
	private function setData($key, $value, $timeout = NULL)
	{
		if(!$timeout)
			$timeout = $this->cacheTime;

		try
		{
			// fix, so timeout will be timestamp based
			$timeout= time() + $timeout;

			$data = $timeout."%".json_encode($value);
			file_put_contents($this->cacheDir.sha1($key), $data);
		}
		catch (Exception $e)
		{
			return false;
		}
		return $value;
	}

	/**
	 * Gets the data from the cache
	 * 
	 * @param $key
	 * @return array
	 */
	private function getData($key)
	{
		// @todo real error handling, not just surpression.
		$data = @file_get_contents($this->cacheDir.sha1($key));
		$f = explode("%", $data);
		$age = array_shift($f);
		$data = implode($f);
		return array("age" => $age, "data" => $data);
	}

	/**
	 * Cleans up old and unused query cache files
	 */
	function cleanUp()
	{
		$dir = opendir($this->cacheDir);
		while($file = readdir($dir))
		{
			if($file != "." && $file != "..")
			{
				$data = self::getData($file);
				$age = $data["age"];
				$time = time();
				if($age <= $time)
				{
					@unlink($this->cacheDir.$file);
				}
			}
		}
	}
}
