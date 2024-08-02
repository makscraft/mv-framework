<?php
/**
 * Main routing file of the MV application.
 * Views files must be located in 'views' directory.
 * 
 * '/' - index page of the application
 * 'e404' - page of 404 error
 * 'fallback' - default fallback url, if it was no any match
 * 
 * Allowed symbols in routes patterns: '*' - any value (dynamic part), '?' - optional part (can be only one in pattern).
 * Url pattern can have any quantity of '*' symbols, and only one symbol '?' at the and of the pattern.
 * 
 * Examples:
 * '/contacts' => 'view-contacts.php'
 * '/news/*'  => 'modules/view-news.php'
 * '/complete/?'  => 'folder/subfolder/view-name.php'
 */

$mvFrontendRoutes = [

'/' => 'view-index.php',
'e404' => 'view-404.php',
'fallback' => 'view-default.php',

'/form' => 'view-form.php'
];