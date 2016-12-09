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
class Database
{
    private static $_configuration = null;
    private static $_database = null;
    private static $_mysql = null;

    public function __construct($configuration)
    {
        # Configuration
        self::$_configuration = $configuration;
        self::$_database = $configuration['database'];
    }

    public function configuration($configuration)
    {
        # Configuration
        if (is_null(self::$_configuration)) {
            self::$_configuration = $configuration;
            self::$_database = $configuration['database'];
        }
    }

    public static function _handle()
    {
        # Lazy Connection
        if (isset(self::$_mysql) === false) {
            # Creating New Connection
            self::$_mysql = mysqli_init();
            self::$_mysql->real_connect(self::$_database['host'], self::$_database['username'], self::$_database['password'], self::$_database['database'], self::$_database['port'], null, MYSQLI_CLIENT_COMPRESS);
            self::$_mysql->set_charset('utf8');

            # Checking Connection State
            if (self::$_mysql->connect_error) {
                throw new Exception('Connection Error [' . self::$_mysql->connect_errno . '] ' . self::$_mysql->connect_error);
            }
        }

        return self::$_mysql;
    }

    public function execute($data)
    {
        foreach ($data as $query) {
            if (self::_handle()->query($query) !== true) {
                echo PHP_EOL . 'Error : ' . self::_handle()->error . ', Query : ' . $query;
            }
            usleep(5);
        }
    }

    /**
     * Return the Average Position for specified Day, Device & Website
     */
    public function average($date, $website, $device)
    {
        $query = 'SELECT AVG(position) AS average
                  FROM `' . str_replace(array(
            '{%device%}',
            '{%website%}'), array(
            self::_handle()->real_escape_string($device),
            self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['queries']) . '`
                  WHERE date = \'' . self::_handle()->real_escape_string($date) . '\';';

        # Executing Query
        if (($resource = self::_handle()->query($query)) === false) {
            throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
        } elseif (is_null($data = $resource->fetch_array()) === true) {
            return false;
        }
        return $data['average'];
    }

    /**
     * Return the Click Ratio (CTR) for specified Day, Device & Website
     */
    public function ratio($date, $website, $device)
    {
        $query = 'SELECT (ROUND((SUM(clicks)) / (SUM(impressions)) * 100)) AS ctr
                  FROM `' . str_replace(array('{%device%}', '{%website%}'),
                              array(self::_handle()->real_escape_string($device),
                                  self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['queries']) . '`
                  WHERE query NOT LIKE \'ouestfrance\' AND query NOT LIKE \'ouest france\'
                        AND date = \'' . self::_handle()->real_escape_string($date) . '\';';

        # Executing Query
        if (($resource = self::_handle()->query($query)) === false) {
            throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
        } elseif (is_null($data = $resource->fetch_array()) === true) {
            return false;
        }
        return $data['ctr'];
    }

    /**
     * Return the Last Insert Date for specified Day, Device & Website
     */
    public function last($query, $website, $device)
    {
        # MAX(date) Query by Table
        switch ($query) {
            case 'query' :
                $query = 'SELECT MAX(date) AS date
                          FROM `' . str_replace(array(
                    '{%device%}',
                    '{%website%}'), array(
                    self::_handle()->real_escape_string($device),
                    self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['queries']) . '`';
                break;
            case 'page' :
                $query = 'SELECT MAX(date) AS date
                          FROM `' . str_replace(array(
                    '{%device%}',
                    '{%website%}'), array(
                    self::_handle()->real_escape_string($device),
                    self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['pages']) . '`';
                break;
            case 'keywords' :
                $query = 'SELECT MAX(date) AS date
                          FROM `' . str_replace(array(
                    '{%device%}',
                    '{%website%}'), array(
                    self::_handle()->real_escape_string($device),
                    self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['pages']) . '`';
                break;
        }

        # Executing Query
        if (($resource = self::_handle()->query($query)) === false) {
            throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
        } elseif (is_null($data = $resource->fetch_array()) === true) {
            return false;
        }

        return strtotime($data['date']);
    }

