<?php

global $ch;
global $wp_rankie_last_log;
global $currentPage;

if(function_exists('curl_init')){
	$ch= curl_init();
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($ch, CURLOPT_TIMEOUT,60);
	curl_setopt($ch, CURLOPT_REFERER, 'http://www.whatsmyserp.com/');
	
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Ubuntu Chromium/41.0.2272.76 Chrome/41.0.2272.76 Safari/537.36');
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
	//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
	curl_setopt($ch, CURLOPT_COOKIEJAR , "cookie.txt");	
}
 
/**
 * Function opt_selected
 */	
if(! function_exists('opt_selected')){
	function opt_selected($src,$val){
		if (trim($src)==trim($val)) echo ' selected="selected" ';
	}
}

/**
 * Function wp_rankie_fetch_rank : switch between rank fetching methods and uses one selected in settings
 */
function wp_rankie_fetch_rank($itemId){
	
	//get what method to use 
	$method = get_option('wp_rankie_method','googledirect');
	
	wp_rankie_log_new('Rank update','Trying to updating rank for keyword #'.$itemId );

	 
	if($method =='googledirect'){
		return wp_rankie_fetch_rank_google($itemId);
	  
	}elseif($method == 'kproxy' || $method== 'zalmos' || $method == 'ezmlm' || $method == 'whatsmyserp'){
		return wp_rankie_fetch_rank_kproxy($itemId);
	}elseif($method == 'ajax'){
		return wp_rankie_fetch_rank_google($itemId);
	}elseif ($method == 'disconnect'){
		return wp_rankie_fetch_rank_google($itemId);
	}elseif ($method == 'googlecustom'){
		return wp_rankie_fetch_rank_googlecustom($itemId);
	}elseif ($method == 'fsecure'){
		return wp_rankie_fetch_rank_fsecure($itemId);
	}
	
}

/**
 * Google fetch rank using Google Custom API
 */
function wp_rankie_fetch_rank_googlecustom($id){
	
	//INI
	global $wpdb;
	global $ch;
	global $currentPage;
	$debug=false;
	
	$wp_rankie_ezmlm_gl = get_option('wp_rankie_ezmlm_gl','com');
	$wp_rankie_ezmlm_gl = 'google.' . $wp_rankie_ezmlm_gl   ;
	$wp_rankie_options = get_option('wp_rankie_options',array());
	
	$return['id'] = $id;
	$return['status'] = 'error';
	$return['rank'] = 0 ;
	$reutrn['message'] = '';
	$reutrn['link'] = '';
	
	//Validate key and engine id are added 
	$wp_rankie_googlecustom_id = get_option('wp_rankie_googlecustom_id','');
	$wp_rankie_googlecustom_key = get_option('wp_rankie_googlecustom_key','');
	
	if( trim($wp_rankie_googlecustom_id) == '' || trim($wp_rankie_googlecustom_key) == ''  ){

		$message = 'Please visit the plugin settings page and add required key and search engine id';
		wp_rankie_log_new('Google custom', $message);
		
		$reutrn['message'] = $message ;
		return $return;
	}
	
	//Now get one key out of many if applicable 
	$wp_rankie_googlecustom_keys = explode(',', $wp_rankie_googlecustom_key);
	$wp_rankie_googlecustom_keys = array_filter($wp_rankie_googlecustom_keys);
	$now = time('now');
	
	$validWorkingKey = '';
	foreach ($wp_rankie_googlecustom_keys as $current_key){
		
		if(trim($current_key) != ''){
			
			//check if key is disabled or not
			$current_keyMd5 = md5($current_key);
			$disabledTill = get_option('wp_rankie_'.$current_keyMd5,'1463843434');
			
			if($disabledTill > $now){
				continue;
			}else{
				$validWorkingKey = $current_key;
				break;
			} 
			
		}
		
	}	
	
	if(trim($validWorkingKey) == ''){
		$message = 'Keys used reached maximum requests limit per day wich is 100 per key. will check again after an hour';
		wp_rankie_log_new('Google custom', $message);
		
		$reutrn['message'] = $message ;
		return $return;
	}else{
		$wp_rankie_googlecustom_key = $validWorkingKey;
		wp_rankie_log_new('Google custom', 'Using Key:'.$validWorkingKey);
	}
	
	//GET RECORD
	$query="SELECT * FROM wp_rankie_keywords where keyword_id=$id";
	$rows=$wpdb->get_results($query);
	$row =$rows[0];
	$keyword = $row->keyword;
	$keyword_site = $row->keyword_site;
	@$last_checked_page  =  $row->last_checked_page;
	$keyword_rank        =  $row->keyword_rank;
	
	$keyword_site_full = ''; //full url if the keyword site is an exact url
	
	if( stristr($keyword_site, 'http:') || stristr($keyword_site, 'https:') || stristr($keyword_site, 'www.') ){
	
		$keyword_site_full = $keyword_site;
	
		//getting the domain
		$host = parse_url($keyword_site ,  PHP_URL_HOST);
		$host = preg_replace('{^www\.}', '',$host);
		$keyword_site = $host;
	
	}
	
	
	// get the startIndex from the last_checked_page
	$startIndex = 1;
	$currentPage= 1;
	
	if(! in_array('OPT_PAGINATE', $wp_rankie_options)){
	
		// get currentPage to check
		if($keyword_rank == 0){
				
			$currentPage = $last_checked_page + 1 ;
				
		}else{
				
			$currentPage = $last_checked_page;
				
			if($currentPage == 0) $currentPage = 1 ;
				
		}
	
		// if passed limit reset
		if($currentPage > 10) $currentPage = 1;
	
		// startIndex from the current page
		$startIndex = ($currentPage -1) * 10 + 1;
	
	}
	
	//Process request 
	$gurl ="https://www.googleapis.com/customsearch/v1?key=" . urlencode(  trim($wp_rankie_googlecustom_key) ) . "&cx=" . urlencode(  trim($wp_rankie_googlecustom_id) ) . "&q=".urlencode(trim($keyword)).'&googlehost='.urlencode($wp_rankie_ezmlm_gl).'&start='.$startIndex;
	 
 
	if($debug) print_r($gurl);
	
	//curl get
	$x='error';
	$url=$gurl;
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_URL, trim($url));

	$exec=curl_exec($ch);
	$x=curl_error($ch);
	
	// validate returned content
	if(trim($exec) == ''){
		$message = 'No content returned from processing the api request possible curl error:'.$x;
		wp_rankie_log_new('Google custom', $message);
		$return['message'] = $message;
		return $return;
	}
	
	
	// validate json
	if( ! stristr($exec , '{') ){
		$message = 'No Json reply returned suspected reply';
		$return['message'] = $message;
		return $return;
	}
 
 	// good let's get results
	$jsonReply = json_decode($exec);
	  
	if(isset($jsonReply->error)){
		
		$jsonErr = $jsonReply->error->errors[0];
		
		$errReason  = $jsonErr->reason;
		$errMessage = $jsonErr->message;
		
		$message = 'Api returned an error: '.$errReason.' '.$errMessage;
		wp_rankie_log_new('Google custom', $message);
		 
		
		// disable limited keys
		if($errReason == 'dailyLimitExceeded'){
			update_option('wp_rankie_'.$current_keyMd5,$now + 60*60);
		}
		
		$return['message'] = $message;
		return $return;
		
	}
	
	$foundLinks = $jsonReply->items;
	
	if($debug) print_r($foundLinks);
	
	if(count($foundLinks) == 0){
	
		//no results apear here 
		wp_rankie_update_rank($id,0,'');
		$return['status']='success';
		$return['message']='No links found in the returned google page';
		wp_rankie_log_new('Google custom', 'No links found ');
		return $return;
					
				 
	}else{
		
		//good news we have links 
		$i=0;
		
		foreach($foundLinks as $foundLink ){
						
			$finalUrl= $foundLink->link;
						
			
			$i++;
			
			  
			// compare final url host with site host
			if (stristr ( $finalUrl, $keyword_site )) {
				// verify
			
				$parsedUrl = parse_url ( $finalUrl );
				$parsedHost = $parsedUrl ['host'];
			
				$parsedHost = preg_replace ( '/^www\./', '', $parsedHost );
			
				if (trim ( $parsedHost ) == trim ( $keyword_site )) {
			
					// fix rank according to the page
					if($currentPage > 1 ){
						$rank = $i + ($currentPage -1) * 10;
					}else{
						$rank = $i;
					}
					
					// Now same domain check if exact match
					if( trim( $keyword_site_full ) != '' ){
					
						// exact match let's match
					
						//clean original url
						$cleanOriginalUrl =  preg_replace('{http[s]?://(www\.)?}', '', $keyword_site_full);
						$cleanOriginalUrl =  preg_replace('{/$}', '', $cleanOriginalUrl);
					
						//clean final url
						$cleanFinalUrl =  preg_replace('{http[s]?://(www\.)?}', '', $finalUrl);
						$cleanFinalUrl =  preg_replace('{/$}', '', $cleanFinalUrl);
					
						if($cleanFinalUrl != $cleanOriginalUrl) continue;
					
					}
					
					$return ['rank'] = $rank;
					$return ['link'] = $finalUrl;
					$return ['status'] = 'success';
			
					wp_rankie_update_rank ( $id, $i, $finalUrl );
			
					return $return;
				}
			}
			
		}
		
	}
			
	//oh loop ended without a match let's update it to 0
	
	if(count($foundLinks) >0){
		$return['count'] = count($foundLinks);
	}
	
	wp_rankie_update_rank($id,0,'', count($foundLinks));
	$return['status']='success';
	return $return;
}

/**
 * Google ajax rank
 */
function wp_rankie_fetch_rank_ajax($id){
	
	//INI
	global $wpdb;
	global $ch;
	
	
	//GET RECORD
	$query="SELECT * FROM wp_rankie_keywords where keyword_id=$id";
	$rows=$wpdb->get_results($query);
	$row =$rows[0];
	$keyword = $row->keyword;
	$keyword_site = $row->keyword_site;
	
	//print_r($row);
	
	//language
	$gl = get_option('wp_rankie_google_gl','us');
	
	
	
	return  wp_rankie_fetch_rank_ajax_call($id,$keyword,$keyword_site,0,$gl);
	 
}


function wp_rankie_fetch_rank_ajax_call($itemId , $itemText , $itemSite , $searchIndex ,$gl){
	
	global  $ch;

	$return['id'] = $itemId ;
	$return['status'] = 'error';
	$return['rank'] = 0 ;
	$reutrn['message'] = '';
	$reutrn['link'] = '';
	
	$glink="http://ajax.googleapis.com/ajax/services/search/web?v=1.0&q=".rawurlencode($itemText)."&rsz=8&start=".$searchIndex * 8 ."&gl=".$gl;
	
	wp_rankie_log_new('google ajax api', 'calling ajax api for page '.$searchIndex);
	
	//curl get
	$x='error';
	$url=$glink;
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_URL, trim($url));
	
	$exec=curl_exec($ch);
	$x=curl_error($ch);
	
	if(trim($exec) == ''){
	
		$return['message'] == 'Empty reply from google ajax call';
		wp_rankie_log_new('google ajax api', 'Got empty reply from google ajax api');
	
	}else{
	
		//good we got a reply
		if(substr($exec, 0,1) != '{' ){
			$return['message'] == 'Empty reply from google ajax call';
			wp_rankie_log_new('google ajax api', 'Got non json reply from google');
		}else{
			//cool we have a json reply
			$jsonReply = json_decode($exec);
			
			
			if(! isset($jsonReply->responseData)){
				$return['message'] == 'ResponseData not found in reply';
				wp_rankie_log_new('google ajax api', 'ResponseData not found in reply');
			}else{
				//good we have a response data check if cursor
				if( ! isset($jsonReply->responseData->cursor) || ! isset($jsonReply->responseData->results) ){
					//oops not results update to 0
					$return['status']='success';
						
					//UPDATE TO 0
					wp_rankie_update_rank($itemId, 0 , '');
					
					return $return;
					
				}else{
					// cool we have cursor so results
					$results = $jsonReply->responseData->results;

					
					$rankExist=wp_rankie_rank_exists($itemSite,$results);
						
					if($rankExist) {
						//good we got a rank 
						$finalRank = $searchIndex * 8 + $rankExist;
						
						$return['status']='success';
						$return['rank'] = $finalRank;
						 
						
						//update rank 
						wp_rankie_update_rank($itemId, $finalRank, $results[$rankExist-1]->unescapedUrl );
						
						return $return;
						
					}else{
						
						
						//hmm site not withen the reults try another page if available
						$searchIndex = $searchIndex + 1 ;
						
						if( $searchIndex < count($jsonReply->responseData->cursor->pages)  ){
							
							return wp_rankie_fetch_rank_ajax_call($itemId , $itemText , $itemSite , $searchIndex  ,$gl);
							
						}else{
							//reached end without result let's set to 0
							$return['status']='success';
							
							//UPDATE TO 0
							wp_rankie_update_rank($itemId, 0 , '');
								
							return $return;
						}
					}	
	
				}
	
			}
				
		}
	
	}// trim exec
	
	return $return;
	
}

