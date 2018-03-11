<?php 

 
 
	if ( ! wp_next_scheduled( 'wp_rankie_daily_event' ) ) {
		wp_schedule_event( time(), 'hourly', 'wp_rankie_daily_event');
	}
 

add_action( 'wp_rankie_daily_event', 'wp_rankie_do_this_daily' );
/**
 * On the scheduled action hook, run a function.
*/
function wp_rankie_do_this_daily() {
	// do something every dai
	
	 
	
	//check if email is set or not 
	$wp_rankie_mail = get_option('wp_rankie_mail' , '');
	if(trim($wp_rankie_mail) == '') return;
	
	
	//today
	$today = time('now') ;
	$currentDay = date('d');
	
	
	 
	//get yesterday date
	$yesterday = time('now') - 24 * 60 * 60 ;
	$yesterdayDay = date('d',$yesterday);
	$yesterdayMonth = date('m',$yesterday);
	
	//last sent 
	$dayLast = get_option('wp_rankie_last_mail' ,$yesterdayDay);
	 
 
	
	if( trim($currentDay) != trim($dayLast)){
		
		//woo we re on a different day let's send report
		
		//update last sent date
		update_option('wp_rankie_last_mail',$currentDay);
		
		global $wpdb;
		$query="SELECT distinct( wp_rankie_changes.keyword_id ) as single_keyword , keyword , keyword_rank , sum(rank_change) as total_rank_change  , keyword_site , keyword_group  FROM `wp_rankie_changes` ,`wp_rankie_keywords`   where wp_rankie_changes.keyword_id =  wp_rankie_keywords.keyword_id  and   year(date) = year(CURDATE())  and month(date) = '$yesterdayMonth' and day(date) = '$yesterdayDay'      group by single_keyword order by total_rank_change DESC";
		
		$rows=$wpdb->get_results($query );
		
		//getting moving forward & backward keywords
		$moving_forware_html = '<br><h3>Pakilo </h3><table><thead><tr><th>Raktažodis</th><th>Dabartinis reitingas</th><th>Reitingo pasikeitimas</th><th>Domenas</th><th>Grupė</th> </tr></thead>';
		$moving_backward_html = '<br><h3>Nukrito </h3><table><thead><tr><th>Raktažodis</th><th>Dabartinis reitingas</th><th>Reitingo pasikeitimas</th><th>Domenas</th><th>Grupė</th> </tr></thead>';
		
		
		$printedKeys=array();
		$positiveSum = 0;
		$negativeSum = 0;
		
		foreach($rows as $row ){
				
			$printedKeys[]=$row->single_keyword;
				
			if( $row->total_rank_change > 0 ){
				$tr = '<tr><td>'.$row->keyword.'</td><td>'.$row->keyword_rank.'</td><td>+'.$row->total_rank_change.'</td><td>'.$row->keyword_site.'</td><td>'.$row->keyword_group.'</td></tr>';
				$moving_forware_html .= $tr;
				$positiveSum = $positiveSum + $row->total_rank_change;
			}elseif( $row->total_rank_change < 0 ){
				$tr = '<tr><td>'.$row->keyword.'</td><td>'.$row->keyword_rank.'</td><td>'.$row->total_rank_change.'</td><td>'.$row->keyword_site.'</td><td>'.$row->keyword_group.'</td></tr>';
				$moving_backward_html .= $tr;
				$negativeSum = $negativeSum + $row->total_rank_change;
			}
				
		}
		
		if($positiveSum == 0 ) $moving_forware_html .= '<tr><td colspan="5" >Pakitimų nėra </td></tr>';
		if($negativeSum == 0 ) $moving_backward_html .= '<tr><td colspan="5" >Pakitimų nėra </td></tr>';
		
		$moving_forware_html .= '</table>';
		$moving_backward_html .= '</table>';
		$head="<h1>GRank ataskaita - $yesterdayDay - $yesterdayMonth </h1> <p>Žemiau 24 valandų raktinių žodžių ataskaita.</p>";
		
		$emailTitle = "GRank ataskaita - $yesterdayDay - $yesterdayMonth";
		$emailBody = $head.$moving_forware_html . $moving_backward_html;
		 
		add_filter( 'wp_mail_content_type', 'wp_rankie_set_content_type' );
		function wp_rankie_set_content_type( $content_type ){
			return 'text/html';
		}
		
		wp_mail( $wp_rankie_mail , $emailTitle, $emailBody );
		
	 
		
	}
  
}

?>