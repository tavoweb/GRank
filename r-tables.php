<?php
 
function create_table_wp_rankie() {
	global $wpdb;
	// comments table
	if (! exists_table_wp_rankie ( 'wp_rankie_log' )) {
		
		$querys = "CREATE TABLE IF NOT EXISTS `wp_rankie_changes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword_id` int(11) NOT NULL,
  `rank_change` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=161 ;

CREATE TABLE IF NOT EXISTS `wp_rankie_keywords` (
  `keyword_id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(300) NOT NULL,
  `keyword_rank` int(11) NOT NULL DEFAULT '0',
  `keyword_site` varchar(300) NOT NULL,
  `keyword_type` varchar(20) NOT NULL DEFAULT 'Manual',
  `keyword_group` varchar(50) NOT NULL DEFAULT '-',
  `date_updated` varchar(50) NOT NULL,
  PRIMARY KEY (`keyword_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=368 ;

CREATE TABLE IF NOT EXISTS `wp_rankie_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(50) NOT NULL,
  `data` text NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=55 ;

CREATE TABLE IF NOT EXISTS `wp_rankie_ranks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `keyword_id` int(11) NOT NULL,
  `rank` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `rank_link` varchar(300) NOT NULL DEFAULT '-',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=536 ;
	";
		// executing quiries
		$que = explode ( ';', $querys );
		foreach ( $que as $query ) {
			if (trim ( $query ) != '') {
				$wpdb->query ( $query );
			}
		}
	}//first version 
	
	//add last_try in the new version
	$current_table_version =get_option('wp_rankie_table_version', 1 );
	
	if( $current_table_version < 2 ){
		$query="ALTER TABLE `wp_rankie_keywords` ADD `last_try` INT NOT NULL DEFAULT '1401036392';";
		$wpdb->query ( $query );
	}
	
	
	if( $current_table_version < 3 ){
		$query="ALTER TABLE wp_rankie_keywords CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;";
		$wpdb->query ( $query );
		
		$query="ALTER TABLE wp_rankie_ranks CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;";
		$wpdb->query ( $query );
	}

	
	if( $current_table_version < 4 ){
		
		$query="ALTER TABLE `wp_rankie_keywords` ADD `last_checked_page` INT NOT NULL DEFAULT '0';";
		$wpdb->query ( $query );
		update_option('wp_rankie_table_version',4);
	}
	
	
	 
}
function exists_table_wp_rankie($table) {
	global $wpdb;
	$rows = $wpdb->get_row ( 'show tables like "' . $table . '"', ARRAY_N );
	return (count ( $rows ) > 0);
}

function wp_rankie_check_table_version(){
	
	$current_table_version =get_option('wp_rankie_table_version', 1 );
	
	if($current_table_version < 4){
		
		create_table_wp_rankie();
		
		echo '<div class="updated">
		        <p>Database tables for Rankie updated successfully.</p>
		    </div>';
	}
	
}