/**
 * Function : rank exists in list of url
 */
function wp_rankie_rank_exists($site,$results){
	
 
	
	$i=1;
	
	foreach ($results as $result ){
		 
		if(stristr( $result->unescapedUrl , $site )){
			
			$parse=parse_url($result->unescapedUrl);
			
			$parse['host']=preg_replace('/^www\./', '', $parse['host']);
			
			if( $parse['host'] == $site ){
				//weekweek we fount the rank
				return $i;
			}
			
		}
	
		
		$i++;	
	}
	
	return false;
}


/**
 * Function : wp_rankie_fetch_rank_ezmlm
 */
function wp_rankie_fetch_rank_ezmlm($id){

	//INI
	global $wpdb;
	
	$return['id'] = $id;
	$return['status'] = 'error';
	$return['rank'] = 0 ;
	$reutrn['message'] = '';
	$reutrn['link'] = '';
	
	//GET RECORD
	$query="SELECT * FROM wp_rankie_keywords where keyword_id=$id";
	$rows=$wpdb->get_results($query);
	$row =$rows[0];
	$keyword = $row->keyword;
	$keyword_site = $row->keyword_site;
	
	//call
	//curl ini
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT,20);
	curl_setopt($ch, CURLOPT_REFERER, 'http://serp-checker.ezmlm.org');
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
	curl_setopt($ch, CURLOPT_COOKIEJAR , "cookie.txt");
	
	//curl post
	$curlurl="http://serp-checker.ezmlm.org/rank.php";
	
	$wp_rankie_ezmlm_gl = get_option('wp_rankie_ezmlm_gl','com');
	
	$curlpost='link='.trim(urlencode($keyword_site)).'&keywords='. trim(urlencode($keyword)) .'&google=Y&pages=10&tld='.$wp_rankie_ezmlm_gl.'&Submit=Check+Rank';
	 
	curl_setopt($ch, CURLOPT_URL, $curlurl);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpost); 
	$exec=curl_exec($ch);
	$x=curl_error($ch);
 
	
	//extracting data 
	
	if(stristr($exec, $keyword_site)){
		
		//good we found the domain let's extract

		/*
		 * <tr class="row1">
					<td>moz.com</td>
					<td>seo</td>
					<td>3</td>
					<td>N/A</td>
					<td>N/A</td>										
					</tr>
		 */
		
		preg_match_all('/<tr class="row1">.*?<td>.*?<\/td>.*?<td>.*?<\/td>.*?<td>(.*?)<\/td>/s', $exec,$matches);
		
		if(isset($matches[1][0]) && is_numeric(trim($matches[1][0])) ){
			$foundRank =$matches[1][0];
			
			//if rank is 0 this may be a fake reult let's verify if it returns real values

			 
			if($foundRank == 0 && $row->keyword_rank != 0 ){
				 
				wp_rankie_log_new('Ezmlm', 'Verifying a 0 rank value ');
				
				$curlpost='link=moz.com&keywords=moz&google=Y&pages=10&tld=com&Submit=Check+Rank';
				
				curl_setopt($ch, CURLOPT_URL, $curlurl);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpost);
				$exec=curl_exec($ch);
				$x=curl_error($ch);
				
				preg_match_all('/<tr class="row1">.*?<td>.*?<\/td>.*?<td>.*?<\/td>.*?<td>(.*?)<\/td>/s', $exec,$matches2);
				$foundRank2 =$matches2[1][0];
				
			  
				if( ! is_numeric($foundRank2) || ! ($foundRank2 > 0) ){
					//here is the catch it returns non valid results 
					$return['message'] = 'emzlm returns 0 results rank will not be regarded';
					wp_rankie_log_new('Ezmlm', 'Returned rank seems to be not correct will be ignored this time ');
					
					return $return;
				}else{
					wp_rankie_log_new('Ezmlm', 'Rank seems correct ');
				}
				
			}

			$return['status'] = 'success';
			$return['rank'] = $foundRank;
			$return['link'] = '';
			
			wp_rankie_update_rank($id,$foundRank,'');
			
		}else{
			$return['message']= 'could not extract numeric rank value ';
			wp_rankie_log_new('Ezmlm', 'could not extract numeric value ');
		}
		
	}else{
		$return['message']= 'Html reply dont contain searched domain ';
		wp_rankie_log_new('Ezmlm', 'Empty reply from ezmlm ');
	}
	
	return $return;
	
}

/**
 * Function wp_rankie_fetch_rank
 * @param unknown $id
 * @return Ambigous <number, string>
 */
function wp_rankie_fetch_rank_whatsmyserp($id){
	 
	//INI
	global $wpdb;
	global $ch;
	
	$return['id'] = $id;
	$return['status'] = 'error';
	$return['rank'] = 0 ;
	$reutrn['message'] = '';
	$reutrn['link'] = '';
	
	//check if whatsmyserp is disabled if yes we will silently return nothing
	$whatsmysert_disabled_till = get_option('whatsmysert_disabled_till', 1401741385 );
	$wp_rankie_whatsmyserp_runout = get_option('wp_rankie_whatsmyserp_runout', 1401741385 );
	$wp_rankie_whatsmyserp_prox_lock = get_option('wp_rankie_whatsmyserp_prox_lock', 1401741385 );
	$wp_rankie_whatsmyserp_proxy_lock_max = get_option('wp_rankie_whatsmyserp_proxy_lock_max', 1401741385 );

 	
	$proxified = false;
	
	//VERIFY ACTIVITY ? 
	if(time('now') < $whatsmysert_disabled_till){
		
		//we now in sake of a proxy or simply return because direct call to whatsmyserp will return limited or 403
		$return['message'] = 'Using whatsmyserp method is now deactivated due to limit reach or permanent block';
		return $return;
 	
	}
	
	//stopHere();
	
	//GET RECORD
	$query="SELECT * FROM wp_rankie_keywords where keyword_id=$id";
	$rows=$wpdb->get_results($query);
	$row =$rows[0];
	$keyword = $row->keyword;
	$keyword_site = $row->keyword_site;
	$wp_rankie_whatsmyserp_blocked = get_option('wp_rankie_whatsmyserp_blocked',array());
	
	//CHECK if blocked
	if(in_array($keyword_site, $wp_rankie_whatsmyserp_blocked)){
		//this sit is blocked
		wp_rankie_log_new('Whatsmyserp','Whatsmysrp will not be able to get rank for this domain '.$keyword_site . ' USE A DIFFERENT METHOD');
		$return['message']='Whatsmysrp will not be able to get rank for this domain '.$keyword_site . 'USE A DIFFERENT METHOD';
		return $return;
		
	}	
	 
	//SERP CALL
	$wp_rankie_whatsmyserp_g = trim( get_option('wp_rankie_whatsmyserp_g','www.google.com') );
	 
 
	//curl get
	$x='error';
	$url='http://www.whatsmyserp.com/serpcheck.php';
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_URL, trim($url));
	curl_setopt($ch, CURLOPT_HEADER,1);
	
	//random agent 
	require_once 'inc/user-agents.php';
	
	curl_setopt($ch, CURLOPT_USERAGENT,  random_user_agent() );
	
	curl_setopt($ch,CURLOPT_HTTPHEADER,array('Referer: http://www.whatsmyserp.com/'));
	
	$exect=curl_exec($ch);
	curl_setopt($ch, CURLOPT_HEADER,1);	
	$x=curl_error($ch);
	
	
	 
	if(stristr($exect, 'PHPSESSID')){
		//get the session
		preg_match('/PHPSESSID\=(.*?);/', $exect,$matches);
	
		if(is_array($matches) && isset($matches[1])){
			$session=$matches[1];
		}
		
		
		if(trim($session) != ''){
			//curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$session.';keywordinputs=%7B%220%22%3A%7B%22keywords%22%3A%22album%20jeunesse%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%221%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%222%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%223%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%224%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%225%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%7D; domaininputs=%7B%22domainname%22%3A%7B%22domainname%22%3A%22leslecturesdeliyah.com%22%2C%22domaincompetition1%22%3A%22%22%2C%22domaincompetition2%22%3A%22%22%2C%22domaincompetition3%22%3A%22%22%2C%22googleregional%22%3A%22www.google.fr%22%7D%7D');
			//curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$session.'; __utmt=1; __qca=P0-1874551402-1412867579473; __utma=128099402.2134654754.1412867579.1412867579.1412867579.1; __utmb=128099402.2.10.1412867579; __utmc=128099402; __utmz=128099402.1412867579.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); keywordinputs=%7B%220%22%3A%7B%22keywords%22%3A%22'. trim(rawurlencode($keyword)) .'%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%221%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%222%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%223%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%224%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%225%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%7D; domaininputs=%7B%22domainname%22%3A%7B%22domainname%22%3A%22'.trim(urlencode($keyword_site)).'%22%2C%22domaincompetition1%22%3A%22%22%2C%22domaincompetition2%22%3A%22%22%2C%22domaincompetition3%22%3A%22%22%2C%22googleregional%22%3A%22'.$wp_rankie_whatsmyserp_g.'%22%7D%7D');
			curl_setopt($ch,CURLOPT_COOKIE,'PHPSESSID='.$session.';keywordinputs=%7B%220%22%3A%7B%22keywords%22%3A%22'. trim(rawurlencode($keyword)) .'%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%221%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%222%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%223%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%224%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%2C%225%22%3A%7B%22keywords%22%3A%22%22%2C%22searches%22%3A%22%22%2C%22curr%22%3A%22%22%2C%22prev%22%3A%22%22%2C%22best%22%3A%22%22%2C%22img%22%3A%22dash%22%7D%7D; domaininputs=%7B%22domainname%22%3A%7B%22domainname%22%3A%22'.trim(urlencode($keyword_site)).'%22%2C%22domaincompetition1%22%3A%22%22%2C%22domaincompetition2%22%3A%22%22%2C%22domaincompetition3%22%3A%22%22%2C%22googleregional%22%3A%22'.$wp_rankie_whatsmyserp_g.'%22%7D%7D');
		}
		
	} 
	
	
 
	//curl get
	$x='error';
	$randId= rand(88888888888, 98888888888);
	//$url='$url='http://www.whatsmyserp.com/getSERPresults.php?domainname=wikimapia.org&googleregional=www.google.com&compdomain1=&compdomain2=&compdomain3=&keyword=wikimapia&cached=0&timeout=8291648&userkeywordid=&prev=1000&_=1430321226439';';
	$url='http://www.whatsmyserp.com/getSERPresults.php?domainname='.trim(urlencode($keyword_site)).'&googleregional='.$wp_rankie_whatsmyserp_g.'&compdomain1=&compdomain2=&compdomain3=&keyword='. trim(rawurlencode($keyword)) .'&cached=0&timeout=8291648&userkeywordid=&prev=1000&_=1430321226439';
	
	
	
	//$url="http://www.whatsmyserp.com/getSERPresults.php?domainname=leslecturesdeliyah.com&googleregional=www.google.fr&compdomain1=&compdomain2=&compdomain3=&keyword=album%20jeunesse&cached=0&userkeywordid=&prev=1000&_=1412867582460";
	//$url2='http://www.whatsmyserp.com/getSERPresults.php?domainname=' . trim(urlencode($keyword_site)) . '&googleregional=www.google.com&compdomain1=moz.com&compdomain2=&compdomain3=&keyword='. trim(rawurlencode($keyword)) .'&cached=0&userkeywordid=88888888888&prev=1000&_=1397911497219';
 	
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_URL, trim($url));
	
	curl_setopt($ch,CURLOPT_HTTPHEADER,array('Referer: http://www.whatsmyserp.com/serpcheck.php'));
	
	$exec=curl_exec($ch);
	 
	$x=curl_error($ch);
 
	//parse results
	
	if(trim($exec) !=""){
		//reply found
		wp_rankie_whatsmyserp_proxy_status('success');
	
		if(stristr($exec, 'You have performed over 100')){
			
			$return['message']='100 request limit reached please add user and password';
			
			if($proxified){
				//proxy limited remove it + report it 
				
				//proxy should be deleted and reported
				wp_rankie_log_new('whatsmyserp', 'Proxy Limited. It will be removed to be replaced.'  );
					
				//delete
				delete_option('wp_rankie_whatsmyserp_proxy');
					
				//report limited
				$licenseactive=true;
				$wp_rankie_license = 'Active';
			 
				
			}else{
				//here we should postbone any whatsmyserp calls for one hour
				update_option('whatsmysert_disabled_till',time('now') + 60 * 60  );
				
				wp_rankie_log_new('whatsmyserp', 'Daily limit of 100 serp reached. will try to get proxies next time');
				
			}

		}elseif( stristr($exec, 'have permission to access') ){

			//We are blocked so if we are on server ip disable direct call for a week 
			
			if($proxified){
				
				wp_rankie_log_new('whatsmyserp', 'Serp using this proxy is not working removing it ...');
				
				delete_option('wp_rankie_whatsmyserp_proxy');
				
				//the proxy is blocked just report and delete it
				$licenseactive=true;
				$wp_rankie_license = 'Active';
				
		 
				
			}else{
				
				wp_rankie_log_new('whatsmyserp','SERP using the server ip is not working. will try to get some proxies');
				
				//our server is blocked disable direct calls for a week
				update_option('whatsmysert_disabled_till',time('now') + 7 * 24 * 60 * 60  );
				
				
			}
			

		}elseif(stristr($exec, 'There was a problem with processing the SERP results')){
			
		 	//bad luck this domain will not work with myserp
			wp_rankie_log_new('whatsmyserp','Whatsmyserp says you will not be able to check serp for this domain '.$keyword_site.' . USE ANOTHER METHOD ');
			
			$wp_rankie_whatsmyserp_blocked = get_option('wp_rankie_whatsmyserp_blocked',array());

			$wp_rankie_whatsmyserp_blocked[] = $keyword_site;
			 
			update_option('wp_rankie_whatsmyserp_blocked' ,$wp_rankie_whatsmyserp_blocked);
			
			$return['message'] = 'Whatsmyserp says you will not be able to check serp for this domain '.$keyword_site ;
			return $return;
			
		}else{
 
			//we are here not limited after 100 , not blocked , not site serp blocked and have content 
			
			if(stristr($exec, 'Domain not found')){
				
				$return['status'] = 'success';
				wp_rankie_update_rank($id,0,'');
				wp_rankie_whatsmyserp_proxy_max($proxified);
					
			}else{
				//may be found
				if(stristr($exec, 'color: blue;')){
					//blue i.e link found {color: blue;">http://moz.com/beginners-guide-to-seo</span>}
					preg_match_all('/blue;">(.*?)<\/span/', $exec ,$matches);
		
					if(isset($matches[1][1])){
	
						//link extracted successfully
						$link=$matches[1][1];
							
						//extract the rank number
						preg_match('/\n(.*?)::toprow::/', $exec , $matches2);
							 
						if(isset($matches2[1])){
							
							//rank extracted successfully
							$final_rank = $matches2[1] ;
						
							$return['status'] = 'success';
							$return['rank'] = $final_rank;
							$return['link'] = $link;
							
							//update rank in db
							wp_rankie_update_rank($id,$final_rank,$link);
							wp_rankie_whatsmyserp_proxy_max($proxified);
							
						}else{
							$return['message'] = 'Can not extract rank ';
							wp_rankie_log_new('whatsmyserp', 'Can not extract ranks ' );
						}
							
					}else{
						wp_rankie_log_new('whatsmyserp', 'Can not extract the ranks');
						$return['message'] = 'Can not extract the link ';
					}
				}else{
					
					//here we have content but don't identify a known pattern
				
					//may be we are on a proxy that is sending wrong response ?! [uncovered case here] 
					wp_rankie_whatsmyserp_proxy_status('notvalid');
					
					wp_rankie_log_new('whatsmyserp', 'no valid reply found unexpected reply');
					$return['message'] = 'No valid rank found unexpected response';
					echo $exec;
				}
					
			}//end domain not found if
		
		}//end if over 100
	
	}else{
		//empty result
		$return['message']='Empty reply found';
		wp_rankie_log_new('whatsmyserp', 'Empty reply from whatsmyserp '.$x);
		
		//if proxy report 
		if($proxified) wp_rankie_whatsmyserp_proxy_status('fail');
		
	}
	
	
	return $return ;
	
}

