<?php

/*
 * New Log Entry
 */
function wp_rankie_log_new($type,$data){
	
	global $wpdb;
	global $wp_rankie_last_log;
	
	$now = date( 'Y-m-d H:i:s');
	$data=addslashes($data);
	 
	$query="INSERT INTO wp_rankie_log (action,date,data) values('$type','$now','$data')";
	$wpdb->query($query);
	
	$wp_rankie_last_log = $data;
	
	//delete more than 100 log 
	$insert = $wpdb->insert_id;
	
	$insert_below_100 = $insert -100 ;
	
	if($insert_below_100 > 0){
		
		//delete 
		$query="delete from wp_rankie_log where id < $insert_below_100";
		$wpdb->query($query);
	}
	
}


/*
 * log page of the plugin
 */
function wp_rankie_log(){
 
	//Table version check
	wp_rankie_check_table_version();
	
	// DB
	global $wpdb;
	 
	//FILTER
	$filter="";
	if( isset( $_POST['action_type']) ){
		$act=$_POST['action_type'];
		if ($act == 'Error' ){
			$action=" action like '%Error%' ";
		}elseif($act == 'approved'){
			$action = " action like 'Comment approved%'";
		}
	}else{
		$action='';
			
	}

	if ($action != ''){
		if($filter == ''){
			$filter=" where $action";
		}else{
			$filter.=" and $action";
		}
	}

	// records number
	if(isset($_POST['number'])){
		$num=$_POST['number'];
	}else{
		$num='100';
	}

	// define limit
	$limit='';
	if (is_numeric($num)) $limit=" limit $num ";

	$qdate='';
	// finally date filters `date` >= str_to_date( '07/03/11', '%m/%d/%y' )
	if(isset($_POST['from']) && $_POST['from'] !='' ){
		$from=$_POST['from'];
		$qdate=" `date` >= str_to_date( '$from', '%m/%d/%y' )";
	}

	if(isset($_POST['to']) && $_POST['to'] !=''){
		$to=$_POST['to'];
		if($qdate == ''){
			$qdate.=" `date` <= str_to_date( '$to', '%m/%d/%y' )";
		}else{
			$qdate.=" and `date` <= str_to_date( '$to', '%m/%d/%y' )";
		}
	}

	if($qdate != ''){
		if($filter == ''){
			$filter=" where $qdate";
		}else{
			$filter.="and $qdate";
		}
	}
	 
	$query="SELECT * FROM wp_rankie_log $filter ORDER BY id DESC $limit";
	 
	$res=$wpdb->get_results( $query);

	?>

 
<style>
.ttw-date {
	width: 81px;
}

.Publish{
	background: none repeat scroll 0 0 #AAAAAA;
    color: #FFFFFF !important;
}

.Publish td{
	color:#FFFFFF !important;
}

</style>
<div class="wrap">
	<div class="icon32" id="icon-edit-comments">
		<br>
	</div>
	<h2>Rankie Log</h2>
	
	<form method="post" action="">
		<div class="tablenav top">

		 

			<div class="alignleft actions">
				<select name="number">
					<option <?php opt_selected($num,'50') ?>
						value="999">Records Number</option>
					<option <?php opt_selected($num,'100') ?>
						value="100">100</option>
					<option <?php opt_selected($num,'500') ?>
						value="500">500</option>
					<option <?php opt_selected($num,'1000') ?>
						value="1000">1000</option>
					<option <?php opt_selected($num,'all') ?>
						value="all">All</option>
				</select> <select name="action_type">
					<option <?php @opt_selected($act,'') ?>
						value="">Show all actions</option>
					<option <?php @opt_selected($act,'Error') ?>
						value="Error">Error</option>

				</select>
			</div>
			<div class="clear"></div>
			<div class="alignleft actions" style="margin: 11px 0 11px 0">

				<label for="field1"> From Date: <small><i>(optional)</i> </small> </label>
				<input class="ttw-date date" name="from" id="field1" type="date"
					autocomplete="off"> <label for="field2"> To Date: </label> <input
					class="ttw-date date" name="to" id="field2" type="date"
					autocomplete="off"> <input type="submit" value="Filter"
					class="button-secondary" id="post-query-submit" name="submit">
			</div>




		</div>

		</form>
		
		<div class="clear"></div>
		<?php $lastrun=get_option('wp_rankie_last_run',1392146043); ?>
		<div id="welcome-panel" class="welcome-panel">
			<p style="margin-top: -13px;"><strong>Current</strong> server time is  <strong>( <?php echo date('h:i:s') ?> )</strong> , Cron last run at <strong>( <?php echo date("h:i:s",$lastrun ) ?> )</strong> this is <strong> ( <?php echo $timdiff = time('now') - $lastrun ?> )</strong> seconds ago and it runs every <strong>( 360 )</strong> seconds to process one item from the queue so it should run again after <strong>( <?php echo( 360 - $timdiff )  ?> )</strong> seconds.
		</div>
	
		
		<table class="widefat fixed">
			<thead>
				<tr>
					<th class="column-date">Index</th>
					<th class="column-response">Date</th>
					<th class="column-response">Type of action</th>
					<th>Data Processed</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>index</th>
					<th>Date</th>
					<th>Type of action</th>
					<th>Data Processed</th>
				</tr>
			</tfoot>
			<tbody>

			<?php
			$i=1;
			foreach ($res as $rec){
				$action=$rec->action;
				//filter the data strip keyword
				$datas=explode(';',$rec->data);
				$data=$datas[0];


				if (stristr($action , 'Posted:')){
					$url = plugins_url().'/wp-auto-spinner';
					$action = 'New Post';
					//restoring link

				}elseif(stristr($action , 'Processing')){
					$action = 'Processing Campaign';
				}
				
				if(stristr($data,'html')){
					 $data='<textarea>'.htmlspecialchars( ($data) ).'</textarea>';
				}else{
					//$data=htmlspecialchars( ($data) );
				}
				

				echo'<tr class="'.$rec->action.'"><td class="column-date">'.$i.'</td><td  class="column-response" style="padding:5px">'.urldecode($rec->date).'</td><td  class="column-response" style="padding:5px">'. $action.'</td><td  style="padding:5px">' .urldecode($data).' </td></tr>';
				$i++;
			}


			?>
			</tbody>
		</table>

</div>
 


			<?php
}