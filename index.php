<?php
 // Absolute filesystem path to the web root
 define('WWW_DIR', dirname(__FILE__));

 // Absolute filesystem path to the application root
 define('APP_DIR', WWW_DIR.'/app');

 // Absolute filesystem path to the libraries
 define('LIBS_DIR', WWW_DIR.'/lib');

 // Absolute filesystem path to the temp directory
 define('TEMP_DIR', WWW_DIR.'/temp');

 // Absolute filesystem path to the logs
 define('LOG_DIR', WWW_DIR.'/log');

 // Load bootstrap file
 require_once(APP_DIR.'/bootstrap.php');
?>