/**
 * This function will remove the proxy after three failed attempts 
 * It resets the fail number flag when successfull update
 * It will report limit reached proxies
 * @param unknown $status fail,success,limit,notvalid
 */
function wp_rankie_whatsmyserp_proxy_status($status){
	
	global $wpdb;
	global $ch;
	
	$wp_rankie_whatsmyserp_proxy = get_option('wp_rankie_whatsmyserp_proxy','');
	
	//FAIL 
	if($status == 'fail'){
		//the proxy call failed
		
		$wp_rankie_proxy_fail_count = get_option('wp_rankie_proxy_fail_count' , 0 );

		if($wp_rankie_proxy_fail_count > 2 ){
			
			//proxy should be deleted and reported
			wp_rankie_log_new('whatsmyserp', 'Proxy failed '. ($wp_rankie_proxy_fail_count ) .' times it will be removed now' );
			
			//delete
			delete_option('wp_rankie_whatsmyserp_proxy');
			
			//report broken
			
			$licenseactive=true;
				$wp_rankie_license = 'Active';
				
	 
			
			
		}else{
			
			//increment the fail times
			update_option('wp_rankie_proxy_fail_count',$wp_rankie_proxy_fail_count + 1 );
			
			wp_rankie_log_new('whatsmyserp', 'Proxy failed '. ($wp_rankie_proxy_fail_count + 1) .' times of 3 allowed before removing it' );
		}
		
	}elseif($status == 'success'){
		
		//succeeded let's reset fail flag
		delete_option('wp_rankie_proxy_fail_count');
		
	}elseif($status == 'notvalid'){
		
		//even if this proxy return cotent it returns nonsense which means it failed to process the request
		// we will delete it without reporting it is a stupid proxy for the system there to ignore checking it and regard as working and don't assign it again
		
		//default valid array
		$valid['proxy'] = $wp_rankie_whatsmyserp_proxy;
		$valid['count'] = 0;
		
		//get validity array
		$wp_rankie_proxy_valid = get_option('wp_rankie_proxy_valid',$valid);
		
		if($valid['proxy'] == $wp_rankie_whatsmyserp_proxy){
			//ok this validity array is for the current proxy not an old one 
			
		}else{
			//this validity array is for an old proxy that no more exist skip it
			$wp_rankie_proxy_valid = $valid;
		}
		
		//if count > 2 delete the proxy if not increment count
		if($wp_rankie_proxy_valid['count'] > 2 ){
			
			//delete this stupid proxy
			delete_option('wp_rankie_whatsmyserp_proxy');
			wp_rankie_log_new('whatsmyserp', 'Proxy giving unexpected response for 3 times it is deleted to be replaced');
			
		}else{
			//increment
			$wp_rankie_proxy_valid['count'] = $wp_rankie_proxy_valid['count'] + 1;
			
			update_option('wp_rankie_proxy_valid' , $wp_rankie_proxy_valid);
			
			wp_rankie_log_new('whatsmyserp', 'Proxy giving unexpected response for the '.$wp_rankie_proxy_valid['count'] . ' time it will be deleted and replaced if it  did it 3 times');
			
		}
		
		
		
	}
	
}

/**
 * Function will record a fetched proxies support and lock if 3 different proxies fetched 
 * 
 */
function wp_rankie_whatsmyserp_proxy_max($proxified){
	
	if($proxified){

		$wp_rankie_whatsmyserp_proxy_max =  get_option('wp_rankie_whatsmyserp_proxy_max',array());
		$wp_rankie_whatsmyserp_proxy_day = get_option ('wp_rankie_whatsmyserp_proxy_day' , '');
		$wp_rankie_whatsmyserp_proxy = get_option('wp_rankie_whatsmyserp_proxy','');
		
		
		$today_day = date('d');
		
		if($today_day == $wp_rankie_whatsmyserp_proxy_day){
			//this is a valid record for today append this proxy to the list
			
			if(! in_array($wp_rankie_whatsmyserp_proxy, $wp_rankie_whatsmyserp_proxy_max)){
				$wp_rankie_whatsmyserp_proxy_max[] = $wp_rankie_whatsmyserp_proxy ;
				update_option('wp_rankie_whatsmyserp_proxy_max', array_filter( $wp_rankie_whatsmyserp_proxy_max ) );
			}
			
			
			
			
		}else{
			//we are on a new day and this is a new day
			
			//update day to today_day
			update_option('wp_rankie_whatsmyserp_proxy_day' , $today_day);
			
			//update used proxies for today
			$wp_rankie_whatsmyserp_proxy_max=array($wp_rankie_whatsmyserp_proxy);
			update_option('wp_rankie_whatsmyserp_proxy_max',$wp_rankie_whatsmyserp_proxy_max);
			 
		}
		
		
	}
	
}

/**
 * Whatsmyserp login
 */

function wp_rankie_whatsmyserp_login(){
	return true;//function cancelled for generating problems
	global $ch;
	 
	$whatsmysert_user= get_option('whatsmysert_user','');
	$whatsmysert_pass = get_option('whatsmysert_pass','');

	if(trim($whatsmysert_pass) != '' && trim($whatsmysert_user) != '' ){
	
	}else{
		wp_rankie_log_new('whatsmyserp', 'No username or password are set this means requests will be limited to 100 ');
		return;
	}
	
	//check if we have a session and already logged in
	$session = get_option('wp_rankie_whatsmyserp_session','');
	
	if(trim($session) != ''){
		//good we have a session stored let's test it 
		$x='error';
		$url='http://www.whatsmyserp.com/';
		
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_URL, trim($url));
		curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$session);
		 
		 
		$exec=curl_exec($ch);
		 
		//check if we are already loggedin
		if(stristr($exec, 'id="loginpromptlogoutbutton')){
			//good news we are still logged in let's return without logging
			wp_rankie_log_new('Whatsmyserp login','we are still logged to whatsmyserp will not login again');
			return true;
		}
		
		
	}
	

 	//curl get
	$x='error';
	$url='http://www.whatsmyserp.com/accountUtils/authenticateuser.php?username='.$whatsmysert_user.'&password='.$whatsmysert_pass;
	
 
	
	curl_setopt($ch, CURLOPT_HTTPGET, 1);
	curl_setopt($ch, CURLOPT_URL, trim($url));
	curl_setopt($ch, CURLOPT_HEADER,1);
	
	$exec=curl_exec($ch);
 	
	curl_setopt($ch, CURLOPT_HEADER,0);
	
	 
	$x=curl_error($ch);
	 
	
	 
	if(trim($exec) == '' || trim($exec) == 'BAD' ){
		wp_rankie_log_new('Whatsmyserp Error', 'can not login to whatsmyserp please check username and password');
	}else{
		
		//save the last session 
		if(stristr($exec, 'PHPSESSID')){
			//get the session
			preg_match('/PHPSESSID\=(.*?);/', $exec,$matches);
			 
			if(is_array($matches) && isset($matches[1])){
				$session=$matches[1];
				
				//update session parameter
				update_option('wp_rankie_whatsmyserp_session',$session);
				wp_rankie_log_new('Whatsmyserp','Login success with session:'.$session);
				
			}
		}
		
		return true;
	}
	
}

/**
 * Fetch RANK from googld directly
 * @param unknown $id
 */
function wp_rankie_fetch_rank_google($id){
	
	//INI
	global $wpdb;
	$wp_rankie_ezmlm_gl = get_option('wp_rankie_ezmlm_gl','com');
	$wp_rankie_google_county = get_option('wp_rankie_google_county','N');
	$wp_rankie_options = get_option('wp_rankie_options',array());
	$wp_rankie_proxies = get_option('wp_rankie_proxies','');
	$debug = false;
	
	$return['id'] = $id;
	$return['status'] = 'error';
	$return['rank'] = 0 ;
	$reutrn['message'] = '';
	$reutrn['link'] = '';
	
	//GET RECORD
	$query="SELECT * FROM wp_rankie_keywords where keyword_id=$id";
	$rows=$wpdb->get_results($query);
	$row =$rows[0];
	$keyword = $row->keyword;
	$keyword_site = trim($row->keyword_site);
	
	$keyword_site_full = ''; //full url if the keyword site is an exact url

	if( stristr($keyword_site, 'http:') || stristr($keyword_site, 'https:') || stristr($keyword_site, 'www.') ){
		
		$keyword_site_full = $keyword_site;
		
		//getting the domain
		$host = parse_url($keyword_site ,  PHP_URL_HOST);
		$host = preg_replace('{^www\.}', '',$host);
		$keyword_site = $host;
		
	}	
	
	//SERP CALL
	
	//curl ini
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER,0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_TIMEOUT,30);
	curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.'.$wp_rankie_ezmlm_gl);
