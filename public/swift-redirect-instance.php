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
        try {
            $created = array();
            $alreadyExist = array();
            foreach($redirects as &$redirect){

                $checkIfExists = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT * FROM $table_name WHERE domain = %s AND `key` = %s",
                        $redirect['domain'],
                        $redirect['key']
                    )
                );
                
                if($checkIfExists === null){
                    $result = $wpdb->insert(
                        $table_name, 
                        $redirect
                    );
    
                    $inserted_id = $wpdb->insert_id;
        
                    $redirect['id'] = $inserted_id;
        
                    array_push($created, $redirect);
                }else{
                    array_push($alreadyExist, $redirect);
                }

            }
        } catch (Exception $ex) {

            return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );

        }

        return wp_send_json( array('status' => 'success', 'data' => $created, 'already_exist' => $alreadyExist ), 200 );
    
    }

    public static function updateRedirect($redirects)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;

        try{
            
            foreach($redirects as $redirect){

                $wpdb->update($table_name , $redirect, array('id' => $redirect['id']));

            }

        } catch (Exception $ex) {

            return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );

        }

        return wp_send_json( array('status' => 'success', 'data' => $redirects), 200 );

    }

    public static function deleteRedirect($ids_to_remove)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . SWIFT_REDIRECT_RULE_LIST_TABLE;

        try {
            foreach($ids_to_remove as $id){
                $result = $wpdb->delete(
                    $table_name,
                    array('id' => $id),
                    array('%d')
                );
            }
        } catch (Exception $ex) {
            return wp_send_json( array('status' => 'error', 'message' => $ex->getMessage()), 500 );
        }

        return wp_send_json(array('status' => 'success', 'message' => 'Redirect '. implode(',', $ids_to_remove) .' deleted'), 200);
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

}
