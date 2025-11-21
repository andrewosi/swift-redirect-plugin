<?php

if ( ! defined( 'ABSPATH' ) ) exit;

include 'swift-redirect-instance.php';

if (!class_exists('SF_SwiftRedirectPublic')) {
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class name, SF_ prefix is used for compatibility
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
            
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Real-time logging required
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
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_path()
            $path = isset($_SERVER['REQUEST_URI']) ? self::sanitize_path( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

            if(count($this->redirects) === 0){
                return;
            }
            
            $redirects_list = json_decode(wp_json_encode($this->redirects), true);

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
                            
                        // For regex patterns, use the key directly (already validated on save)
                        // Add delimiters if not present
                        $pattern = $rule_object->key;
                        if ( ! preg_match( '#^[#/~].*[#/~][imsxADSUXJu]*$#', $pattern ) ) {
                            $pattern = '#' . $pattern . '#i';
                        }
                        
                        // Validate regex pattern before use
                        $regex_error = null;
                        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler -- Used for safe regex validation, not debug
                        set_error_handler( function( $errno, $errstr ) use ( &$regex_error ) {
                            $regex_error = $errstr;
                            return true;
                        }, E_WARNING );
                        $is_valid = preg_match( $pattern, '' ) !== false;
                        restore_error_handler();
                        
                        if ( $is_valid && null === $regex_error ) {
                            if(preg_match($pattern, $path, $matches)){
                                $rule_object->countRedirectsIncrement();
                                self::execute_SwiftRedirect($rule_object->code, $target, $protocol, $host, $path, $user_agent);
                            }
                        }

                    }else if($rule_object->domain == $host && $rule_object->key == $path){
                        
                        $rule_object->countRedirectsIncrement();
                        self::execute_SwiftRedirect($rule_object->code, $target, $protocol, $host, $path, $user_agent);
                    }
                }
            }
        }

        public static function get_SwiftRedirectList(){
            // Try to get from cache first
            $cache_key = 'swift_redirect_list_enabled';
            $cached_data = get_transient( $cache_key );

            if ( false !== $cached_data ) {
                return $cached_data;
            }

            // Cache miss - fetch from database
            global $wpdb;
            $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is from constant, safe
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Cached via get_SwiftRedirectList() method
            $data = $wpdb->get_results(
                "SELECT * FROM $table_name WHERE is_enabled = 1;"
            );

            // Cache for 5 minutes (300 seconds)
            set_transient( $cache_key, $data, 300 );

            return $data;
        }

        /**
         * Clear redirects cache - call this when redirects are created/updated/deleted
         */
        public static function clear_redirects_cache() : void {
            delete_transient( 'swift_redirect_list_enabled' );
        }

        public function SwiftRedirectDetermine404(){
            if(is_404()){
                global $wpdb;
                $table_name_404 = $wpdb->prefix . SWIFT_REDIRECT_404_LIST_TABLE;

                $host = isset($_SERVER['HTTP_HOST']) ? self::truncate_value( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ), 191 ) : '';
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_path()
                $request_link = isset($_SERVER['REQUEST_URI']) ? self::truncate_value( self::sanitize_path( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 191 ) : '';

                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name_404 is from constant, safe
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Real-time 404 logging required
                $exist_in_db = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT * FROM $table_name_404 WHERE host = %s AND request_link = %s",
                        $host,
                        $request_link
                    )
                );

                if(!empty($exist_in_db)){
                    
                    $count_of_requests = $exist_in_db[0]->count_of_requests + 1;

                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name_404 is from constant, safe
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Real-time 404 logging required
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
                    
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Real-time 404 logging required
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
