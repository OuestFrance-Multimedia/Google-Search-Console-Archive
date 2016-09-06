# Search Console Archive #

## Description ##

Search Console Archive store your Google Search Console (Webmaster Tools) Data to exceed the 90 days history and add a lot of analysis and search tools.

- Unlimited history
- Up to 5000 records each day
- Regexp search
- Period comparison
- Multiples websites

### Beta ###

This tool is in Beta, but we use it a lot in a daily basis and are comfortable enough with the stability and features to release it.

## Requirements ##
- MySQL Server (5+)
- Nginx or Apache with PHP (5.3+, may be compatible with previous versions)
- Access to Cron

## Configuration ##

### Creating a Google Service Account ###

Follow the instructions to Create a Service Account  
(See https://developers.google.com/api-client-library/php/auth/service-accounts#creatinganaccount)

- Generate & download a p12 Key for your account
- Put the P12 key in the configuration folder


### Script configuration ###

Rename configuration/base.php.sample to configuration/base.php

Edit base.php and fill/replace the following :
- Timezone & locale
- Api Login
- Websites to check (Add http:// or https:// before your website DNS)
- Database configuration (Hostname, password, port, database name)


### Database configuration ###

You will need a MySQL database for Search Console Archive

- Open docs/sql/website.sql, replace the {%website%} token with your website name 
(The ['table'] key used in the website array in the configuration file)
- Run the SQL code to in your MySQL Database to create base tables for every website you want to add
- Run filters.sql too


### Data Import configuration ###

You need to run cron.php everyday once, it will take the last 7 days of data available.

Don't forget that Search Console data is not up to date, you have a 3-5 day delay, more some times, check this page for explanation/missing data : https://support.google.com/webmasters/answer/6211453#search_analytics

!! **For the first run, edit line 38 of cron.php and put 90 days to import all available history**

    # Parameters
    $date['from'] = (isset($_GET['start'])) ? $_GET['start'] : date('Y-m-d', strtotime('-90 days'));
    $date['to'] = (isset($_GET['end'])) ? $_GET['end'] : date('Y-m-d', strtotime('-1 days'));

You need to do the same (Adjust the time) everytime Google Search Console Data is missing

## Google API PHP Client ##
Search Console Archive use [Google APIs Client Library for PHP](https://github.com/google/google-api-php-client) to query Google API

The script is provided in this repository but the preferred method is via [composer](https://getcomposer.org)

Be sure to keep Google API PHP Client at version 1.*

##  What do I do if something isn't working ? ##
If there is a specific bug with this tool, please [file a issue](https://github.com/OuestFrance-Multimedia/Search-Console-Archive/issues) in the Github issues tracker, including an example of the failing code and any specific errors retrieved.
