<?php
/**
 * MV - content management framework for building websites and applications.
 * 
 * Defines the active models for the project.
 * Model class files should be located in the ~/models directory.
 * Naming conventions for model classes: 'products.model.php', 'catalog_reviews.model.php'.
 * Corresponding SQL tables must match the class names in lowercase (e.g., 'products', 'catalog_reviews').
 * 
 * Example: ['Products', 'CatalogReviews']
 */

$mvActiveModels = [
	'Pages', 'Blocks', 'Seo'
];