//	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:28.0) Gecko/20100101 Firefox/28.0');
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_4) AppleWebKit/601.5.17 (KHTML, like Gecko) Version/9.1 Safari/601.5.17');
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
	@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
	@curl_setopt($ch, CURLOPT_COOKIEJAR , "cookie.txt");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	
	//verbose	
	$verbose=fopen( dirname(__FILE__) . '/verbose.txt', 'w');
	curl_setopt($ch, CURLOPT_VERBOSE , 1);
	curl_setopt($ch, CURLOPT_STDERR,$verbose);
	
	//ncr effect
    if(in_array('OPT_REDIRECT', $wp_rankie_options) || $wp_rankie_ezmlm_gl == 'com'){
    	//curl_setopt($ch,CURLOPT_COOKIE,'PREF=ID=64f763c8151956d0:U=0d5ba1d864ffeaec:FF=0:LD=en:CR=2:TM=1396795115:LM=1396798847:GBV=1:S=61zTw2b5wEFJtDWc');
		//curl_setopt($ch,CURLOPT_COOKIE,'PREF=ID=3ab979913006a56d:U=87f1e0e728e5a77f:FF=0:LD=en:CR=2:TM=1401195347:LM=1401195348:S=KwFUu0y5OlUOTdlp');
    
    }

    //curl get
    $x='error';
    $url='https://www.google.'.$wp_rankie_ezmlm_gl.'/search?q='.urlencode($keyword).'&btnG=Search&client=ubuntu&channel=fs&num=100';
 
    //if google .com ncr google first
    if($wp_rankie_ezmlm_gl == 'com'){
    	$url= $url . '&ie=utf-8&oe=utf-8&gfe_rd=cr&ei=sw9CVbCuPKaA8QfX0ICYBA&gws_rd=cr';
    }
    
    //specific country
    if($wp_rankie_google_county != 'N'){
    	$url.= '&tbs=ctr:country'.$wp_rankie_google_county.'&cr=country'.$wp_rankie_google_county;
    }
    
    if($debug) echo ' gurl:'.$url;
    
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    curl_setopt($ch, CURLOPT_URL, trim($url));
    
    //proxification 
    $proxified=in_array('OPT_PROXY', $wp_rankie_options);
    if(in_array('OPT_PROXY', $wp_rankie_options)){
    	 
    	if(trim($wp_rankie_proxies) !=''){
    		
    		//parsing proxies 
    		$proxies= array_filter (explode("\n", $wp_rankie_proxies));
    		
    		foreach($proxies as $proxy){
    			
    			if(trim($proxy) !='' && stristr($proxy, ':') ){
    				$validProxies[]=$proxy;
    			}
    			
    		}
    		
    		foreach($validProxies as $validProxy){
    			 
    			wp_rankie_log_new('Google directly proxy', 'trying to use the proxy '.$validProxy);
    			
    			$proxy=$validProxy;
    			curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 0);
    			
    			$proxy_parts=explode(':', $proxy);
    			
    			if(count($proxy_parts) > 2){

    				//authentication
    				$loginpassw = $proxy_parts[2].':'.$proxy_parts[3];
    				curl_setopt($ch, CURLOPT_PROXY, trim($proxy_parts[0].':'.$proxy_parts[1]));
    				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $loginpassw);
    				 
    				
    			}else{
    				curl_setopt($ch, CURLOPT_PROXY, $proxy );
    			}
    			
    				
    			
    			$exec=curl_exec($ch);
    			$x=curl_error($ch);
    			
    			 
    			if(trim($exec) == ''){
    				$return['message']='Empty google page';
    				wp_rankie_rotate_proxyies($validProxies,$validProxy);
    				
    				wp_rankie_log_new('Google direct proxy', 'Empty reply with possible connection failure '.$x);
    				
    			}else{
    			
    				if(stristr($exec, 'protect our users') || stristr($exec, 'answer/86640') ){
    					
    					$return['message']='Google blocked the request ... ';
    					wp_rankie_rotate_proxyies($validProxies,$validProxy);
    					wp_rankie_log_new('google direct proxy', 'Google showed us a captcha. ');
    				}else{
    			
    					if(  stristr($exec, 'CaptchaRedirect')){
    						
    						wp_rankie_log_new('Google direct proxy', 'Google asking for filling a captcha ');
    			
    						$return['message']='Google asked captcha solving ... ';
    						wp_rankie_rotate_proxyies($validProxies,$validProxy);
    			
    					}else{
    						
    						//seems as valid proxy let's break here 
    						wp_rankie_log_new('Google direct proxy', 'Proxy seems to be working ');
    						break;
    						
    					}
    				}
    			}
    					
    			
    		}
    	}else{
    		wp_rankie_log_new('Google direct proxy', 'No proxies found');
    		$return['message']='No proxies found ... ';
    	}
    
    }else{

    	$exec=curl_exec($ch);
    	$x=curl_error($ch);
    	
    }
    
	//verify valid google search reply
	if(trim($exec) == ''){
		$return['message']='Empty google page';
		if( ! $proxified) wp_rankie_log_new('google direct', 'Empty reply from google '.$x);
	}else{
		
		if(stristr($exec, 'protect our users') || stristr($exec, 'answer/86640') ){
			
			$return['message']='Google blocked the request ... ';
			if( ! $proxified) wp_rankie_log_new('google direct', 'Google showed us a captcha You may need to use proxies if you want to use Google directly method. ');
			
		}else{
		
			if(  stristr($exec, 'CaptchaRedirect')){
				
				$return['message']='Google asked captcha solving ... ';
				if( ! $proxified) wp_rankie_log_new('google direct', 'Google asked for solving a captcha for getting the results ');
				
			}else{
			
				//good we have a reply 
				if(stristr($exec, 'did not match any documents')){
					//no results for that term 
					$return['message'] = 'Term has no results at all';
					wp_rankie_log_new('google direct', 'Search term has no results at all ');
					wp_rankie_update_rank($id,0,'');
					$return['status']='success';
				}else{
					//goo d news term may have results let's get links 
					// <li class="g"><h3 class="r"><a href="
		
					//<li class\="g">
					preg_match_all('/<h3 class\="r"><a href\="(.*?)"/', $exec,$matches);
					 
					$foundLinks=$matches[1];

					// print
					if($debug) print_r($foundLinks);
					
					if(count($foundLinks) == 0){
						//no results apear here 
						wp_rankie_update_rank($id,0,'');
						$return['status']='success';
						$return['message']='No links found in the returned google page';
						wp_rankie_log_new('google direct', 'No links found ');
						
					 
					}else{
						//good news we have links 
						$i=0;
						foreach($foundLinks as $foundLink ){
							
							
							$i++;
							
							
	                        if(stristr($foundLink, '/url?')){
								
								$foundUrl_arr = explode('=', $foundLink);
								
								$foundUrl=$foundUrl_arr[1];
								
								$foundUrl_arr2=explode('&amp;', $foundUrl);
								
								$finalUrl = $foundUrl_arr2[0];
	                        }else{
	                        	$finalUrl = $foundLink;
	                        }
							
							//echo $finalUrl . "\n";
							
							//compare final url host with site host 
							if(stristr($finalUrl, $keyword_site)){
								//verify 
								
								$parsedUrl=parse_url($finalUrl);
								$parsedHost = $parsedUrl['host'];
								
								$parsedHost=preg_replace('/^www\./', '', $parsedHost);
								
								if(trim($parsedHost) == trim($keyword_site)){
									
									// Now same domain check if exact match
									if( trim( $keyword_site_full ) != '' ){
										
										// exact match let's match
										
										//clean original url
										$cleanOriginalUrl =  preg_replace('{http[s]?://(www\.)?}', '', $keyword_site_full);
										$cleanOriginalUrl =  preg_replace('{/$}', '', $cleanOriginalUrl);
										
										//clean final url
										$cleanFinalUrl =  preg_replace('{http[s]?://(www\.)?}', '', $finalUrl);
										$cleanFinalUrl =  preg_replace('{/$}', '', $cleanFinalUrl);
										
										if($cleanFinalUrl != $cleanOriginalUrl) continue;
										
									} 
									
									$return['rank']=$i;
									$return['link']=$finalUrl;
									$return['status']='success';
									
									wp_rankie_update_rank($id,$i,$finalUrl);
									
									return $return;
									
									
								}
								
								
							}
							
						}
						
						//oh loop ended without a match let's update it to 0 
						wp_rankie_update_rank($id,0,'' , count($foundLinks));
						$return['status']='success';
						
					}//links found
				
				}//no match
				
		
			}//captcha
		
		}//protect our users
	
	}//trim content 
	
	return $return;
	
}

/**
 * Fetch RANK from google directly via zalmos
 */

function wp_rankie_fetch_rank_zalmos($id) {
	
	
	// INI
	global $wpdb;
	$wp_rankie_ezmlm_gl = get_option ( 'wp_rankie_ezmlm_gl', 'com' );
	$wp_rankie_options = get_option ( 'wp_rankie_options', array () );
	$wp_rankie_zalmos_debug = false	;

	$return ['id'] = $id;
	$return ['status'] = 'error';
	$return ['rank'] = 0;
	$reutrn ['message'] = '';
	$reutrn ['link'] = '';

	// GET RECORD
	$query = "SELECT * FROM wp_rankie_keywords where keyword_id=$id";
	$rows = $wpdb->get_results ( $query );
	$row = $rows [0];
	$keyword = $row->keyword;
	$keyword_site = $row->keyword_site;

	// SERP CALL

	// curl ini
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 30 );
	curl_setopt ( $ch, CURLOPT_REFERER, 'http://www.zalmos.com/' );
	curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:28.0) Gecko/20100101 Firefox/28.0' );
	curl_setopt ( $ch, CURLOPT_MAXREDIRS, 5 ); // Good leeway for redirections.
	@curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 0 ); // Many login forms redirect at least once.
	@curl_setopt ( $ch, CURLOPT_COOKIEJAR, "cookie.txt" );
	curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );

	// verbose
	$verbose = fopen ( dirname ( __FILE__ ) . '/verbose.txt', 'w' );
	curl_setopt ( $ch, CURLOPT_VERBOSE, 1 );
	curl_setopt ( $ch, CURLOPT_STDERR, $verbose );
 
	// curl get
	$x = 'error';
	$gurl = 'https://www.google.' . $wp_rankie_ezmlm_gl . '/search?q=' . urlencode ( $keyword ) . '&btnG=Search&client=ubuntu&channel=fs&num=100';
 
	if($wp_rankie_zalmos_debug) print_r($gurl);
	
	//if google .com ncr google first
	if($wp_rankie_ezmlm_gl == 'com'){
 		$gurl= $gurl . '&ie=utf-8&oe=utf-8&gfe_rd=cr&ei=sw9CVbCuPKaA8QfX0ICYBA&gws_rd=cr';		
	}
 
	
	
	//curl post
	$curlurl="https://proxy.zalmos.com/includes/process.php?action=update";
	$curlpost="u=".urlencode($gurl);
	curl_setopt($ch, CURLOPT_URL, $curlurl);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpost);
	
	curl_setopt($ch, CURLOPT_HEADER, 1);
	
	$x='error';
	$exec=curl_exec($ch);
	$x=curl_error($ch);
	$cuinfo = curl_getinfo($ch);
	
	curl_setopt($ch, CURLOPT_HEADER, 0);
	
	
	
	//get redirect url Location: https://proxy in rare cases there is no redirect_url 
	if( $cuinfo['http_code'] == 302 && ! isset($cuinfo['redirect_url']) ){
		
		if(stristr($exec, 'Location:')){
			 
							
			preg_match( '{Location:(.*)}' , $exec, $loc_matches);
			$redirect_url = trim($loc_matches[1]);
			
			if(trim($redirect_url) !=  ''){
				$cuinfo['redirect_url'] = $redirect_url;
			}
				
			
		}
		 
	}
	
	
	 
	if( $cuinfo['http_code'] == 302 && trim($cuinfo['redirect_url'] != '') ){
		
		//valid let's get the real page 
	
		//curl get
		$x='error';
		$url=$cuinfo['redirect_url'];
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_URL, trim($url));
		 
		$exec = wp_rankie_curl_exec_follow($ch);
		$x=curl_error($ch);
		
		
		if($wp_rankie_zalmos_debug) print_r($exec);
	   
		 
		
 		//verify valid google page 
 		if(stristr($exec,  ($keyword) )){
 			
 			//valid google page get found links 
 			preg_match_all('{<cite class="_Rm.*?">(.*?)</cite>}', $exec,$matches);
 			$foundLinks = $matches[1];
 			
 			if($wp_rankie_zalmos_debug) print_r($foundLinks);
 			
 			if (count ( $foundLinks ) == 0) {
 				// no results apear here
 				wp_rankie_update_rank ( $id, 0, '' );
 				$return ['status'] = 'success';
 				$return ['message'] = 'No links found in the returned google page';
 				wp_rankie_log_new ( 'google via zalmos', 'No links found ' );
 				
 				 
 			} else {
 				// good news we have links
 				$i = 0;
 				
 			 	
 				foreach ( $foundLinks as $foundLink ) {
 			
 					 
 					$i ++;
 			
 					 //clear html
 					 $finalUrl = str_replace(array('<b>','</b>'), '', $foundLink);
 					 
 					 //remove span tags for rtl googles 
 					 $finalUrl =preg_replace('{<.*?>}', '', $finalUrl);
 			
 					 
 					 if( !stristr($finalUrl, 'http://') && ! stristr($finalUrl, 'https://') ) $finalUrl = 'http://'.$finalUrl;
 					 
 					 if($wp_rankie_zalmos_debug) echo $finalUrl."\n";
 				 
 					 
 					// compare final url host with site host
 					if (stristr ( $finalUrl, $keyword_site )) {
 						
 						 
 						if(stristr($finalUrl ,' &rsaquo')){
 							$tempArr = explode(' &rsaquo', $finalUrl);
 							$finalUrl = $tempArr[0];
 						}
 						
 						// verify
 						
 						$parsedUrl = parse_url ( $finalUrl );
 						$parsedHost = $parsedUrl ['host'];
 			
 						$parsedHost = preg_replace ( '/^www\./', '', $parsedHost );
 			
 						
 						
 						if (trim ( $parsedHost ) == trim ( $keyword_site )) {
 			
 							$return ['rank'] = $i;
 							$return ['link'] = $finalUrl;
 							$return ['status'] = 'success';
 			
 							wp_rankie_update_rank ( $id, $i, $finalUrl );
 			
 							return $return;
 						}
 					}
 				}
 			
 				// oh loop ended without a match let's update it to 0
 				wp_rankie_update_rank ( $id, 0, '' );
 				$return ['status'] = 'success';
 			} // links found
 			
 			
 			
 		}else{
 			$return['message'] = 'Zalmos can not verify valid returned google page '.$x;
 			
 			if(stristr($exec, '503 Service Unavailable')){
 				wp_rankie_log_new('Zalmos unavailable','Zalmos currently not available, we will try again.');
 			}
 			 
 		}
		 
		 
		
	}else{
		
		//not valid 
		$return['message'] = 'Zalmos first page redirct did not redirect as expected';
		
	}
	
	

	return $return;
}

