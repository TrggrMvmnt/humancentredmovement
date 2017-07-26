<?php

// API method class. Not namespaced function names as only instantiated in Cache_dev_tools
class TFSP_Tools_Functions{

    public function clear_nginx_cache_url(){
        $purger = new TFSP_Purger();
        $urls = array($_POST['cache_url']);
        $response = $purger->process_url_list($urls);
        if ($response[0] == false){
            $response[1] = "Unable to find the specified URL in cache.";
        }
        return $response;
    }

    public function clear_nginx_cache_site(){
        $purger = new TFSP_Purger();
        $domain = TFSP_HOME_DOMAIN;
        $domain_url = "http://127.0.0.1/purge_site/$domain";
        return($purger->process_site_purge($domain_url));
    }

    public function enable_lets_encrypt(){
        $response = $this->call_api(array(
            "domain" => TFSP_HOME_DOMAIN,
            "command" => "certificate",
            "subcommand" => "order_lets_encrypt"
        ));
        $response = json_decode($response, true);
        return array($response['success'], $response['message']);
    }

    public function set_nginx_cache_status(){
        $new_status = $_POST['status'];
        $response = $this->call_api(array(
            "domain" => TFSP_HOME_DOMAIN,
            "command" => "cache",
            "subcommand" => "set_status",
            "status" => ($new_status == 0 ? 'disabled' : 'enabled')
        ));
        $response = json_decode($response, true);
        // format to response handler
        return array($response['success'], $response['message'], $response['status']);
    }

    public function get_nginx_cache_status($json=true){
        $response = $this->call_api(array(
            "domain" => TFSP_HOME_DOMAIN,
            "command" => "cache",
            "subcommand" => "get_status"
        ));
        if (!$json){
            $response = json_decode($response, true);
            if (in_array("status", $response) and $response['status'] == true){
                return true;
            }
            return false;
        }

        $response = json_decode($response, true);
        $array =  array($response['success'], $response['message'], $response['status']);
        return $array;
    }
    public function get_pagespeed_status(){
        $response = $this->call_api(array(
            "domain" => TFSP_HOME_DOMAIN,
            "command" => "cache",
            "subcommand" => "get_pagespeed"
        ));

        $response = json_decode($response, true);
        if ($response['message'] == true){
            return $response['status'];
        }
        return false;
    }
    public function set_pagespeed_status(){
        $response = $this->call_api(array(
            "domain" => TFSP_HOME_DOMAIN,
            "command" => "cache",
            "subcommand" => "set_pagespeed",
             "level" => $_POST['pagespeed_level']
        ));

        $response = json_decode($response, true);
        if ($response['success'] == true){
            return $response['message'];
        }
        return false;
        $response = json_decode($response, true);
        $array =  array($response['success'], $response['message'], $response['status']);
        return $array;
    }

    public function get_letsencrypt_status(){
        $response = $this->call_api(array(
            "domain" => TFSP_HOME_DOMAIN,
            "command" => "certificate",
            "subcommand" => "lets_encrypt_status"
        ));

        $response = json_decode($response, true);
        if ($response['message'] == true){
            return true;
        }
        return false;
    }
    public function get_staging_status(){
        $response = $this->call_api(array(
            "domain" => TFSP_HOME_DOMAIN,
            "command" => "staging",
            "subcommand" => "get_status"
        ));

        $response = json_decode($response, true);
        if ($response['success'] == true){
            return true;
        }
        return false;
    }

    public function get_cloudflare_cache_status($json=true){
        $response = $this->call_api(array(
            "domain" => TFSP_HOME_DOMAIN,
            "command" => "cloudflare",
            "subcommand" => "get_details"
        ));
        if (!$json){
            $response = json_decode($response, true);
            if (isset($response['status'])){
                if( $response['status'] == true){
                    return true;
                }
            }
            return false;
        }

        $response = json_decode($response, true);
        $array =  array($response['success'], $response['message'], $response['status']);
        return $array;
    }

    public function call_api($parameters){
        // convert assoc array -> json
        $parameters = json_encode($parameters);
        // Open socket with default timeout
        $client = stream_socket_client(
            "unix:///tmp/apisocket",
            $errno, $errmsg,
            $timeout = ini_get("default_socket_timeout")
        );

        // Check socket is open
        if (!$client){
            error_log("34SP API - Socket Error Occurred: $errmsg ($errno)");
            return "An Error Occurred: $errmsg ($errno). Please Retry.";
        }
        else{
            $data = "";
            // Set timeout for data transfer
            stream_set_timeout ($client , TFSP_SOCKET_TIMEOUT);
            // Write and read 4k chunk. Limitation by design.
            fwrite($client, $parameters);
            $data = stream_get_contents($client);
            fclose($client);
            return $data;
        }
    }// End function call_api

}// End Class TFSP_Tools_Functions

?>