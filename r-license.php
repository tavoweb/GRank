<?php
function wp_rankie_admin_notice() {
	
	if(! function_exists('curl_init') ){
	
		?>
			
			<div class="error">
			        <p><a href="http://curl.haxx.se/">cURL</a> is not installed. you must install it for wordpress Rankie plugin to function.</p>
			</div>
			
			<?php
			
	}
	
	$licenseactive=true;
				$wp_rankie_license = 'Active';
	
	if(trim($licenseactive) == ''){
		?>
			<div class="error">
		        <p>Wordpress Rankie is ready to go. Please update your license <a href="<?php echo admin_url('admin.php?page=wp_rankie_settings') ?>">here</a>.</p>
		    </div>
	    <?php
	}

}
add_action( 'admin_notices', 'wp_rankie_admin_notice' );