/**
 * Fetch RANK from google directly via KProxy
 */

function wp_rankie_fetch_rank_kproxy($id) {


	// INI
	global $wpdb;
	$wp_rankie_ezmlm_gl = get_option ( 'wp_rankie_ezmlm_gl', 'com' );
	$wp_rankie_options = get_option ( 'wp_rankie_options', array () );
	$wp_rankie_zalmos_debug = false		;

	$return ['id'] = $id;
	$return ['status'] = 'error';
	$return ['rank'] = 0;
	$reutrn ['message'] = '';
	$reutrn ['link'] = '';

	// GET RECORD
	$query = "SELECT * FROM wp_rankie_keywords where keyword_id=$id";
	$rows = $wpdb->get_results ( $query );
	$row = $rows [0];
	$keyword = $row->keyword;
	$keyword_site = $row->keyword_site;
	
	$keyword_site_full = ''; //full url if the keyword site is an exact url
	
	if( stristr($keyword_site, 'http:') || stristr($keyword_site, 'https:') || stristr($keyword_site, 'www.') ){
	
		$keyword_site_full = $keyword_site;
	
		//getting the domain
		$host = parse_url($keyword_site ,  PHP_URL_HOST);
		$host = preg_replace('{^www\.}', '',$host);
		$keyword_site = $host;
	
	}
	

	// SERP CALL

	// curl ini
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 30 );
	curl_setopt ( $ch, CURLOPT_REFERER, 'http://www.kproxy.com/' );
	curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:28.0) Gecko/20100101 Firefox/28.0' );
	curl_setopt ( $ch, CURLOPT_MAXREDIRS, 5 ); // Good leeway for redirections.
	@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 ); // Many login forms redirect at least once.
	@curl_setopt( $ch, CURLOPT_COOKIEJAR, "cookie.txt" );
	curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );

	// verbose
	$verbose = fopen ( dirname ( __FILE__ ) . '/verbose.txt', 'w' );
	curl_setopt ( $ch, CURLOPT_VERBOSE, 1 );
	curl_setopt ( $ch, CURLOPT_STDERR, $verbose );

	// curl get
	$x = 'error';
	$gurl = 'https://www.google.' . $wp_rankie_ezmlm_gl . '/search?q=' . urlencode ( $keyword ) . '&btnG=Search&client=ubuntu&channel=fs&num=100';
	
	$kproxyGoogle = wp_rankie_kproxy_url($wp_rankie_ezmlm_gl);
	
	$return['kurl'] = $kproxyGoogle;
	
	$gurl = $kproxyGoogle. 'search?q=' . urlencode ( $keyword ) . '&num=100';

	curl_setopt ( $ch, CURLOPT_HTTPHEADER,array( 'Referer: '.$kproxyGoogle ,  'Cookie: KP_DAT2__="Ym8hLXUtLiMjI05CVmdxekZINmtO'.wp_rankie_random_letter().'zlbNnVRbGI1UUFvNSMjI0'.wp_rankie_random_letter().'fbn0odHN'.wp_rankie_random_letter().'QWRqVG9OXVT="' ));
	
	
	if($wp_rankie_zalmos_debug) print_r($gurl);

	//if google .com ncr google first
	if($wp_rankie_ezmlm_gl == 'com'){
		//$gurl= $gurl . '&ie=utf-8&oe=utf-8&gfe_rd=cr&ei=sw9CVbCuPKaA8QfX0ICYBA&gws_rd=cr';
	}

	//curl get
 	curl_setopt($ch, CURLOPT_URL, $gurl);
	curl_setopt($ch, CURLOPT_HTTPGET, true);
 
	curl_setopt($ch, CURLOPT_HEADER, 1);
	
	$x='error';
	$exec=curl_exec($ch);
	$x=curl_error($ch);
	$cuinfo = curl_getinfo($ch);
	
	curl_setopt($ch, CURLOPT_HEADER, 0);
	
	 	 
	//get redirect url Location: https://proxy in rare cases there is no redirect_url
	if( $cuinfo['http_code'] == 302 && ! isset($cuinfo['redirect_url']) ){

		if(stristr($exec, 'Location:')){

				
			preg_match( '{Location:(.*)}' , $exec, $loc_matches);
			$redirect_url = trim($loc_matches[1]);
				
			if(trim($redirect_url) !=  ''){
				$cuinfo['redirect_url'] = $redirect_url;
			}

				
		}
			
	}



	if( $cuinfo['http_code'] == 302 && trim($cuinfo['redirect_url'] != '') ){

		//valid let's get the real page

		//curl get
		$x='error';
		$url=$cuinfo['redirect_url'];
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_URL, trim($url));
			
		$exec = wp_rankie_curl_exec_follow($ch);
		$x=curl_error($ch);


		if($wp_rankie_zalmos_debug) print_r($exec);

	 } 
		

		//verify valid google page
		if(stristr($exec,  ($keyword) )){

			//valid google page get found links
			preg_match_all('{<cite class="_Rm.*?">(.*?)</cite>}', $exec,$matches);
			$foundLinks = $matches[1];
 
			if($wp_rankie_zalmos_debug) print_r($foundLinks);
			
			if(stristr($exec, 'CaptchaRedirect')){
				
				$return ['status'] = 'error';
				$return ['message'] = 'Kproxy showed a captcha,skip this time';
				wp_rankie_log_new ( 'google via Kproxy', 'Kproxy showed a captcha,skip this time ' );
				
			}elseif (count ( $foundLinks ) == 0) {
				// no results apear here
				wp_rankie_update_rank ( $id, 0, '',0 );
				$return ['status'] = 'success';
				$return ['message'] = 'No links found in the returned google page';
				wp_rankie_log_new ( 'google via Kproxy', 'No links found ' );
					

			} else {
				// good news we have links
				$i = 0;
					

				foreach ( $foundLinks as $foundLink ) {

						
					$i ++;

					//clear html
					$finalUrl = str_replace(array('<b>','</b>'), '', $foundLink);
						
					//remove span tags for rtl googles
					$finalUrl =preg_replace('{<.*?>}', '', $finalUrl);

						
					if( !stristr($finalUrl, 'http://') && ! stristr($finalUrl, 'https://') ) $finalUrl = 'http://'.$finalUrl;
						
					if($wp_rankie_zalmos_debug) echo $finalUrl."\n";

						
					// compare final url host with site host
					if (stristr ( $finalUrl, $keyword_site )) {
							

						if(stristr($finalUrl ,' &rsaquo')){
							$tempArr = explode(' &rsaquo', $finalUrl);
							$finalUrl = $tempArr[0];
						}
							
						// verify
							
						$parsedUrl = parse_url ( $finalUrl );
						$parsedHost = $parsedUrl ['host'];

						$parsedHost = preg_replace ( '/^www\./', '', $parsedHost );

							
							
						if (trim ( $parsedHost ) == trim ( $keyword_site )) {

							// Now same domain check if exact match
							if( trim( $keyword_site_full ) != '' ){
									
								// exact match let's match
									
								//clean original url
								$cleanOriginalUrl =  preg_replace('{http[s]?://(www\.)?}', '', $keyword_site_full);
								$cleanOriginalUrl =  preg_replace('{/$}', '', $cleanOriginalUrl);
									
								//clean final url
								$cleanFinalUrl =  preg_replace('{http[s]?://(www\.)?}', '', $finalUrl);
								$cleanFinalUrl =  preg_replace('{/$}', '', $cleanFinalUrl);
									
								if($cleanFinalUrl != $cleanOriginalUrl) continue;
									
							}
							
							$return ['rank'] = $i;
							$return ['link'] = $finalUrl;
							$return ['status'] = 'success';

							wp_rankie_update_rank ( $id, $i, $finalUrl );

							return $return;
						}
					}
				}

				// oh loop ended without a match let's update it to 0
				wp_rankie_update_rank ( $id, 0, '' ,count($foundLinks));
				$return ['status'] = 'success';
			} // links found



		}else{
			
			
			if(stristr($exec, 'CaptchaRedirect')){
			
				$return ['status'] = 'error';
				$return ['message'] = 'Kproxy showed a captcha,skip this time';
				wp_rankie_log_new ( 'google via Kproxy', 'Kproxy showed a captcha,skip this time ' );
			}else{
				$return['message'] = 'kProxy can not verify valid returned google page '.$x;
				
				if(stristr($exec, '503 Service Unavailable')){
					wp_rankie_log_new('KProxy unavailable','KProxy currently not available, we will try again.');
				}	
			}
			
			
			
				
		}
			
			



	return $return;
}

/**
 * function wp_rankie_kproxy_url: get kproxy url for a specific google
 * @param unknown $googleDomainExt
 * @return string
 */
