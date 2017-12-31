//INI
var oTable;
var wpRankieChecked = Array(); // contains index of checked pages
var updateIndex = 0;

//Load the Visualization API and the piechart package.
google.load('visualization', '1.0', {'packages':['corechart']});

// Callback that creates and populates a data table, 
// instantiates the pie chart, passes in the data and
// draws it.
function drawChart() {

// Create the data table.
var data = google.visualization.arrayToDataTable([
	                                                  ['Rank', 'Ranking History'],
	                                                  ['29',   1],
	                                                  ['30',   2],
	                                                  ['31',   4],
	                                                  ['32',   60]
	                                             ]);

// Set chart options
var options = { title :'How Much Pizza I Ate Last Night',
                  width: 500, height: 400,
        vAxis: { direction:-1, viewWindowMode:"pretty"}};

// Instantiate and draw our chart, passing in some options.
var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
chart.draw(data, options);

}


// DIALOG POP
jQuery('.add-new-h2').click(function() {
	
	//ini dialog
	jQuery('#wp-rankie-group-select option:first-child').attr('selected','selected');
	
	jQuery('#wp-rankie-keywords-add').val('');
	
	var rankieDialog = jQuery('#keywordsDialog').dialog( {position: {
        my: "center", 
        at: "center",
        of:window
}}  );
	 
	 
	
	rankieDialog.dialog('open');
	
	jQuery('#wp-rankie-keywords-add').focus();
	
	jQuery('#wp-rankie-group-select').trigger('change');
	
});

// DIALOG NEW GROUP
jQuery('#wp-rankie-group-select').change(function(){
    if( jQuery('#wp-rankie-group-select').val() == 'wp-rankie-group-new' ){
        jQuery('#wp-rankie-group-new-text').show();
        jQuery('#wp-rankie-group-new-text').focus();
    }else{
        jQuery('#wp-rankie-group-new-text').hide();
    }
});

