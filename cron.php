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
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

# Bootstrap
require 'bootstrap.php';
?>
    Configuration :
    Database : <?php echo $configuration['database']['username'] . '@' . $configuration['database']['host'] . ':' . $configuration['database']['port'] . '/' . $configuration['database']['database'] . PHP_EOL ?>
    Usage : php cron.php $from
    $from : days of data history to check from now
    Docs : https://developers.google.com/webmaster-tools/v3/searchanalytics/query
<?php
# Parameters
$date['from'] = (isset($_GET['start'])) ? $_GET['start'] : date('Y-m-d', (isset($argv[1]) ? strtotime('-' . (int)$argv[1] . ' days') : strtotime('-7 days')));
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
                    'to' => $time))) != false
            ) {
                # Using Results
                foreach ($data->getRows() as $data) {
                    # SQL Statistics Query
                    $sql[] = 'INSERT INTO ' . str_replace(array(
                            '{%device%}',
                            '{%website%}'), array(
                            $device,
                            $website['table']), $configuration['database']['table']['pages']) . ' (`page`,`impressions`,`clicks`,`position`,`date`)
                          VALUES (\'' . substr($database->_handle()->real_escape_string(str_replace($website['url'], '', $data->keys[0])), 0, 250) . '\',' . (integer)$data->impressions . ',' . (integer)$data->clicks . ',' . (float)round($data->position, 1) . ',\'' . $data->keys[1] . '\')
                          ON DUPLICATE KEY UPDATE impressions = ' . (integer)$data->impressions . ', clicks = ' . (integer)$data->clicks . ', position = ' . (float)round($data->position, 1) . ';';
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
                    'to' => $time))) != false
            ) {
                foreach ($data->getRows() as $data) {
                    # SQL Statistics Query
                    $sql[] = 'INSERT INTO ' . str_replace(array(
                            '{%device%}',
                            '{%website%}'), array(
                            $device,
                            $website['table']), $configuration['database']['table']['queries']) . ' (`query`,`impressions`,`clicks`,`position`,`date`)
                          VALUES (\'' . $database->_handle()->real_escape_string($data->keys[0]) . '\',' . (integer)$data->impressions . ',' . (integer)$data->clicks . ', \'' . (float)round($data->position, 1) . '\',\'' . $data->keys[1] . '\')
                          ON DUPLICATE KEY UPDATE impressions = ' . (integer)$data->impressions . ', clicks = ' . (integer)$data->clicks . ', position = ' . (float)round($data->position, 1) . ';';
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
