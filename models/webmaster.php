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
class SearchConsole_Query
{
    private $_service = null;
    private $_request = null;
    private $_website = null;
    private $_filters = array();

    public function __construct($service)
    {
        # Configuration
        $this->_service = $service;

        # Request
        $this->_request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();

        # Default Values
        $this->_request->setSearchType('web');
        $this->_request->setRowLimit(200);
    }

    # Shortlinks
    public function pages($website, $device, $date)
    {
        # Making some time before request
        usleep(15000);

        # Group Clause
        $group = array(
            'page',
            'date',
            'device');

        # Executing Request
        return $this->website($website['url'])->device($device)->from($date['from'])->to($date['to'])->group($group)->limit(5000)->execute();
    }

    public function queries($website, $device, $date)
    {
        # Making some time before request
        usleep(15000);

        # Group Clause
        $group = array(
            'query',
            'date',
            'device');

        # Executing Request
        return $this->website($website['url'])->device($device)->from($date['from'])->to($date['to'])->group($group)->limit(5000)->execute();
    }

    # Shortlinks
    public function keywords($website, $device, $date)
    {
        # Variable
        $data = array();

        # Group Clause
        $group = array(
            'page',
            'query',
            'date',
            'device');

        # Executing Request
        $api = $this->website($website['url'])->device($device)->page($website['page'])->from($date['from'])->to($date['to'])->group($group)->limit(5000)->execute();

        # Aggregation
        if ($date['from'] != $date['to']) {
            foreach ($api->getRows() as $id => $row) {
                if (isset($data[$row->keys[1]]) === false) {
                    $data[$row->keys[1]] = array();
                    $data[$row->keys[1]]['clicks'] = $row['clicks'];
                    $data[$row->keys[1]]['impressions'] = $row['impressions'];
                    $data[$row->keys[1]]['position'] = $row['position'];
                    $data[$row->keys[1]]['ctr'] = $row['ctr'];
                } else {
                    $data[$row->keys[1]]['clicks'] += $row['clicks'];
                    $data[$row->keys[1]]['impressions'] += $row['impressions'];
                    $data[$row->keys[1]]['position'] = ($data[$row->keys[1]]['position'] * $data[$row->keys[1]]['impressions'] + $row['position'] * $row['impressions']) / ($data[$row->keys[1]]['impressions'] + $row['impressions']);
                    $data[$row->keys[1]]['ctr'] = $row['clicks'] / $row['impressions'];
                }
            }
        } else {
            foreach ($api->getRows() as $row) {
                $data[$row->keys[1]] = array(
                    'clicks' => $row['clicks'],
                    'impressions' => $row['impressions'],
                    'position' => $row['position'],
                    'ctr' => $row['ctr']);
            }
        }
        return $data;
    }

    public function execute()
    {
        # Adding Filters
        $filters = new Google_Service_Webmasters_ApiDimensionFilterGroup();
        $filters->setFilters($this->_filters);

        $this->_request->setDimensionFilterGroups(array(
            $filters));

        try {
            # Executing Query
            return $this->_service->searchanalytics->query($this->_website, $this->_request);
        } catch (Exception $e) {
            # Waiting before Requesting Again
            sleep(20);

            # Executing Query
            return $this->_service->searchanalytics->query($this->_website, $this->_request);
        }
    }

    public function group($type)
    {
        $this->_request->setDimensions($type);
        return $this;
    }

    public function limit($limit)
    {
        $this->_request->setRowLimit((integer)$limit);
        return $this;
    }

    public function website($website)
    {
        $this->_website = $website;
        return $this;
    }

    public function from($date)
    {
        $this->_request->setStartDate($date);
        return $this;
    }

    public function to($date)
    {
        $this->_request->setEndDate($date);
        return $this;
    }

    public function device($device)
    {
        $filter = new Google_Service_Webmasters_ApiDimensionFilter();
        $filter->setDimension('device');
        $filter->setOperator('equals');
        $filter->setExpression($device);
        $this->_filters[] = $filter;

        return $this;
    }

    public function page($page)
    {
        $filter = new Google_Service_Webmasters_ApiDimensionFilter();
        $filter->setDimension('page');
        $filter->setOperator('equals');
        $filter->setExpression($page);
        $this->_filters[] = $filter;

        return $this;
    }

    private function _trace($message)
    {
        echo $message . PHP_EOL;
    }
}
