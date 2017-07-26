<?php
/*
Plugin Name: 34SP.com Control Panel Tools
Description: Manage your 34SP.com options through WordPress, and control backend functionality
Version: 2.1.7
Author: 34SP.com
Author URI: https://www.34SP.com
*/
// Class of functions hooked into WP Admin. namespaced function names.
class TFSP_Tools{

    public $tfsp_banlist = array('Very Simple Splash Page','WP Staging');

    public function __construct(){
        // hacky check that this isn't being called from elswhere.
        defined( 'ABSPATH' ) or die();

        // Global parameter for socket connection in seconds:
        define("TFSP_SOCKET_TIMEOUT", 5);
        preg_match("/^\/var\/www\/vhosts\/(?P<domain>.+)\/httpdocs\/wp-content/A", __DIR__, $tfsp_domain_regexp);
        define ('TFSP_HOME_DOMAIN', $tfsp_domain_regexp['domain']);
        // include dependancies from classes dir
        foreach (glob(plugin_dir_path(__FILE__) ."classes/*.php") as $filename)
        {
            require_once $filename;
        }
        // include cli.php if using wp-cli and register command
        if( defined( 'WP_CLI' ) && WP_CLI == true )
        {
          //Require CLI if required
          $cli_path = plugin_dir_path(__FILE__) .'cli.php';
       	  require_once $cli_path;
          WP_CLI::add_command('hosting', 'Hosting');
        }

        add_action('init', array(&$this, 'backend_init'));
        add_action('admin_init', array(&$this, 'admin_init'));

        // Add WP Hooks specifically for menu page
        add_action('admin_menu', array(&$this, 'setup_developer_menu'));
        add_action( 'wp_ajax_tfsp_handle_postback', array(&$this, 'tfsp_handle_postback'));
    }// END Construct

    //Hooks associated with init.
    public function backend_init(){
        // Add nocache headers to whitelisted URLS
        add_action( 'send_headers', array(&$this, 'tfsp_nocache_headers') );

        // Add fail2ban action:
        add_action('wp_login_failed', array(&$this, 'fail2ban_login_failed'));

        // Add plugin blocker actions
        add_action( 'admin_notices', array(&$this, 'pb_admin_notice'));
        add_action('activate_plugin', array(&$this, 'check_plugin_blocklist'));

        //Insecure Password checker
        add_action( 'admin_notices', array(&$this, 'insecure_admin_notice' ));

        // Add mail fixer filter
        add_filter( 'wp_mail_from', array(&$this, 'from_mail') );

        // Disable email for updates
        add_filter( 'auto_core_update_send_email', '__return_false' );

        // Add Nginx Cache purge Actions
        $purger = new TFSP_purger();
        add_action( 'save_post', array( $purger, 'clear_cache_from_post_id') );
        add_action( 'edit_post', array( $purger, 'clear_cache_from_post_id') );
        add_action( 'delete_post', array( $purger, 'clear_cache_from_post_id') );
        add_action( 'wp_trash_post', array( $purger, 'clear_cache_from_post_id') );
        add_action( 'comment_post', array( $purger, 'clear_cache_from_comment_id') );
        add_action( 'edit_comment', array( $purger, 'clear_cache_from_comment_id') );
        add_action( 'delete_comment', array( $purger, 'clear_cache_from_comment_id') );
        add_action( 'edit_terms', array( $purger, 'clear_cache_from_term_id'));
        add_action( 'delete_term', array( $purger, 'clear_cache_from_term_id'));
        add_action( 'wp_set_comment_status', array( $purger, 'clear_cache_from_comment_id') );
        add_action( 'wp_update_nav_menu', array( $purger, 'clear_cache_from_menu_update') );

        //Remove user warning after change of password
        add_filter( 'after_password_reset', array(&$this, 'remove_insecure_warning') );

        //Trigger redirects for Multisite
        $this->redirect_multisite_admin();

    }
    //Hooks associated with admin init.
    public function admin_init(){

        //Setup scripts & headers
        add_action('admin_enqueue_scripts', array(&$this, 'setup_scripts'));
        add_action('send_headers', array(&$this, 'setup_headers'));
        add_action('admin_menu', array(&$this,'hidenag'));
        if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ){
            $functions = new TFSP_Tools_Functions();
            $staging = $functions -> get_staging_status();
            if ($staging){
                define("TFSP_STAGING", true);
            }
            elseif (!is_ssl()){
                $cert_status = $functions -> get_letsencrypt_status(false);
                if (!$cert_status){
                    add_action( 'admin_notices', array(&$this, 'letsencrypt_admin_notice' ));
                }
            }

            unset($functions);
        }

