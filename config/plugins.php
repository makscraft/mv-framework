<?php
/**
 * MV - content management framework for building websites and applications.
 * 
 * Defines the active plugins for the project.
 * Plugin class files should reside in the ~/plugins directory.
 * Naming conventions for plugin classes: 'search.plugin.php', 'shop_cart.plugin.php'.
 * SQL tables are optional for plugins but can be created if needed.
 * Plugin objects are automatically instantiated in the Builder's `$mv` object.
 * 
 * Example: ['Search', 'ShopCart']
 */

$mvActivePlugins = [];