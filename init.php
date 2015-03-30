<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require 'vendor/autoload.php';

// Register a logger
new \Sitewit\WpPlugin\SW_Logger();

// Initialize plugin functionality
$inc = \Sitewit\WpPlugin\SW_Plugin::get_instance();
$inc->init_hooks();
