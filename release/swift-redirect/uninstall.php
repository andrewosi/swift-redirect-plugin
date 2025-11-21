<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN') ) {
	exit();
}


  if (get_option('quickredirect_del_tables') == 1) {
    
    global $wpdb;

    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local variables in uninstall script
    $swift_redirect_table_rule = $wpdb->prefix . 'quick_redirect_list';
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local variables in uninstall script
    $swift_redirect_table_logs = $wpdb->prefix . 'quick_redirect_logs';
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Local variables in uninstall script
    $swift_redirect_table_404 = $wpdb->prefix . 'quick_redirect_404';
    
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Uninstall script: DROP TABLE is required and safe, $wpdb->prefix is safe
    $wpdb->query("DROP TABLE IF EXISTS $swift_redirect_table_rule");
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Uninstall script: DROP TABLE is required and safe, $wpdb->prefix is safe
    $wpdb->query("DROP TABLE IF EXISTS $swift_redirect_table_logs");
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Uninstall script: DROP TABLE is required and safe, $wpdb->prefix is safe
    $wpdb->query("DROP TABLE IF EXISTS $swift_redirect_table_404");

}

