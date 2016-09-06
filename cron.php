<?php
/**
 * Google Search Console Archive
 *
 * Copyright (C) 2016 : Cyrille Mahieux (c.mahieux@of2m.fr) & Vincent Robert (v.robert@of2m.fr) @ Ouest France Multimedia
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

# Bootstrap
require 'bootstrap.php';

?>
                   .
                 ....8ob.
              o88888888888b.
          ..o888888888888888b..
          888888888888888P""888P
         8888888888888888888888.
        d88888888888888888888888bc.
       o8888888888888888"   ""38888Poo..
      .8888888888P888888        "38888888
      88888888888 8888888eeeeee.   ""38"8
     P" 888888888 """""       `""o._.oP
        8888888888.
        88888888888
        '888888888 8b.
         "88888888b  """"3booooooo..
          "888888888888888b         "b.
           "8888888888888888888888b    "8
            "8888888888888888888888888   b
                ""888888888888888888888  c
                   "8888888888888888888  P
                    "88888888888888888888"
                    .88888888888888888888
                   .88888 ><)))°> 88888P    Google Console API Query
                 od888888888888888888P"

Configuration :
  Database : <?php echo $configuration['database']['username'] . '@' . $configuration['database']['host'] . ':' . $configuration['database']['port'] . '/' . $configuration['database']['database'] . PHP_EOL?>
Usage : php cron.php $from
        from : days of data history to check from now
Docs : https://developers.google.com/webmaster-tools/v3/searchanalytics/query

<?php
# Parameters
$date['from'] = (isset($_GET['start'])) ? $_GET['start'] : date('Y-m-d', (isset($argv[1]) ? strtotime('-' . int($argv[1]) . ' days') : strtotime('-7 days')));
$date['to'] = (isset($_GET['end'])) ? $_GET['end'] : date('Y-m-d', strtotime('-1 days'));

# Making Credential for API
$credentials = new Google_Auth_AssertionCredentials($configuration['api']['login'], $configuration['api']['scope'], $configuration['api']['key']);

# Creating Client & Applying Credentials
$client = new Google_Client();
$client->setAssertionCredentials($credentials);
if ($client->getAuth()->isAccessTokenExpired()) {
    $client->getAuth()->refreshTokenWithAssertion();
}

# Variables
$sql = array();
$pages = array();
$queries = array();
$commands = array();

# Starting Webmaster Tools Service
$service = new Google_Service_Webmasters($client);

# Database Object
$database = new Database($configuration);

# Trace
echo PHP_EOL . 'Calling Search Console API ';

# Iterating each Website
foreach ($configuration['websites'] as $website) {

    # Iterating each Device
    foreach ($configuration['device'] as $device) {

        # Iterating each Days
        for ($time = $date['from']; strtotime($time) <= strtotime($date['to']); $time = date('Y-m-d', strtotime($time . ' +1 day'))) {
            # WebmasterTools Request Object
            $query = new SearchConsole_Query($service);

            # Top Page
            if (($data = $query->pages($website, $device, array(
                'from' => $time,
                'to' => $time))) != false) {

                # Using Results
                foreach ($data->getRows() as $data) {
                    # SQL Statistics Query
                    $sql[] = 'REPLACE INTO ' . str_replace(array(
                        '{%device%}',
                        '{%website%}'), array(
                        $device,
                        $website['table']), $configuration['database']['table']['pages']) . ' (`page`,`impressions`,`clicks`,`position`,`date`)
                          VALUES (\'' . $database->_handle()->real_escape_string(str_replace($website['url'], '', $data->keys[0])) . '\',' . (integer)$data->impressions . ',' . (integer)$data->clicks . ',' . (float)round($data->position, 1) . ',\'' . $data->keys[1] . '\');';
                }

                # Trace
                echo '.';
            } else {
                # Trace
                echo PHP_EOL . 'Error with SearchConsole_Query::pages(' . $website['url'] . ',' . $device . ',' . $time . ')';
            }

            # Top Query
            if (($data = $query->queries($website, $device, array(
                'from' => $time,
                'to' => $time))) != false) {

                foreach ($data->getRows() as $data) {
                    # SQL Statistics Query
                    $sql[] = 'REPLACE INTO ' . str_replace(array(
                        '{%device%}',
                        '{%website%}'), array(
                        $device,
                        $website['table']), $configuration['database']['table']['queries']) . ' (`query`,`impressions`,`clicks`,`position`,`date`)
                          VALUES (\'' . $database->_handle()->real_escape_string($data->keys[0]) . '\',' . (integer)$data->impressions . ',' . (integer)$data->clicks . ', \'' . (float)round($data->position, 1) . '\',\'' . $data->keys[1] . '\')';
                }

                # Trace
                echo '.';
            } else {
                # Trace
                echo PHP_EOL . 'Error with SearchConsole_Query::queries(' . $website['url'] . ',' . $device . ',' . $time . ')';
            }
        }
    }
}

# Trace
echo PHP_EOL . 'Inserting into Database ';

# Executing Queries
$database->execute($sql);

echo PHP_EOL . 'End of Script' . PHP_EOL;