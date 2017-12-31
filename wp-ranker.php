<?php 
/*
Plugin Name:GRank
Plugin URI: https://github.com/tavoweb/grank
Description: Wordpress google ranking tracking plugin
Version: 1.6.3
GitHub Plugin URI: https://github.com/tavoweb/grank
Author: TavoWEB 
Author URI: https://tavoweb.lt
*/


 
// UPDATES
$licenseactive=true;
				$wp_rankie_license = 'Active';
				if(trim($licenseactive) != ''){

	//fire checks
	require 'plugin-updates/plugin-update-checker.php';
	$wp_rankie_UpdateChecker = PucFactory::buildUpdateChecker(
			'',
			__FILE__,
			'wp-ranker'
	);

	//append keys to the download url
	$wp_rankie_UpdateChecker->addResultFilter('wp_rankie_addResultFilter');
	function wp_rankie_addResultFilter($info){
			
		$wp_rankie_license = get_option('wp_rankie_license','');

		if(isset($info->download_url)){
			$info->download_url = $info->download_url . '&key='.$wp_rankie_license;
		}
		return $info;
	}
}

//generic functions 
require_once 'r-functions.php';

//log
require_once 'r-log.php';

//Menus
require_once 'r-menus.php';

//Dashboard page
require_once 'r-dashboard.php';

//Settings page
function wp_rankie_settings_fn(){
	require_once 'r-settings.php';	
}

//Ajax 
require_once 'r-ajax.php';

//Reports page
function wp_rankie_reports_fn(){
	require_once 'r-reports.php';
}

//catch new words hook
require_once 'r-catch.php';  

//internal cron schedule
require_once 'r-schedule.php';

//internal cron schedule
require_once 'r-schedule-report.php';

//research page 
require_once 'r-research.php';

//license notice
require_once 'r-license.php';

//plugin tables
register_activation_hook( __FILE__, 'create_table_wp_rankie' );
require_once 'r-tables.php';

//support widget
require_once 'widget.php';


?>