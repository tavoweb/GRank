<?php

function wp_rankie_research() {
	
		 
		$wp_rankie_research_gl = get_option('wp_rankie_research_gl' , 'google.com');
		$wp_keyword_tool_alphabets = get_option('wp_keyword_tool_alphabets' , 'a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z');
		$letters_arr=explode(',', trim($wp_keyword_tool_alphabets));
		$letters=array_filter($letters_arr);
	
		?>
	<div class="wrap">
	
	<h2>Rankie Keyword Research</h2>
	
	<table style="margin-top:10px;width:100%;max-width: 900px;">
		<tbody>
		<tr>
			
			<td><input style="width:100%" type="text" value="" autocomplete="off" placeholder="Keyword..." size="14" class="newtag form-input-tip" id="wp_keyword_tool_search_txt"></td>
			<td style="width: 135px;" ><input style="width:100%"  type="button" tabindex="3" value="Search" class="button" id="wp_keyword_tool_more"></td>
			<td style="width: 38px;"><input style="width:100%"  type="button" tabindex="3" value="x" class="button tagadd" id="wp_keyword_tool_clean"></td>
			
		</tr>
		<tr><td colspan="3">
		
			
			<div class="hidden" id="wp_keyword_tool_body">
		
		
				<div id="wp_keyword_tool_keywords" class="wp-tab-panel"></div>
				
				<div style="margin-bottom:10px;padding-left:5px"><label><input type="checkbox" id="wp_keyword_tool_check" value="s">Check/uncheck all</label></div>
				
				<input type="button"   value="Show as list" class="button" id="wp_keyword_tool_list_btn">
				
				<p>
					keyword tool has found (<span class="wp_keyword_tool_count"></span>) keywords for the term
				(<span class="wp_keyword_tool_keyword_status"></span>) 
				
				
				</p>
				
			</div>
		
		</td></tr>
		
		</tbody>
	</table>
		
		 
	
	
		
		
		 
		
		
	
	
	<div  style="display: none"  id="wp-keyword-tool-list-wrap">
		<textarea style="width:100%;height: 300px;" id="wp-keyword-tool-list"></textarea>
	</div>
	
	</div>
	
	<script type="text/javascript">
		var wp_keyword_tool_google= '<?php echo $wp_rankie_research_gl ?>';
		var wp_keyword_tool_letters = <?php echo json_encode($letters) ?> ;
	
	</script>
	
		
	
	<?php
	}
	
 