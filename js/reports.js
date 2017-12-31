//Google maps
google.load('visualization', '1.0', {'packages':['corechart']});

//UPDATE REPORT BUTTON
jQuery('#generate_button').unbind('click');
jQuery('#generate_button').click(
    	
    function(){
    	
    	jQuery('.spinner').show().addClass('is-active');
        
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
    
            data: {
                action: 'wp_rankie_generate_report',
                site: jQuery('#site').val(),
                group: jQuery('#group').val(),
                type: jQuery('#type').val(),
                year: jQuery('#year').val(),
                month: jQuery('#month').val()
                
            },
            
            success:function(data){
            jQuery('.spinner').hide();
            
             var res = jQuery.parseJSON(data);
             jQuery('#report_title').html(res[3]);
             var data = google.visualization.arrayToDataTable(res[0]);

			                  // Set chart options
			                  var options = {	
			                		  			title: res[3]  ,
			                                    width: jQuery('.report_map').width() - 10 , height: 190,
			                          vAxis: { direction:1, viewWindowMode:"pretty"}};

			                  // Instantiate and draw our chart, passing in some options.
			                  var chart = new google.visualization.LineChart(document.getElementById('report_map'));
			                  chart.draw(data, options);
			                  
			                  
			                  //add tables 
			                  jQuery('#report_tables').empty();
			                  jQuery('#report_tables').html(res[1]);
			                  
			                  //draw the pie 
			                  
			                  var data = google.visualization.arrayToDataTable(res[2][0]);

			                  // Create and draw the visualization.
			                  new google.visualization.PieChart(document.getElementById('report_pie')).
			                      draw(data, {title: res[3] + " vs Outranking"});

			                  var data = google.visualization.arrayToDataTable(res[2][1]);

			                  // Create and draw the visualization.
			                  new google.visualization.PieChart(document.getElementById('report_pie_2')).
			                      draw(data, {title: res[3] + " summary"});

             
            
            }
        
        });
        
        return false;
    }

);

//Download button
jQuery('#download_button').click(function(){
	var doc = new jsPDF();
	// We'll make our own renderer to skip this editorvar 
	specialElementHandlers = {	'#report_map': function(element, renderer){		return true;	} ,'#pie_holder':function(element,renderer){return true;} };
	// All units are in the set measurement for the document
	// This can be changed to "pt" (points), "mm" (Default), "cm", "in"

	doc.fromHTML(jQuery('#report_wrap').get(0), 15, 15, {	'width': 170, 	'elementHandlers': specialElementHandlers});
	doc.save( jQuery('#report_title').html() +  '.pdf');
	return false;
});

//report head adjustment
jQuery('#type').change(function(){
    jQuery('.dte'  ).hide();
    jQuery('.' + jQuery(this).val() ).show();
});


function updateChildMonths(){
    
    jQuery('.year_month').hide();
    jQuery('.' + jQuery('#year').val()).show();

}

jQuery('#year').change(function(){
    
    updateChildMonths();

});


jQuery(document).ready(function(){
	updateChildMonths();
});