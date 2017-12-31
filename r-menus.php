<?php
/**
 * Menus of the plugin
 */
 
add_action('admin_menu', 'wp_ranker_control_menu');

function wp_ranker_control_menu() {

	$capability = 'administrator';
	
	if (current_user_can('editor')){
		$capability = 'editor';
	}elseif (current_user_can('author')){
		$capability = 'author';
	}	
	
	
	 
	add_menu_page( 'GRank', 'GRank', $capability, 'wp_rankie', 'wp_rankie_dashboard_fn', 'dashicons-chart-area', 77777788877777 );
	
	$dashboardSlug=add_submenu_page( 'wp_rankie',  'Wordpress Rankie','Dashboard', $capability, 'wp_rankie', 'wp_rankie_dashboard_fn' );
	
	$reportsSlug = add_submenu_page( 'wp_rankie', 'Wordpress Rankie Reports', 'Reports', $capability, 'wp_rankie_reports', 'wp_rankie_reports_fn' );
	$researchSlug = add_submenu_page( 'wp_rankie', 'Wordpress Rankie Keyword Research', 'Research', $capability, 'wp_rankie_research', 'wp_rankie_research' );
	
	$settingsSlug = add_submenu_page( 'wp_rankie', 'Wordpress Rankie Settings', 'Settings', 'administrator', 'wp_rankie_settings', 'wp_rankie_settings_fn' );
	
	$logSlug = add_submenu_page( 'wp_rankie', 'Wordpress Rankie Log', 'Log', 'administrator', 'wp_rankie_log', 'wp_rankie_log' );
	
	
	
	add_action('admin_head-'.$dashboardSlug, 'wp_rankie_admin_head_dashboard');
	add_action('admin_head-'.$settingsSlug, 'wp_rankie_admin_head_settings');
	add_action('admin_head-'.$reportsSlug , 'wp_rankie_admin_head_reports');
	add_action('admin_head-'.$researchSlug , 'wp_rankie_admin_head_research');
	/*

	$synonymsSlug=add_submenu_page( 'wp_auto_spinner', 'Wp Auto Spinner custom thesaurus', 'My Thesaurus', 'administrator', 'wp_auto_spinner_thesaurus', 'wp_auto_spinner_thesaurus' );
	add_action('admin_head-'.$synonymsSlug, 'wp_auto_spinner_admin_head_thesaurus');

	add_submenu_page( 'wp_auto_spinner', 'Wp Auto Spinner settings', 'Settings', 'administrator', 'wp_auto_spinner_settings', 'wp_auto_spinner_fn' );
	add_submenu_page( 'wp_auto_spinner', 'Wp Auto Spinner Spinning Queue', 'Queue', 'administrator', 'wp_auto_spinner_queue', 'wp_auto_spinner_queue' );

	add_submenu_page( 'wp_auto_spinner', 'Wp Auto Spinner Log', 'Log', 'administrator', 'wp_auto_spinner_log', 'wp_auto_spinner_log' );
	*/

}

// Dashboard styles & scripts
function wp_rankie_admin_head_dashboard(){
	
	//data tables
	wp_enqueue_script('wp-rankie-data-tables',plugins_url( '/js/jquery.dataTables.min.js' , __FILE__ ));
	
	//google fucken chart
	wp_enqueue_script('wp-rankie-google-jsapi','https://www.google.com/jsapi');
	
	//ui dialog
	wp_enqueue_style ( 'wp-jquery-ui-dialog' );
	wp_enqueue_script ( 'jquery-ui-dialog' );
	
	//dashboard style
	wp_enqueue_style('wp-rankie-dashboard-css',plugins_url( '/css/dashboard.css' , __FILE__ ));
	
	//dashboard
	wp_enqueue_script('wp-rankie-dashboard',plugins_url( '/js/dashboard.js?v=1.0' , __FILE__ ));
	
}

// Settings page styles and scripts
function wp_rankie_admin_head_settings(){
	
	wp_enqueue_script('wp-rankie-settings',plugins_url( '/js/settings.js' , __FILE__ ));
	wp_enqueue_script('wp-rankie-research-settings',plugins_url( '/js/options.js' , __FILE__ ));
}
 
// Reports page styles and scripts
function wp_rankie_admin_head_reports(){
	
	//google fucken chart
	wp_enqueue_script('wp-rankie-google-jsapi','https://www.google.com/jsapi');
	
	wp_enqueue_script('wp-rankie-reports',plugins_url( '/js/reports.js' , __FILE__ ));
	wp_enqueue_script('wp-rankie-jspdf',plugins_url( '/js/jspdf.min.js' , __FILE__ ));
	
	wp_enqueue_style('wp-rankie-dashboard-css',plugins_url( '/css/dashboard.css' , __FILE__ ));

}

// Reports page styles and scripts
function wp_rankie_admin_head_research(){

	//ui dialog
	wp_enqueue_style ( 'wp-jquery-ui-dialog' );
	wp_enqueue_script ( 'jquery-ui-dialog' );
	
	
	wp_enqueue_script('wp-rankie-gcomplete',plugins_url( '/js/jquery.gcomplete.0.1.2.js' , __FILE__ ));
	wp_enqueue_script('wp-rankie-main',plugins_url( '/js/main.js' , __FILE__ ));

	wp_enqueue_style('wp-rankie-dashboard-css',plugins_url( '/css/dashboard.css' , __FILE__ ));

}