<?php

if ( ! defined( 'ABSPATH' ) ) exit;

include(__DIR__ . DIRECTORY_SEPARATOR . '../public/swift-redirect-instance.php');

if (!class_exists('SF_SwiftRedirectAdmin')) {

    class SF_SwiftRedirectAdmin{

        private $manifest_entry = null;

        function __construct(){
            $this->swiftRedirect_init();
        }

        public function swiftRedirect_init() : void{
            add_action( 'admin_menu', array($this, 'swiftRedirect_admin_menu'), 11 );
            add_action( 'admin_enqueue_scripts', array($this, 'swiftRedirect_script_enqueue') );
            register_activation_hook( SWIFT_REDIRECT_FILE, array($this, 'swiftRedirect_activate') );
            add_action('wp_ajax_swift-redirect_admin', array($this, 'swiftRedirect_endpoint'));
            add_action('wp_ajax_swift-redirect_log', array($this, 'swiftRedirect_log'));
            add_action('wp_ajax_swift-redirect_404', array($this, 'swiftRedirect_404'));
            add_action('wp_ajax_swift-redirect_export', array($this, 'swiftRedirect_export'));
            add_action('wp_ajax_swift-redirect_import', array($this, 'swiftRedirect_import'));
            add_action('wp_ajax_get_swift-redirect_del_tables', array($this, 'get_swiftRedirect_del_tables'));
            add_action('wp_ajax_set_swift-redirect_del_tables', array($this, 'set_swiftRedirect_del_tables'));

        }

        private function swiftRedirect_guard_request() : void{
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json( array( 'status' => 'error', 'message' => __( 'Insufficient permissions.', 'swift-redirect' ) ), 403 );
            }

            $nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';

            if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'swiftRedirect-nonce' ) ) {
                wp_send_json( array( 'status' => 'error', 'message' => __( 'Unauthorized.', 'swift-redirect' ) ), 401 );
            }

            // Rate limiting: max 60 requests per minute per user
            if ( ! $this->swiftRedirect_check_rate_limit() ) {
                wp_send_json( array( 'status' => 'error', 'message' => __( 'Too many requests. Please try again later.', 'swift-redirect' ) ), 429 );
            }
        }

        private function swiftRedirect_check_rate_limit() : bool {
            $user_id = get_current_user_id();
            if ( 0 === $user_id ) {
                return false;
            }

            $transient_key = 'swift_redirect_rate_limit_' . $user_id;
            $request_count = get_transient( $transient_key );

            if ( false === $request_count ) {
                // First request in this minute
                set_transient( $transient_key, 1, 60 ); // 60 seconds
                return true;
            }

            if ( $request_count >= 60 ) {
                // Rate limit exceeded
                return false;
            }

            // Increment counter
            set_transient( $transient_key, $request_count + 1, 60 );
            return true;
        }

        public function swiftRedirect_script_enqueue() : void{
            $screen = get_current_screen();
            if ($screen->id == 'toplevel_page_swift-redirect') {
                
                $plugin_url = plugin_dir_url( SWIFT_REDIRECT_FILE );
                
                $arr = [
                    'nonce' => wp_create_nonce('swiftRedirect-nonce'),
                    'pluginUrl' => $plugin_url,
                ];

                $asset = $this->swiftRedirect_get_manifest_entry();

                if ( empty( $asset ) || empty( $asset['file'] ) ) {
                    return;
                }

                $plugin_url  = plugin_dir_url( SWIFT_REDIRECT_FILE );
                $plugin_path = plugin_dir_path( SWIFT_REDIRECT_FILE );

                $script_path = $plugin_url . 'public-script/' . ltrim( $asset['file'], '/' );
                $script_file = $plugin_path . 'public-script/' . ltrim( $asset['file'], '/' );
                $version = file_exists( $script_file ) ? filemtime( $script_file ) : false;

                wp_register_script(
                    'swiftRedirect-script-boot',
                    $script_path,
                    array(),
                    $version,
                    true
                );

                if ( ! empty( $asset['css'] ) && is_array( $asset['css'] ) ) {
                    foreach ( $asset['css'] as $index => $css_file ) {
                        $css_handle = sprintf( 'swiftRedirect-style-%s', $index );
                        $css_path   = $plugin_url . 'public-script/' . ltrim( $css_file, '/' );
                        $css_file_path = $plugin_path . 'public-script/' . ltrim( $css_file, '/' );
                        $css_version = file_exists( $css_file_path ) ? filemtime( $css_file_path ) : false;
                        wp_register_style( $css_handle, $css_path, array(), $css_version );
                        wp_enqueue_style( $css_handle );
                    }
                }

                wp_localize_script('swiftRedirect-script-boot', 'admin_app_vars', $arr);

                wp_enqueue_script('swiftRedirect-script-boot');

                wp_enqueue_style('material-icon-set', 'https://fonts.googleapis.com/css?family=Roboto:300,400,500,700|Material+Icons', [], true);
//                wp_enqueue_script('swiftRedirect-fontawesome', plugin_dir_url(SWIFT_REDIRECT_FILE).'public-script/js/fontawesome.js', array(), true);
            }
        }

        public function swiftRedirect_activate() : void{
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $table_name_redirects = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;
            $sql = "CREATE TABLE $table_name_redirects (
            id INTEGER (11) NOT NULL AUTO_INCREMENT,
            domain varchar(191) NOT NULL,
            `key` varchar(191) NOT NULL,
            is_regex TINYINT(1) NOT NULL DEFAULT 0,
            is_enabled TINYINT(1) NOT NULL DEFAULT 1,
            is_params TINYINT(1) NOT NULL DEFAULT 0,
            target_url varchar(191) NOT NULL,
            code INTEGER (11) NOT NULL,
            count_of_redirects INTEGER (11) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
            ) $charset_collate;";
            maybe_create_table( $table_name_redirects, $sql );

            $table_name_logs = $wpdb->prefix . SWIFT_REDIRECT_LOG_LIST_TABLE;
            $sql = "CREATE TABLE $table_name_logs (
            id INTEGER (11) NOT NULL AUTO_INCREMENT,
            redirect_from varchar(191) NOT NULL,
            redirect_to varchar(191) NOT NULL,
            user_agent varchar(191) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
            ) $charset_collate;";
            maybe_create_table( $table_name_logs, $sql );

            $table_name_404 = $wpdb->prefix . SWIFT_REDIRECT_404_LIST_TABLE;
            $sql = "CREATE TABLE $table_name_404 (
            id INTEGER (11) NOT NULL AUTO_INCREMENT,
            host varchar(191) NOT NULL,
            request_link varchar(191) NOT NULL,
            count_of_requests INTEGER (11) NOT NULL,
            is_redirect TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
            ) $charset_collate;";
            maybe_create_table( $table_name_404, $sql );

            update_option('sf_swiftRedirect_del_tables', 0, 'yes');

        }

        public function get_swiftRedirect_del_tables(){
           
            $this->swiftRedirect_guard_request();

            return wp_send_json( array('status' => 'success', 'del_tables' => (int) get_option('sf_swiftRedirect_del_tables')), 200 );
        }

        public function set_swiftRedirect_del_tables(){
            $this->swiftRedirect_guard_request();

            $request_body = $this->swiftRedirect_get_json_input();
            $new_value = isset( $request_body['new_value'] ) ? absint( $request_body['new_value'] ) : 0;

            update_option('sf_swiftRedirect_del_tables', $new_value, 'yes');

            return wp_send_json( array('status' => 'success', 'del_tables' => (int) get_option('sf_swiftRedirect_del_tables')), 200 );
        }

        public function swiftRedirect_admin_menu() : void{
            add_menu_page(
                __( 'Swift Redirect', 'textdomain' ),
                'Swift Redirect',
                'manage_options',
                'swift-redirect',
                array($this, 'swiftRedirect_options_page')
            );
        }

        private function swiftRedirect_format_json(){

            global $wpdb;
            $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;

            $redirects_query = $wpdb->prepare(
                "SELECT domain, `key`, is_regex, is_enabled, is_params, target_url, code, count_of_redirects, created_at FROM $table_name;"
            );

            $redirects = $wpdb->get_results($redirects_query);

            return $redirects;

        }

        public function swiftRedirect_export(){

            $this->swiftRedirect_guard_request();

            $to_export = $this->swiftRedirect_format_json();
            header('Content-Disposition: attachment; filename="swift-redirect-'.gmdate('d-m-Y').'.json"');

            wp_send_json($to_export, 200);

        }

        public function swiftRedirect_import(){

            $this->swiftRedirect_guard_request();

            header('X-WP-Nonce: ' . wp_create_nonce('swiftRedirect-nonce'));

            $request =  $this->swiftRedirect_get_json_input();

            $new_redirects = isset( $request['new_redirects'] ) ? (array) $request['new_redirects'] : array();
            SF_SwiftRedirectInstance::createRedirect($new_redirects);

        }

        public function swiftRedirect_options_page() : void{

             echo '<div class="swiftRedirect-admin-page" id="app-swift-redirect-app">
                    <router-view></router-view>
                </div>';

        }

        public function swiftRedirect_endpoint(){

            $this->swiftRedirect_guard_request();

            header('X-WP-Nonce: ' . wp_create_nonce('swiftRedirect-nonce'));

            $method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) );

            switch ($method) {
                case "GET":
                    $input_vars = $this->swiftRedirect_get_pagination_from_request();
                    // Add search parameter if provided
                    if ( isset( $_GET['search'] ) ) {
                        $input_vars['search'] = sanitize_text_field( wp_unslash( $_GET['search'] ) );
                    }
                    try{
                        
                        $data = self::swiftRedirectsWithPagination($input_vars);
                        return wp_send_json( array('status' => 'success', 'data' => $data ), 200 );

                    } catch (Exception $ex) {

                        return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );

                    }

                    break;
                case "POST":
                        $request_body = $this->swiftRedirect_get_json_input();
                        $new_redirects = isset( $request_body['new_redirects'] ) ? (array) $request_body['new_redirects'] : array();
                        SF_SwiftRedirectInstance::createRedirect($new_redirects);
                    break;
                case "PUT":
                        $request_body = $this->swiftRedirect_get_json_input();
                        $update_redirects = isset( $request_body['update_redirects'] ) ? (array) $request_body['update_redirects'] : array();
                        SF_SwiftRedirectInstance::updateRedirect($update_redirects);
                    break;
                case "DELETE":
                        $request_body = $this->swiftRedirect_get_json_input();
                        $ids_to_remove = isset( $request_body['ids_to_remove'] ) ? (array) $request_body['ids_to_remove'] : array();
                        SF_SwiftRedirectInstance::deleteRedirect($ids_to_remove);
                    break;
            }
        }

        public static function swiftRedirectsWithPagination($request) : array
        {
            global $wpdb;
            $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;
            $limit = absint( $request['limit'] ?? 15 );
            $offset = absint( $request['offset'] ?? 0 );
            $search = isset( $request['search'] ) ? sanitize_text_field( wp_unslash( $request['search'] ) ) : '';

            $result = array();

            // Build WHERE clause for search
            if ( ! empty( $search ) ) {
                $search_like = '%' . $wpdb->esc_like( $search ) . '%';
                $query = $wpdb->prepare(
                    "SELECT * FROM $table_name WHERE domain LIKE %s OR `key` LIKE %s OR target_url LIKE %s LIMIT %d OFFSET %d;",
                    $search_like,
                    $search_like,
                    $search_like,
                    $limit,
                    $offset
                );
                $data = $wpdb->get_results($query);
                
                $query_total = $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE domain LIKE %s OR `key` LIKE %s OR target_url LIKE %s;",
                    $search_like,
                    $search_like,
                    $search_like
                );
            } else {
                $query = $wpdb->prepare(
                    "SELECT * FROM $table_name LIMIT %d OFFSET %d;",
                    $limit,
                    $offset
                );
                $data = $wpdb->get_results($query);
                
                $query_total = $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name;"
                );
            }
            
            $total = $wpdb->get_results($query_total);

            $it = 0;
            if ( ! empty( $total ) && isset( $total[0] ) ) {
                foreach($total[0] as $k => $v)
                {
                    $it = $v;
                }
            }
            $result['data'] = $data;
            $result['total'] = (int) $it;

            $available_hosts = array();

            $polylang_option = get_option('polylang');
            if(!empty($polylang_option) && is_array($polylang_option)){
                if(isset($polylang_option['force_lang']) && $polylang_option['force_lang'] == 3 && !empty($polylang_option['domains']) && is_array($polylang_option['domains'])){
                    foreach($polylang_option['domains'] as $host){

                        $remove_protocol = preg_replace('#^(https?://)?#', '', rtrim($host, '/'));

                        array_push($available_hosts, $remove_protocol);

                    }
                }else if(isset($polylang_option['force_lang']) && $polylang_option['force_lang'] == 2){
                    $pll_languages = get_option('_transient_pll_languages_list');
                    if(!empty($pll_languages) && is_array($pll_languages)){
                        foreach($pll_languages as $host){

                            $remove_protocol = preg_replace('#^(https?://)?#', '', rtrim($host['home_url'], '/'));

                            array_push($available_hosts, $remove_protocol);

                        }
                    }
                }else{

                    $remove_protocol = preg_replace('#^(https?://)?#', '', rtrim(get_site_url(), '/'));

                    array_push($available_hosts, $remove_protocol);
                }
            }else{

                $remove_protocol = preg_replace('#^(https?://)?#', '', rtrim(get_site_url(), '/'));

                array_push($available_hosts, $remove_protocol);
            }

            $result['hosts_list'] = $available_hosts;

            // Safe: table name is from constant, no user input
            $all_count_redirects = $wpdb->get_var("SELECT SUM(count_of_redirects) FROM $table_name");

            $result['count_of_redirects'] = intval($all_count_redirects);

            return $result;
        }

        public function swiftRedirect_log(){

            $this->swiftRedirect_guard_request();

            header('X-WP-Nonce: ' . wp_create_nonce('swiftRedirect-nonce'));

            $method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );

            if(!empty($method)){
                $input_vars = $this->swiftRedirect_get_pagination_from_request();
                try{

                    global $wpdb;
                    $table_name = $wpdb->prefix . SWIFT_REDIRECT_LOG_LIST_TABLE;
                    $limit = $input_vars['limit'];
                    $offset = $input_vars['offset'];

                    $result = array();

                    $query = $wpdb->prepare(
                        "SELECT * FROM $table_name LIMIT %d OFFSET %d;",
                        $limit,
                        $offset
                    );
                    $data = $wpdb->get_results($query);
                    
                    $query_total = $wpdb->prepare(
                        "SELECT COUNT(*) FROM $table_name;"
                    );

                    $total = $wpdb->get_results($query_total);

                    $it = 0;
                    if ( ! empty( $total ) && isset( $total[0] ) ) {
                        foreach($total[0] as $k => $v)
                        {
                            $it = $v;
                        }
                    }

                    $result['data'] = $data;
                    $result['total'] = (int) $it;

                    return wp_send_json( array('status' => 'success', 'data' => $result ), 200 );

                } catch (Exception $ex) {

                    return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );

                }
            }else{
                return wp_send_json( array('status' => 'error', 'message' => 'Incorrect query'), 500 );
            }

        }

        public function swiftRedirect_404(){

            $this->swiftRedirect_guard_request();

            header('X-WP-Nonce: ' . wp_create_nonce('swiftRedirect-nonce'));

            $method = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) );

            global $wpdb;
            $table_name = $wpdb->prefix . SWIFT_REDIRECT_404_LIST_TABLE;
           
            switch ($method) {
                case "GET":
                    try{
                        $pagination = $this->swiftRedirect_get_pagination_from_request();
                        $limit = $pagination['limit'];
                        $offset = $pagination['offset'];
    
                        $result = array();
    
                        $query = $wpdb->prepare(
                            "SELECT * FROM $table_name LIMIT %d OFFSET %d;",
                            $limit,
                            $offset
                        );
                        $data = $wpdb->get_results($query);
                        
                        $query_total = $wpdb->prepare(
                            "SELECT COUNT(*) FROM $table_name;"
                        );
    
                        $total = $wpdb->get_results($query_total);
    
                        $it = 0;
                        if ( ! empty( $total ) && isset( $total[0] ) ) {
                            foreach($total[0] as $k => $v)
                            {
                                $it = $v;
                            }
                        }
    
                        $result['data'] = $data;
                        $result['total'] = (int) $it;
    
                        return wp_send_json( array('status' => 'success', 'data' => $result ), 200 );
    
                    } catch (Exception $ex) {
    
                        return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );
    
                    }

                    break;
                case "PUT":
                    
                        $request_body = $this->swiftRedirect_get_json_input();
                        $add_to_redirects = isset( $request_body['add_to_redirects'] ) ? $request_body['add_to_redirects'] : array();
                        list( $row_id, $row_data ) = $this->swiftRedirect_sanitize_404_row( $add_to_redirects );

                        if ( 0 === $row_id || empty( $row_data ) ) {
                            return wp_send_json( array('status' => 'error', 'message' => __( 'Invalid payload.', 'swift-redirect' )), 400 );
                        }
                        
                        try{
                            
                            $wpdb->update($table_name , $row_data, array('id' => $row_id));
                
                        } catch (Exception $ex) {
                
                            return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );
                
                        }
                
                        return wp_send_json( array('status' => 'success', 'data' => $row_data), 200 );

                    break;
            }

        }

        private function swiftRedirect_get_manifest_entry() : array {
            if ( null !== $this->manifest_entry ) {
                return $this->manifest_entry;
            }

            $manifest_path = trailingslashit( plugin_dir_path( SWIFT_REDIRECT_FILE ) ) . 'public-script/manifest.json';

            if ( ! file_exists( $manifest_path ) ) {
                $this->manifest_entry = array();
                return $this->manifest_entry;
            }

            $manifest_content = file_get_contents( $manifest_path );
            $manifest = json_decode( $manifest_content, true );

            if ( ! is_array( $manifest ) || empty( $manifest['src/main.ts'] ) ) {
                $this->manifest_entry = array();
                return $this->manifest_entry;
            }

            $this->manifest_entry = $manifest['src/main.ts'];

            return $this->manifest_entry;
        }

        private function swiftRedirect_get_json_input() : array {
            $raw_body = file_get_contents('php://input');

            if ( empty( $raw_body ) ) {
                return array();
            }

            $decoded = json_decode( $raw_body, true );

            return is_array( $decoded ) ? $decoded : array();
        }

        private function swiftRedirect_get_pagination_from_request() : array {
            $limit = isset( $_GET['limit'] ) ? absint( wp_unslash( $_GET['limit'] ) ) : 15;
            $offset = isset( $_GET['offset'] ) ? absint( wp_unslash( $_GET['offset'] ) ) : 0;

            if ( $limit <= 0 ) {
                $limit = 15;
            }

            $limit = min( $limit, 200 );

            return array(
                'limit' => $limit,
                'offset' => $offset,
            );
        }

        private function swiftRedirect_sanitize_404_row( $row ) : array {
            $row_id = isset( $row['id'] ) ? absint( $row['id'] ) : 0;
            $data = array();

            if ( isset( $row['is_redirect'] ) ) {
                $data['is_redirect'] = absint( $row['is_redirect'] );
            }

            if ( isset( $row['count_of_requests'] ) ) {
                $data['count_of_requests'] = absint( $row['count_of_requests'] );
            }

            return array( $row_id, $data );
        }

    }

}
