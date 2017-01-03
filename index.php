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
header('Content-Type: text/html; charset=utf-8');

# Stream
ob_start();

# Bootstrap
require 'bootstrap.php';

# Parameters
$website = ((isset($_REQUEST['website'])) && (empty($_REQUEST['website']) === false)) ? $_REQUEST['website'] : key($configuration['websites']);
$device = ((isset($_REQUEST['device'])) && (empty($_REQUEST['device']) === false)) ? $_REQUEST['device'] : 'desktop';
$query = ((isset($_REQUEST['query'])) && (empty($_REQUEST['query']) === false)) ? $_REQUEST['query'] : 'page';
$search = ((isset($_REQUEST['search'])) && (empty($_REQUEST['search']) === false)) ? urldecode($_REQUEST['search']) : null;
$mode = ((isset($_REQUEST['mode'])) && (empty($_REQUEST['mode']) === false)) ? $_REQUEST['mode'] : null;
$compare = (isset($_REQUEST['compare'])) ? $_REQUEST['compare'] : false;
$aggregate = (isset($_REQUEST['aggregate'])) ? true : false;
$group = ((isset($_REQUEST['group'])) && (empty($_REQUEST['group']) === false)) ? $_REQUEST['group'] : 'day';
$history = ((isset($_REQUEST['history'])) && (empty($_REQUEST['history']) === false)) ? $_REQUEST['history'] : 180;

# Database Object
$database = new Database($configuration);

# Counting Keywords/Pages & Last Modify
$last = date('Y-m-d', $database->last($query, $website, ($device == '*') ? 'desktop' : $device));

