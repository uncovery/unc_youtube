<?php

global $YOUTUBE;

// your Youtube API Key
$YOUTUBE['api_key'] = "myAPIkey";

// your Youtube channel ID
$YOUTUBE['channel'] = 'myChannelID';

// path to required library https://github.com/uncovery/unc_serial_curl
require_once('/path/to/unc_serial_curl/unc_serial_curl.php');

// path to cert file for required library https://github.com/uncovery/unc_serial_curl
$YOUTUBE['curl_cert'] = "/path/to/unc_serial_curl/google.crt";

global $UNC_DB;

$UNC_DB = array(
    'database'  => 'database', // your database name
    'username'  => 'username', // your database username
    'server'  => 'localhost',       // your database server
    'password'  => 'password', // your database password
);

// path to required library requirement https://github.com/uncovery/uncovery_mysql
require_once('/path/to/uncovery_mysql/uncovery_mysql.inc.php');