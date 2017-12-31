<?php 

//wp-load
require_once('../../../wp-load.php');

update_option('wp_rankie_license_active', 'active');

echo get_option('wp_rankie_license_active', 'not active');

?>