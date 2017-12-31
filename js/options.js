jQuery(document).ready(
    function () {
         
        jQuery('#field1zz').change(function () {
            jQuery('#field-wp_keyword_tool_alphabets').text(alllangs[jQuery(this).val()]);
        });
    }
);