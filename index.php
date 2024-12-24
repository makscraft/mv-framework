<?php
/**
 * MV - modern content management framework for building websites and applications.
 * 
 * https://mv-framework.com
 * https://mv-framework.ru
 */

//Load the autoload file to initialize the application
require_once 'config/autoload.php';

//Create the main Builder object, which manages models, plugins, and core features
$mv = new Builder();

//Include the pre-view script, executed before rendering any views
include $mv -> views_path.'before-view.php';

//Use the router to determine the current route and include the corresponding view file
include $mv -> router -> defineRoute();

// Display the debug panel at the bottom of the page if 'DebugPanel' is enabled in settings
$mv -> displayDebugPanel();