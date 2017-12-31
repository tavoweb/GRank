<?php

// Dashboard
function wp_rankie_dashboard_fn() {
	
	//INI
	global $wpdb;
	
	//Table verision check
	wp_rankie_check_table_version();
	
	?>
<div class="wrap">
	<h2>
		Rankie Dashboard <a class="add-new-h2" href="#">Add Keywords</a>
	</h2>

	<?php 
		//get counts 
		
		// total count
		$query="SELECT count(*) as count FROM wp_rankie_keywords";
		$rows=$wpdb->get_results($query);
		$totalCount=$rows[0]->count;
		
		// manual count
		$query="SELECT count(*) as count FROM wp_rankie_keywords where keyword_type = 'Manual'";
		$rows=$wpdb->get_results($query);
		$manualCount=$rows[0]->count;
		
		// Auto count
		$query="SELECT count(*) as count FROM wp_rankie_keywords where keyword_type = 'Auto'";
		$rows=$wpdb->get_results($query);
		$autoCount=$rows[0]->count;
		
		// last log message
		$query="SELECT *  FROM wp_rankie_log order by id desc limit 1";
		$rows=$wpdb->get_results($query);
		$lastLog = $rows[0]->data;
		
		//Google Country
		$wp_rankie_google_gl=get_option('wp_rankie_google_gl','N');
		if(trim($wp_rankie_google_gl) == 'N'){
			$wp_rankie_google_gl ='';
		}else{
			$wp_rankie_google_gl = '&gl='.$wp_rankie_google_gl;
		}
		
		$wp_rankie_whatsmyserp_g = get_option('wp_rankie_whatsmyserp_g','www.google.com');
		
		$wp_rankie_method=get_option('wp_rankie_method','whatsmyserp');
		$wp_rankie_screen= get_option('wp_rankie_screen' , '200');
	
	?>

	<ul class="subsubsub">
		<li class="all"><a class="current" href="#">All <span id="totalCount" class="count">(<?php echo $totalCount ; ?>)</span></a> |</li>
		<li class="manual"><a href="#">Manual <span id="manualCount" class="count">(<?php echo $manualCount ?>)</span></a> |</li>
		<li class="auto"><a href="#">Auto <span class="count">(<?php echo $autoCount ?>)</span></a></li>

	</ul>
	<div class="clear"></div>
	<div id="field-site-container" class="tablenav top">



		<div class="alignleft actions bulkactions">
			<select class="action_select" name="action">
				<option selected="selected" value="-1">Bulk Actions</option>
				<option value="trash">Delete</option>
				<option value="change">Change Group</option>
			</select> <input type="submit" value="Apply" class="button action" id="doaction" name="">
		</div>

		<div style="display: none; margin-left: -10px;" class="spinner spinner-bulk"></div>

		<select name="site" id="wp-rankie-select-site">
			<option value="all">All Sites</option>
			
				<?php 

					//get disnct groups 
					global $wpdb;
					$query="SELECT distinct keyword_site  FROM wp_rankie_keywords ";
					$sites=$wpdb->get_results($query);
					
					foreach ($sites as $row){
						echo '<option  value="'. $row->keyword_site .'"  >'. $row->keyword_site .'</option>'; 
					}
					
				?> 			
			
			 
		</select> <select name="site" id="wp-rankie-group">
			<option value="all">All Groups</option>
			<?php 
				//get disnct groups
				global $wpdb;
				$query="SELECT distinct keyword_group  FROM wp_rankie_keywords ";
				$groups=$wpdb->get_results($query);
					
				foreach ($groups as $row){
					echo '<option  value="'. $row->keyword_group .'"  >'. $row->keyword_group .'</option>';
				}
			?>
			

		</select>
		
		<p class="wp-rankie-search-box">
			<label for="post-search-input" class="screen-reader-text">Search Keywords:</label> <input placeholder="Keyword..." type="search" value="" name="s" id="post-search-input"> <input type="submit" value="Search Keywords" class="button" id="search-submit" name="">
		</p>

		</div>
		
		<div class="clear"></div>

	<table id="rankie-keywords" class="widefat">
		<thead>
			<tr>
				<th style="" class="manage-column column-cb check-column" id="cb" scope="col"><label for="cb-select-all-1" class="screen-reader-text">Select All</label><input type="checkbox" id="cb-select-all-1"></th>
				<th class="column-response">Keyword</th>
				<th class="column-response">Rank</th>
				<th class="column-response">Site</th>
				<th class="column-response">Up-to-date</th>
				<th class="column-response">Delete</th>
				<th class="column-response">Type</th>
				<th class="column-response">Group</th>
				<th class="column-response"></th>



			</tr>
		</thead>
		<tfoot>
			<tr>
				<th style="" class="manage-column column-cb check-column" scope="col"><label for="cb-select-all-1" class="screen-reader-text">Select All</label><input type="checkbox" id="cb-select-all-1"></th>
				<th class="column-response">Keyword</th>
				<th class="column-response">Rank</th>
				<th class="column-response">Site</th>
				<th class="column-response">Up-to-date</th>
				<th class="column-response">Delete</th>
				<th class="column-response">Type</th>
				<th class="column-response">Group</th>
				<th class="column-response"></th>


			</tr>
		</tfoot>
		<tbody>

			<?php 

				// RENDERING ROWS
				
				$query="SELECT * FROM wp_rankie_keywords";
				$rows=$wpdb->get_results($query);
				$wp_rankie_update_interval = get_option('wp_rankie_update_interval','1');
				$wp_rankie_update_threshold = $wp_rankie_update_interval * 24 *60 *60;
				
				foreach($rows as $row){
					?>
					
						<tr>

							<?php 
									$flag = 0;
									$diff =  time('now') - $row->date_updated; 
									if($diff > $wp_rankie_update_threshold ) $flag = 1 ;
							?>
			
							<td><input type="checkbox" class="wp-rankie-keyword-id" value="<?php echo $row->keyword_id ?>" name="post[]" id="cb-select-<?php echo $row->keyword_id ?>"></td>
			
							<td> <span class="wp-rankie-keyword-text"><?php echo $row->keyword ?></span></td>
							
							<td>  <?php  
							
							echo $row->keyword_rank ;
							 
							?> </td>
							
							<td> <span class="wp-rankie-keyword-site" ><?php echo $row->keyword_site ?></span></td>
							
							<td> <div class="spinner spinner-<?php echo $row->keyword_id ?>" style="display: none;"></div>
								<a class="wp-rankie-update-row" href="#">
									<div class="updatedz updated-<?php echo $row->keyword_id ?> dashicons <?php if($flag == 1){echo 'dashicons-clock' ; }else{echo 'dashicons-yes' ;}?>"></div>
								</a>
							</td>
							
							<td><a  class="wp-rankie-delete-row"  href="#"><div class="dashicons dashicons-no-alt"></div></a></td>
							
							<td><?php echo $row->keyword_type ?></td>
							
							<td><?php echo $row->keyword_group ?></td>
							
							
							<td><input type="hidden" class="wp-rankie-updated" value="<?php echo $flag ?>" /></td>
			
						</tr>		

					<?php 
				}
			
			?>
			 
		</tbody>
	</table>
	
	<div class="tablenav bottom">
	<div class="alignleft actions bulkactions">
			<select name="action2">
					<option selected="selected" value="-1">Bulk Actions</option>
					<option value="trash">Delete</option>
			</select>
			<input type="submit" name="" id="doaction2" class="button action" value="Apply">
			<div style="display: none; " class="spinner spinner-bulk"></div>
		</div>
	</div>
	
	<div class="clear"></div>
	<div><p>* The plugin updates a single keyword each 6 minutes and will try to update a new one after <span class="next_key"></span></span> seconds. Please be patient as the plugin is designed to work during the whole day to keep requests rate to Google safe.</p> <p>* Last log message -> <span class="wp_rankie_last_log" ><?php echo $lastLog ?> </span><br></p></div>
	<div class="categorydiv" id="taxonomy-category">
	    <ul class="category-tabs" id="category-tabs">
	        <li class="tabs"><a href="#">Rank Charts</a>
	        </li>
	        <li class=""><a href="#">Rank Records</a>
	        </li>
	    </ul>
	
	    <div style="display: block;" class="tabs-panel" id="category-pop">
	         
	           
	         
	         <div id="chart_div"  ><p>Click any keyword above to show it's ranking chart here ...</p></div>
	         
	         
	        
	         
	    </div>
	
	    <div class="tabs-panel" id="category-all" style="display: none;">
	         
	         <div class="wp-rankie-chart-contain">
		         <table class="widefat"  id="wp-rakie-chart" >
					<caption>Unique ranking Records of "<span class="wp-rakie-chart-site">SITE</span>" on keyword "<span class="wp-rakie-chart-keyword">KEYWORD</span>" </caption>
					<thead>
						<tr>
							<th>Date</th> 
							<th>Rank</th>
							<th>Link</th>
							 
						</tr>
					</thead>
					<tbody>
						  
					</tbody>
				</table>
				<div class="description" style="margin-top:20px;"><strong>Important:</strong> If today's rank is the same as the last unique rank found it may not be recorded here. If you have the success check mark under Up-to-date field it means the rank was updated today.</div>
			</div>
	         
	    </div>
	    
 
	
	</div>
	
	
</div>

<div style="display:none" id="keywordsDialog">

	<table id="wp-rankie-table-add">
		
		<tbody>
			<tr>
				<td>Site</td>
				<td><input class="fwidth" id="wp-rankie-keywords-site" type="text" placeholder="your site" value="<?php echo $_SERVER['HTTP_HOST']; ?>" /></td>
			</tr>
			<tr>
				<td>Keywords</td>
				<td><textarea id="wp-rankie-keywords-add"></textarea><br><div class="description">add your keywords one keyword per line  </div></td>
				
			</tr>
			<tr>
				<td>
					Match type
				</td>
				<td>
					 <input checked="checked" type="radio" name="matchType" value="domain">Any from the domain<br>
	 				 <input type="radio" name="matchType" value="url">Exact url<br>
				</td>
				
			</tr>
			
			<tr>
				<td>Group</td>
				<td>
					<select name="wp-rankie-group-select" id="wp-rankie-group-select">
						<option  value="General"  >General</option>
						<?php 
							//get disnct groups 
							global $wpdb;
							$query="SELECT distinct keyword_group  FROM wp_rankie_keywords ";
							$groups=$wpdb->get_results($query);
							
							foreach ($groups as $row){
								if($row->keyword_group != "General")

								echo '<option  value="'. $row->keyword_group .'"  >'. $row->keyword_group .'</option>'; 
							}
							
						?> 
						
						
						<option  value="wp-rankie-group-new"  >New Group</option>
					</select>
					
					<input  class="fwidth"  style="display:none;" placeholder="New Group Name.." id="wp-rankie-group-new-text" name="wp-rankie-group-new-text" type="text" value="" >
					
				<br><div class="description">add your keywords one keyword per line  </div></td>
			</tr>
			
			<tr>
				<td></td>
				<td><div class="spinner spinner-btn-add" style="float: right !important;display: none; margin-bottom: -25px;"></div>
				<input   class="fwidth"  class="button"  type="submit" value="Add Keywords" id="wp-rankie-keywords-add-btn" /></td>
			</tr>
		</tbody>
	
	</table>
	
	
	
	

</div>


<div style="display:none" id="groupChange">

	<table id="wp-rankie-table-change">
		
		<tbody>
			 
			
			<tr>
				<td>Group</td>
				<td>
					<select name="wp-rankie-group-select-change" id="wp-rankie-group-select-change">
						<option  value="General"  >General</option>
						<?php 
							//get disnct groups 
							global $wpdb;
							$query="SELECT distinct keyword_group  FROM wp_rankie_keywords ";
							$groups=$wpdb->get_results($query);
							
							foreach ($groups as $row){
								
								if($row->keyword_group != "General")
								echo '<option  value="'. $row->keyword_group .'"  >'. $row->keyword_group .'</option>';
								
							}
							
						?>
						 
						<option  value="wp-rankie-group-new-change"  >New Group</option>
						
					</select>
					
					<input style="display:none;" placeholder="New Group Name.." id="wp-rankie-group-new-text-change" name="wp-rankie-group-new-text-change" type="text" value="" >
					
				<br></td>
			</tr>
			
			<tr>
				<td></td>
				<td><div class="spinner spinner-btn-change" style="float: right !important;display: none; margin-bottom: -25px;"></div>
				<input  class="button"  type="submit" value="Change Group" id="wp-rankie-keywords-change-btn" /></td>
			</tr>
		</tbody>
	
	</table>
	
	
	
	

</div>


<script type="text/javascript">

						var totalCount = <?php echo $totalCount ?>;
						var manualCount = <?php echo $manualCount ?>;
						var googleL='<?php echo $wp_rankie_google_gl ?>';
						var googleWhatsmyserp='<?php echo $wp_rankie_whatsmyserp_g ?>';
						
						var googleMethod = '<?php echo $wp_rankie_method ?>';
						var sScroll = '<?php echo $wp_rankie_screen ?>';
						
</script>

<?php
}