function wp_rankie_kproxy_url($googleDomainExt){

	// Domains
	$domains="com,ad,ae,com.af,com.ag,com.ai,am,co.ao,com.ar,as,at,com.au,az,ba,com.bd,be,bf,bg,com.bh,bi,bj,com.bn,com.bo,com.br,bs,co.bw,by,com.bz,ca,cd,cf,cg,ch,ci,co.ck,cl,cm,cn,com.co,co.cr,com.cu,cv,cz,de,dj,dk,dm,com.do,dz,com.ec,ee,com.eg,es,com.et,fi,com.fj,fm,fr,ga,ge,gg,com.gh,com.gi,gl,gm,gp,gr,com.gt,gy,com.hk,hn,hr,ht,hu,co.id,ie,co.il,im,co.in,iq,is,it,je,com.jm,jo,co.jp,co.ke,com.kh,ki,kg,co.kr,com.kw,kz,la,com.lb,li,lk,co.ls,lt,lu,lv,com.ly,co.ma,md,me,mg,mk,ml,mn,ms,com.mt,mu,mv,mw,com.mx,com.my,co.mz,com.na,com.nf,com.ng,com.ni,ne,nl,no,com.np,nr,nu,co.nz,com.om,com.pa,com.pe,com.ph,com.pk,pl,pn,com.pr,ps,pt,com.py,com.qa,ro,ru,rw,com.sa,com.sb,sc,se,com.sg,sh,si,sk,com.sl,sn,so,sm,st,com.sv,td,tg,co.th,com.tj,tk,tl,tm,tn,to,com.tr,tt,com.tw,co.tz,com.ua,co.ug,co.uk,com.uy,co.uz,com.vc,co.ve,vg,co.vi,com.vn,vu,ws,rs,co.za,co.zm,co.zw,cat,com.cy";
	$domainsArr = explode(',', $domains);


	// Kproxy Keys
	$kproxyKeys = "sruj/snvrabu/smno/p2/,sig/smuqzat/slmn/p2/,sjg/smuqzat/slmn/p2/,skg/suxm/sqyudex/spqr/p2/,slg/suxm/sqyudex/spqr/p2/,sng/suxm/sqyudex/spqr/p2/,srg/smuqzat/slmn/p2/,stg/swl/spxtcdw/sopq/p2/,swg/suxm/sqyudex/spqr/p2/,sxg/smuqzat/slmn/p2/,syg/smuqzat/slmn/p2/,szg/suxm/sqyudex/spqr/p2/,seg/smuqzat/slmn/p2/,sfh/smuqzat/slmn/p2/,sih/suxm/sqyudex/spqr/p2/,sjh/smuqzat/slmn/p2/,skh/smuqzat/slmn/p2/,slh/smuqzat/slmn/p2/,smh/suxm/sqyudex/spqr/p2/,snh/smuqzat/slmn/p2/,soh/smuqzat/slmn/p2/,ssh/suxm/sqyudex/spqr/p2/,sth/suxm/sqyudex/spqr/p2/,swh/suxm/sqyudex/spqr/p2/,sxh/smuqzat/slmn/p2/,sbh/swl/spxtcdw/sopq/p2/,sdh/smuqzat/slmn/p2/,seh/suxm/sqyudex/spqr/p2/,sfi/smuqzat/slmn/p2/,sii/smuqzat/slmn/p2/,ski/smuqzat/slmn/p2/,sli/smuqzat/slmn/p2/,smi/smuqzat/slmn/p2/,sni/smuqzat/slmn/p2/,spi/swl/spxtcdw/sopq/p2/,sqi/smuqzat/slmn/p2/,sri/smuqzat/slmn/p2/,ssi/smuqzat/slmn/p2/,sti/suxm/sqyudex/spqr/p2/,swi/swl/spxtcdw/sopq/p2/,szi/suxm/sqyudex/spqr/p2/,sai/smuqzat/slmn/p2/,sei/smuqzat/slmn/p2/,sjj/smuqzat/slmn/p2/,soj/smuqzat/slmn/p2/,spj/smuqzat/slmn/p2/,srj/smuqzat/slmn/p2/,stj/suxm/sqyudex/spqr/p2/,sej/smuqzat/slmn/p2/,shk/suxm/sqyudex/spqr/p2/,sjk/smuqzat/slmn/p2/,slk/suxm/sqyudex/spqr/p2/,sxk/smuqzat/slmn/p2/,syk/suxm/sqyudex/spqr/p2/,snl/smuqzat/slmn/p2/,sol/suxm/sqyudex/spqr/p2/,srl/smuqzat/slmn/p2/,swl/smuqzat/slmn/p2/,sfm/smuqzat/slmn/p2/,sjm/smuqzat/slmn/p2/,slm/smuqzat/slmn/p2/,smm/suxm/sqyudex/spqr/p2/,snm/suxm/sqyudex/spqr/p2/,sqm/smuqzat/slmn/p2/,srm/smuqzat/slmn/p2/,sum/smuqzat/slmn/p2/,swm/smuqzat/slmn/p2/,sym/suxm/sqyudex/spqr/p2/,sdm/smuqzat/slmn/p2/,spn/suxm/sqyudex/spqr/p2/,ssn/smuqzat/slmn/p2/,swn/smuqzat/slmn/p2/,syn/smuqzat/slmn/p2/,szn/smuqzat/slmn/p2/,sio/swl/spxtcdw/sopq/p2/,sjo/smuqzat/slmn/p2/,sqo/swl/spxtcdw/sopq/p2/,sro/smuqzat/slmn/p2/,sso/swl/spxtcdw/sopq/p2/,svo/smuqzat/slmn/p2/,sxo/smuqzat/slmn/p2/,syo/smuqzat/slmn/p2/,sjp/smuqzat/slmn/p2/,srp/suxm/sqyudex/spqr/p2/,stp/smuqzat/slmn/p2/,sup/swl/spxtcdw/sopq/p2/,sjq/swl/spxtcdw/sopq/p2/,smq/suxm/sqyudex/spqr/p2/,snq/smuqzat/slmn/p2/,slq/smuqzat/slmn/p2/,swq/swl/spxtcdw/sopq/p2/,sbq/suxm/sqyudex/spqr/p2/,seq/smuqzat/slmn/p2/,sfr/smuqzat/slmn/p2/,sgr/suxm/sqyudex/spqr/p2/,snr/smuqzat/slmn/p2/,spr/smuqzat/slmn/p2/,sxr/swl/spxtcdw/sopq/p2/,syr/smuqzat/slmn/p2/,szr/smuqzat/slmn/p2/,sar/smuqzat/slmn/p2/,sdr/suxm/sqyudex/spqr/p2/,sfs/swl/spxtcdw/sopq/p2/,sis/smuqzat/slmn/p2/,sjs/smuqzat/slmn/p2/,sls/smuqzat/slmn/p2/,sps/smuqzat/slmn/p2/,sqs/smuqzat/slmn/p2/,sss/smuqzat/slmn/p2/,sxs/smuqzat/slmn/p2/,sys/suxm/sqyudex/spqr/p2/,szs/smuqzat/slmn/p2/,sas/smuqzat/slmn/p2/,sbs/smuqzat/slmn/p2/,scs/suxm/sqyudex/spqr/p2/,sds/suxm/sqyudex/spqr/p2/,ses/swl/spxtcdw/sopq/p2/,sft/suxm/sqyudex/spqr/p2/,skt/suxm/sqyudex/spqr/p2/,slt/suxm/sqyudex/spqr/p2/,snt/suxm/sqyudex/spqr/p2/,sjt/smuqzat/slmn/p2/,sqt/smuqzat/slmn/p2/,stt/smuqzat/slmn/p2/,sut/suxm/sqyudex/spqr/p2/,swt/smuqzat/slmn/p2/,szt/smuqzat/slmn/p2/,set/swl/spxtcdw/sopq/p2/,sru/suxm/sqyudex/spqr/p2/,sfv/suxm/sqyudex/spqr/p2/,sjv/suxm/sqyudex/spqr/p2/,smv/suxm/sqyudex/spqr/p2/,spv/suxm/sqyudex/spqr/p2/,sqv/smuqzat/slmn/p2/,ssv/smuqzat/slmn/p2/,swv/suxm/sqyudex/spqr/p2/,sxv/smuqzat/slmn/p2/,syv/smuqzat/slmn/p2/,sdv/suxm/sqyudex/spqr/p2/,sfw/suxm/sqyudex/spqr/p2/,stx/smuqzat/slmn/p2/,szx/smuqzat/slmn/p2/,sbx/smuqzat/slmn/p2/,sfy/suxm/sqyudex/spqr/p2/,sgy/suxm/sqyudex/spqr/p2/,shy/smuqzat/slmn/p2/,sjy/smuqzat/slmn/p2/,sly/suxm/sqyudex/spqr/p2/,smy/smuqzat/slmn/p2/,sny/smuqzat/slmn/p2/,spy/smuqzat/slmn/p2/,sqy/suxm/sqyudex/spqr/p2/,ssy/smuqzat/slmn/p2/,sty/smuqzat/slmn/p2/,sry/smuqzat/slmn/p2/,syy/smuqzat/slmn/p2/,say/suxm/sqyudex/spqr/p2/,siz/smuqzat/slmn/p2/,slz/smuqzat/slmn/p2/,smz/swl/spxtcdw/sopq/p2/,soz/suxm/sqyudex/spqr/p2/,spz/smuqzat/slmn/p2/,sqz/smuqzat/slmn/p2/,srz/smuqzat/slmn/p2/,ssz/smuqzat/slmn/p2/,stz/smuqzat/slmn/p2/,swz/suxm/sqyudex/spqr/p2/,syz/smuqzat/slmn/p2/,sbz/suxm/sqyudex/spqr/p2/,sez/swl/spxtcdw/sopq/p2/,sfa/suxm/sqyudex/spqr/p2/,sla/swl/spxtcdw/sopq/p2/,spa/swl/spxtcdw/sopq/p2/,sda/suxm/sqyudex/spqr/p2/,sea/swl/spxtcdw/sopq/p2/,shb/suxm/sqyudex/spqr/p2/,sjb/swl/spxtcdw/sopq/p2/,slb/smuqzat/slmn/p2/,snb/swl/spxtcdw/sopq/p2/,ssb/suxm/sqyudex/spqr/p2/,szb/smuqzat/slmn/p2/,sxc/smuqzat/slmn/p2/,sxx/smuqzat/slmn/p2/,sff/swl/spxtcdw/sopq/p2/,srf/swl/spxtcdw/sopq/p2/,sbf/swl/spxtcdw/sopq/p2/,sygj/snvrabu/smno/p2/,sdi/suxm/sqyudex/spqr/p2/" ;
	$kproxyKeysArr = explode(',', $kproxyKeys);

	// Find key
	for($i = 0 ; $i < count($domainsArr) ; $i++ ){


		if( $domainsArr[$i] == $googleDomainExt ){
			
			$serverNum = rand(1,10);
			
			return 'http://server'.$serverNum.'.kproxy.com/servlet/redirect.srv/'. $kproxyKeysArr[$i];
				
		}

	}

}

/**
 * function wp_rankie_fetch_rank_disconnect
 * @param unknown $id
 * @return Ambigous <string, unknown>
 */
function wp_rankie_fetch_rank_disconnect($id) {

	// INI
	global $wpdb;
	$wp_rankie_disconnect_c = get_option('wp_rankie_disconnect_c','US');
	$wp_rankie_options = get_option ( 'wp_rankie_options', array () );
	$wp_rankie_zalmos_debug = true	;

	$return ['id'] = $id;
	$return ['status'] = 'error';
	$return ['rank'] = 0;
	$reutrn ['message'] = '';
	$reutrn ['link'] = '';

	// GET RECORD
	$query = "SELECT * FROM wp_rankie_keywords where keyword_id=$id";
	$rows = $wpdb->get_results ( $query );
	$row = $rows [0];
	$keyword = $row->keyword;
	$keyword_site = $row->keyword_site;

	// SERP CALL

	// curl ini
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 30 );
	curl_setopt ( $ch, CURLOPT_REFERER, 'https://search.disconnect.me' );
	curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:28.0) Gecko/20100101 Firefox/28.0' );
	curl_setopt ( $ch, CURLOPT_MAXREDIRS, 5 ); // Good leeway for redirections.
	@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 ); // Many login forms redirect at least once.
	@curl_setopt( $ch, CURLOPT_COOKIEJAR, "cookie.txt" );
	curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
 
	// curl get
	$x = 'error';
	$gurl = 'https://www.google.' . $wp_rankie_ezmlm_gl . '/search?q=' . urlencode ( $keyword ) . '&btnG=Search&client=ubuntu&channel=fs&num=100';
	$gurl = 'https://search.disconnect.me/searchTerms/search?start=nav&option=Web&query='.urlencode($keyword).'&ses=Google&location_option='.$wp_rankie_disconnect_c.'&nextDDG=%2Fsearch%3Fq%3D%26num%3D100%26sa%3DN&showIcons=false&filterIcons=none&js_enabled=1&source=None';
	 
 
	if($wp_rankie_zalmos_debug) print_r($gurl);

	//curl get
	curl_setopt($ch, CURLOPT_URL, $gurl);
	curl_setopt($ch, CURLOPT_HTTPGET, true);
	curl_setopt($ch, CURLOPT_HEADER, 1);

	$x='error';
	$exec=curl_exec($ch);
	$x=curl_error($ch);
	$cuinfo = curl_getinfo($ch);
	
	curl_setopt($ch, CURLOPT_HEADER, 0);

	//get redirect url Location: https://proxy in rare cases there is no redirect_url
	if( $cuinfo['http_code'] == 302 && ! isset($cuinfo['redirect_url']) ){

		if(stristr($exec, 'Location:')){

			preg_match( '{Location:(.*)}' , $exec, $loc_matches);
			$redirect_url = trim($loc_matches[1]);

			if(trim($redirect_url) !=  ''){
				$cuinfo['redirect_url'] = $redirect_url;
			}

		}
			
	}

	if( $cuinfo['http_code'] == 302 && trim($cuinfo['redirect_url'] != '') ){

		//valid let's get the real page

		//curl get
		$x='error';
		$url=$cuinfo['redirect_url'];
		curl_setopt($ch, CURLOPT_HTTPGET, 1);
		curl_setopt($ch, CURLOPT_URL, trim($url));
			
		$exec = wp_rankie_curl_exec_follow($ch);
		$x=curl_error($ch);


		if($wp_rankie_zalmos_debug) print_r($exec);

	}

	 
	 
	//verify valid google page no-results
	if(stristr($exec,   'cookie_compliance'  )){

		//valid google page get found links
		preg_match_all('{<a href="(.*?)" class="title">}', $exec,$matches);
		$foundLinks = $matches[1];
		 
		if($wp_rankie_zalmos_debug) print_r($foundLinks);

		if (count ( $foundLinks ) == 0) {
			// no results apear here
			wp_rankie_update_rank ( $id, 0, '' );
			$return ['status'] = 'success';
			$return ['message'] = 'No links found in the returned google page';
			wp_rankie_log_new ( 'google via Disconnect', 'No links found ' );
				

		} else {
			// good news we have links
			$i = 0;
				

			foreach ( $foundLinks as $foundLink ) {

				//skip ads
				if(stristr($foundLink , '/ads/index')){
					continue;
				}
				
				$i ++;

				$finalUrl = $foundLink;

				if( !stristr($finalUrl, 'http://') && ! stristr($finalUrl, 'https://') ) $finalUrl = 'http://'.$finalUrl;

				if($wp_rankie_zalmos_debug) echo $finalUrl." $i \n";


				// compare final url host with site host
				if (stristr ( $finalUrl, $keyword_site )) {
						

					if(stristr($finalUrl ,' &rsaquo')){
						$tempArr = explode(' &rsaquo', $finalUrl);
						$finalUrl = $tempArr[0];
					}
						
					// verify
					$parsedUrl = parse_url ( $finalUrl );
					$parsedHost = $parsedUrl ['host'];

					$parsedHost = preg_replace ( '/^www\./', '', $parsedHost );

					if (trim ( $parsedHost ) == trim ( $keyword_site )) {

						$return ['rank'] = $i;
						$return ['link'] = $finalUrl;
						$return ['status'] = 'success';

						wp_rankie_update_rank ( $id, $i, $finalUrl );

						return $return;
					}
				}
			}

			// oh loop ended without a match let's update it to 0
			wp_rankie_update_rank ( $id, 0, '' );
			$return ['status'] = 'success';
		} // links found



	}else{
		$return['message'] = 'Disconnect can not verify valid returned google page '.$x;

		if(stristr($exec, '503 Service Unavailable')){
			wp_rankie_log_new('Disconnect unavailable','KProxy currently not available, we will try again.');
		}

	}
		
		



	return $return;
}

