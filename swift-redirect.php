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

if ( ! defined( 'ABSPATH' ) ) exit;

defined('SWIFT_REDIRECT_FILE') or define('SWIFT_REDIRECT_FILE', __FILE__);
define('SWIFT_REDIRECT_RULE_LIST_TABLE', 'swift_redirect_list');
define('SWIFT_REDIRECT_LOG_LIST_TABLE', 'swift_redirect_logs');
define('SWIFT_REDIRECT_404_LIST_TABLE', 'swift_redirect_404');
defined('SWIFT_REDIRECT_PRODUCTION') or define('SWIFT_REDIRECT_PRODUCTION', 'yes');

if (is_admin()) {
  require_once('admin/swift-redirect-admin.php');
  new SF_SwiftRedirectAdmin();
}else{
  require_once('public/swift-redirect-public.php');
  new SF_SwiftRedirectPublic();
}
