<?php

if ( ! defined( 'ABSPATH' ) ) exit;

include 'swift-redirect-instance.php';

if (!class_exists('SF_SwiftRedirectPublic')) {

    class SF_SwiftRedirectPublic{
        
        public $redirects;

        function __construct(){
            $this->redirects = self::get_SwiftRedirectList();
            $this->SwiftRedirect_init();
        }

        public function SwiftRedirect_init(){
            add_action('init', array($this, 'run_SwiftRedirect'));
            add_action('template_redirect', array($this, 'SwiftRedirectDetermine404'));
        }

        private static function execute_SwiftRedirect($code, $target, $protocol, $host, $path, $user_agent) : void
        {

            header('HTTP/1.1 ' . $code);
            header('Location: ' . $target, true, $code);
            header('Connection: close');
            
            global $wpdb;
            $table_name_logs = $wpdb->prefix . SWIFT_REDIRECT_LOG_LIST_TABLE;

            $redirect_log = array(
                "redirect_from" => $protocol . "://$host$path",
                "redirect_to" => $target,
                "user_agent" => $user_agent
            );
            
            $result = $wpdb->insert(
                $table_name_logs, 
                $redirect_log
            );

        }

        public function run_SwiftRedirect(){

            $protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
            $host = $_SERVER['HTTP_HOST'];
            $path = $_SERVER['REQUEST_URI'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            if(count($this->redirects) === 0){
                return;
            }
            
            $redirects_list = json_decode(wp_json_encode($this->redirects), true);

            foreach ($redirects_list as $rule) {

                $rule_object = new SF_SwiftRedirectInstance($rule);

                if(!preg_match('/[a-zA-Z]/', urldecode($path))){
                    $path = urldecode($path);
                }
                if($rule_object->is_enabled){
                    
                    $target = $rule_object->target_url;
                    if($rule_object->is_params){
                        $target .= '?' . wp_parse_url($path, PHP_URL_QUERY);
                    }

                    if($rule_object->domain == $host && $rule_object->is_regex == true){
                            
                        $pattern = '#' . str_replace('/', '\/', $rule_object->key) . '#i';
                        
                        if(preg_match($pattern, $path, $matches)){
                            self::execute_SwiftRedirect($rule_object->code, $target, $protocol, $host, $path, $user_agent);
                            $rule_object->countRedirectsIncrement();
                            die();
                        }

                    }else if($rule_object->domain == $host && $rule_object->key == $path){
                        
                        self::execute_SwiftRedirect($rule_object->code, $target, $protocol, $host, $path, $user_agent);
                        $rule_object->countRedirectsIncrement();
                        die();
                    }
                }
            }
        }

        public static function get_SwiftRedirectList(){
            global $wpdb;
            $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;

            $data = $wpdb->get_results(
                "SELECT * FROM $table_name;"
            );
            return $data;
        }

        public function SwiftRedirectDetermine404(){
            if(is_404()){
                global $wpdb;
                $table_name_404 = $wpdb->prefix . SWIFT_REDIRECT_404_LIST_TABLE;

                $host = $_SERVER['HTTP_HOST'];
                $request_link = $_SERVER['REQUEST_URI'];

                if(!preg_match('/[a-zA-Z]/', urldecode($request_link))){
                    $request_link = urldecode($request_link);
                }

                $exist_in_db = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $table_name_404 WHERE host = %s AND request_link = %s",
                        $host,
                        $request_link
                    )
                );

                if(!empty($exist_in_db)){
                    
                    $count_of_requests = $exist_in_db[0]->count_of_requests + 1;

                    $wpdb->query($wpdb->prepare(
                        "UPDATE $table_name_404 
                        SET count_of_requests = %s
                        WHERE id = %d",
                        $count_of_requests, $exist_in_db[0]->id
                    ));

                }else{
                    $request_404 = array(
                        "host" => $host,
                        "request_link" => $request_link,
                        "count_of_requests" => 1
                    );
                    
                    $result = $wpdb->insert(
                        $table_name_404, 
                        $request_404
                    );
                }
            }
        }
    }
}
