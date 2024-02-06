<?php
/**
  * Plugin Name: Swift Redirect
  * Domain Path: /languages
  * description: Best plugin for redirects
  * Version: 1.0
  * Requires at least: 4.7
  * Requires PHP: 7.0
  * Author: Reliability&Care Code Group
  * License: GPLv2 or later
  * License URI: https://www.gnu.org/licenses/gpl-2.0.html
  */

if (!defined('ABSPATH')) {
  header("HTTP/1.1 403 Forbidden");
  wp_die('forbidden');
}

defined('SWIFT_REDIRECT_FILE') or define('SWIFT_REDIRECT_FILE', __FILE__);
define('SWIFT_REDIRECT_RULE_LIST_TABLE', 'swift_redirect_list');
define('SWIFT_REDIRECT_LOG_LIST_TABLE', 'swift_redirect_logs');
define('SWIFT_REDIRECT_404_LIST_TABLE', 'swift_redirect_404');
defined('SWIFT_REDIRECT_DEVELOPMENT') or define('SWIFT_REDIRECT_DEVELOPMENT', 'yes');

if (is_admin()) {
  require_once('admin/swift-redirect-admin.php');
  new SwiftRedirectAdmin();
}else{
  require_once('public/swift-redirect-public.php');
  new SwiftRedirectPublic();
}
