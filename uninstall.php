<?php
if( ! defined('WP_UNINSTALL_PLUGIN') ) exit; 

global $wpdb; 

$wpdb->query("DELETE FROM $wpdb->commentmeta WHERE meta_key IN ('phone', 'title', 'rating')");