        // Register settings
        add_settings_section(
            'tfsp_update_section',
            '',
            null,
            'tfsp_update_settings'
        );

        add_settings_field(
            "tfsp_update_settings",
            "Update Schedule",
            array(&$this, 'draw_schedule_dropdown'),
            'tfsp_update_settings',
            'tfsp_update_section'
        );

        // Register settings
        add_settings_section(
            'tfsp_core_update_section',
            '',
            null,
            'tfsp_core_update_settings'
        );

        add_settings_field(
            "tfsp_core_update_settings",
            "Update Schedule",
            array(&$this, 'draw_core_schedule_dropdown'),
            'tfsp_core_update_settings',
            'tfsp_core_update_section'
        );

        // Register settings
        add_settings_section(
            'tfsp_update_notifications_section',
            '',
            null,
            'tfsp_update_notifications_settings'
        );

        add_settings_field(
            "tfsp_update_notifications_settings",
            "Update Notifications",
            array(&$this, 'draw_notifications_dropdown'),
            'tfsp_update_notifications_settings',
            'tfsp_update_notifications_section'
        );

        register_setting( 'tfsp_update_section', 'tfsp_update_schedule', array(&$this, 'update_schedule_callback'));
        register_setting( 'tfsp_core_update_section', 'tfsp_core_update_schedule', array(&$this, 'update_core_schedule_callback'));
        register_setting( 'tfsp_update_notifications_section', 'tfsp_update_notifications', array(&$this, 'update_notifications_callback'));