// DIALOG KEYWORD ADD
jQuery('#wp-rankie-keywords-add-btn').click(function() {
	
	var newKeywordsText = jQuery('#wp-rankie-keywords-add').val();
	var newKeywordsArr = newKeywordsText.split('\n');
	var wpRankieSite = jQuery('#wp-rankie-keywords-site').val().trim();
	var wpRankieSelectedGroup = jQuery('#wp-rankie-group-select').val();
	
	//handling host url
	if( jQuery( 'input[name="matchType"]:checked').val()  == 'domain'){
		
	 link=wpRankieSite.replace('http://','');
	 link=link.replace('https://','');
	 link=link.replace(  /^www\./,'');
	 link='http://' + link;
	 
	 var hostnamelink = jQuery('<a>').prop('href',  link).prop('hostname');
	 wpRankieSite = jQuery.trim( hostnamelink.replace(  /^www\./,'') );
	 
	}
	 
	 //add this url to the url list if not found
	 if(jQuery('#wp-rankie-select-site option[value="'+ wpRankieSite +'"]').length == 0 ){
		 jQuery('#wp-rankie-select-site').append('<option value="' + wpRankieSite + '">' + wpRankieSite +'</option> ' ) ;
	 }

	
	//handling group
	if(jQuery('#wp-rankie-group-select').val() == 'wp-rankie-group-new'){
	    wpRankieSelectedGroup = jQuery('#wp-rankie-group-new-text').val();
	    
	    //add this group to the list
	    jQuery('#wp-rankie-group-select').prepend('<option value="' + wpRankieSelectedGroup + '">' + wpRankieSelectedGroup +'</option> ' ) ;
	    jQuery('#wp-rankie-group').append('<option value="' + wpRankieSelectedGroup + '">' + wpRankieSelectedGroup +'</option> ' ) ;
	    
	}
	
	jQuery('.spinner-btn-add').show().addClass('is-active');
	
	//sending ajax with add request 
	jQuery.ajax({
        url: ajaxurl,
        type: 'POST',

        data: {
            action: 'wp_rankie_add_keywords',
            keywords: newKeywordsText,
            site: wpRankieSite,
            group: wpRankieSelectedGroup
            
        },
        
        success:function(data){
        	console.log(data);
        	
        	jQuery('.spinner-btn-add').hide();
        	
        	 var res = jQuery.parseJSON(data);
        	 jQuery(res).each(function(index,val){
        		 console.log(' keyword with id '+ val['id'] +' '+val['keyword']);
        		 oTable.dataTable().fnAddData( [   '<input type="checkbox" class="wp-rankie-keyword-id"  value="' + val['id'] +'" name="post[]" id="cb-select-' + val['id'] +'">' 
        		                                 , '<span class="wp-rankie-keyword-text">' +  val['keyword'] + '</span>' 
        		                                 ,  '-' 
        		                                 , '<span class="wp-rankie-keyword-site" >' + wpRankieSite + '</span>'
        		                                 , '<div class="spinner spinner-'+val['id']+'" style="display: none;"></div><a class="wp-rankie-update-row" href="#"><div class="updatedz updated-'+ val['id'] +' dashicons dashicons-clock"></div></a>'
        		                                 , '<a  class="wp-rankie-delete-row"  href="#"><div  class="dashicons dashicons-no-alt"></div></a>'
        		                                 , 'Manual'
        		                                 ,  wpRankieSelectedGroup 
        		                                 ,  '<input type="hidden" class="wp-rankie-updated" value="1" />' 
        		                                 ]  , false);
        		 
        		 //incrementing counts 
        		 totalCount = totalCount+1;
        		 manualCount = manualCount + 1 ;
        		 
        		 
        		 
        	 });
        	 
        	 //redraw
        	  oTable.fnDraw();
        	
        	jQuery('#keywordsDialog').dialog('close');
        	jQuery('#totalCount').text( '(' + totalCount + ')' );
        	jQuery('#manualCount').text( '(' + manualCount + ')' );
        	
        	//clickers
   		 	wpRankieUpdateClick();
        	
        }
    });
	
	jQuery(newKeywordsArr).each(function(index, val) {
		if (jQuery.trim(val) != '') {
			
			
			
		}

	});
	
	

	return false;

});

// DELETE KEYWORD FUNCTION
function wpRankieDeleteKeyword( keyIndex ){
	oTable.dataTable().fnDeleteRow(keyIndex,null , false);
}

// CHANGE KEYWORD GROUP FUNCTIN
function wpRankieChangeKeyword( keyIndex ,newGroup){
	 oTable.fnUpdate (    newGroup     ,  keyIndex  , 7 , false);
}

//DELETE ROW BUTTON
jQuery(document).on('click', '.wp-rankie-delete-row' , function() {
	
	var deleteIndex=oTable.fnGetPosition( jQuery(this).parent().parent()[0] );
	
	console.log('deleteIndex:' + deleteIndex);
	
	jQuery(this).parent().parent().css('background-color','red');
	
	
	jQuery(this).parent().parent().fadeOut('slow',function(){
		
		wpRankieDeleteKeyword( deleteIndex );
		oTable.fnDraw();
		
	});
	
	  
	
	//ajax call to delete
	
	jQuery.ajax({
        url: ajaxurl,
        type: 'POST',

        data: {
            action: 'wp_rankie_delete_keywords',
            ids: jQuery(this).parent().parent().find('.wp-rankie-keyword-id').val()
            
            
        }
	});
	
	return false;
	
});

