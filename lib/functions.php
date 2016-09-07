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

/**
 * Make HTML Human Readable Interval
 *
 * @param array $interval
 *            Interval array ('base' & 'compare')
 * @return string
 */
function html_interval($interval)
{
    $string = '';

    if ($interval['base']['from'] != $interval['base']['to']) {
        $string = ' from ' . iconv('ISO-8859-1', 'UTF-8', strftime('%e %B %Y', strtotime($interval['base']['from']))) . ' to ' . iconv('ISO-8859-1', 'UTF-8', strftime('%e %B %Y', strtotime($interval['base']['to'])));
    } else {
        $string = ' for ' . iconv('ISO-8859-1', 'UTF-8', strftime('%A %e %B %Y', strtotime($interval['base']['from'])));
    }
    if (isset($interval['compare'])) {
        $string .= ' compared with ';

        if ($interval['compare']['from'] != $interval['compare']['to']) {
            $string .= ' period from ' . iconv('ISO-8859-1', 'UTF-8', strftime('%e %B %Y', strtotime($interval['compare']['from']))) . ' to ' . iconv('ISO-8859-1', 'UTF-8', strftime('%e %B %Y', strtotime($interval['compare']['to'])));
        } else {
            $string .= ' ' . iconv('ISO-8859-1', 'UTF-8', strftime('%A %e %B %Y', strtotime($interval['compare']['from'])));
        }
    }

    return $string;
}