/**
 * using search.f-secure.com
 * @param unknown $id
 */
function wp_rankie_fetch_rank_fsecure($id){
	
	// INI
	global $wpdb;
	global $currentPage;
	
	$wp_rankie_options = get_option ( 'wp_rankie_options', array () );
	$wp_rankie_zalmos_debug = false	;
	
	$return ['id'] = $id;
	$return ['status'] = 'error';
	$return ['rank'] = 0;
	$reutrn ['message'] = '';
	$reutrn ['link'] = '';
	
	// GET RECORD
	$query = "SELECT * FROM wp_rankie_keywords where keyword_id=$id";
	$rows = $wpdb->get_results ( $query );
	$row = $rows [0];
	$keyword             =  $row->keyword;
	$keyword_site        =  $row->keyword_site;
	@$last_checked_page  =  $row->last_checked_page;
	$keyword_rank        =  $row->keyword_rank;
	
	$keyword_site_full = ''; //full url if the keyword site is an exact url
	
	if( stristr($keyword_site, 'http:') || stristr($keyword_site, 'https:') || stristr($keyword_site, 'www.') ){
	
		$keyword_site_full = $keyword_site;
	
		//getting the domain
		$host = parse_url($keyword_site ,  PHP_URL_HOST);
		$host = preg_replace('{^www\.}', '',$host);
		$keyword_site = $host;
	
	}
	
	
	// SERP CALL
	
	// curl ini
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 30 );
	curl_setopt ( $ch, CURLOPT_REFERER, 'https://search.f-secure.com/search' );
	curl_setopt ( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:28.0) Gecko/20100101 Firefox/28.0' );
	curl_setopt ( $ch, CURLOPT_MAXREDIRS, 5 ); // Good leeway for redirections.
	@curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 ); // Many login forms redirect at least once.
	@curl_setopt( $ch, CURLOPT_COOKIEJAR, "cookie.txt" );
	curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
	
	// curl get
	$x = 'error';

	// get the startIndex from the last_checked_page
	$startIndex = 0;
	$currentPage= 1;	
	
	if(! in_array('OPT_PAGINATE', $wp_rankie_options)){
		
		// get currentPage to check
		if($keyword_rank == 0){
			
			$currentPage = $last_checked_page + 1 ;
			
		}else{
			
			$currentPage = $last_checked_page;
			
			if($currentPage == 0) $currentPage = 1 ;
			
		}
		
		// if passed limit reset
		if($currentPage > 10) $currentPage = 1;
		
		// startIndex from the current page
		$startIndex = ($currentPage -1) * 10;
		
	}
	
	$gurl = 'https://search.f-secure.com/search?query='.urlencode(trim($keyword)).'&start_index='.$startIndex;
	
	if($wp_rankie_zalmos_debug) print_r($gurl);
	
	//curl get
	curl_setopt($ch, CURLOPT_URL, $gurl);
	curl_setopt($ch, CURLOPT_HTTPGET, true);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	
	$x='error';
	$exec=curl_exec($ch);
	$x=curl_error($ch);
	$cuinfo = curl_getinfo($ch);
	
	curl_setopt($ch, CURLOPT_HEADER, 0);
	 
	//verify valid f-secure page no-results
	if(stristr($exec,   'searchForm'  )){
	
		//valid google page get found links
		preg_match_all('{" ></div><a href="(.*?)">.*?</a></h2>}s', $exec,$matches);
 
		$foundLinks = $matches[1];
			
		if($wp_rankie_zalmos_debug) print_r($foundLinks);
	
		if (count ( $foundLinks ) == 0) {
			
			// no results apear here
			wp_rankie_update_rank ( $id, 0, '' );
			$return ['status'] = 'success';
			$return ['message'] = 'No links found in the returned google page';
			wp_rankie_log_new ( 'google via F-Secure', 'No links found ' );
	
	
		}else{
			
			// good news we have links
			$i = 0;
	
			foreach ( $foundLinks as $foundLink ) {
	
				//skip ads
				if(stristr($foundLink , '/ads/index')){
					continue;
				}
	
				$i ++;
	
				$finalUrl = $foundLink;
	
				if( !stristr($finalUrl, 'http://') && ! stristr($finalUrl, 'https://') ) $finalUrl = 'http://'.$finalUrl;
	
				if($wp_rankie_zalmos_debug) echo $finalUrl." $i \n";
	
	
				// compare final url host with site host
				if (stristr ( $finalUrl, $keyword_site )) {
	
	
					if(stristr($finalUrl ,' &rsaquo')){
						$tempArr = explode(' &rsaquo', $finalUrl);
						$finalUrl = $tempArr[0];
					}
	
					// verify
					$parsedUrl = parse_url ( $finalUrl );
					$parsedHost = $parsedUrl ['host'];
	
					$parsedHost = preg_replace ( '/^www\./', '', $parsedHost );
	
					if (trim ( $parsedHost ) == trim ( $keyword_site )) {
						
						// fix rank according to the page
						if($currentPage > 1 ){
							$rank = $i + ($currentPage -1) * 10;
						}else{
							$rank = $i;
						}
						
						// Now same domain check if exact match
						if( trim( $keyword_site_full ) != '' ){
						
							// exact match let's match
						
							//clean original url
							$cleanOriginalUrl =  preg_replace('{http[s]?://(www\.)?}', '', $keyword_site_full);
							$cleanOriginalUrl =  preg_replace('{/$}', '', $cleanOriginalUrl);
						
							//clean final url
							$cleanFinalUrl =  preg_replace('{http[s]?://(www\.)?}', '', $finalUrl);
							$cleanFinalUrl =  preg_replace('{/$}', '', $cleanFinalUrl);
						
							if($cleanFinalUrl != $cleanOriginalUrl) continue;
						
						}
						
	
						$return ['rank'] = $rank;
						$return ['link'] = $finalUrl;
						$return ['status'] = 'success';
	
						wp_rankie_update_rank ( $id, $i, $finalUrl );
	
						return $return;
					}
				}
			}
	
			// oh loop ended without a match let's update it to 0
			wp_rankie_update_rank ( $id, 0, '',count($foundLinks) );
			$return ['status'] = 'success';
		} // links found
	
	
	
	}else{
		$return['message'] = 'F-Secure can not verify valid returned google page '.$x;
	
		if(stristr($exec, '503 Service Unavailable')){
			wp_rankie_log_new('F-Secure unavailable','F-Secure currently not available, we will try again.');
		}
	
	}
	
	
	
	
	
	return $return;
	
}

/**
 * Curl exec follow
 * @param unknown $ch
 * @return mixed
 */

function wp_rankie_curl_exec_follow( &$ch){

	$max_redir = 5;

	for ($i=0;$i<$max_redir;$i++){

		$exec=curl_exec($ch);
			
		$info = curl_getinfo($ch);

			
			
		if($info['http_code'] == 301 ||  $info['http_code'] == 302){
				
			curl_setopt($ch, CURLOPT_URL, trim($info['redirect_url']));
 
			
			$exec=curl_exec($ch);
				
		}else{
				
			//no redirect just return
			break;
				
		}


	}

	return $exec;

}

/**
 * Rotate Proxies
 */
function wp_rankie_rotate_proxyies($proxies,$currentProxy){
	
	 
	foreach ($proxies as $proxy){
		if(trim($proxy) !=trim($currentProxy)){
			$newProxies[]=$proxy;
		}
	}
	
	$newProxies[]=$currentProxy;
	
	update_option('wp_rankie_proxies', implode("\n", $newProxies) );
	
}

/**
 * Function : wp_rankie_update_rank
 * @param unknown $id
 * @param unknown $rank
 * @param unknown $link
 */
function wp_rankie_update_rank($id,$rank,$link,$linksCount = 0){

	//ini
	$now = time('now');
	global $wpdb;
	global $currentPage;
	
	//if currentPage exists update it
	if(is_numeric($currentPage)) {
		$currentPageUpdate = ',last_checked_page='.$currentPage;
		
		// fix rank according to the page
		if($currentPage > 1 && $rank !=0){
			$rank = $rank + ($currentPage -1) * 10;
		}
		
		$currentPageLogPart = ' at page '.$currentPage;
		
	}
	else{
		$currentPageUpdate = '';
		$currentPageLogPart = '';
	}
 
	//update date last update to now
	$query="update wp_rankie_keywords set keyword_rank = $rank , date_updated = '$now' $currentPageUpdate where keyword_id=$id";
	$wpdb->query($query);
	
	
	$logMessage = 'Rank for keyword #'.$id.' updated successfully to '.$rank.' '.$currentPageLogPart;
	
	if($rank ==0 && $linksCount != 0){
		$logMessage.= " out of ".$linksCount. " checked results.";
	}
	
	wp_rankie_log_new('Rank update', $logMessage );

	//add a new record if there is a record change
	$query="SELECT * FROM wp_rankie_ranks  where keyword_id='$id' order by id DESC limit 1 ";
	$rows=$wpdb->get_results($query);


	$updaterank=false;

	if(count($rows) != 0 ){
		$row=$rows[0];
		$lastrank=$row->rank;
		 
		if (trim($rank) != trim($lastrank)){
			
			//update rank
			$updaterank=true;
			
			//record a rank change 
			if($rank==0){
				// lost old rank i.e negative change
				$rank_change =  $rank - $lastrank     ;
				
			}else{
				
				//current rank is not zero
				if($lastrank != 0){
					
					$rank_change =  $lastrank - $rank   ;
				}else{
					
					//last rank =0 
					$rank_change =  $rank ;
					
				}
				
			}
			
			if($rank != 0 && $lastrank !=0){
				
				$query="insert into  wp_rankie_changes(keyword_id,rank_change) values ($id,$rank_change )";
				$wpdb->query($query);
				
			}
			
		}
		 
	}else{
		
		if($rank != 0 ){
			//update rank
			$updaterank = true;
		}
		
	}

	if($updaterank && $rank != 0){
		//add a new rank record
		$query="insert into  wp_rankie_ranks(keyword_id,rank ,rank_link) values ($id,$rank,'".addslashes($link)."')";
		$wpdb->query($query);
	}
}


/**
 * Function : wp_rankie_generate_report
 */
