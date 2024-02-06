<?php

include(__DIR__ . DIRECTORY_SEPARATOR . '../public/swift-redirect-instance.php');

if (!class_exists('SwiftRedirectAdmin')) {

    class SwiftRedirectAdmin{

        function __construct(){
            $this->swiftRedirect_init();
        }

        public function swiftRedirect_init() : void{
            add_action( 'admin_menu', array($this, 'swiftRedirect_admin_menu'), 11 );
            add_action( 'admin_enqueue_scripts', array($this, 'swiftRedirect_script_enqueue') );
            add_filter('script_loader_tag', array($this, 'swiftRedirect_add_type_attribute'), 10, 3);
            register_activation_hook( SWIFT_REDIRECT_FILE, array($this, 'swiftRedirect_activate') );
            add_action('wp_ajax_swift-redirect_admin', array($this, 'swiftRedirect_endpoint'));
            add_action('wp_ajax_swift-redirect_log', array($this, 'swiftRedirect_log'));
            add_action('wp_ajax_swift-redirect_404', array($this, 'swiftRedirect_404'));
            add_action('wp_ajax_swift-redirect_export', array($this, 'swiftRedirect_export'));
            add_action('wp_ajax_swift-redirect_import', array($this, 'swiftRedirect_import'));
            add_action('wp_ajax_get_swift-redirect_del_tables', array($this, 'get_swiftRedirect_del_tables'));
            add_action('wp_ajax_set_swift-redirect_del_tables', array($this, 'set_swiftRedirect_del_tables'));

        }

        public function swiftRedirect_script_enqueue() : void{
            $screen = get_current_screen();
            if ($screen->id == 'toplevel_page_swift-redirect') {
               if (defined('SWIFT_REDIRECT_DEVELOPMENT') && SWIFT_REDIRECT_DEVELOPMENT === 'yes'){
                    wp_enqueue_script('swiftRedirect-script-boot', 'http://localhost:5173/' . 'src/main.ts', array(), true);
               }else{
                   wp_enqueue_script('swiftRedirect-script-boot', plugin_dir_url(SWIFT_REDIRECT_FILE).'public-script/js/main.js', array(), true);
               }
                wp_enqueue_style('material-icon-set', 'https://fonts.googleapis.com/css?family=Roboto:300,400,500,700|Material+Icons', [], true);
                wp_enqueue_script('swiftRedirect-fontawesome', 'https://kit.fontawesome.com/5e91bee56f.js', array(), true);
            }
        }

        public function swiftRedirect_add_type_attribute($tag, $handle, $src) : string{
            
            if ( 'swiftRedirect-script-boot' !== $handle ) {
                return $tag;
            }

            $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
            return $tag;
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

            update_option('swiftRedirect_del_tables', 0, 'yes');

        }

        public function get_swiftRedirect_del_tables(){
           
            return wp_send_json( array('status' => 'error', 'del_tables' => get_option('swiftRedirect_del_tables')), 200 );
        }

        public function set_swiftRedirect_del_tables(){
            $request =  json_decode(file_get_contents('php://input'), true);
            
            return wp_send_json( array('status' => 'error', 'del_tables' => update_option('swiftRedirect_del_tables', $request['new_value'], 'yes')), 200 );
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

            $redirects = $wpdb->get_results(
                "SELECT domain, `key`, is_regex, is_enabled, is_params, target_url, code, count_of_redirects, created_at FROM $table_name;"
            );

            return $redirects;

        }

        public function swiftRedirect_export(){

            $to_export = $this->swiftRedirect_format_json();
            header('Content-Disposition: attachment; filename="swift-redirect-'.date('d-m-Y').'.json"');

            wp_send_json($to_export, 200);

        }

        public function swiftRedirect_import(){

            if ( !isset($_SERVER['HTTP_X_WP_NONCE']) || !wp_verify_nonce( $_SERVER['HTTP_X_WP_NONCE'], 'swiftRedirect-nonce' ) )  {

                return wp_send_json( array('status' => 'error', 'message' => 'Unauthorized.'), 401 );

            }

            header('X-WP-Nonce: ' . wp_create_nonce('swiftRedirect-nonce'));

            $request =  json_decode(file_get_contents('php://input'), true);

            $new_redirects = $request['new_redirects'];
            swiftRedirectInstance::createRedirect($new_redirects);

        }

        public function swiftRedirect_options_page() : void{
            $arr = [
                    'nonce' => wp_create_nonce('swiftRedirect-nonce'),
                ];

             echo '<script>
                     let admin_app_vars = ' . json_encode($arr) . '
                 </script>
                 <div class="swiftRedirect-admin-page" id="app-swift-redirect-app">
                    <router-view></router-view>
                </div>';
        }

        public function swiftRedirect_endpoint(){

            if ( !isset($_SERVER['HTTP_X_WP_NONCE']) || !wp_verify_nonce( $_SERVER['HTTP_X_WP_NONCE'], 'swiftRedirect-nonce' ) )  {

                return wp_send_json( array('status' => 'error', 'message' => 'Unauthorized.'), 401 );

            }

            header('X-WP-Nonce: ' . wp_create_nonce('swiftRedirect-nonce'));

            $method = $_SERVER['REQUEST_METHOD'];

            $request = ($method === 'GET') ? $_GET : json_decode(file_get_contents('php://input'), true);

            switch ($method) {
                case "GET":
                    try{

                        $data = self::swiftRedirectsWithPagination($_GET);
                        return wp_send_json( array('status' => 'success', 'data' => $data ), 200 );

                    } catch (Exception $ex) {

                        return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );

                    }

                    break;
                case "POST":

                        $new_redirects = $request['new_redirects'];
                        swiftRedirectInstance::createRedirect($new_redirects);

                    break;
                case "PUT":

                        $update_redirects = $request['update_redirects'];
                        swiftRedirectInstance::updateRedirect($update_redirects);

                    break;
                case "DELETE":

                        $ids_to_remove = $request['ids_to_remove'];
                        swiftRedirectInstance::deleteRedirect($ids_to_remove);

                    break;
            }
        }

        public static function swiftRedirectsWithPagination($request) : array
        {
            global $wpdb;
            $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;
            $limit = $request['limit'];
            $offset = $request['offset'];

            $result = array();
            $data = $wpdb->get_results(
                "SELECT * FROM $table_name LIMIT $limit OFFSET $offset;"
            );
            $total = $wpdb->get_results(
                   "SELECT COUNT(*) FROM $table_name;"
             );
            foreach($total[0] as $k => $v)
            {
                $it = $v;
            }
            $result['data'] = $data;
            $result['total'] = (int) $it;

            $available_hosts = array();

            if(!empty(get_option('polylang'))){
                if(get_option('polylang')['force_lang'] == 3 && !empty(get_option('polylang')['domains'])){
                    foreach(get_option('polylang')['domains'] as $host){

                        $remove_protocol = preg_replace('#^(https?://)?#', '', rtrim($host, '/'));

                        array_push($available_hosts, $remove_protocol);

                    }
                }else if(get_option('polylang')['force_lang'] == 2 && !empty(get_option('_transient_pll_languages_list'))){
                    foreach(get_option('_transient_pll_languages_list') as $host){

                        $remove_protocol = preg_replace('#^(https?://)?#', '', rtrim($host['home_url'], '/'));

                        array_push($available_hosts, $remove_protocol);

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

            $all_count_redirects = $wpdb->get_var("SELECT SUM(count_of_redirects) FROM $table_name");

            $result['count_of_redirects'] = intval($all_count_redirects);

            return $result;
        }

        public function swiftRedirect_log(){

            if ( !isset($_SERVER['HTTP_X_WP_NONCE']) || !wp_verify_nonce( $_SERVER['HTTP_X_WP_NONCE'], 'swiftRedirect-nonce' ) )  {

                return wp_send_json( array('status' => 'error', 'message' => 'Unauthorized.'), 401 );

            }

            header('X-WP-Nonce: ' . wp_create_nonce('swiftRedirect-nonce'));

            $method = $_SERVER['REQUEST_METHOD'];

            $request = ($method === 'GET') ? $_GET : false;

            if(!empty($request)){
                try{

                    global $wpdb;
                    $table_name = $wpdb->prefix . SWIFT_REDIRECT_LOG_LIST_TABLE;
                    $limit = $request['limit'];
                    $offset = $request['offset'];

                    $result = array();

                    $data = $wpdb->get_results(
                        "SELECT * FROM $table_name LIMIT $limit OFFSET $offset;"
                    );

                    $total = $wpdb->get_results(
                           "SELECT COUNT(*) FROM $table_name;"
                     );

                    foreach($total[0] as $k => $v)
                    {
                        $it = $v;
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

            if ( !isset($_SERVER['HTTP_X_WP_NONCE']) || !wp_verify_nonce( $_SERVER['HTTP_X_WP_NONCE'], 'swiftRedirect-nonce' ) )  {

                return wp_send_json( array('status' => 'error', 'message' => 'Unauthorized.'), 401 );

            }

            header('X-WP-Nonce: ' . wp_create_nonce('swiftRedirect-nonce'));

            $method = $_SERVER['REQUEST_METHOD'];

            $request = ($method === 'GET') ? $_GET : json_decode(file_get_contents('php://input'), true);

            global $wpdb;
            $table_name = $wpdb->prefix . SWIFT_REDIRECT_404_LIST_TABLE;
           
            switch ($method) {
                case "GET":
                    try{

                        $limit = $request['limit'];
                        $offset = $request['offset'];
    
                        $result = array();
    
                        $data = $wpdb->get_results(
                            "SELECT * FROM $table_name LIMIT $limit OFFSET $offset;"
                        );
    
                        $total = $wpdb->get_results(
                               "SELECT COUNT(*) FROM $table_name;"
                         );
    
                        foreach($total[0] as $k => $v)
                        {
                            $it = $v;
                        }
    
                        $result['data'] = $data;
                        $result['total'] = (int) $it;
    
                        return wp_send_json( array('status' => 'success', 'data' => $result ), 200 );
    
                    } catch (Exception $ex) {
    
                        return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );
    
                    }

                    break;
                case "PUT":
                    
                        $add_to_redirects = $request['add_to_redirects'];
                        
                        try{
                            
                            $wpdb->update($table_name , $add_to_redirects, array('id' => $add_to_redirects['id']));
                
                        } catch (Exception $ex) {
                
                            return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );
                
                        }
                
                        return wp_send_json( array('status' => 'success', 'data' => $redirects), 200 );

                    break;
            }

        }

    }

}
