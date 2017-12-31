<?php
add_action('wp_head', 'wp_rankie_function_wp_head_hook');
function wp_rankie_function_wp_head_hook(){

	//know if auto is enabled
	$wp_rankie_options = get_option('wp_rankie_options',array());

	if( ! in_array('OPT_CATCH', $wp_rankie_options) ){
		return ;
	}

	//$ref=$_SERVER['HTTP_REFERER']='http://www.google.com/url?sa=t&rct=j&q=localhost&source=web&cd=2&ved=0CCoQFjAB&url=http%3A%2F%2Fkriuo.com%2F%3Fp%3D260&ei=3qNOU7LHGsStPL6EgZAO&usg=AFQjCNHcgPGufq7_-dSAGWhOrL67C43uOw';
 
	$ref='';
	
	if(isset($_SERVER['HTTP_REFERER'])){
		$ref=$_SERVER['HTTP_REFERER'];
	}
	
	
	
	
	if(trim($ref=='')) return;

	$refs = parse_url($ref);
	$host = $refs['host'];

	if(stristr($host, 'google.')){

		//google refer search for a keyword
		if(stristr($ref, 'q=')){
			//found a query let's extract
			$query_st= explode('q=', $ref);

			$after_q = $query_st[1];
				
			if(trim($after_q) == '') return;
				
			//we got data after q= let's get query
				
			$after_q_arr=explode('&', $after_q);
				
			$after_q_before_and = $after_q_arr[0];
				
			if(trim($after_q_before_and) == '');

			$searchTerm= trim( urldecode($after_q_before_and));

			//now we have a query let's check if it is already tracked or add it for track

			$currentHost= ($_SERVER ['HTTP_HOST'] );
				
			$currentHost= trim( str_replace('www.', '', $currentHost) );
				
			global $wpdb;
			$query="SELECT count(*) as count FROM `wp_rankie_keywords` where keyword ='".addslashes($searchTerm)."' and keyword_site='$currentHost' ";
				
			$rows=$wpdb->get_results($query);
				

			$row=$rows[0];
				
			$count = $row->count;
				

				
			if($count == 0 ){
				//add this one keyword
				$query="insert into wp_rankie_keywords(keyword,keyword_site,keyword_type) values ('".addslashes($searchTerm)."','$currentHost' , 'Auto')";
			
				$wpdb->query($query);
			}
				
				
		}

	}


}
