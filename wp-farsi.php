<?php
/**
Plugin Name: WP-Farsi
Plugin URI: http://wordpress.org/extend/plugins/wp-farsi
Description: افزونه مبدل تاریخ میلادی به شمسی، مکمل و سازگار با افزونه‌های مشابه.
Author: Ali.Dbg
Author URI: https://github.com/alidbg/wp-farsi
Version: 2.0
License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
*/

defined('ABSPATH') || exit;
define('WPFA_NUMS', get_option('wpfa_nums'));

if (extension_loaded('mbstring')) {
    mb_internal_encoding('UTF-8');
    mb_language('neutral');
    mb_http_output('UTF-8');
} elseif (function_exists('ini_set')) {
    ini_set('default_charset', 'UTF-8');
    ini_set('date.timezone', 'UTC');
}

require_once plugin_dir_path(__FILE__) . 'pdate.php';

function numbers_fa($str = '') {
    return str_replace(range(0, 9), preg_split("//u", "۰۱۲۳۴۵۶۷۸۹", 10, 1), $str);
}

function wpfa_activate() {
    update_option('WPLANG', 'fa_IR');
    update_option('start_of_week', '6');
    update_option('timezone_string', 'Asia/Tehran');
    if(WPFA_NUMS === false) add_option('wpfa_nums', 'on');
    $l = ABSPATH . 'wp-admin/includes/translation-install.php';
    if (file_exists($l)) {require_once($l);wp_download_language_pack('fa_IR');}
}

function wpfa_init() {
    global $wp_locale;
    $wp_locale->number_format['thousands_sep'] = ",";
    $wp_locale->number_format['decimal_point'] = ".";
    if (isset($_POST['aa'], $_POST['mm'], $_POST['jj']))
        list($_POST['aa'], $_POST['mm'], $_POST['jj']) = jalali2gregorian(zeroise(intval($_POST['aa']), 4), zeroise(intval($_POST['mm']), 2), zeroise(intval($_POST['jj']), 2));
}

function wpfa_patch_func($patch = false) {
    $f = ABSPATH . 'wp-includes/functions.php';
    if (!is_writable($f)) return;
    $re = "/(else\\s+return\\s+(date.*[(]))/";
    $src = file_get_contents($f);
    if (preg_match($re, $src, $match) === 1){
      if ($match[2] === "date(" && $patch)
         file_put_contents($f, str_replace($match[1], "else\n\t\treturn date_i18n(", $src));
      elseif ($match[2] === "date_i18n(" && !$patch)
         file_put_contents($f, str_replace($match[1], "else\n\t\treturn date(", $src));
    }
}

function timestampdiv() {?>
<script type='text/javascript'>
var c = ("۰,۱,۲,۳,۴,۵,۶,۷,۸,۹,Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec").split(",");
var d = ("0,1,2,3,4,5,6,7,8,9,فرو,ارد,خرد,تیر,مرد,شهر,مهر,آبا,آذر,دی,بهم,اسف").split(",");
jQuery(document).ready(function(){
    jQuery("#timestampdiv,.timestamp-wrap,.inline-edit-date,.jj,.mm,.aa,.hh,.mn,.ss").html(function(a,b){
    jQuery.each(c,function(a,c){b=b.replace(new RegExp(c,'g'),d[a])});return b});
    jQuery("#mm option[value='"+jQuery('#hidden_mm').val()+"']").attr("selected","selected")
});
</script>
<?php 
}

function dreg_jsfa() {
    wp_deregister_script('ztjalali_reg_admin_js');
    wp_deregister_script('ztjalali_reg_date_js');
    wp_deregister_script('wpp_admin');
}

function wpfa_date_i18n($g = '', $f = '', $t = '') {
    $d = wpfa_date($f,intval($t));
    return WPFA_NUMS === "on" ? numbers_fa($d) : $d;
}

function wpfa_load() {
    foreach (array(
        'date_i18n', 'get_post_time', 'get_comment_date', 'get_comment_time', 'get_the_date', 'the_date', 'get_the_time', 'the_time',
        'get_the_modified_date', 'the_modified_date', 'get_the_modified_time', 'the_modified_time', 'get_post_modified_time', 'number_format_i18n'
    ) as $i) remove_all_filters($i);
    if (mysql2date("Y m", "2014 12", true) !== mysql2date("Y m", "2014 12", false)) wpfa_patch_func(true);
    if (WPFA_NUMS === "on") add_filter('number_format_i18n', 'numbers_fa');
    add_filter('date_i18n', 'wpfa_date_i18n', 10, 3);
}

function wpfa_nums() {
    register_setting('general', 'wpfa_nums', 'esc_attr');
    add_settings_field('wpfa_nums', '<label for="wpfa_nums">ساختار اعداد</label>', create_function('', '
        echo \'<label><input type="checkbox" name="wpfa_nums" ' . (WPFA_NUMS === "on" ? "checked" : "") . '/> 
        <span>فارسی ۰۱۲۳۴۵۶۷۸۹</span></label>\';'), 'general');
}

register_activation_hook(__FILE__, 'wpfa_activate');
register_deactivation_hook(__FILE__, 'wpfa_patch_func');
add_action('init', 'wpfa_init');
add_action('admin_init', 'wpfa_nums');
add_action('admin_footer', 'timestampdiv');
add_action('wp_print_scripts', 'dreg_jsfa', 98);
add_action('plugins_loaded', 'wpfa_load', 98);