    /**
     * Return count of all occurences of query/page for specified Day, Device & Website
     */
    public function count($query, $time, $website, $device, $interval = true)
    {
        # Variable
        $date = array();

        # MAX(date) Query by Table
        switch ($query) {
            case 'query' :
                $query = 'SELECT COUNT(DISTINCT query) AS count
                          FROM `' . str_replace(array(
                    '{%device%}',
                    '{%website%}'), array(
                    self::_handle()->real_escape_string($device),
                    self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['queries']) . '`';
                break;
            case 'page' :
                $query = 'SELECT COUNT(DISTINCT page) AS count
                          FROM `' . str_replace(array(
                    '{%device%}',
                    '{%website%}'), array(
                    self::_handle()->real_escape_string($device),
                    self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['pages']) . '`';
                break;
        }

        # Interval Mode, Making Requests for various Time Periods
        if ($interval === true) {
            # Making Request for each Interval @FIXME check date & interval
            # Executing Query
            if (($resource = self::_handle()->query($query . ' WHERE date > ' . self::_handle()->real_escape_string(date('Y-m-d', $time - 7 * 86400)))) === false) {
                throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
            } elseif (is_null($data = $resource->fetch_array()) === true) {
                return false;
            } else {
                $date['<7days'] = $data['count'];
            }

            # Executing Query
            if (($resource = self::_handle()->query($query . ' WHERE date > ' . self::_handle()->real_escape_string(date('Y-m-d', $time - 15 * 86400)))) === false) {
                throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
            } elseif (is_null($data = $resource->fetch_array()) === true) {
                return false;
            } else {
                $date['<15days'] = $data['count'];
            }

            # Executing Query
            if (($resource = self::_handle()->query($query . ' WHERE date > ' . self::_handle()->real_escape_string(date('Y-m-d', $time - 30 * 86400)))) === false) {
                throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
            } elseif (is_null($data = $resource->fetch_array()) === true) {
                return false;
            } else {
                $date['<30days'] = $data['count'];
            }

            # Executing Query
            if (($resource = self::_handle()->query($query . ' WHERE date < NOW()')) === false) {
                throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
            } elseif (is_null($data = $resource->fetch_array()) === true) {
                return false;
            } else {
                $date['<NOW()'] = $data['count'];
            }
            return $date;
        } else {
            # Making Request from the beginning
            if (($resource = self::_handle()->query($query)) === false) {
                throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
            } elseif (is_null($data = $resource->fetch_array()) === true) {
                return false;
            } else {
                return $data['count'];
            }
        }
    }

