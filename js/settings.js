jQuery(document).ready(function() {

	function rankieAdjustGoogleSelector() {
		var selectorVal = jQuery('#wp_rankie_method').val();
		jQuery('.google_selector').hide();
		jQuery('.' + selectorVal).show();
	}

	rankieAdjustGoogleSelector();
	
	jQuery('#wp_rankie_method').change(rankieAdjustGoogleSelector);

});