// BULK ACTIONS
jQuery('.action').unbind('click');
jQuery('.action').click( function(){
	
 
var selectedIds = '' ;
var deleteIndex =0;
var selectVal = jQuery('.action_select').val();

selectedLength = jQuery('.wp-rankie-keyword-id:checked').length;
 
//Trash
if( selectVal == 'trash' ){

	jQuery('.wp-rankie-keyword-id:checked').each(function(){
	    selectedIds = selectedIds + ',' + jQuery(this).val();
	    
	    deleteIndex=oTable.fnGetPosition( jQuery(this).parent().parent()[0] );
	   
	    console.log(deleteIndex);
	    
	    wpRankieDeleteKeyword(deleteIndex);
	    
	    jQuery('#cb-select-' + jQuery(this).val() ).parent().parent().remove();
	     
	    
	 });
	 
	 	//ajax call to delete
	    jQuery('.spinner-bulk').show().addClass('is-active');
		
	    jQuery.ajax({
	     url: ajaxurl,
	     type: 'POST',
	
	     data: {
	         action: 'wp_rankie_delete_keywords',
	         ids:  selectedIds
	         
	         
	     },
	     success: function(){
	     	jQuery('.spinner-bulk').hide();
	     	 alert( selectedLength + ' keys deleted' );
	     }
		});
	 
	 oTable.fnDraw();

	 
//CHANGE GROUP	 
}else if(  selectVal == 'change' ){
	
	jQuery('#wp-rankie-group-select-change option:first-child').attr('selected','selected');
	
	
	var rankieDialog = jQuery('#groupChange').dialog( {position: {
        my: "center", 
        at: "center",
        of:window
	}}  );
	
	
	
	rankieDialog.dialog('open');
	 
}

jQuery('select[name="action"]').val('-1');

console.log(selectedIds);

return false;

});

// CHANGE GROUP BUTTON CLICKED
jQuery('#wp-rankie-keywords-change-btn').click(function(){
	
	var selectedIds = '' ;
	
	//Group name
	var newGroup = jQuery('#wp-rankie-group-select-change').val();
	
	if( newGroup == 'wp-rankie-group-new-change'){
		
		newGroup = jQuery('#wp-rankie-group-new-text-change').val();
		
		if(newGroup == ''){
		
			newGroup = jQuery('#wp-rankie-group-select-change').val();

		}else{
			
			//add new group
			 jQuery('#wp-rankie-group-select').prepend('<option value="' + newGroup + '">' + newGroup +'</option> ' ) ;
			 jQuery('#wp-rankie-group').append('<option value="' + newGroup + '">' + newGroup +'</option> ' ) ;
			   
		}
		
	}
	
	jQuery('.wp-rankie-keyword-id:checked').each(function(){
		 
		selectedIds = selectedIds + ',' + jQuery(this).val();
	    
	    changeIndex=oTable.fnGetPosition( jQuery(this).parent().parent()[0] );

	    wpRankieChangeKeyword(changeIndex,newGroup);
	    
	    //jQuery('#cb-select-' + jQuery(this).val() ).parent().parent().remove();
	     
	    
	 });
	
	jQuery('#groupChange').dialog('close');
	jQuery('.wp-rankie-keyword-id').attr('checked', false); 
	
 
 	//ajax call to change group
    jQuery('.spinner-bulk').show().addClass('is-active');
	
    jQuery.ajax({
     url: ajaxurl,
     type: 'POST',

     data: {
         action: 'wp_rankie_change_keywords',
         ids:  selectedIds,
         group: newGroup
         
     },
     success: function(){
     	jQuery('.spinner-bulk').hide();
     	alert( selectedLength + ' keys changed' );
     	
     }
	});
 
   oTable.fnDraw();

	
	return false;
});

// CHANGE GROUP BOX NEW GROUP
jQuery('#wp-rankie-group-select-change').change(function(){
    if( jQuery('#wp-rankie-group-select-change').val() == 'wp-rankie-group-new-change' ){
        jQuery('#wp-rankie-group-new-text-change').show();
        jQuery('#wp-rankie-group-new-text-change').focus();
    }else{
        jQuery('#wp-rankie-group-new-text-change').hide();
    }
});