        /* Settings and hooks specific to Plugin Page */
        global $pagenow;
        //No point adding the filters to a screen they wont see.
        if( current_user_can('activate_plugins') && $pagenow == 'plugins.php'){
          if ( ! function_exists( 'get_plugins' ) ) {
            //Needed as it doesn't always autoload.
          	require_once ABSPATH . 'wp-admin/includes/plugin.php';
          }
          //Pray this is cached as its resource intensive
          $all_plugins = get_plugins();
          foreach($all_plugins as $key => $value){
            add_filter( 'plugin_action_links_'.$key, array(&$this,'add_plugin_links'), 10,5);
          }

          $this->update_plugin_list();
        }
    }

    public function setup_headers(){
      //Avoiding ironies
        if(is_admin()){
            header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        }
    }

    public function tfsp_nocache_headers(){
        $path = $_SERVER["REQUEST_URI"];
        $url = wp_parse_url($path);
        $url = $url['path'];
        $wl_urls = $this -> get_whitelist();

        if (is_array($wl_urls)){
            foreach ($wl_urls as $url_reg){
                if (fnmatch($url_reg, $url)){
                    header( 'X-Tfsp-Override: BYPASS' );
                }
            }
        }
    }

    public function from_mail( $email ) {
        // Use home url rather than constant,
        // to avoid sites that were built with www.
        if($email == 'wordpress@'){
            $domain = get_option('home');
            $domain = str_ireplace('www.', '', parse_url($domain, PHP_URL_HOST));
            $email .= $domain;
        }
        return $email;
    }

    public function setup_scripts($hook){
        //check if we are within our own plugin or just return
        if (!in_array($hook, array('tools_page_tfsp_cache', 'tools_page_tfsp_updates', 'tools_page_tfsp_setup'))){
            return;
        }
        // Namespaced Function to include Scripts in WP-admin
        wp_enqueue_style('TFSP_Tools_cache', plugins_url('css/cache.css', __FILE__), array(), "2.0.0", 'all');
        wp_enqueue_script( 'TFSP_Tools_global', plugins_url('js/tfsp.js', __FILE__), array(), "2.0.0", 'all');


    }

    public function setup_developer_menu(){
        //add item to WP menu
        if ( !defined( 'TFSP_STAGING' ) || !TFSP_STAGING ){
            add_submenu_page(
                'tools.php',
                'Developer Tools',
                '34SP.com Tools',
                'manage_options',
                'tfsp_cache',
                array(&$this, 'draw_cache_page')
            );

            add_submenu_page(
                'tools.php',
                'Developer Tools',
                '34SP.com Tools',
                'manage_options',
                'tfsp_setup',
                array(&$this, 'draw_setup_page')
            );
            add_action( 'admin_head', array(&$this, 'remove_menus'));
        }
        add_submenu_page(
            'tools.php',
            'Developer Tools',
            '34SP.com Tools',
            'manage_options',
            'tfsp_updates',
            array(&$this, 'draw_updates_page')
        );
    }// END public function setup_developer_submenu()

    public function remove_menus() {
        if ( !defined( 'TFSP_STAGING' ) || !TFSP_STAGING ){
            remove_submenu_page( 'tools.php', 'tfsp_updates' );
            remove_submenu_page( 'tools.php', 'tfsp_setup' );
        }
        else{
            remove_submenu_page( 'tools.php', 'tfsp_cache' );
            remove_submenu_page( 'tools.php', 'tfsp_setup' );
        }
    }

    public function letsencrypt_admin_notice(){
            echo"<div class='update-nag' >You are not using your free Let's Encrypt certificate. To complete your site setup, visit <a href='".admin_url()."tools.php?page=tfsp_setup'>34SP.com Developer Tools</a> and enable your certificate now.</div>";
    }
    public function insecure_admin_notice(){
      $user_id = get_current_user_id();
      if(get_user_meta($user_id, 'tfsp_insecure_password',true) == 1){
            echo"<div class='error notice' id='error' >
            <strong>Your password is INSECURE, please change your password immediately by <a href='".admin_url()."profile.php'>clicking here</a></strong>
<p>For more information see Helping to secure our clients passwords</p>";
      }
    }

    public function draw_cache_page(){
        // Check this is a user with privileges
        if(!current_user_can('manage_options')){
            wp_die(__('You do not have sufficient privileges.'));
        }
        // Instantiate API and get status of caches
        $functions = new TFSP_Tools_Functions();
        $cache_status = $functions -> get_nginx_cache_status(false);
        $pagespeed_status = $functions -> get_pagespeed_status();
        $cloudflare_enabled = $functions -> get_cloudflare_cache_status(false);
        $wl_urls = $this -> get_whitelist();
        include(sprintf("%s/templates/cache.php", dirname(__FILE__)));
        unset($functions);
    }// END public function draw_developer_menu()

    public function draw_setup_page(){
        // Check this is a user with privileges
        if(!current_user_can('manage_options')){
            wp_die(__('You do not have sufficient privileges.'));
        }
        if (!is_ssl()){
            $functions = new TFSP_Tools_Functions();
            $cert_status = $functions -> get_letsencrypt_status(false);
            unset($functions);
        }
        include(sprintf("%s/templates/setup.php", dirname(__FILE__)));
    }// END public function draw_developer_me

    public function draw_updates_page(){
        // Check this is a user with privileges
        if(!current_user_can('manage_options')){
            wp_die(__('You do not have sufficient privileges.'));
        }
        // Get current schedule
        $schedule = get_option('tfsp_update_schedule', '0');
        if ($schedule == '0'){
            $delay_string = "run immediately";
        }
        if ($schedule == '1'){
            $delay_string = "delay for 24 hours";
        }
        if ($schedule == '7'){
            $delay_string = "delay for 7 days";
        }
        $core_schedule = get_option('tfsp_core_update_schedule', '0');
        if ($core_schedule == '0'){
            $core_delay_string = "run immediately";
        }
        if ($core_schedule == '1'){
            $core_delay_string = "delay for 24 hours";
        }
        if ($core_schedule == '7'){
            $core_delay_string = "delay for 7 days";
        }


        include(sprintf("%s/templates/updates.php", dirname(__FILE__)));

    }// END public function draw_updates_page()

    // Helper function to create the dropdown box for plugin updates
    public function draw_schedule_dropdown(){
        $current_schedule = get_option( 'tfsp_update_schedule', '0' );
        echo '<select name="tfsp_update_schedule" id="tfsp_update_schedule">';
        echo '<option value="0"';
        if ($current_schedule == '0'){ echo ' selected="selected"'; }
        echo'>Run updates Immediately</option>';
        echo '<option value="1"';
        if ($current_schedule == '1'){ echo ' selected="selected"'; }
        echo'>Delay updates for 24 hours</option>';
        echo '<option value="7"';
        if ($current_schedule == '7'){ echo ' selected="selected"'; }
        echo'>Delay updates for 7 days</option> </select>';
    }

    // Helper function to create the dropdown box for plugin updates
    public function draw_core_schedule_dropdown(){
        $current_schedule = get_option( 'tfsp_core_update_schedule', '0' );
        echo '<select name="tfsp_core_update_schedule" id="tfsp_update_schedule">';

        echo '<option value="0"';
        if ($current_schedule == '0'){ echo ' selected="selected"'; }
        echo'>Run updates Immediately</option>';

        echo '<option value="1"';
        if ($current_schedule == '1'){ echo ' selected="selected"'; }
        echo'>Delay updates for 24 hours</option>';

        echo '<option value="7"';
        if ($current_schedule == '7'){ echo ' selected="selected"'; }
        echo'>Delay updates for 7 days</option> </select>';
    }

    // Helper function to create the dropdown box for plugin updates
    public function draw_notifications_dropdown(){
        $current_schedule = get_option( 'tfsp_update_notifications', 'admins' );
        echo '<select name="tfsp_update_notifications" id="tfsp_update_notifications">';
        echo '<option value="admins"';
        if ($current_schedule == 'admins'){ echo ' selected="selected"'; }
        echo'>Notify WordPress Administrators</option>';
        echo '<option value="contacts"';
        if ($current_schedule == 'contacts'){ echo ' selected="selected"'; }
        echo'>Notifify Site Contacts</option> </select>';
    }

    // Update Schedule setting validation and response
    public function update_schedule_callback($input) {
        if (!in_array($input, array('0', '1', '7'))){
            add_settings_error(
                'tfsp_update_schedule',
                'tfsp_update_error',
                'Please choose a schedule option',
                'error'
            );
        }
        else{
            add_settings_error(
                'tfsp_update_schedule',
                'tfsp_update_success',
                "Settings Updated",
                'updated'
            );
        }
        return $input;
    }

    // Update Schedule setting validation and response
    public function update_core_schedule_callback($input) {
        if (!in_array($input, array('0', '1', '7'))){
            add_settings_error(
                'tfsp_core_update_schedule',
                'tfsp_update_error',
                'Please choose a schedule option',
                'error'
            );
        }
        else{
            add_settings_error(
                'tfsp_core_update_schedule',
                'tfsp_update_success',
                "Settings Updated",
                'updated'
            );
        }
        return $input;
    }

    // Update Schedule setting validation and response
    public function update_notifications_callback($input) {
        if (!in_array($input, array('admins', 'contacts'))){
            add_settings_error(
                'tfsp_update_notifications',
                'tfsp_update_error',
                'Please choose a contact option',
                'error'
            );
        }
        else{
            add_settings_error(
                'tfsp_update_notifications',
                'tfsp_update_success',
                "Contact Notification Preferences Updated",
                'updated'
            );
        }
        return $input;
    }

    // Cache whitelist validation and response
    public function get_whitelist() {
       $whitelist = get_option( 'tfsp_cache_whitelist', '' );
       if ( !is_array($whitelist) ){
            $whitelist = array($whitelist);
       }
       return $whitelist;
    }

    public function set_whitelist() {
       add_option( 'tfsp_cache_whitelist', '', '' ,'yes');
       $whitelist = $_POST['whitelist_cache'];
       $new_whitelist = array();
       foreach(preg_split("/((\r?\n)|(\r\n?))/", $whitelist) as $line){
            $line=trim($line);
            $line=wp_parse_url($line);
            $new_whitelist[] = $line['path'];
       }
       update_option( 'tfsp_cache_whitelist', $new_whitelist, 'yes');
       return array("1", "Updated whitelist");

    }

    public function draw_debug_page(){
        // Check this is a user with privileges
        if(!current_user_can('manage_options')){
            wp_die(__('You do not have sufficient privileges.'));
        }
        // Instantiate API and get status of caches
        $functions = new TFSP_Tools_Functions();

        include(sprintf("%s/templates/debug.php", dirname(__FILE__)));

        // Destroy API
        unset($functions);
    }// END public function draw_debug_page()

