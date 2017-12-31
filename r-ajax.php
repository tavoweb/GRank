<?php

//ADD KEYWORDS
add_action( 'wp_ajax_wp_rankie_add_keywords', 'wp_rankie_add_keywords_callback' );
function wp_rankie_add_keywords_callback(){
	global $wpdb;
	$keywords=$_POST['keywords'];
	$site = $_POST['site'];
	$group = $_POST['group'];
	$now = time('now');
	$keywords_arr = explode("\n", $keywords);
	$keywords_arr = array_filter($keywords_arr);
	
	 
	
	$addedArray=array();
	$newItem=array();
	if(count($keywords_arr) > 0){
		foreach ($keywords_arr as $newkeyword){
			if(trim($newkeyword) != ''){
				//insert this keyword 
				$query="insert into wp_rankie_keywords(keyword,keyword_site,keyword_type,keyword_group,date_updated	) values ('$newkeyword','$site','Manual','$group','1396170942')";
				
				$wpdb->query($query);
				$lastid = $wpdb->insert_id;
				$newItem['id'] = $lastid ;
				$newItem['keyword']=$newkeyword ;
				
				
				$addedArray[] = $newItem;
			
			}
			
			
		}
		
		
	}
	
	echo json_encode($addedArray);
	
 	die();
	
}

//DELETE RECORD
add_action( 'wp_ajax_wp_rankie_delete_keywords', 'wp_rankie_delete_keywords_callback' );
function wp_rankie_delete_keywords_callback(){
	global  $wpdb;
	$ids = $_POST['ids'];
	
	$ids_arr = explode(',', $ids);
	$ids_arr = array_filter($ids_arr);
	
	foreach ($ids_arr as $id){
		echo 'deleting '.$id;
		
		$query="delete from wp_rankie_keywords where keyword_id=$id";
		$wpdb->query($query);
	}
	
}

//UPDATE RANK VIA AJAX GOOGLE :wp_rankie_update_rank
add_action( 'wp_ajax_wp_rankie_update_rank', 'wp_rankie_update_rank_callback' );
function wp_rankie_update_rank_callback(){
	wp_rankie_update_rank($_POST['itm'],$_POST['rank'],$_POST['url']);
	die();
}

//GET RANK TABLE
add_action( 'wp_ajax_wp_rankie_get_rank', 'wp_rankie_get_rank_callback' );
function wp_rankie_get_rank_callback(){
	
	global  $wpdb;
	$id = $_POST['itm'];
	
	
	$query="SELECT * FROM wp_rankie_ranks where keyword_id = $id order by date DESC limit 9 ";
	$rows=$wpdb->get_results($query);
	
	$ranks=array(array('Rank','Rank'));
	$ranks2=array(array('Rank','Rank'));
	
	$rows=array_reverse($rows);
	
	foreach($rows as $row){
		if($row->rank != 0 ){
		 
			$newRank = array(  date('m-d', strtotime( $row->date ) ) , (int) $row->rank );
			$ranks[]=$newRank;
			
			$newRank2 = array(  $row->date , (int) $row->rank ,$row->rank_link);	
			$ranks2[]=$newRank2;
		} 
	}
	
	 
	
	if(@count($newRank) == 0 ) {
		$newRank =array(0,0);
		$ranks[]=$newRank;
	}
	
	print_r (json_encode( array( $ranks , array_reverse($ranks2) ) ));
	die();
}

//FETCH RANK wp_rankie_fetch_rank
add_action( 'wp_ajax_wp_rankie_fetch_rank', 'wp_rankie_fetch_rank_callback' );
function wp_rankie_fetch_rank_callback(){
 	
	global $wp_rankie_last_log;
	
	//set disable time for cron because UI update is working 
	$disable_until = time('now') + 8 * 60 ; // now + 8 minutes
	update_option('wp_rankie_disabled_till',$disable_until);
	
	$itemId=$_POST['itm'];
	$ranking=wp_rankie_fetch_rank($itemId);
	
	//last log message inject
	$ranking['lastLog'] =$wp_rankie_last_log; 
	
	print_r(json_encode($ranking) );
	die();
	
}

//GENERATE REPORT
add_action( 'wp_ajax_wp_rankie_generate_report', 'wp_rankie_generate_report_callback' );
function wp_rankie_generate_report_callback(){
	
	$report=wp_rankie_generate_report($_POST);
	
	print_r(json_encode($report));
	
 	die();

}

//UPDATE GROUP
add_action( 'wp_ajax_wp_rankie_change_keywords', 'wp_rankie_change_keywords_callback' );

function wp_rankie_change_keywords_callback() {
	
	global  $wpdb;
	$ids = $_POST['ids'];
	$group = ($_POST['group']);
	$ids_arr = explode(',', $ids);
	 
	
	foreach ($ids_arr as $id){
	 	
		$query="update wp_rankie_keywords set keyword_group = '$group' where keyword_id=$id";
		$wpdb->query($query);
	}
	
 die();
}