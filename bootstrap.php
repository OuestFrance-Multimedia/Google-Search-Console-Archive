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

# Loading Composer autloader
require 'vendor/autoload.php';

# Functions
require 'lib/functions.php';

# Models
require 'models/database.php';
require 'models/webmaster.php';

# Loading Configuration
$configuration = require 'configuration/base.php';

# Timezone & Locale
date_default_timezone_set($configuration['timezone']);
setlocale(LC_TIME, $configuration['locale']);
