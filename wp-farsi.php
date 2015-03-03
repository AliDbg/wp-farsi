<?php 
/**
	Plugin Name: WP-Farsi
	Plugin URI: http://wordpress.org/extend/plugins/wp-farsi
	Description: افزونه مبدل تاریخ میلادی به شمسی، مکمل و سازگار با افزونه‌های مشابه. 
	Author: Ali.Dbg
	Author URI: https://github.com/alidbg/wp-farsi
	Version: 1.0
	License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
*/ 

defined('ABSPATH')||die;
require_once plugin_dir_path( __FILE__ ) . 'pdate.php';

function num2fa($str,$cur=false){
 	return str_replace(preg_split("//u","0123456789/,",12,1),preg_split("//u","۰۱۲۳۴۵۶۷۸۹٫".($cur?'٬':'،'),12,1),$str);
}

function download_faIR(){
	$l = ABSPATH.'wp-admin/includes/translation-install.php';
	if(file_exists($l)){require_once($l);wp_download_language_pack('fa_IR');}
}

function wpfa_activate(){
    download_faIR();
    update_option('WPLANG', 'fa_IR');
    update_option('start_of_week', '6');
}

function wpfa_deactivate(){
	patch_func(false);
	delete_option("wpfa_nums");
}

function post2pdate(){
    if (isset($_POST['aa'], $_POST['mm'], $_POST['jj']))
        list($_POST['aa'], $_POST['mm'], $_POST['jj']) = jalali2gregorian(zeroise(intval($_POST['aa']), 4), zeroise(intval($_POST['mm']), 2), zeroise(intval($_POST['jj']), 2));
}

function patch_func($patch){
	$source  = ABSPATH . 'wp-includes/functions.php';
	$replace = "else\n\t\treturn date_i18n( " . '$format, $i' . " );";
	$pattern = "else\n\t\treturn date( " . '$format, $i' . " );";
	if(!$patch) list($replace, $pattern) = array($pattern, $replace);
    if(is_file($source) && is_writable($source)) file_put_contents($source, str_replace($pattern, $replace, file_get_contents($source)));
}

function timestampdiv(){?>
<script>jQuery(document).ready(function(){jQuery("#timestampdiv").html(function(a,b){
var c=("۰,۱,۲,۳,۴,۵,۶,۷,۸,۹,01-Jan,02-Feb,03-Mar,04-Apr,05-May,06-Jun,07-Jul,08-Aug,09-Sep,10-Oct,11-Nov,12-Dec").split(","),
d=("0,1,2,3,4,5,6,7,8,9,01-فرو,02-ارد,03-خرد,04-تیر,05-مرد,06-شهر,07-مهر,08-آبا,09-آذر,10-دی,11-بهم,12-اسف").split(",");
jQuery.each(c,function(a,c){b=b.replace(new RegExp(c,"gi"),d[a])});return b});
jQuery("#mm option[value='"+jQuery('#hidden_mm').val()+"']").attr("selected","selected")})</script>
<?php
}

function dreg_jsfa() {
	wp_dequeue_script('ztjalali_reg_admin_js');
	wp_dequeue_script('ztjalali_reg_date_js');
	wp_dequeue_script('wpp_admin');
}

function wpfa_load(){
	if (extension_loaded('mbstring')){
		mb_internal_encoding('UTF-8');
		mb_language('neutral');
		mb_http_output('UTF-8');
	}
	ini_set('default_charset','UTF-8');
	ini_set('date.timezone',  'UTC');
	date_default_timezone_set('UTC');
	update_option('timezone_string', 'Asia/Tehran');

	if (num2fa(mysql2date("Y m", "2014 12", true)) != num2fa(mysql2date("Y m", "2014 12", false))) patch_func(true);

	$remove_filters = array(
		'date_i18n','get_post_time','get_comment_date',	'get_comment_time',	'get_the_date',	'the_date', 'get_the_time',	'the_time',
		'get_the_modified_date','the_modified_date','get_the_modified_time','the_modified_time','get_post_modified_time', 'number_format_i18n'
	); 
	for ($i = 0; $i < sizeof($remove_filters); $i++) remove_all_filters($remove_filters[$i]);

	add_filter('date_i18n', create_function('$g,$f,$t','return wpfa_pdate($f,intval($t));'), 10, 3);

	if(get_option('wpfa_nums') === false) add_option('wpfa_nums', 'on');
	if(get_option('wpfa_nums') == "on"){
		add_filter('date_i18n', 'num2fa');
		add_filter('number_format_i18n', create_function('$s','return num2fa($s,true);'));
	}
}

function wpfa_nums(){
    register_setting('general', 'wpfa_nums', 'esc_attr');
    add_settings_field('wpfa_nums', '<label for="wpfa_nums">ساختار اعداد</label>', create_function('',
    	'$val = get_option(\'wpfa_nums\');$checked = $val == "on" ? \' checked="checked"\' : "";
		echo \'<label><input type="checkbox" id="wpfa_nums" name="wpfa_nums"\'.$checked.\' /> <span>فارسی ۰۱۲۳۴۵۶۷۸۹</span></label>\';'), 'general');
}

register_activation_hook(__FILE__ , 'wpfa_activate');
register_deactivation_hook( __FILE__ , 'wpfa_deactivate');
add_action('init', 'post2pdate');
add_action('admin_init', 'wpfa_nums');
add_action('admin_footer', 'timestampdiv');
add_action('wp_print_scripts', 'dreg_jsfa', 98);
add_action('plugins_loaded', 'wpfa_load', 98);