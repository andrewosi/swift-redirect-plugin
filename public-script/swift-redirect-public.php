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
            $sanitized_target = esc_url_raw( $target );

            if ( empty( $sanitized_target ) ) {
                return;
            }
            
            self::allow_target_host($sanitized_target);

            global $wpdb;
            $table_name_logs = $wpdb->prefix . SWIFT_REDIRECT_LOG_LIST_TABLE;

            $redirect_log = array(
                "redirect_from" => self::truncate_value( esc_url_raw( $protocol . "://$host$path" ) ),
                "redirect_to" => self::truncate_value( $sanitized_target ),
                "user_agent" => self::truncate_value( sanitize_text_field( $user_agent ) ),
            );
            
            $wpdb->insert(
                $table_name_logs, 
                $redirect_log,
                array( '%s', '%s', '%s' )
            );

            wp_safe_redirect( $sanitized_target, $code, 'swift-redirect' );
            exit;

        }

        public function run_SwiftRedirect(){

            $protocol = function_exists( 'wp_is_https' ) ? ( wp_is_https() ? 'https' : 'http' ) : ( is_ssl() ? 'https' : 'http' );
            $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
            $path = isset($_SERVER['REQUEST_URI']) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : '';

            if(count($this->redirects) === 0){
                return;
            }
            
            $redirects_list = json_decode(wp_json_encode($this->redirects), true);
            $path = self::sanitize_path($path);

            foreach ($redirects_list as $rule) {

                $rule_object = new SF_SwiftRedirectInstance($rule);

                if($rule_object->is_enabled){
                    
                    $target = $rule_object->target_url;
                    if($rule_object->is_params){
                        $query = wp_parse_url($path, PHP_URL_QUERY);
                        if ( ! empty( $query ) ) {
                            $separator = strpos( $target, '?' ) === false ? '?' : '&';
                            $target .= $separator . sanitize_text_field( $query );
                        }
                    }

                    if($rule_object->domain == $host && $rule_object->is_regex == true){
                            
                        $pattern = '#' . preg_quote( $rule_object->key, '#' ) . '#i';
                        
                        if(preg_match($pattern, $path, $matches)){
                            $rule_object->countRedirectsIncrement();
                            self::execute_SwiftRedirect($rule_object->code, $target, $protocol, $host, $path, $user_agent);
                        }

                    }else if($rule_object->domain == $host && $rule_object->key == $path){
                        
                        $rule_object->countRedirectsIncrement();
                        self::execute_SwiftRedirect($rule_object->code, $target, $protocol, $host, $path, $user_agent);
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

                $host = isset($_SERVER['HTTP_HOST']) ? self::truncate_value( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ), 191 ) : '';
                $request_link = isset($_SERVER['REQUEST_URI']) ? self::truncate_value( self::sanitize_path( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 191 ) : '';

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
                    
                    $wpdb->insert(
                        $table_name_404, 
                        $request_404,
                        array('%s', '%s', '%d')
                    );
                }
            }
        }

        private static function sanitize_path( $path ) : string {
            if ( empty( $path ) ) {
                return '/';
            }

            $decoded = rawurldecode( $path );
            $decoded = preg_replace( '/[^\p{L}\p{N}\/\-\_\.\?\&\=\%\:\#]/u', '', $decoded );

            return $decoded ?: '/';
        }

        private static function truncate_value( $value, $length = 180 ) : string {
            if ( strlen( $value ) <= $length ) {
                return $value;
            }

            return substr( $value, 0, $length );
        }

        private static function allow_target_host( $target_url ) : void {
            $host = wp_parse_url( $target_url, PHP_URL_HOST );

            if ( empty( $host ) ) {
                return;
            }

            add_filter(
                'allowed_redirect_hosts',
                static function ( $hosts ) use ( $host ) {
                    $hosts[] = $host;
                    return array_unique( $hosts );
                }
            );
        }
    }
}
