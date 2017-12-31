<?php
if (! function_exists ( 'deandev_add_dashboard_widgets' )) {
	
	add_action ( 'wp_dashboard_setup', 'deandev_add_dashboard_widgets' );
	function deandev_add_dashboard_widgets() {
		add_meta_box ( 'deandev_dashboard_widget', 'TavoWEB Pagalba', 'deandev_dashboard_widget_function', 'dashboard', 'side', 'high' );
	}
	
	/**
	 * Create the function to output the contents of our Dashboard Widget.
	 */
	function deandev_dashboard_widget_function() {
		
		 
		$purl = plugins_url('',__FILE__);
		 
		?>

<table>
 <img style="margin-bottom: 20px;    max-height: 38px;" src="https://www.tavoweb.lt/logo/logor.png">
	<tbody>
		<tr>
		    <td>

				<p>
					Norėdamin gauti TavoWEB pagalbą kreipkitės<br> e-paštu <b>mano@tavoweb.lt</b> <br> telefonu <b>+37069370376.</b>
				</p>
				<p>
					<a href="https://mano.tavoweb.lt/knowledge-base" target="_blank" class="button"> TavoWEB pagalba </a>
				</p>
				<p>
					<a href="https://www.stinklas.lt" target="-_blank" class="button">sTinklas.lt</a>
				</p>
				<p>
					<a href="https://github.com/tavoweb/" target="-_blank" class="button">Kiti mūsų įskiepiai</a>
				</p>
					<p>
						<small><a href="https://www.tavoweb.lt" target="_blank">Apsilankykite mūsų svetainėje</a></small>
						<small>| <a id="wp_tavoweb_widget_hide" href="#">  Neberodyti valdiklio</a></small>
						
					</p>
					
			</td>
		</tr>

		
		<tr><td>&nbsp;</td></tr>

	</tbody>
</table>

<script type="text/javascript">
	jQuery('#wp_tavoweb_widget_hide').click(function(){
		jQuery('#deandev_dashboard_widget-hide').trigger('click');
	});
</script>

<?php
	} // function of the widget
}//function exists
