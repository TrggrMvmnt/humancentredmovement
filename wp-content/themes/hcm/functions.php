<?php 

add_action( 'wp_enqueue_scripts', 'salient_child_enqueue_styles');
function salient_child_enqueue_styles() {
	
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css', array('font-awesome'));

    if ( is_rtl() ) 
   		wp_enqueue_style(  'salient-rtl',  get_template_directory_uri(). '/rtl.css', array(), '1', 'screen' );
}




//Redux Default Option Name
$opt_name = "salient_redux";

/**
 * Filter hook for filtering the args. Good for child themes to override or add to the args array. Can also be used in other functions.
 * */
if ( ! function_exists( 'change_arguments' ) ) {
    function change_arguments( $args ) {
        $args['menu_title'] = esc_attr__( 'HCM Settings', 'rosematheme' );
        $args['page_title'] = esc_attr__( 'HCM Settings', 'rosematheme' );
        return $args;
    }
}

// Change Arguments
add_filter('redux/options/' . $opt_name . '/args', 'change_arguments' );



?>