<?php
/**
 * MV - content management framework for developing internet sites and applications.
 * 
 * Available active plugins of the project.
 * Files of plugins classes must be located in folder ~/plugins.
 * Model class names examples 'search.plugin.php', 'shop_cart.plugin.php'.
 * SQL table is not required for plugin, but can be created if needed.
 * Objects of plugins are being constructed automatically in Builder $mv object.
 * 
 * Example: ['Search', 'ShopCart']
 */

$mvActivePlugins = [];