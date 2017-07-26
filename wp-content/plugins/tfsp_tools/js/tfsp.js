// since wp 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
function tfsp_function_call(params, action, callback){
    charset = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    url = ajaxurl;
    standard_params = {
       'action': 'tfsp_handle_postback',
       'function': action,
    };
    
    if (url.indexOf("?") > -1){
        url = url + "&_nocache=";
    }
    else{
        url = url + "?_nocache=";
    }
    for(i = 0; i < 10; i++) {
        url += charset.charAt(Math.floor(Math.random() * charset.length));
    }    
    
    for (var attribute in standard_params) { 
        params[attribute] = standard_params[attribute]; 
    }
    
    // Lock window
    jQuery('#tfsp_loader').addClass("tfsp_loading");

    jQuery.post(url, params, function(response) {
         // Unlock window 
         jQuery('#tfsp_loader').removeClass("tfsp_loading");      
        // Trigger callback  
        var fn = window[callback];
        if(typeof fn === 'function') {
            fn(response);
        }       
    }).error(function() {
        // Unlock window 
         jQuery('#tfsp_loader').removeClass("tfsp_loading");      
        // Trigger callback - return 0 due to timeout.
        var fn = window[callback];
        if(typeof fn === 'function') {
            fn(0);
        }
    });
}

// Function to add response to WordPress style message
// Element IDs namespaced to avoid collision
function tfsp_show_nginx_response(response){
    response = JSON.parse(response);
    jQuery('#tfsp_response_output').html(response.message);
    jQuery('#nginx_status_button').html(response.param == 1 ? 'Disable' : 'Enable');
    jQuery('#nginx_cache_status_submit').attr('data-status', response.param== 1 ? '0' : '1');
    jQuery('#nginx_status_display').html(response.param == 1 ? 'Enabled' : 'Disabled');

}



// Function to add response to WordPress style message
// Element IDs namespaced to avoid collision
function tfsp_show_response(response){
    jQuery('#tfsp_response_output').html(response);
}

// Click handler for dynamically added WordPress message
// Click handlers namespaced to avoid collision

jQuery(document).ready(function(){
    jQuery(document).on('click', '.tfsp-notice-dismiss', function(){
        jQuery('#tfsp_response_output').html('');
    });
});