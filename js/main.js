 

/**
 * Doc Ready
 */
jQuery(document)
    .ready(
        function () {

            // Search button
            jQuery('#wp_keyword_tool_search_btn')
                .click(
                    function () {
                        var keyword = jQuery(
                            '#wp_keyword_tool_search_txt')
                            .val();

                        if (keyword == '') {
                            alert('Write Keyword First');
                            return false;
                        }

                        jQuery
                            .ajax({
                                url: jQuery(
                                    '#wp_keyword_tool_ajax_src')
                                    .val() + '&key=' + encodeURIComponent(keyword),
                                context: document.body,
                                success: function (data) {

                                    jQuery(
                                        '#wp_keyword_tool_ajax-loading')
                                        .addClass(
                                            'ajax-loading');
                                    jQuery(
                                        '#wp_keyword_tool_search_btn')
                                        .removeAttr(
                                            'disabled');
                                    jQuery(
                                        '#wp_keyword_tool_search_btn')
                                        .removeClass(
                                            'disabled');

                                    var res = jQuery
                                        .parseJSON(data);
                                    console.log(res);
                                    if (res['status'] == 'success') {

                                        var words = res['words'];
                                        var volume = res['volume'];

                                        for (var i = 0; i < words.length; i++) {
                                            jQuery(
                                                '#wp_keyword_tool_keywords')
                                                .append(
                                                    '<div class="wp_keyword_tool_itm "><input type="checkbox" value="' + words[i] + '"><div class="wp_keyword_tool_keyword">' + words[i] + '</div><div class="wp_keyword_tool_volume">' + volume[i] + '</div><div class="clear"></div></div>');
                                        }

                                        jQuery(
                                            '#wp_keyword_tool_body')
                                            .slideDown();

                                    } else if (res['status'] == 'Error') {
                                        var error = res['error'];
                                        jQuery(
                                            '#suggestionContain')
                                            .prepend(
                                                '<a href="#" title="error" class="box errors corners" style="margin-top: 0pt ! important;"><span class="close">&nbsp;</span>' + error + ' .</a>');
                                        activate_close();

                                    }

                                },
                                beforeSend: function () {
                                    jQuery(
                                        '#wp_keyword_tool_ajax-loading')
                                        .removeClass(
                                            'ajax-loading');
                                    jQuery(
                                        '#wp_keyword_tool_search_btn')
                                        .addClass(
                                            'disabled');
                                    jQuery(
                                        '#wp_keyword_tool_search_btn')
                                        .attr(
                                            'disabled',
                                            'disabled');

                                }

                            });

                        return false;

                    });

            // Clean Button
            jQuery('#wp_keyword_tool_clean').click(function () {
                jQuery('#wp_keyword_tool_body').slideUp();
                jQuery('#wp_keyword_tool_keywords').slideUp();
                jQuery('#wp_keyword_tool_keywords').empty();
                jQuery('#wp_keyword_tool_keywords').slideDown();

                return false;
            });

            var currentIndex = 0;
            var currentKeyword = '';
            var currentLetter = 'a';
            var currentSearch = '';
            // load more button
            jQuery('#wp_keyword_tool_more')
                .click(
                    function () {
                        currentIndex = 0;
                        var newKeyword = jQuery('#wp_keyword_tool_search_txt').val();
                        currentKeyword = newKeyword;
                        jQuery('#wp_keyword_tool_body').show();

                        letters=wp_keyword_tool_letters;
                       
                        for (currentIndex; currentIndex < letters.length; currentIndex++) {
                            currentLetter = letters[currentIndex];

                            //now let's google 
                            currentSearch = currentKeyword + ' ' + currentLetter;
                            console.log('New search:' + currentSearch);
                            
                            var gurl='http://clients1.'+ wp_keyword_tool_google +'/complete/search';
                            if (location.protocol === 'https:') {
            				    // page is secure
            					gurl='https://clients1.'+ wp_keyword_tool_google +'/complete/search';
            				}

                            jQuery.get(
                                gurl,
                                'output=json&q=' + currentSearch + '&client=firefox',
                                function (data) {
                                    var list = data[1];

                                    if (list.length == 0) {
                                        console.log('no suggestions');
                                    } else {

                                        jQuery('.wp_keyword_tool_keyword_status').html(jQuery('#wp_keyword_tool_search_txt').val());

                                        for (var i = 0; i < list.length; i++) {

                                            console.log(list[i]);

                                            jQuery('#wp_keyword_tool_keywords').append('<label class="wp_keyword_tool_itm "><input type="checkbox" value="' + list[i] + '">' + list[i] + '</label><br>');

                                            jQuery('.wp_keyword_tool_count').html(jQuery('label.wp_keyword_tool_itm').length);
                                        }
                                        //jQuery('#wp_keyword_tool_keywords').scrollTop(jQuery('#wp_keyword_tool_keywords').prop('scrollHeight')) ;
                                    }

                                },
                                'jsonp'
                            );




                        }




                    });

            // add tags button
            jQuery('#wp_keyword_tool_tag_btn')
                .click(
                    function () {

                        jQuery(
                            '.wp_keyword_tool_itm input:checked')
                            .each(
                                function () {
                                    jQuery(
                                        '#new-tag-post_tag')
                                        .val(
                                            jQuery(
                                                '#new-tag-post_tag')
                                            .val() + ',' + jQuery(
                                                this)
                                            .val());
                                    jQuery(this).attr(
                                        'checked',
                                        false);
                                });
                        
                       

                        
                        jQuery('#post_tag input.tagadd')
                            .trigger('click');
                        return false;
                    });

            // Watch Keywords btn
            jQuery('#wp_keyword_tool_density_btn')
                .click(
                    function () {
                    	
                    	var newKeys='';

                        jQuery(
                            '.wp_keyword_tool_itm input:checked')
                            .each(
                                function () {
                                    jQuery(
                                        '#wp_keyword_tool_density_head')
                                        .show();
                                   
                                    newKeys = newKeys + ',' + jQuery(this).val();
                                    
                                    jQuery(
                                        '#wp_keyword_tool_keywords_density')
                                        .append(
                                            '<div class="wp_keyword_tool_itm tagchecklist"><span><a   class="ntdelbutton">X</a></span><div class="wp_keyword_tool_keyword">' + jQuery(
                                                this)
                                            .val() + '</div><div class="wp_keyword_tool_volume">%</div><div class="clear"></div></div>');
                                    
                                    
                                    	
                                    
                                });
                        
                        removeBtn();
                        
                        
                        //send save request
                        jQuery
                        .ajax({
                            url: jQuery(
                                '#wp_keyword_tool_ajax_src')
                                .val() + '&action=tag_add&data=' + encodeURIComponent(newKeys),
                            context: document.body,
                            success: function (data) {

                            	console.log(data);

                            },
                            beforeSend: function () {
                                

                            }

                        });

                        return false;
                    });
            
            //list button
        	jQuery('#wp-keyword-tool-list-wrap').dialog({
                autoOpen: false,
                dialogClass : 'wp-dialog',
                position: 'center',
                draggable: false,
                width: 400,
                title: 'Keyword List'
            });

            jQuery('#wp_keyword_tool_list_btn').click(function(){
            	var txtList='';
            	jQuery('#wp-keyword-tool-list').text('');
            	
            	jQuery('#wp_keyword_tool_keywords input[type="checkbox"]:checked').each(function(){
            		
            		console.log( jQuery(this).val() );
            		
            		txtList = txtList  + jQuery(this).val() + '\n'; 
            	});
            	
            	console.log(txtList);
            	
            	jQuery('#wp-keyword-tool-list').text(txtList);
            
            	var keysDialog = jQuery('#wp-keyword-tool-list-wrap').dialog({position: {
                    my: "center", 
                    at: "center",
                    of: window
            	}});
            	
            	
            	keysDialog.dialog('open');
            	
            });

            // gcomplete
            jQuery("#wp_keyword_tool_search_txt").gcomplete({
                style: "default",
                effect: false,
                pan: '#wp_keyword_tool_search_txt'
            });

            /**
             * Check all and de select all check wp_keyword_tool_check
             */
            jQuery('#wp_keyword_tool_check')
                .click(
                    function () {
                        if (jQuery(this).attr('checked') == 'checked') {
                            jQuery(
                                '#wp_keyword_tool_keywords input:checkbox')
                                .attr('checked', 'true');
                        } else {
                            jQuery(
                                '#wp_keyword_tool_keywords input:checkbox')
                                .removeAttr('checked');
                        }
                    });

            /**
             * what density btn
             */
            jQuery('#wp_keyword_tool_density_info').click(
                function () {

                    if (jQuery('#wp_keyword_tool_density_info_box')
                        .css('display') == 'none') {

                        jQuery('#wp_keyword_tool_density_info_box')
                            .show();
                    } else {
                        jQuery('#wp_keyword_tool_density_info_box')
                            .hide();

                    }

                    return false;

                });
            
            
            function removeBtn(){
            //remove density keyword
            jQuery('.wp_keyword_tool_itm .ntdelbutton').click(function(){
                
            	//remove class
            	
            	var removeWord = (jQuery(this).parent().parent().find('.wp_keyword_tool_keyword').html());
                
                //remove call
                jQuery
                .ajax({
                    url: jQuery(
                        '#wp_keyword_tool_ajax_src')
                        .val() + '&action=tag_remove&data=' + encodeURIComponent(removeWord),
                    context: document.body,
                    success: function (data) {

                    	console.log(data);

                    },
                    beforeSend: function () {
                        

                    }

                });
                
                
                jQuery(this).parent().parent().fadeOut('fast').remove();

            });
            
            //remove selector class
            jQuery('.wp_keyword_tool_itm .ntdelbutton').removeClass('ntdelbutton');
            
            }//end function remove btn
            
            removeBtn();
            

        });