    public function detail($query, $website, $device, $search, $aggregate, $interval = null, $group = 'day')
    {
        # Device Wildcard
        if ($device == '*') {
            $data = array();
            # Iterating through each device
            foreach (self::$_configuration['device'] as $device) {
                # Query
                $_data = $this->detail($query, $website, $device, $search, $aggregate, $interval, $group);
                foreach ($_data as $_search) {
                    if (isset($data[$_search[3]]) === true) {
                        # Averaging Position
                        $data[$_search[3]][2] = round(($data[$_search[3]][2] * $data[$_search[3]][0] + $_search[2] * $_search[0]) / ($data[$_search[3]][0] + $_search[0]), 1);
                        $data[$_search[3]][0] += $_search[0];
                        $data[$_search[3]][1] += $_search[1];
                    } else {
                        $data[$_search[3]] = $_search;
                    }
                }
            }

            # Sorting
            krsort($data);
            return $data;
        }

        # Checking Group by Day or Week
        $date = ($group == 'day') ? ' date ' : ' YEARWEEK(date, 1) ';

        # Aggegrate Mode with Special Query
        if ($aggregate == true) {
            # Analysing Search
            $removals = array();
            $preservals = array();

            # Wildcards
            $search = str_replace('*', '%', $search);

            # Finding Negatives
            preg_match_all('/^(?:\-\'([^\']+)\'|\-([^\' ]+))/i', $search, $removal, PREG_SET_ORDER);
            foreach ($removal as $data) {
                if (empty(trim(end($data))) === false) {
                    $removals[] = ' ' . $query . ' NOT LIKE \'' . ((strpos(current($data), '^') !== false) ? '' : '%') . self::_handle()->real_escape_string(strtolower(current($data))) . ((strpos(current($data), '$') !== false) ? '' : '%') . '\' ';
                }
                $search = str_replace(reset($data), '', $search);
            }

            preg_match_all('/(?:\-\'([^\']+)\'|[^\w]\-([^\' ]+))/i', $search, $removal, PREG_SET_ORDER);
            foreach ($removal as $data) {
                if (empty(trim(end($data))) === false) {
                    $removals[] = ' ' . $query . ' NOT LIKE \'' . ((strpos(current($data), '^') !== false) ? '' : '%') . self::_handle()->real_escape_string(strtolower(current($data))) . ((strpos(current($data), '$') !== false) ? '' : '%') . '\' ';
                }
                $search = str_replace(reset($data), '', $search);
            }

            # Adding Positives Search
            preg_match_all('/(?:\'([^\']+)\'|([^\' ]+))/i', $search, $preserval, PREG_SET_ORDER);
            foreach ($preserval as $data) {
                if (empty(trim(end($data))) === false) {
                    $preservals[] = ' ' . $query . ' LIKE \'' . ((strpos(current($data), '^') !== false) ? '' : '%') . self::_handle()->real_escape_string(strtolower(current($data))) . ((strpos(current($data), '$') !== false) ? '' : '%') . '\' ';
                }
            }

            # Removing ^ & $ Wilcards
            $removals = str_replace(array(
                '^',
                '$'), '', $removals);
            $preservals = str_replace(array(
                '^',
                '$'), '', $preservals);

            # Details Query by Table
            switch ($query) {
                case 'query' :
                    # Data Aggregated by days
                    $query = 'SELECT SUM(impressions) AS impressions,
                                     SUM(clicks) AS clicks,
                                     ROUND(AVG(position), 1) AS position,
                             ' . $date . '
                              FROM `' . str_replace(array(
                        '{%device%}',
                        '{%website%}'), array(
                        self::_handle()->real_escape_string($device),
                        self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['queries']) . '`
                              WHERE ' . ((empty($interval) === false) ? (is_array($interval) ? ' date >= \'' . $interval['from'] . '\' AND date <= \'' . $interval['to'] . '\' ' : ' date = \'' . $interval . '\'') : '') . ((empty($interval) === false) && (empty($removals) === false) ? ' AND ' : '') . ((empty($removals) === false) ? ' (' . implode(' AND ', $removals) . ') ' : '') . (((empty($interval) === false) || (empty($removals) === false)) && (empty($preservals) === false) ? ' AND ' : '') . ((empty($preservals) === false) ? ' (' . implode(' OR ', $preservals) . ')' : '') . '
                              GROUP BY ' . $date . '
                              ORDER BY ' . $date . ' DESC';
                    break;
                case 'page' :
                    $query = 'SELECT SUM(impressions) AS impressions,
                                     SUM(clicks) AS clicks,
                                     ROUND(AVG(position), 1) AS position,
                             ' . $date . '
                              FROM `' . str_replace(array(
                        '{%device%}',
                        '{%website%}'), array(
                        self::_handle()->real_escape_string($device),
                        self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['pages']) . '`
                              WHERE ' . ((empty($interval) === false) ? (is_array($interval) ? ' date >= \'' . $interval['from'] . '\' AND date <= \'' . $interval['to'] . '\' ' : ' date = \'' . $interval . '\'') : '') . ((empty($interval) === false) && (empty($removals) === false) ? ' AND ' : '') . ((empty($removals) === false) ? ' (' . implode(' AND ', $removals) . ') ' : '') . (((empty($interval) === false) || (empty($removals) === false)) && (empty($preservals) === false) ? ' AND ' : '') . ((empty($preservals) === false) ? ' (' . implode(' OR ', $preservals) . ')' : '') . '
                              GROUP BY ' . $date . '
                              ORDER BY ' . $date . ' DESC';
            }

            # Executing Query
            if (($resource = self::_handle()->query($query)) === false) {
                throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
            } elseif (is_null($data = $resource->fetch_all()) === true) {
                return false;
            }
        } else {
            # Basic Detail Mode Query by Table
            switch ($query) {
                case 'query' :
                    # Data Aggregated by days
                    $query = 'SELECT SUM(impressions) AS impressions,
                                     SUM(clicks) AS clicks,
                                     ROUND(AVG(position), 1) AS position,
                             ' . $date . '
                              FROM `' . str_replace(array(
                        '{%device%}',
                        '{%website%}'), array(
                        self::_handle()->real_escape_string($device),
                        self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['queries']) . '`
                              WHERE query = \'' . self::_handle()->real_escape_string(strtolower($search)) . '\'' . ((empty($interval) === false) ? (is_array($interval) ? ' AND date >= \'' . $interval['from'] . '\' AND date <= \'' . $interval['to'] . '\' ' : ' AND date = \'' . $interval . '\'') : '') . '
                              GROUP BY ' . $date . '
                              ORDER BY ' . $date . ' DESC';
                    break;
                case 'page' :
                    $query = 'SELECT SUM(impressions) AS impressions,
                                     SUM(clicks) AS clicks,
                                     ROUND(AVG(position), 1) AS position,
                             ' . $date . '
                              FROM `' . str_replace(array(
                        '{%device%}',
                        '{%website%}'), array(
                        self::_handle()->real_escape_string($device),
                        self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['pages']) . '`
                              WHERE page = \'' . self::_handle()->real_escape_string(strtolower($search)) . '\'' . ((empty($interval) === false) ? (is_array($interval) ? ' AND date >= \'' . $interval['from'] . '\' AND date <= \'' . $interval['to'] . '\' ' : ' AND date = \'' . $interval . '\'') : '') . '
                              GROUP BY ' . $date . '
                              ORDER BY ' . $date . ' DESC';
                    break;
            }

            # Executing Query
            if (($resource = self::_handle()->query($query)) === false) {
                throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
            } elseif (is_null($data = $resource->fetch_all()) === true) {
                return false;
            }
        }

        # Reassigning first day of week
        if ($group == 'week') {
            foreach ($data as $date => $_data) {
                unset($data[$date]);
                $date = date('Y-m-d', strtotime(substr($_data[3], 0, 4) . 'W' . substr($_data[3], - 2)));
                $_data[3] = $date;
                $data[$date] = $_data;
            }
        }
        return $data;
    }

    /**
     *
     * @param string $query
     *            Query Type (query/page)
     * @param string $website
     *            Website
     * @param string $device
     *            Device
     * @param mixed $date
     *            Date or days interval, in case of array, from is NOT included
     * @param string $search
     *            Keywords
     * @param boolean $aggregate
     *            Agreggate Results
     */
    public function search($query, $website, $device, $interval, $search, $aggregate)
    {
        # Device Wildcard
        if ($device == '*') {
            $data = array();
            # Iterating through each device
            foreach (self::$_configuration['device'] as $device) {
                # Query
                $_data = $this->search($query, $website, $device, $interval, $search, $aggregate);
                foreach ($_data as $_search) {
                    if (isset($data[$_search[0]]) === true) {
                        # Averaging Position
                        $data[$_search[0]][3] = round(($data[$_search[0]][3] * $data[$_search[0]][1] + $_search[3] * $_search[1]) / ($data[$_search[0]][1] + $_search[1]), 1);
                        $data[$_search[0]][1] += $_search[1];
                        $data[$_search[0]][2] += $_search[2];
                    } else {
                        $data[$_search[0]] = $_search;
                    }
                }
            }
            return $data;
        }

        # Analysing Search
        $removals = array();
        $preservals = array();

        # Wildcards
        $search = str_replace('*', '%', $search);

        # Finding Negatives
        preg_match_all('/^(?:\-\'([^\']+)\'|\-([^\' ]+))/i', $search, $removal, PREG_SET_ORDER);
        foreach ($removal as $data) {
            if (empty(trim(end($data))) === false) {
                $removals[] = ' ' . $query . ' NOT LIKE \'' . ((strpos(current($data), '^') !== false) ? '' : '%') . self::_handle()->real_escape_string(strtolower(current($data))) . ((strpos(current($data), '$') !== false) ? '' : '%') . '\' ';
            }
            $search = str_replace(reset($data), '', $search);
        }

        preg_match_all('/(?:\-\'([^\']+)\'|[^\w]\-([^\' ]+))/i', $search, $removal, PREG_SET_ORDER);
        foreach ($removal as $data) {
            if (empty(trim(end($data))) === false) {
                $removals[] = ' ' . $query . ' NOT LIKE \'' . ((strpos(current($data), '^') !== false) ? '' : '%') . self::_handle()->real_escape_string(strtolower(current($data))) . ((strpos(current($data), '$') !== false) ? '' : '%') . '\' ';
            }
            $search = str_replace(reset($data), '', $search);
        }

        # Adding Positives Search
        preg_match_all('/(?:\'([^\']+)\'|([^\' ]+))/i', $search, $preserval, PREG_SET_ORDER);
        foreach ($preserval as $data) {
            if (empty(trim(end($data))) === false) {
                $preservals[] = ' ' . $query . ' LIKE \'' . ((strpos(current($data), '^') !== false) ? '' : '%') . self::_handle()->real_escape_string(strtolower(current($data))) . ((strpos(current($data), '$') !== false) ? '' : '%') . '\' ';
            }
        }

        # Removing ^ & $ Wilcards
        $removals = str_replace(array(
            '^',
            '$'), '', $removals);
        $preservals = str_replace(array(
            '^',
            '$'), '', $preservals);

        # Details Query by Table
        switch ($query) {
            case 'query' :
                # Data Aggregated, from {last entry} to {last entry - $date} days
                if ($aggregate == true) {
                    $query = 'SELECT query,
                                     SUM(impressions) AS impressions,
                                     SUM(clicks) AS clicks,
                                     ROUND(AVG(position), 1) AS position
                               FROM `' . str_replace(array(
                        '{%device%}',
                        '{%website%}'), array(
                        self::_handle()->real_escape_string($device),
                        self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['queries']) . '`
                               WHERE ' . ((is_array($interval)) ? ' date >= \'' . $interval['from'] . '\' AND date <= \'' . $interval['to'] . '\' ' : ' date = \'' . $interval . '\'') . ((empty($removals) === false) ? ' AND (' . implode(' AND ', $removals) . ')' : '') . ((empty($preservals) === false) ? ' AND (' . implode(' OR ', $preservals) . ')' : '') . '
                               GROUP BY query
                               ORDER BY clicks DESC';
                } else {
                    $query = 'SELECT query,
                                     impressions,
                                     clicks,
                                     position
                               FROM `' . str_replace(array(
                        '{%device%}',
                        '{%website%}'), array(
                        self::_handle()->real_escape_string($device),
                        self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['queries']) . '`
                               WHERE ' . ((is_array($interval)) ? ' date >= \'' . $interval['from'] . '\' AND date <= \'' . $interval['to'] . '\' ' : ' date = \'' . $interval . '\'') . ((empty($removals) === false) ? ' AND (' . implode(' AND ', $removals) . ')' : '') . ((empty($preservals) === false) ? ' AND (' . implode(' OR ', $preservals) . ')' : '') . '
                               ORDER BY clicks DESC';
                }
                break;
            case 'page' :
                if ($aggregate == true) {
                    $query = 'SELECT page,
                                     SUM(impressions) AS impressions,
                                     SUM(clicks) AS clicks,
                                     ROUND(AVG(position), 1) AS position
                               FROM `' . str_replace(array(
                        '{%device%}',
                        '{%website%}'), array(
                        self::_handle()->real_escape_string($device),
                        self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['pages']) . '`
                               WHERE ' . ((is_array($interval)) ? ' date >= \'' . $interval['from'] . '\' AND date <= \'' . $interval['to'] . '\' ' : ' date = \'' . $interval . '\'') . ((empty($removals) === false) ? ' AND (' . implode(' AND ', $removals) . ')' : '') . ((empty($preservals) === false) ? ' AND (' . implode(' OR ', $preservals) . ')' : '') . '
                               GROUP BY page
                               ORDER BY clicks DESC';
                } else {
                    $query = 'SELECT page,
                                     impressions,
                                     clicks,
                                     position
                               FROM `' . str_replace(array(
                        '{%device%}',
                        '{%website%}'), array(
                        self::_handle()->real_escape_string($device),
                        self::_handle()->real_escape_string(self::website_table_name($website))), self::$_configuration['database']['table']['pages']) . '`
                               WHERE ' . ((is_array($interval)) ? ' date >= \'' . $interval['from'] . '\' AND date <= \'' . $interval['to'] . '\' ' : ' date = \'' . $interval . '\'') . ((empty($removals) === false) ? ' AND (' . implode(' AND ', $removals) . ')' : '') . ((empty($preservals) === false) ? ' AND (' . implode(' OR ', $preservals) . ')' : '') . '
                               ORDER BY clicks DESC';
                }

                break;
        }

        # Executing Query
        if (($resource = self::_handle()->query($query)) === false) {
            throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
        } elseif (is_null($data = $resource->fetch_all()) === true) {
            return false;
        }
        return $data;
    }

    public function filters($query, $website, $action = null, $data = null)
    {
        switch ($action) {
            case 'delete' :
                $query = 'DELETE
                          FROM `filters`
                          WHERE website = \'' . self::_handle()->real_escape_string(strtolower($website)) . '\'
                                AND query = \'' . self::_handle()->real_escape_string(strtolower($query)) . '\'
                                AND name = \'' . self::_handle()->real_escape_string(strtoupper($data['name'])) . '\'
                                AND value = \'' . self::_handle()->real_escape_string(strtolower($data['value'])) . '\'
                          LIMIT 1';

                # Executing Query
                if (($resource = self::_handle()->query($query)) === false) {
                    throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
                }
                return true;
                break;
            case 'add' :
                $query = 'INSERT INTO `filters`
                          (`name`,`query`,`value`,`website`)
                          VALUES (\'' . self::_handle()->real_escape_string(strtoupper($data['name'])) . '\',
                                  \'' . self::_handle()->real_escape_string(strtolower($query)) . '\',
                                  \'' . self::_handle()->real_escape_string(strtolower($data['value'])) . '\',
                                  \'' . self::_handle()->real_escape_string(strtolower($website)) . '\')
                          ON DUPLICATE KEY UPDATE value = \'' . self::_handle()->real_escape_string(strtolower($data['value'])) . '\'';

                # Executing Query
                if (($resource = self::_handle()->query($query)) === false) {
                    throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
                }
                return true;
                break;
            default :
                $query = 'SELECT *
                          FROM `filters`
                          WHERE website = \'' . self::_handle()->real_escape_string(strtolower($website)) . '\'
                                AND query = \'' . self::_handle()->real_escape_string(strtolower($query)) . '\'';

                # Executing Query
                if (($resource = self::_handle()->query($query)) === false) {
                    throw new Exception('Query Error [' . self::$_mysql->sqlstate . '] : ' . $query);
                } elseif (is_null($data = $resource->fetch_all()) === true) {
                    return false;
                }
                break;
        }
        return $data;
    }

    public function website_table_name($website)
    {
        return self::$_configuration['websites'][$website]['table'];
    }

    private function _trace($message)
    {
        #echo $message . PHP_EOL;
    }
}