// CHANGE GROUP SELECT TRIGGER
jQuery('.action_select').change(function(){
	if(jQuery(this).val() == 'change'){
		
		if(jQuery('.wp-rankie-keyword-id:checked').length != 0){
			jQuery('.action').trigger('click');		
		}
		
	}
});


// FILTER TYPE : AUTO MANUAL ALL
jQuery('.subsubsub li a').click(function() {
	jQuery('.subsubsub li a').removeClass('current');
	jQuery(this).addClass('current');

	if (jQuery(this).parent().hasClass('all')) {
		oTable.fnFilter('', 6);
	} else if (jQuery(this).parent().hasClass('auto')) {
		oTable.fnFilter('auto', 6);
	} else if (jQuery(this).parent().hasClass('manual')) {
		oTable.fnFilter('manual', 6);
	}

	return false;
});

// FILTER BY SITE
jQuery('#wp-rankie-select-site').change(function() {
	if (jQuery(this).val() == 'all') {
		oTable.fnFilter('', 3);
	} else {
		oTable.fnFilter(jQuery(this).val(), 3);
	}
});

// FILTER BY GROUP
jQuery('#wp-rankie-group').change(function() {
	if (jQuery(this).val() == 'all') {
		oTable.fnFilter('', 7);
	} else {
		oTable.fnFilter( '^' + jQuery(this).val() + '$' , 7 ,true);
	}
});

// SEARCH KEYWORDS

// function filter by search
function wpRankieSearch() {
	oTable.fnFilter(jQuery('#post-search-input').val(), 1);
}

// text chagne
jQuery('#post-search-input').change(function() {
	wpRankieSearch();
});

// key up
jQuery('#post-search-input').keyup(function() {
	wpRankieSearch();
});

// button click
jQuery('#search-submit').click(function() {
	wpRankieSearch();
	return false;
});



//UPDATE RANK
function wpRankieLinkExists( link , links ){

    link=link.replace('http://','');
    link=link.replace(  /^www\./,'');
    
    link='http://' + link;

     var hostnamelink = jQuery('<a>').prop('href',  link).prop('hostname');
     link = jQuery.trim( hostnamelink.replace(  /^www\./,'') );
     
     console.log( link );

        var i  ;
      for ( i = 0 ; i < links.length ; i++){
         
         val=links[i];
      
        console.log(val['unescapedUrl']);
       
       var hostname = jQuery('<a>').prop('href',  val['unescapedUrl']).prop('hostname');
       hostname = jQuery.trim( hostname.replace(  /^www\./,'') );
        
        console.log(hostname);
      
       if(link == hostname ){
        console.log('found:' + val['unescapedUrl'] );
         return Array(i + 1 , val['unescapedUrl'] ) ;
       } 
     
     } 
     
     return false;
}


