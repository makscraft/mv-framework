<?php
/**
 * MV - content management framework for developing internet sites and applications.
 * 
 * https://mv-framework.com
 * https://mv-framework.ru
 */

//Main autoload file, starting the application
require_once 'config/autoload.php';

//Main front object of the application with all models and plugins
$mv = new Builder();

//File with code, which is implemented before code of each view
include $mv -> views_path.'before-view.php';

//Router determines current route and the view file to include
include $mv -> router -> defineRoute();

//Shows debug panel at the bottom if the 'DebugPanel' setting is set to true
$mv -> displayDebugPanel();