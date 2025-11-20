<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SF_SwiftRedirectInstance{
    public $id;
    public $domain;
    public $is_regex;
    public $is_params;
    public $is_enabled;
    public $key;
    public $code;
    public $target_url;
    public $count_of_redirects;

    function __construct($redirect_instance){
        $this->id = $redirect_instance['id'];
        $this->domain = $redirect_instance['domain'];
        $this->is_regex = boolval($redirect_instance['is_regex']);
        $this->is_params = boolval($redirect_instance['is_params']);
        $this->is_enabled = boolval($redirect_instance['is_enabled']);
        $this->key = $redirect_instance['key'];
        $this->code = $redirect_instance['code'];
        $this->target_url = $redirect_instance['target_url'];
        $this->count_of_redirects = $redirect_instance['count_of_redirects'];
    }
    
    public static function createRedirect($redirects)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;
        $created = array();
        $alreadyExist = array();
        $invalid = array();

        try {
            foreach($redirects as $redirect){
                $prepared = self::prepareRedirectRow($redirect);

                if ( is_wp_error( $prepared ) ) {
                    $invalid[] = array(
                        'item' => $redirect,
                        'message' => $prepared->get_error_message(),
                    );
                    continue;
                }

                $data = $prepared['data'];

                $checkIfExists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT id FROM $table_name WHERE domain = %s AND `key` = %s",
                        $data['domain'],
                        $data['key']
                    )
                );
                
                if($checkIfExists === null){
                    $wpdb->insert(
                        $table_name, 
                        $data,
                        array(
                            '%s',
                            '%s',
                            '%d',
                            '%d',
                            '%d',
                            '%s',
                            '%d',
                            '%d',
                        )
                    );
    
                    $inserted_id = $wpdb->insert_id;
        
                    $data['id'] = $inserted_id;
        
                    $created[] = $data;
                }else{
                    $alreadyExist[] = $data;
                }

            }
        } catch (Exception $ex) {

            return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );

        }

        // Clear cache after creating redirects
        delete_transient( 'swift_redirect_list_enabled' );

        return wp_send_json( array('status' => 'success', 'data' => $created, 'already_exist' => $alreadyExist, 'invalid' => $invalid ), 200 );
    
    }

    public static function updateRedirect($redirects)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;
        $updated = array();
        $invalid = array();

        try{
            
            foreach($redirects as $redirect){

                $prepared = self::prepareRedirectRow($redirect, true);

                if ( is_wp_error( $prepared ) ) {
                    $invalid[] = array(
                        'item' => $redirect,
                        'message' => $prepared->get_error_message(),
                    );
                    continue;
                }

                $data = $prepared['data'];
                $row_id = $prepared['id'];

                $wpdb->update(
                    $table_name,
                    $data,
                    array('id' => $row_id),
                    array(
                        '%s',
                        '%s',
                        '%d',
                        '%d',
                        '%d',
                        '%s',
                        '%d',
                        '%d',
                    ),
                    array('%d')
                );

                $data['id'] = $row_id;
                $updated[] = $data;
            }

        } catch (Exception $ex) {

            return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );

        }

        // Clear cache after updating redirects
        delete_transient( 'swift_redirect_list_enabled' );

        return wp_send_json( array('status' => 'success', 'data' => $updated, 'invalid' => $invalid), 200 );

    }

    public static function deleteRedirect($ids_to_remove)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;

        try {
            foreach($ids_to_remove as $id){
                $id = absint( $id );

                if ( 0 === $id ) {
                    continue;
                }

                $wpdb->delete(
                    $table_name,
                    array('id' => $id),
                    array('%d')
                );
            }
        } catch (Exception $ex) {
            return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );
        }

        // Clear cache after deleting redirects
        delete_transient( 'swift_redirect_list_enabled' );

        return wp_send_json(array('status' => 'success', 'message' => 'Redirect '. implode(',', array_map('absint', $ids_to_remove)) .' deleted'), 200);
    }

    public function countRedirectsIncrement() : void
    {

        global $wpdb;
        $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;

        $count_of_redirects = $this->count_of_redirects + 1;

        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name 
            SET count_of_redirects = %s
            WHERE id = %d",
            $count_of_redirects, $this->id
        ));

    }

    private static function prepareRedirectRow($redirect, $requires_id = false)
    {
        if ( ! is_array( $redirect ) ) {
            return new WP_Error( 'swift_redirect_invalid', __( 'Invalid redirect payload.', 'swift-redirect' ) );
        }

        $domain = isset( $redirect['domain'] ) ? sanitize_text_field( wp_unslash( $redirect['domain'] ) ) : '';
        $domain = strtolower( $domain );
        // Remove multiple consecutive dots
        $domain = preg_replace( '/\.{2,}/', '.', $domain );
        // Remove leading/trailing dots
        $domain = trim( $domain, '.' );

        if ( false !== strpos( $domain, '://' ) ) {
            $parsed = wp_parse_url( $domain, PHP_URL_HOST );
            if ( $parsed ) {
                $domain = $parsed;
            }
        }

        $key = isset( $redirect['key'] ) ? sanitize_text_field( wp_unslash( $redirect['key'] ) ) : '';
        // Remove multiple consecutive slashes and dots
        $key = preg_replace( '/\/{2,}/', '/', $key );
        $key = preg_replace( '/\.{2,}/', '.', $key );
        $key = '/' . ltrim( $key, '/' );
        // Normalize multiple slashes to single
        $key = preg_replace( '/\/{2,}/', '/', $key );

        $target_url = isset( $redirect['target_url'] ) ? sanitize_text_field( wp_unslash( $redirect['target_url'] ) ) : '';
        $target_url = trim( $target_url );
        // Add protocol if missing
        if ( ! empty( $target_url ) && ! preg_match( '#^https?://#i', $target_url ) ) {
            $target_url = 'https://' . $target_url;
        }
        $target_url = esc_url_raw( $target_url );

        if ( empty( $domain ) || empty( $key ) || empty( $target_url ) ) {
            return new WP_Error( 'swift_redirect_required', __( 'Domain, key and target URL are required.', 'swift-redirect' ) );
        }

        if ( ! self::is_valid_domain( $domain ) ) {
            return new WP_Error( 'swift_redirect_invalid_domain', __( 'Domain contains invalid characters.', 'swift-redirect' ) );
        }

        if ( ! self::is_valid_path( $key ) ) {
            return new WP_Error( 'swift_redirect_invalid_path', __( 'Path contains invalid characters.', 'swift-redirect' ) );
        }

        if ( ! wp_http_validate_url( $target_url ) ) {
            return new WP_Error( 'swift_redirect_invalid_url', __( 'Target URL must be a valid URL.', 'swift-redirect' ) );
        }

        $http_code = isset( $redirect['code'] ) ? absint( $redirect['code'] ) : 301;
        $allowed_codes = array(301, 302, 303, 307, 308);
        if ( ! in_array( $http_code, $allowed_codes, true ) ) {
            $http_code = 301;
        }

        $count_of_redirects = isset( $redirect['count_of_redirects'] ) ? absint( $redirect['count_of_redirects'] ) : 0;

        $data = array(
            'domain' => $domain,
            'key' => $key,
            'is_regex' => isset( $redirect['is_regex'] ) ? absint( $redirect['is_regex'] ) : 0,
            'is_enabled' => isset( $redirect['is_enabled'] ) ? absint( $redirect['is_enabled'] ) : 1,
            'is_params' => isset( $redirect['is_params'] ) ? absint( $redirect['is_params'] ) : 0,
            'target_url' => $target_url,
            'code' => $http_code,
            'count_of_redirects' => $count_of_redirects,
        );

        $row_id = isset( $redirect['id'] ) ? absint( $redirect['id'] ) : 0;

        if ( $requires_id && 0 === $row_id ) {
            return new WP_Error( 'swift_redirect_missing_id', __( 'Redirect ID is required.', 'swift-redirect' ) );
        }

        return array(
            'id' => $row_id,
            'data' => $data,
        );
    }

    private static function is_valid_domain( $domain ) {
        if ( 'localhost' === $domain ) {
            return true;
        }

        $candidate = strtolower( $domain );

        if ( function_exists( 'idn_to_ascii' ) ) {
            $converted = idn_to_ascii( $candidate, IDNA_DEFAULT, defined( 'INTL_IDNA_VARIANT_UTS46' ) ? INTL_IDNA_VARIANT_UTS46 : 0 );
            if ( false !== $converted ) {
                $candidate = $converted;
            }
        }

        return (bool) preg_match( '/^(?=.{1,253}$)(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))*$/', $candidate );
    }

    private static function is_valid_path( $path ) {
        return (bool) preg_match( '#^/[A-Za-z0-9\-._~/%&=?@:]*$#', $path );
    }
}
