<?php 
/*
    wp-farsi admin inc
 */
function wpfa_load_first() {
    $plugins = get_option('active_plugins');
    $path = plugin_basename( WPFA_FILE );
    if (is_array($plugins) and $plugins[0] !== $path) {
        $key = array_search($path, $plugins);
        array_splice($plugins, $key, 1);
        array_unshift($plugins, $path);
        update_option('active_plugins', $plugins);
    }
}

function timestampdiv() {
?><script type='text/javascript'>
    var c = ("۰,۱,۲,۳,۴,۵,۶,۷,۸,۹,Jan,Feb,Mar,Apr,May,Jun,Jul,Aug,Sep,Oct,Nov,Dec").split(",");
    var d = ("0,1,2,3,4,5,6,7,8,9,فرو,ارد,خرد,تیر,مرد,شهر,مهر,آبا,آذر,دی,بهم,اسف").split(",");
    jQuery(document).ready(function(){
    jQuery("#timestampdiv,.timestamp-wrap,.inline-edit-date,.jj,.mm,.aa,.hh,.mn,.ss").html(function(a,b){
    jQuery.each(c,function(a,c){b=b.replace(new RegExp(c,'g'),d[a])});return b});
    jQuery("#mm option[value='"+jQuery('#hidden_mm').val()+"']").attr("selected","selected")});
</script><?php 
}

function dreg_jsfa() {
    wp_deregister_script('ztjalali_reg_admin_js');
    wp_deregister_script('ztjalali_reg_date_js');
    wp_deregister_script('wpp_admin');
}

function wpfa_nums_field() {
    register_setting('general', 'wpfa_nums', 'esc_attr');
    add_settings_field('wpfa_nums', '<label for="wpfa_nums">ساختار اعداد</label>', create_function('', '
        echo \'<label><input type="checkbox" name="wpfa_nums" ' . (WPFA_NUMS === "on" ? "checked" : "") . '/> 
        <span>فارسی ۰۱۲۳۴۵۶۷۸۹</span></label>\';'), 'general');
}

if (numbers_fa(mysql2date("Y", "2015", 0)) === "۱۳۹۴"){
    add_action('admin_footer', 'timestampdiv');
    add_action('wp_print_scripts', 'dreg_jsfa', 900);
}