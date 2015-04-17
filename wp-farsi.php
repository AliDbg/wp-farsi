<?php
/**
Plugin Name: WP-Farsi
Plugin URI: http://wordpress.org/extend/plugins/wp-farsi
Description: مبدل تاریخ میلادی وردپرس به خورشیدی، فارسی ساز، مبدل اعداد انگلیسی به فارسی، رفع مشکل هاست با زبان و تاریخ، سازگار با افزونه‌های مشابه.
Author: Ali.Dbg 😉
Author URI: https://github.com/alidbg/wp-farsi
Version: 2.4.0
License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
*/

defined('ABSPATH') or exit;
define('WPFA_NUMS', get_option('wpfa_nums'));
define('WPFA_FILE', __FILE__);

require_once dirname( WPFA_FILE ) . '/pdate.php';

function wpfa_activate() {
    update_option('WPLANG', 'fa_IR');
    update_option('start_of_week', '6');
    update_option('timezone_string', 'Asia/Tehran');
    if (WPFA_NUMS === false) add_option('wpfa_nums', 'on');
    $inc = ABSPATH . 'wp-admin/includes/translation-install.php';
    if (file_exists($inc)) {
        require_once($inc);
        wp_download_language_pack('fa_IR'); 
    }
}

function wpfa_patch_func($patch = false) {
    $file = ABSPATH . 'wp-includes/functions.php';
    if (!is_writable($file)) return;
    $src = file_get_contents($file);
    if (preg_match_all('/else\s+return\s+(date.*)[(]/', $src, $match) === 1) 
        file_put_contents($file, str_replace($match[0][0], (rtrim($match[1][0]) === "date" && $patch ? "else\n\t\treturn date_i18n(" : "else\n\t\treturn date("), $src));
}

function numbers_fa( $string ) {
    static $en_nums = array('0','1','2','3','4','5','6','7','8','9');
    static $fa_nums = array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹');
    return str_replace($en_nums, $fa_nums, $string);
}

function wpfa_date_i18n( $g, $f, $t ) {
    $d = wpfa_date($f, intval($t));
    return WPFA_NUMS === "on" ? numbers_fa($d) : $d;
}

function wpfa_apply_filters() {
    ini_set('default_charset', 'UTF-8');
    ini_set('date.timezone', 'UTC');
    if (extension_loaded('mbstring')) {
        mb_internal_encoding('UTF-8');
        mb_language('neutral');
        mb_http_output('UTF-8');
    }
    foreach (array(
        'date_i18n', 'get_post_time', 'get_comment_date', 'get_comment_time', 'get_the_date', 'the_date', 'get_the_time', 'the_time',
        'get_the_modified_date', 'the_modified_date', 'get_the_modified_time', 'the_modified_time', 'get_post_modified_time', 'number_format_i18n'
    ) as $i) remove_all_filters($i);
    add_filter('date_i18n', 'wpfa_date_i18n', 10, 3);
    if (WPFA_NUMS === "on")
        add_filter('number_format_i18n', 'numbers_fa');
}

function post_jalali2gregorian(){
    if (isset($_POST['aa'], $_POST['mm'], $_POST['jj']))
        list($_POST['aa'], $_POST['mm'], $_POST['jj']) = jalali2gregorian(zeroise(intval($_POST['aa']), 4), zeroise(intval($_POST['mm']), 2), zeroise(intval($_POST['jj']), 2));
}

function wpfa_init() {
    global $wp_locale;
    $wp_locale->number_format['thousands_sep'] = ",";
    $wp_locale->number_format['decimal_point'] = ".";
    if (numbers_fa(mysql2date("Y", "2015", 0)) !== "۱۳۹۴") 
        wpfa_patch_func(true);
    else post_jalali2gregorian();
}

function wpfa_admin(){
    require_once dirname( WPFA_FILE ) . "/wpfa_admin.php";
    wpfa_nums_field();
    wpfa_load_first();
}

wpfa_apply_filters();
add_action('init', 'wpfa_init');
add_action('admin_init', 'wpfa_admin');
add_action('wp_loaded', 'wpfa_apply_filters', 900);
register_activation_hook( WPFA_FILE , 'wpfa_activate');
register_deactivation_hook( WPFA_FILE , 'wpfa_patch_func');