function wp_rankie_generate_report($args){
	
	global $wpdb;
 
	$month=$args['month'];
	$year=$args['year'];
	
	//site and group criteria
	$criteria = '';
	$chartTag = '' ;
	
	if( trim($args['site']) != 'all' ){
		$criteria = " and keyword_site = '{$args['site']}' ";
		$chartTag = $args['site'].' ';
	}
	
	if(trim($args['group']) != 'all'){
		$criteria = $criteria . " and keyword_group = '{$args['group']}' ";
		$chartTag .= '(' . $args['group'] . ' group) ';
	}
	
	$chartTag.= ' Ranking ';
	 
	//getting live ranks for current criteria top 3
	$query = " select count(*)  as count from wp_rankie_keywords where keyword_rank > 0 and keyword_rank < 4  $criteria  ";
	$rows=$wpdb->get_results($query );
	$row=$rows[0];
	$topThreeCount =  (int) $row->count ;
	
	//top 10
	$query = " select count(*)  as count from wp_rankie_keywords where keyword_rank > 0 and keyword_rank < 11  $criteria  ";
	$rows=$wpdb->get_results($query );
	$row=$rows[0];
	$topTenCount = (int)  $row->count ;
	
	//top 100
	$query = " select count(*)  as count from wp_rankie_keywords where keyword_rank > 0 and keyword_rank < 101  $criteria  ";
	$rows=$wpdb->get_results($query );
	$row=$rows[0];
	$topHunderedCount = (int)  $row->count ;
		
	
	//out rank =0
	$query = " select count(*)  as count from wp_rankie_keywords where keyword_rank = 0  or keyword_rank >100  $criteria  ";
	$rows=$wpdb->get_results($query );
	$row=$rows[0];
	$topOutRank =  (int)  $row->count ;
	
	$summaryHtml ='<h3>Summary</h3><table class="widefat"> <thead><th>Postion</th> <th>Keyword Count</th></thead> <tbody> <tr><td> in Top 3 </td><td>'.$topThreeCount.'</td></tr> <tr><td>in Top 10 </td><td>'.$topTenCount.'</td></tr> <tr><td>in top 100</td><td>'.$topHunderedCount.'</td></tr> <tr><td>Not in top 100</td><td>'.$topOutRank.'</td></tr> </tbody> </table>';
	
	//get all ranking for keywords ordered by rank
	$query = " select * from wp_rankie_keywords where keyword_rank > 0   $criteria order by keyword_rank ASC ";
	$rows=$wpdb->get_results($query );
	
	
	
	$allRankedKeys = '<h3>All Current Rankings</h3><table class="widefat"> <thead> <th>Keyword</th>  <th>Rank</th>  <th>Domain</th>  <th>Group</th>  </thead> <tbody> ';
	
	foreach($rows as $row){
		$tr = '<tr> <td>'.$row->keyword.'</td> <td>'.$row->keyword_rank.'</td><td>'.$row->keyword_site.'</td><td>'.$row->keyword_group.'</td></tr>';
		$allRankedKeys.=$tr;
	}
	
	$allRankedKeys .= '</tbody></table>';
	
	//DAILY REPORT
	if($args['type'] == 'day'){
		
		$chartTag.= ' on '.$month . ' - ' .$year ;
		
		//get rank changes by foreach day 
		$query="SELECT distinct( day(date) ) as single_day , sum(rank_change) as rank_change  , keyword_site , keyword_group  FROM `wp_rankie_changes` ,`wp_rankie_keywords`   where wp_rankie_changes.keyword_id =  wp_rankie_keywords.keyword_id  and   year(date) ='{$year}' and month(date) = '{$month}' $criteria group by single_day";
		
		//get dates that have rank change
		$rows=$wpdb->get_results($query);
		foreach ($rows as $row){
			$recorded_ranks[$row->single_day] = $row->rank_change;
		}
	 	
		//getting dates in selected month 
		$today = strtotime(date("Y-m-d"));
		$num = cal_days_in_month ( CAL_GREGORIAN, $month, $year );
		
		//get days in current month
		for($i =1 ;$i<= $num;$i++){
		
			$month_days[] = $i;
			$thisDate=   strtotime($year . "-" . $month . "-" .  $i  );
		
			if($thisDate == $today ) break;
		
		}
		
	 
		$starting_rank=0;
		
		//get final records for all days in the month 
		$final_ranks[]=array('Day','Rank');
		$final_ranks[]=array(0,0);
		foreach ($month_days as $month_day){
			
			if(isset($recorded_ranks[$month_day])){
				//got a new record 
				$starting_rank = $starting_rank + $recorded_ranks[$month_day];
			}
			
			$final_ranks[]= array($month_day , $starting_rank); 
			
			
		}
		
		
		//getting moving forward & backward keywords
		$moving_forware_html = '<br><h3>Pakilo </h3><table class="widefat"><thead><tr><th>Keyword</th><th>Current Rank</th><th>Rank Change</th><th>Domain name</th><th>Group</th> </tr></thead>';
		$moving_backward_html = '<br><h3>Nukrito </h3><table class="widefat"><thead><tr><th>Keyword</th><th>Current Rank</th><th>Rank Change</th><th>Domain name</th><th>Group</th> </tr></thead>';
		 
		
		$query="SELECT distinct( wp_rankie_changes.keyword_id ) as single_keyword , keyword , keyword_rank , sum(rank_change) as total_rank_change  , keyword_site , keyword_group  FROM `wp_rankie_changes` ,`wp_rankie_keywords`   where wp_rankie_changes.keyword_id =  wp_rankie_keywords.keyword_id  and   year(date) ='{$year}' and month(date) = '{$month}'  $criteria    group by single_keyword order by total_rank_change DESC";
		$rows=$wpdb->get_results($query );
		
		$printedKeys=array();
		$positiveSum = 0;
		$negativeSum = 0;
		
		foreach($rows as $row ){
			
			$printedKeys[]=$row->single_keyword;
			
			if( $row->total_rank_change > 0 ){
				$tr = '<tr><td>'.$row->keyword.'</td><td>'.$row->keyword_rank.'</td><td>+'.$row->total_rank_change.'</td><td>'.$row->keyword_site.'</td><td>'.$row->keyword_group.'</td></tr>';
				$moving_forware_html .= $tr;
				$positiveSum = $positiveSum + $row->total_rank_change;
			}else{
				$tr = '<tr><td>'.$row->keyword.'</td><td>'.$row->keyword_rank.'</td><td>'.$row->total_rank_change.'</td><td>'.$row->keyword_site.'</td><td>'.$row->keyword_group.'</td></tr>';
				$moving_backward_html .= $tr;
				$negativeSum = $negativeSum + $row->total_rank_change;
			}
			
		}
		
		$negativeSum = $negativeSum * -1 ;
		
		$moving_forware_html .= '</table>';
		$moving_backward_html .= '</table>';
		 
		
		
		
		$total_html = $moving_forware_html.$moving_backward_html . $summaryHtml .$allRankedKeys;
		
		
	 
		
		$pie [0] = array( array('UP','Down') , array('UP',$positiveSum) ,array('Down', $negativeSum) );
		$pie [1] = array( array('Position','Keyword Count') , array('In top 3',$topThreeCount) ,array('in top 10', $topTenCount) , array('in top 100' , $topHunderedCount) , array('not in top 100' , $topOutRank) );
		 		
		return  array( $final_ranks ,$total_html ,$pie , $chartTag )  ;
		 
		
	}elseif($args['type'] == 'month'){
		
		$chartTag.= ' on ' .$year ;
		
		$query="SELECT distinct( month(date) ) as single_month , sum(rank_change) as rank_change  , keyword_site , keyword_group  FROM `wp_rankie_changes` ,`wp_rankie_keywords`   where wp_rankie_changes.keyword_id =  wp_rankie_keywords.keyword_id  and   year(date) ='{$year}'  $criteria group by single_month";
		$rows=$wpdb->get_results($query);

		foreach ($rows as $row){
			$recorded_ranks[$row->single_month] = $row->rank_change;
		}
		
		 
		 
		//getting months in selected Year
		$tomonth = strtotime(date("Y-m"));
		
		$num=12;
		for($i =1 ;$i<= $num;$i++){
			
			$year_months[] = $i;
			
			$thisDate=   strtotime($year . "-" . $i    );
		
			if($thisDate == $tomonth ) break;
		
		}
		  
		$starting_rank=0;
		
		//get final records
		$final_ranks[]=array('Month','Rank');
		$final_ranks[]=array(0,0);
		foreach ($year_months as $year_month){
				
			if(isset($recorded_ranks[$year_month])){
				//got a new record
				$starting_rank = $starting_rank + $recorded_ranks[$year_month];
			}
				
			$final_ranks[]= array($year_month , $starting_rank);
				
				
		}
		
		 
		
		//getting moving forward & backward keywords
		$moving_forware_html = '<br><h3>Pakilo </h3><table class="widefat"><thead><tr><th>Keyword</th><th>Current Rank</th><th>Rank Change</th><th>Domain name</th><th>Group</th> </tr></thead>';
		$moving_backward_html = '<br><h3>Nukrito </h3><table class="widefat"><thead><tr><th>Keyword</th><th>Current Rank</th><th>Rank Change</th><th>Domain name</th><th>Group</th> </tr></thead>';
		 
		
		$query="SELECT distinct( wp_rankie_changes.keyword_id ) as single_keyword , keyword , keyword_rank , sum(rank_change) as total_rank_change  , keyword_site , keyword_group  FROM `wp_rankie_changes` ,`wp_rankie_keywords`   where wp_rankie_changes.keyword_id =  wp_rankie_keywords.keyword_id  and   year(date) ='{$year}'   $criteria    group by single_keyword order by total_rank_change DESC";
		$rows=$wpdb->get_results($query );
		
		$printedKeys=array();
		$positiveSum = 0;
		$negativeSum = 0;
		
		foreach($rows as $row ){
			
			$printedKeys[]=$row->single_keyword;
			
			if( $row->total_rank_change > 0 ){
				$tr = '<tr><td>'.$row->keyword.'</td><td>'.$row->keyword_rank.'</td><td>+'.$row->total_rank_change.'</td><td>'.$row->keyword_site.'</td><td>'.$row->keyword_group.'</td></tr>';
				$moving_forware_html .= $tr;
				$positiveSum = $positiveSum + $row->total_rank_change;
			}else{
				$tr = '<tr><td>'.$row->keyword.'</td><td>'.$row->keyword_rank.'</td><td>'.$row->total_rank_change.'</td><td>'.$row->keyword_site.'</td><td>'.$row->keyword_group.'</td></tr>';
				$moving_backward_html .= $tr;
				$negativeSum = $negativeSum + $row->total_rank_change;
			}
			
		}
		
		$negativeSum = $negativeSum * -1 ;
		
		$moving_forware_html .= '</table>';
		$moving_backward_html .= '</table>';
		 
		
		
		
		$total_html = $moving_forware_html.$moving_backward_html . $summaryHtml .$allRankedKeys;
		
		
	 
		
		$pie [0] = array( array('UP','Down') , array('UP',$positiveSum) ,array('Down', $negativeSum) );
		$pie [1] = array( array('Position','Keyword Count') , array('In top 3',$topThreeCount) ,array('in top 10', $topTenCount) , array('in top 100' , $topHunderedCount) , array('not in top 100' , $topOutRank) );
		 		
		return  array( $final_ranks ,$total_html ,$pie , $chartTag )  ;
		
		
	}elseif($args['type'] == 'year'){
		
		$query="SELECT distinct( year(date) ) as single_year , sum(rank_change) as rank_change  , keyword_site , keyword_group  FROM `wp_rankie_changes` ,`wp_rankie_keywords`   where wp_rankie_changes.keyword_id =  wp_rankie_keywords.keyword_id    $criteria group by single_year";
	 
		$rows=$wpdb->get_results($query);
		
		$recorded_ranks=array();
		foreach ($rows as $row){
			$recorded_ranks[$row->single_year] = $row->rank_change;
		}
		
		   
		$starting_rank=0;
		
		//get final records
		$final_ranks[]=array('Month','Rank');
		$final_ranks[]=array(2012,0);
		
		
		foreach ($recorded_ranks as $key=>$val){
		
		//got a new record
		$starting_rank = $starting_rank + $val;
 
		$final_ranks[]= array($key , $val );
		
		}
		
		//getting moving forward & backward keywords
		$moving_forware_html = '<br><h3>Pakilo </h3><table class="widefat"><thead><tr><th>Keyword</th><th>Current Rank</th><th>Rank Change</th><th>Domain name</th><th>Group</th> </tr></thead>';
		$moving_backward_html = '<br><h3>Nukrito </h3><table class="widefat"><thead><tr><th>Keyword</th><th>Current Rank</th><th>Rank Change</th><th>Domain name</th><th>Group</th> </tr></thead>';
		 
		if(trim( $criteria) != '') $criteria = ' and '.$criteria;
		$query="SELECT distinct( wp_rankie_changes.keyword_id ) as single_keyword , keyword , keyword_rank , sum(rank_change) as total_rank_change  , keyword_site , keyword_group  FROM `wp_rankie_changes` ,`wp_rankie_keywords`   where wp_rankie_changes.keyword_id =  wp_rankie_keywords.keyword_id    $criteria    group by single_keyword order by total_rank_change DESC";
		$rows=$wpdb->get_results($query );
		
		$printedKeys=array();
		$positiveSum = 0;
		$negativeSum = 0;
		
		foreach($rows as $row ){
			
			$printedKeys[]=$row->single_keyword;
			
			if( $row->total_rank_change > 0 ){
				$tr = '<tr><td>'.$row->keyword.'</td><td>'.$row->keyword_rank.'</td><td>+'.$row->total_rank_change.'</td><td>'.$row->keyword_site.'</td><td>'.$row->keyword_group.'</td></tr>';
				$moving_forware_html .= $tr;
				$positiveSum = $positiveSum + $row->total_rank_change;
			}else{
				$tr = '<tr><td>'.$row->keyword.'</td><td>'.$row->keyword_rank.'</td><td>'.$row->total_rank_change.'</td><td>'.$row->keyword_site.'</td><td>'.$row->keyword_group.'</td></tr>';
				$moving_backward_html .= $tr;
				$negativeSum = $negativeSum + $row->total_rank_change;
			}
			
		}
		
		$negativeSum = $negativeSum * -1 ;
		
		$moving_forware_html .= '</table>';
		$moving_backward_html .= '</table>';
		 
		
		
		
		$total_html = $moving_forware_html.$moving_backward_html . $summaryHtml .$allRankedKeys;
		
		
	 
		
		$pie [0] = array( array('UP','Down') , array('UP',$positiveSum) ,array('Down', $negativeSum) );
		$pie [1] = array( array('Position','Keyword Count') , array('In top 3',$topThreeCount) ,array('in top 10', $topTenCount) , array('in top 100' , $topHunderedCount) , array('not in top 100' , $topOutRank) );
		 		
		return  array( $final_ranks ,$total_html ,$pie , $chartTag )  ;
		
		
	}
}	

/**
 * function random_letter: returns a random letter
 * @return string letter
 */
function wp_rankie_random_letter(){

	$letters='a,A,b,B,c,C,d,D,e,E,f,F,g,G,h,H,i,I,j,J,k,K,l,L,m,M,n,N,o,O,p,P,q,Q,r,R,s,S,t,T,u,U,v,V,w,W,x,X,y,Y,z,Z';
	$lettersArr = explode(',', $letters);
	$randIndex = rand( 0 , count($lettersArr) - 1 );
	return $lettersArr[$randIndex];
}