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

# Parameters
$website = ((isset($_REQUEST['website'])) && (empty($_REQUEST['website']) === false)) ? $_REQUEST['website'] : 'ofa';
$device = ((isset($_REQUEST['device'])) && (empty($_REQUEST['device']) === false)) ? $_REQUEST['device'] : 'desktop';
$query = ((isset($_REQUEST['query'])) && (empty($_REQUEST['query']) === false)) ? $_REQUEST['query'] : 'query';
$search = ((isset($_REQUEST['search'])) && (empty($_REQUEST['search']) === false)) ? urldecode($_REQUEST['search']) : null;
$mode = ((isset($_REQUEST['mode'])) && (empty($_REQUEST['mode']) === false)) ? $_REQUEST['mode'] : null;
$action = ((isset($_REQUEST['action'])) && (empty($_REQUEST['action']) === false)) ? $_REQUEST['action'] : null;

# Database Object
$database = new Database($configuration);
switch ($mode) {
    case 'filter':
        echo $database->filters($query, $website, $action, $_REQUEST);
        break;
}