function wpRankieGoogle(itemId , itemText , itemSite , searchIndex,itemTd ,fnUpdateRank ){

   jQuery('.spinner-'+ itemId ).show().addClass('is-active');
   jQuery('.updated-'+ itemId ).hide();
  
  jQuery.ajax({
      url: '//ajax.googleapis.com/ajax/services/search/web?v=1.0&rsz=8&start='+searchIndex * 8 +'&q='+itemText + googleL ,
      type:"GET",
      dataType: 'jsonp',
     
      async:'true',
      success:function (data) {
      
      jQuery('.spinner-'+ itemId ).hide();
      jQuery('.updated-'+ itemId ).show();
       
       if( data['responseData']  ){
            //check the links if contains the data 
            if(data['responseData']['cursor']){
                //found results finally check if our link exists in them 
               var rankieExists=wpRankieLinkExists( itemSite , data['responseData']['results'] ) ;
               if( rankieExists  ){
            	   	
            	   	//update icon
            	    jQuery('.updated-'+ itemId ).removeClass('dashicons-clock').addClass('dashicons-yes');
            	   	
                    console.log(searchIndex * 8  +  rankieExists);
                    fnUpdateRank (itemTd, itemId ,  searchIndex * 8  +  rankieExists[0] , rankieExists[1]  )  ;
               }else{
                    console.log('not found in index:' + searchIndex);
                    
                    //may be another index ? 
                    console.log(data['responseData']['results'].length);
                    wpRankieChecked[itemId] = searchIndex + 1;
                    searchIndex=searchIndex + 1 ;
                    
                    if(searchIndex < data['responseData']['results'].length ){
                    	 wpRankieGoogle(itemId , itemText , itemSite , searchIndex , itemTd , fnUpdateRank )  ;
                    }else{
                    	jQuery('.updated-'+ itemId ).removeClass('dashicons-clock').addClass('dashicons-yes');
                        fnUpdateRank(itemTd,itemId , 0 , '-');
                    }
                    
                    
               }
                
            }else{ //hint not covered 
                console.log('No results forund for this keyword');
                return false;
            }
            
            
       }else{
        console.log('responseData not found in result');
        return false;
       }
       
       if(data.length != 0){
            //valid json returned check if result data contains
             console.log( data['cursor'] );
            
            
       } else {
           console.log('Not json');
       }
    
     },
      error: function (request, status, error) {
        alert(request.responseText);
    }
  }); 
    
}

//Whatsmyserp processor
function wpRankieGoogle2(itemId , itemText , itemSite , searchIndex , itemTd ,fnUpdateRank ){

	jQuery('.spinner-'+ itemId ).show().addClass('is-active');
	jQuery('.updated-'+ itemId ).hide();
	
	
	
	//ajax call to get rank 
	jQuery.ajax({
        
    	url: ajaxurl,
        type: 'POST',

        data: {
        	
            action: 'wp_rankie_fetch_rank',
            itm: itemId,
             
            
        },
        success: function(data){
        	
        	 jQuery('.spinner-'+ itemId ).hide();
             jQuery('.updated-'+ itemId ).show();
             jQuery('.updated-'+ itemId ).removeClass('dashicons-clock').addClass('dashicons-yes');
             
             var res = jQuery.parseJSON(data);
             
             
             
             if(res['status'] == 'success'){
            	 
            	 fnUpdateRank( itemTd , res['id'] , res['rank'] , res['link'] );
             }
             
             jQuery('.wp_rankie_last_log').html(res['lastLog']);
             
        	
        }
    });

}

//update UI Rank
function updateUIRank(itemTd , rankId,rankNum , rankUrl){
	 
    console.log('Final rank for id = '+ rankId + ' is ' + rankNum ); 
    
    itmPosition = oTable.fnGetPosition( itemTd ) ;
    
    itmRowIndex = itmPosition[0];
    itmCoulmnIndex = itmPosition[1];
    
    
    oTable.fnUpdate (    rankNum     ,  itmRowIndex  , 2 , false);
    oTable.fnUpdate ( '<input type="hidden" class="wp-rankie-updated" value="0" />'   , itmRowIndex , 8 , false );
    
    if(googleMethod == 'ajax'){
	    jQuery.ajax({
	           url: ajaxurl,
	           type: 'POST',
	
	           data: {
	               action: 'wp_rankie_update_rank',
	               rank: rankNum,
	               itm: rankId,
	               url: rankUrl
	               
	           }	        
	    });
	}

}