/*AJAX request route handler*/
    public function tfsp_handle_postback(){
        // Instantiate API
        $functions = new TFSP_Tools_Functions();
        switch ($_POST['function']){
            /* Postback functions.
            each function will read any needed POST parameters individually.
            Printed return value is AJAX response.*/
            case 'enable_lets_encrypt':
                $api_response =  $functions -> enable_lets_encrypt();
            break;
            case 'clear_nginx_cache_url':
                $api_response =  $functions -> clear_nginx_cache_url();
            break;
            case 'clear_nginx_cache_site':
                $api_response =  $functions -> clear_nginx_cache_site();
            break;
            case 'set_nginx_cache_status':
                $api_response =  $functions -> set_nginx_cache_status();
            break;
            case 'get_nginx_cache_status':
                $api_response =  $functions -> get_nginx_cache_status();
            break;
            case 'set_whitelist':
                $api_response =  $this -> set_whitelist();
            break;
            case 'set_pagespeed_status':
                $api_response =  $functions -> set_pagespeed_status();
            break;
            case 'get_pagespeed_status':
                $api_response =  $functions -> get_pagespeed_status();
            break;
            default:
                $api_response =  array(false, "Unknown function call");
        }

        if ($api_response[0] == true){
            $response_type = 'updated';
        }
        else {
            $response_type = 'error';
        }
        add_settings_error(
            '',
            'api_callback_response',
            $api_response[1],
            $response_type
        );

        if (isset($_POST['json'])  && $_POST['json'] == true){
                ob_start();
                include(sprintf("%s/templates/ajax_response.php", dirname(__FILE__)));
                $message = ob_get_contents();
                ob_end_clean();
                echo json_encode(array('success'=> 1, 'message'=> $message, 'param'=> $api_response[2]));
        }
        else{
            include(sprintf("%s/templates/ajax_response.php", dirname(__FILE__)));
        }
        exit;
     }// END public function handle_postback()


    public function fail2ban_login_failed() {
        status_header(401);
    }


    public function should_block_plugin($plugin_name) {
        $banlist = $this->tfsp_banlist;
        if (in_array($plugin_name, $banlist)) {
            return true;
        }
        return false;
    }

    public function check_plugin_blocklist($plugin_file) {
        $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file);
        if ($this->should_block_plugin($plugin_data['Name'])) {
            wp_redirect( self_admin_url("plugins.php?plugin_blocked=true&plugin_name=" . urlencode($plugin_data['Name'])) );
        exit;
        }
    }

    public function pb_admin_notice() {
        global $pagenow;
        if( $pagenow == 'plugins.php' ){
            if ( current_user_can( 'install_plugins' ) ) {
                if ( isset($_GET['plugin_blocked']) ) {
                        echo '<div id="error" class="error notice is-dismissible"><p>Plugin ' . esc_attr( $_GET['plugin_name'] ) . ' cannot be activated as it is on the blocked plugin list.</div>';
                }
        }
        }
    }

    public function add_plugin_links($actions, $plugin_file )
    {
      $no_update_plugins = get_option( '34sp_no_update_plugins', array() );


      if( in_array( $plugin_file, $no_update_plugins ) ){
        $url = self_admin_url('plugins.php?plugin_updater=enable&plugin='.$plugin_file);
        return array_merge( array( 'allow_updates' => '<a href="'. wp_nonce_url( $url, 'plugin_updater' ).'">Enable Updates' ),  $actions );
      }
      else{
        $url = self_admin_url('plugins.php?plugin_updater=disable&plugin='.$plugin_file);
        return array_merge( array( 'allow_updates' => '<a href="'. wp_nonce_url( $url, 'plugin_updater' ).'">Disable Updates' ),  $actions );
      }
    }

    public function update_plugin_list()
    {

      global $pagenow;
      //Naughty Folks are naughty
      if(!current_user_can('activate_plugins') || $pagenow != 'plugins.php') return;

      //Only check for Query Var
      if(!isset($_GET['plugin_updater'])) return;

        if(check_admin_referer('plugin_updater') && isset($_GET['plugin'])){
          $plugin = $_GET['plugin'];
          //ok
          $disabled_plugins = get_option( '34sp_no_update_plugins', array());
          if($_GET['plugin_updater'] == 'enable'){
            //remove from list

            //unset($disabled_plugins[$plugin]);
            $disabled_plugins = array_diff( $disabled_plugins, array($plugin));

          }
          elseif( $_GET['plugin_updater'] == 'disable' ){
            //add to list
            $disabled_plugins[] = $plugin;

          }else {
            //that's not a good thing
            wp_die(__('Plugin Updater has no data'));
          }
          add_action('pre_current_active_plugins', array(&$this, 'update_notice'));
         return update_option( '34sp_no_update_plugins', $disabled_plugins);

        }
    }

    //@todo merge with other admin notice function
    function update_notice()
    {
      echo '<div class="updated">
                 <p>Plugin update preferences changed</p>
             </div>';
    }

    private function hidenag()
    {
      //remove the notice for a new version of WordPress is available
      remove_action( 'admin_notices', 'update_nag', 3 );
    }

    private function remove_insecure_warning($user_id, $password)
    {
      delete_user_meta($user_id,'tfsp_insecure_password');
    }

    //Map and Redirect the URL in certain scenarios
    private function redirect_multisite_admin()
    {
      global $wp;
      $url = add_query_arg($wp->query_string, '', home_url($wp->request));
      $path = parse_url($url, PHP_URL_PASS);
      if(defined('SUBDOMAIN_INSTALL')){
        if(SUBDOMAIN_INSTALL == true && $path == '/wp-admin/network/site-new.php')
        {
          //force to the correct location
          wp_redirect( '/wp/wp-admin/network/site-new.php' );
          exit;
        }
      }
      return $url;
    }

}// END CLASS TFSP_Tools

// Initialise plugin:
if(class_exists('TFSP_Tools')){
	  $TFSP_Tools = new TFSP_Tools();
}
