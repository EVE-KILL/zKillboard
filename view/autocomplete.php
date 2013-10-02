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

//get the query value
if ($app->request()->isPost())
{
	$query = $app->request()->post('query');
}

//declare the base data/sql etc		
$entities = array(
	/*array('type' => 'faction',     'query' => '',		   																												'image' => 'Alliance/%1$d_32.png'),*/
	array('type' => 'alliance',    'query' => 'SELECT allianceID AS id, name FROM zz_alliances WHERE name LIKE :query OR ticker LIKE :query LIMIT 9',                  'image' => 'Alliance/%1$d_32.png'),
	array('type' => 'corporation', 'query' => 'SELECT corporationID AS id, name FROM zz_corporations WHERE name LIKE :query OR ticker LIKE :query LIMIT 9',            'image' => 'Corporation/%1$d_32.png'),
	array('type' => 'character',   'query' => 'SELECT characterID AS id, name FROM zz_characters WHERE name LIKE :query LIMIT 9',                                      'image' => 'Character/%1$d_32.jpg'),
	array('type' => 'item',        'query' => 'SELECT typeID AS id, typeName AS name, groupID FROM ccp_invTypes WHERE published = 1 AND typeName LIKE :query LIMIT 9', 'image' => 'Type/%1$d_32.png'),
	array('type' => 'system',      'query' => 'SELECT solarSystemID  AS id, solarSystemName AS name FROM ccp_systems WHERE solarSystemName LIKE :query LIMIT 9',       'image' => ''),
	array('type' => 'region',      'query' => 'SELECT regionID AS id, regionName AS name FROM ccp_regions WHERE regionName LIKE :query LIMIT 9',                       'image' => ''),
	array('type' => 'campaign',    'query' => 'SELECT id AS id, campaignTitle as name FROM zz_campaigns WHERE campaignTitle LIKE :query LIMIT 9',                      'image' => ''),
);

// define the faction data
$data = array(
	"Caldari State"			=> array("id" => "500001", "name" => "Caldari State"), 
	"Minmatar Republic"		=> array("id" => "500002", "name" => "Minmatar Republic"), 
	"Amarr Empire"			=> array("id" => "500003", "name" => "Amarr Empire"), 
	"Gallente Federation"	=> array("id" => "500004", "name" => "Gallente Federation")
);

//define our array for the results
$search_results = array();

//for each entity type, get any matches and process them
foreach ($entities as $key => $entity)
{
	if($entity["type"] != "faction") // skip the query since factions aren't in the db anyway
		$results = Db::query($entity['query'], array(":query" => $query . '%'), 30); //see if we have any things that matches the thing

	if($entity["type"] == "faction") // if the type is faction, do the array search stuff
	{
		//check if the data matches factions aswell and add it to the array
		foreach($data as $key => $value)
			if(stripos($key, $query) !== FALSE)
				$results[] = $value;
	}

	//merge the reults into an single array to throw back to the browser
	foreach ($results as $result)
	{
		$search_results[] = array_merge($result, array('type' => $entity['type'], 'image' => sprintf($entity['image'], $result['id'])));
	}
}

//declare out json return type
$app->contentType('application/json; charset=utf-8');

//if we have some results then sort them
if (count($search_results) > 0)
{
	//get the values of the column we intend to sort by
	foreach ($search_results as $key => $row) { $sort_by[$key] = $row['name']; }

	//perfom and return the sorted array
	array_multisort($sort_by, SORT_ASC, SORT_NATURAL, $search_results);	
}

//return the top 15 results as a json object
echo json_encode(array_slice($search_results, 0, 15));