//update click
function  wpRankieUpdateClick(){
	
	var nNodes = oTable.fnGetNodes( );

	
	jQuery(nNodes).find('.wp-rankie-update-row').unbind('click');
	jQuery(nNodes).find('.wp-rankie-update-row').click( function() {
	
	  var itemId = (jQuery(this).parent().parent().find('.wp-rankie-keyword-id').val());
	  var itemSite = (jQuery(this).parent().parent().find('.wp-rankie-keyword-site').text());
	  var itemText = (jQuery(this).parent().parent().find('.wp-rankie-keyword-text').text());
	  var itemTd = jQuery(this).parent()[0];
	 
	  console.log( 'id:'+itemId);
	  console.log( 'itemSite:'+itemSite);
	  console.log( 'itemText:'+itemText);
	  
	  
	   //loop on google from last index
	  	var searchIndex=0;
	  
	  	if (! wpRankieChecked[itemId] ){
		    wpRankieChecked[itemId] = 0;
		}else{
			searchIndex=wpRankieChecked[itemId];
		}
	   
	  	console.log('searchIndex:' + searchIndex);
	    
	  	 if( googleMethod == 'ajax'){
	  		 
	  		 if(  searchIndex < 8){
	  			 wpRankieGoogle(itemId,itemText,itemSite,searchIndex,itemTd, updateUIRank);
	  		 }else{
	  			 console.log('reached end of search');
	  		 }
		     
	     
	  	 }else  {
	  		 
	  		 console.log('Method:server side');
	  		 wpRankieGoogle2(itemId,itemText,itemSite,searchIndex,itemTd, updateUIRank );
	  		 
	  	 }
	  	 
	  	 return false;
	 
	});

}

//function fetched rank updater 


//update all waiting function
function wpRankieUpateAll(){
	
	updateIndex++;
	//console.log('updateIndex:' + updateIndex);

	if( updateIndex % 360 == 0){
	
		updateIndex = 1;

	}else{
		jQuery('.next_key').text( 360 - updateIndex );
		t = setTimeout("wpRankieUpateAll()", 1000);
		return;
	}
	
	spinCount = jQuery(oTable.fnGetNodes( )).find('.spinner:visible').length;

	if( spinCount < 1 ) {
	    
		jQuery('.wp-rankie-updated[value = "1"]:first').parent().parent().find('td .spinner:hidden').parent().find('.wp-rankie-update-row').trigger('click');
		
	}
	
	t = setTimeout("wpRankieUpateAll()", 1000);

}


//tabs clicker
jQuery('.category-tabs li').click(function(){
    jQuery('.category-tabs li').removeClass('tabs');
    jQuery(this).addClass('tabs');
    
    jQuery('.categorydiv .tabs-panel').hide();
    jQuery('.categorydiv .tabs-panel').eq(jQuery(this).index()).show();
    
    return false;
    
});

 

