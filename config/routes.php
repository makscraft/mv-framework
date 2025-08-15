<?php
/**
 * Main routing file for the MV application.
 * View files must be located in the 'views' directory.
 *
 * Route definitions:
 * '/' - index page of the application
 * 'e404' - custom 404 error page
 * 'fallback' - default route if no other match is found
 *
 * Allowed symbols in route patterns:
 * '*' - matches any value (dynamic part)
 * '?' - optional part (only one '?' can be used per pattern).
 *
 * URL pattern rules:
 * - You can have any number of '*' symbols in a pattern.
 * - Only one '?' symbol is allowed at the end of the pattern.
 *
 * Example routes:
 * '/contacts' => 'view-contacts.php'
 * '/news/*' => 'modules/view-news.php'
 * '/complete/?' => 'folder/subfolder/view-name.php'
 *
 * You can combine the above patterns with any number of URL parts.
 */

$mvFrontendRoutes = [

'/' => 'view-index.php',
'e404' => 'view-404.php',
'fallback' => 'view-default.php',
'/robots.txt' => 'seo/view-robots.php',

'/form' => 'view-form.php'
];