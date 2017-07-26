<?php
class TFSP_purger{

    // Curl call to Nginx cache localhost server block
    private function _call($url){
        $curl = curl_init();

        curl_setopt ($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec ($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close ($curl);
        //Flush the object cache as well
        wp_cache_flush();

        switch ($httpcode){
            case"200":
                return array(true, "Cleared the cache successfully");
            break;
            case "404":
                return array(false, "Unable to find page in cache");
            break;
            default:
                return array(false, "An error occurred");
        }
    }

    // Purge url list endpoint. accepts list of URLs
    public function process_url_list($urls){
        // for url in urls, format and purge url
        $domain = TFSP_HOME_DOMAIN;
        $static_endpoint = "http://127.0.0.1/purge_url/$domain/";
        foreach ($urls as $cache_url){
            $cache_url = str_replace("https://", "", $cache_url);
            $cache_url = str_replace("http://", "", $cache_url);
            $formatted_url = trailingslashit($cache_url);
            //process mobile
            $mobile_response = $this->_call("$static_endpoint$formatted_url"."1");
            $final_response = $this->_call("$static_endpoint$formatted_url"."0");
        }
        // Return the last value received, to deal with manual single url purges.
        return $final_response;
    }

    // Purge site cache endpoint
    public function process_site_purge(){
        // for url in urls, format and purge url
        $domain = TFSP_HOME_DOMAIN;
        $static_endpoint = "http://127.0.0.1/purge_site/$domain";
        return $this->_call(trailingslashit($static_endpoint));
    }

    // Following functions build url list from action type and whatver id is
    //passed from do_action, then calls endpoint

    public function clear_cache_from_post_id($_id){
        $urls = array();
        array_push($urls, get_permalink($_id));
        array_push($urls, get_home_url());

        //Clear Posts if page is set as blog home
        $page_for_posts = get_option( 'page_for_posts' );
        if(isset($page_for_posts) && is_numeric($page_for_posts)){
          array_push($urls, get_permalink($page_for_posts));
        }

        $urls = array_merge($urls,  $this ->get_related_terms_urls($_id));
        $this->process_url_list($urls);
    }

    public function clear_cache_from_comment_id($_id){
        $urls = array();
        $output = get_comment( $_id );
        array_push($urls, get_permalink($output->comment_post_ID));
        array_push($urls, get_home_url());

        //Clear Posts if page is set as blog home
        $page_for_posts = get_option( 'page_for_posts' );
        if(isset($page_for_posts) && is_numeric($page_for_posts)){
          array_push($urls, get_permalink($page_for_posts));
        }

        $related_urls = $this ->get_related_terms_urls($output->comment_post_ID);
        $urls = array_merge($urls, $related_urls);
        $this->process_url_list($urls);
    }

    public function clear_cache_from_term_id($_id){
            $urls = array();
            array_push($urls, get_category_link( $_id ));
            array_push($urls, get_home_url());

            //Clear Posts if page is set as blog home
            $page_for_posts = get_option( 'page_for_posts' );
            if(isset($page_for_posts) && is_numeric($page_for_posts)){
              array_push($urls, get_permalink($page_for_posts));
            }

            $this->process_url_list($urls);
    }

    public function clear_cache_from_menu_update(){
        $this -> process_site_purge();
    }

    // Helper function to get all related URLS that need flushing.
    public function get_related_terms_urls($_id){
        $terms_urls = array();
        $taxonomies = get_post_taxonomies( $_id );
        foreach ($taxonomies as $taxonomy){
            $terms = wp_get_post_terms( $_id, $taxonomy);
            foreach ($terms as $term){
                array_push($terms_urls, get_category_link( $term -> term_id ));
            }
        }
        return $terms_urls;
    }
}