// DOC READY
jQuery(document).ready(function() {
	
	//SELECT 
	 jQuery('#cb-select-all-1').click(function(){
		    
	     if (jQuery(this).attr('checked') == 'checked') {
	                 jQuery('.wp-rankie-keyword-id').removeAttr('checked');
	             jQuery('.wp-rankie-keyword-id:visible').attr('checked', 'true');   

	      }else{

	        jQuery('.wp-rankie-keyword-id:visible').removeAttr('checked');
	      }
	 
	});
	

	// DIALOG INI
	jQuery('#keywordsDialog').dialog({
		autoOpen : false,
		dialogClass : 'wp-dialog',
		position : 'center',
		draggable : false,
		width : 400,
		title : 'Add New Keywords'
	});


	jQuery('#groupChange').dialog({
		autoOpen : false,
		dialogClass : 'wp-dialog',
		position : 'center',
		draggable : false,
		width : 400,
		title : 'Change Keywords Group'
	});
	
	// Rank Sorting 
	jQuery.fn.dataTableExt.oSort['mystring-asc'] = function(x,y) {
	
		var retVal;
		x = jQuery.trim(x);
		y = jQuery.trim(y);

		console.log(x);
		console.log(y);
		
		if (x==y) retVal= 0;
		else if (x == "0" || x == " ") retVal= 1;
		else if (y == "0" || y == " ") retVal= -1;
		else if (x > y) retVal= 1;
		else retVal = -1; // y) retVal= 1;
		return retVal;
		 
	}
	
	jQuery.fn.dataTableExt.oSort['mystring-desc'] = function(y,x) { 
			if (x==y) return 0;
			if (x == "0") return -1;
			if (y == "0") return 1;
			if (x > y) return 1;	
	}
	
	jQuery.fn.dataTableExt.oSort['NumericOrBlank-asc'] = function(x,y) {
			var retVal;
			
			if(x == 0) return 1;
			
			if (y = parseFloat(jQuery.trim(y).replace(/,/g,''))) {
				x = (x = parseFloat(jQuery.trim(x).replace(/,/g,''))) ? x : 0;
				if (x==y) retVal= 0; 
				else retVal = (x>y) ? 1 : -1;
			} else {
				retVal = -1;
			}
			return retVal;
			}
			jQuery.fn.dataTableExt.oSort['NumericOrBlank-desc'] = function(y,x) {
			var retVal;
			x = (x = parseFloat(jQuery.trim(x).replace(/,/g,''))) ? x : 0;
			y = (y = parseFloat(jQuery.trim(y).replace(/,/g,''))) ? y : 0;
			if (x==y) retVal= 0; 
			else retVal = (x>y) ? 1 : -1; 
			return retVal;
	}
	
	// DATA TABLE INI
	oTable = jQuery('#rankie-keywords').dataTable({
		"sScrollY": sScroll,
		"bPaginate": false,
		"oLanguage" : {
			"oPaginate" : {
				"sNext" : "›",
				"sPrevious" : "‹"
			}
		},

		"bFilter" : true,
		"iDisplayLength" : 7 ,
		
		"aoColumnDefs": [
	          { "sType": "NumericOrBlank", "aTargets": [ 2 ] }
	        ]
		

	});

	wpRankieUpdateClick();
	
	wpRankieUpateAll();
	
	//GET CHART
	
	jQuery('#rankie-keywords_wrapper').on('click','.wp-rankie-keyword-text', function(){
	
	//jQuery('.wp-rankie-keyword-text').click(function(){
	    
	    var itemId =jQuery(this).parent().parent().find('.wp-rankie-keyword-id').val() ;
	    var itemSite = jQuery(this).parent().parent().find('.wp-rankie-keyword-site').text() ;
	    var itemKeyword = jQuery(this).text();
	    
	    jQuery('.wp-rakie-chart-site').text( itemSite );
	    jQuery('.wp-rakie-chart-keyword').text( itemKeyword );
	    
	    jQuery('#chart_div').html('<div style="display:block" class="spinner is-active"></div>');
	    
	    jQuery.ajax({
			            url: ajaxurl,
			            type: 'POST',
		
			            data: {
			                action: 'wp_rankie_get_rank',
			                itm: itemId
			                
			            },
			            success: function(data){
			                
			                  var res = jQuery.parseJSON(data);
			                  
			                  // Create the data table.
			                  var data = google.visualization.arrayToDataTable(res[0]);

			                  // Set chart options
			                  jQuery('#category-tabs li:first-child').trigger('click');
			                  var options = {	
			                		  			title: "- Ranking Records of (" + itemSite + ") for (" + itemKeyword + ")" ,
			                                    width: jQuery('#chart_div').width() - 10 , height: 190,
			                          vAxis: { title:"Rank" ,direction:-1, viewWindowMode:"pretty"},
			                          titleTextStyle : {bold:false},
			                          hAxis:{title : "Change date"}
			                  
			                  };

			                  // Instantiate and draw our chart, passing in some options.
			                  var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
			                  chart.draw(data, options);

			                  jQuery('#wp-rakie-chart tbody tr').remove();
			                  
			                  jQuery(res[1]).each(function(index,val){
			                	  if (val[0] != 'Rank'){
			                		  jQuery('#wp-rakie-chart tbody ').append( '<tr><td>' + val[0] + '</td> <td>' + val[1] + '</td> <td><a href="' + val[2] + '">' + val[2] + '</a></td> </tr>' ) ;
			                	  }
			                  });
			                  
			            }
			        });
	    
	});
});