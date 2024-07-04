<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN') ) {
	exit();
}


  if (get_option('quickredirect_del_tables') == 1) {
    
    global $wpdb;

    $table_rule = $wpdb->prefix . 'quick_redirect_list';
    $table_logs = $wpdb->prefix . 'quick_redirect_logs';
    $table_404 = $wpdb->prefix . 'quick_redirect_404';
    
    $wpdb->query("DROP TABLE IF EXISTS $table_rule");
    $wpdb->query("DROP TABLE IF EXISTS $table_logs");
    $wpdb->query("DROP TABLE IF EXISTS $table_404");

}