# Page/Query Detail Mode
switch ($mode) {
    case 'detail' :
        # Making Interval
        $interval['base'] = array(
            'from' => date('Y-m-d', is_numeric($history) ? strtotime($last . ' - ' . $history . ' days') : 0),
            'to' => $last);

        # Detail Mode : Keywords/Pages, Database Request
        $data = $database->detail($query, $website, $device, $search, $aggregate, $interval['base'], $group);

        # Stream for Trace/Errors
        $stream = ob_get_contents();
        ob_end_clean();

        # Templates
        include dirname(__FILE__) . '/resources/templates/header.phtml';
        include dirname(__FILE__) . '/resources/templates/detail.phtml';

        break;
    case 'keywords' :
        # Keywords Mode : Keywords that lead to a specific Page
        $interval['base'] = isset($_REQUEST['date']) ? $_REQUEST['date'] : array(
            'from' => $last,
            'to' => $last);

        # Making Credential for API
        $credentials = new Google_Auth_AssertionCredentials($configuration['api']['login'], $configuration['api']['scope'], $configuration['api']['key']);

        # Creating Client & Applying Credentials
        $client = new Google_Client();
        $client->setAssertionCredentials($credentials);
        if (@$client->getAuth()->isAccessTokenExpired()) {
            @$client->getAuth()->refreshTokenWithAssertion();
        }

        # Starting Webmaster Tools Service
        $service = new Google_Service_Webmasters($client);

        # WebmasterTools Request Object
        $model = new SearchConsole_Query($service);

        # Query that lead to Page, checking HTTPS
        $url = $configuration['websites'][$website]['url'];
        $parameters = array(
            'url' => $url,
            'page' => $configuration['websites'][$website]['url'] . $search);

        # Calling SearchConsole API
        $data = $model->keywords($parameters, $device, $interval['base']);

        # Stream for Trace/Errors
        $stream = ob_get_contents();
        ob_end_clean();

        # Templates
        include dirname(__FILE__) . '/resources/templates/header.phtml';
        include dirname(__FILE__) . '/resources/templates/keywords.phtml';
        break;
    case 'matrix' :
        # Getting Axis Variables
        $xAxis = isset($_REQUEST['xAxis']) ? $_REQUEST['xAxis'] : 'impressions';
        $yAxis = isset($_REQUEST['yAxis']) ? $_REQUEST['yAxis'] : 'clicks';
        $zAxis = isset($_REQUEST['zAxis']) ? $_REQUEST['zAxis'] : 'impressions';

        # Getting Filters
        $filters = $database->filters($query, $website);
        $interval['base'] = isset($_REQUEST['date']) ? $_REQUEST['date'] : array(
            'from' => $last,
            'to' => $last);

        # Making request for each filter
        foreach ($filters as $filter) {
            $data[$filter[0]] = $database->detail($query, $website, $device, $filter[2], true, $interval['base']);

            # Summing for the Interval
            foreach ($data[$filter[0]] as $item) {
                if (isset($data[$filter[0]]['position']) === true) {
                    # Averaging Position
                    $data[$filter[0]]['position'] = round(($data[$filter[0]]['position'] * $data[$filter[0]]['impressions'] + $item[2] * $item[0]) / ($data[$filter[0]]['impressions'] + $item[0]), 1);
                    $data[$filter[0]]['impressions'] += $item[0];
                    $data[$filter[0]]['clicks'] += $item[1];
                } else {
                    $data[$filter[0]]['position'] = $item[2];
                    $data[$filter[0]]['impressions'] = $item[0];
                    $data[$filter[0]]['clicks'] = $item[1];
                }
            }
        }

        # Computing Compare Date Interval with Previous Year
        if ($interval['base']['from'] != $interval['base']['to']) {
            $interval['compare'] = array(
                'from' => date('Y-m-d', strtotime($interval['base']['from'] . (($compare == 'year') ? '- 1 year' : ' - ' . (1 + round((strtotime($interval['base']['to']) - strtotime($interval['base']['from'])) / 86400)) . ' days'))),
                'to' => date('Y-m-d', (($compare == 'year') ? strtotime($interval['base']['to'] . ' - 1 year') : strtotime($interval['base']['from'] . ' - 1 day'))));
            $aggregate = true;
        } else {
            $interval['compare'] = array(
                'from' => date('Y-m-d', strtotime($interval['base']['from'] . (($compare == 'year') ? ' - 1 year' : ' - 1 day'))),
                'to' => date('Y-m-d', strtotime($interval['base']['from'] . (($compare == 'year') ? ' - 1 year' : ' - 1 day'))));
            $aggregate = false;
        }

        # Always Compare Mode
        $compare = array();

        # Making request for each filter
        foreach ($filters as $filter) {
            # Making Query
            $items = $database->detail($query, $website, $device, $filter[2], true, $interval['compare']);

            # Sorting
            foreach ($items as $item) {
                if (isset($compare[$filter[0]]['#' . $item[0]]) === true) {
                    # Averaging Position
                    $compare[$filter[0]]['#' . $item[0]][2] = round(($compare[$filter[0]]['#' . $item[0]][2] * $compare[$filter[0]]['#' . $item[0]][0] + $item[2] * $item[0]) / ($compare[$filter[0]]['#' . $item[0]][0] + $item[0]), 0);
                    $compare[$filter[0]]['#' . $item[0]][0] += $item[0];
                    $compare[$filter[0]]['#' . $item[0]][1] += $item[1];
                } else {
                    $compare[$filter[0]]['#' . $item[0]] = $item;
                }
            }

            # Summing for the Interval (foreach not really nescessary)
            foreach ($compare[$filter[0]] as $item) {
                if (isset($compare[$filter[0]]['position']) === true) {
                    # Averaging Position
                    $compare[$filter[0]]['position'] = round(($compare[$filter[0]]['position'] * $compare[$filter[0]]['impressions'] + $item[2] * $item[0]) / ($compare[$filter[0]]['impressions'] + $item[0]), 0);
                    $compare[$filter[0]]['impressions'] += $item[0];
                    $compare[$filter[0]]['clicks'] += $item[1];
                } else {
                    $compare[$filter[0]]['position'] = $item[2];
                    $compare[$filter[0]]['impressions'] = $item[0];
                    $compare[$filter[0]]['clicks'] = $item[1];
                }
            }
        }

        # Making Comparison
        $statistics = array();
        foreach ($filters as $filter) {
            if (isset($compare[$filter[0]])) {
                $statistics[$filter[0]]['position'] = $data[$filter[0]]['position'] - $compare[$filter[0]]['position'];
                $statistics[$filter[0]]['impressions'] = max(- 100, min(100, round(($data[$filter[0]]['impressions'] - $compare[$filter[0]]['impressions']) / $compare[$filter[0]]['impressions'] * 100, 0)));
                $statistics[$filter[0]]['clicks'] = max(- 100, min(100, round(($data[$filter[0]]['clicks'] - $compare[$filter[0]]['clicks']) / $compare[$filter[0]]['clicks'] * 100, 0)));
                $statistics[$filter[0]]['ctr'] = round($data[$filter[0]]['clicks'] / $data[$filter[0]]['impressions'] * 100 - $compare[$filter[0]]['clicks'] / $compare[$filter[0]]['impressions'] * 100, 0);
            }
        }

        # Stream for Trace/Errors
        $stream = ob_get_contents();
        ob_end_clean();

        # Templates
        include dirname(__FILE__) . '/resources/templates/header.phtml';
        include dirname(__FILE__) . '/resources/templates/matrix.phtml';
        break;
    default :
        # List Mode : Keywords/Pages for website
        $interval['base'] = isset($_REQUEST['date']) ? $_REQUEST['date'] : array(
            'from' => $last,
            'to' => $last);

        # Computing Date Interval
        if ($interval['base']['from'] != $interval['base']['to']) {
            $aggregate = true;
        } else {
            $aggregate = false;
        }

        # Making Queries
        $data = $database->search($query, $website, $device, $interval['base'], $search, $aggregate);
        $filters = $database->filters($query, $website);

        # Compare Mode
        if ($compare !== false) {
            # Computing Compare Date Interval with Previous Year
            if ($interval['base']['from'] != $interval['base']['to']) {
                $interval['compare'] = array(
                    'from' => date('Y-m-d', strtotime($interval['base']['from'] . (($compare == 'year') ? '- 1 year' : ' - ' . (1 + round((strtotime($interval['base']['to']) - strtotime($interval['base']['from'])) / 86400)) . ' days'))),
                    'to' => date('Y-m-d', (($compare == 'year') ? strtotime($interval['base']['to'] . ' - 1 year') : strtotime($interval['base']['from'] . ' - 1 day'))));
                $aggregate = true;
            } else {
                $interval['compare'] = array(
                    'from' => date('Y-m-d', strtotime($interval['base']['from'] . (($compare == 'year') ? ' - 1 year' : ' - 1 day'))),
                    'to' => date('Y-m-d', strtotime($interval['base']['from'] . (($compare == 'year') ? ' - 1 year' : ' - 1 day'))));
                $aggregate = false;
            }

            # Making Query
            $items = $database->search($query, $website, $device, $interval['compare'], $search, $aggregate);

            # Sorting
            $compare = array();
            foreach ($items as $item) {
                $compare['#' . $item[0]] = $item;
            }
        }

        # Stream for Trace/Errors
        $stream = ob_get_contents();
        ob_end_clean();

        # Templates
        include dirname(__FILE__) . '/resources/templates/header.phtml';
        include dirname(__FILE__) . '/resources/templates/list.phtml';
}
?>
</div>
</body>
</html>
