<?php
/*
Plugin Name: Ads Management
Plugin URI: http://microsolutionsbd.com/
Description: Ads Management Plugin Description.
Version: 0.1
Author: Micro Solutions Bangladesh
Author URI: http://microsolutionsbd.com/
Text Domain: msbd-adsmp
License: GPL2
*/

define('MSBD_ADSMP_URL', trailingslashit(plugins_url(basename(dirname(__FILE__)))));

class AdsManagement {
    
    var $version = '0.1';
    var $plugin_name = 'Ads Management';
    
    var $sqltable = 'msbd_adsmp';

    var $admin;
    var $db;    

    /**
     * @var msbd_adsmp_options_obj
     */
    var $msbd_adsmp_options_obj;    

    /**
     * The variable that stores all current options
     */
    var $adsmp_options;


    function __construct() {
        
        global $wpdb;
        $this->sqltable = $wpdb->prefix . $this->sqltable;
        
        $this->adsm_options_name = 'msbd_adsmp_options';
        
        $this->msbd_adsmp_options_obj = new MsbdAdsMOptions($this);
        
        $this->db = new MsbdAdsMDb($this);
        $this->admin = new MsbdAdsmAdmin($this);


        // 'plugins_loaded' hook is called once any activated plugins have been loaded. Is generally used for immediate filter setup, or plugin overrides.
        add_action('plugins_loaded', array(&$this, 'on_load'));
        
        // Runs after WordPress has finished loading but before any headers are sent. Useful for intercepting $_GET or $_POST triggers. 
        add_action('init', array(&$this, 'init'));        
        
        //Loading Styles and Scripts for this Frontend
        add_action('wp_enqueue_scripts', array(&$this, 'load_scripts_styles'), 100);


        add_shortcode( 'manage_adv' , array(&$this, 'msbd_shortcode_manage_advertisement') );
        
        
        //
        add_filter('the_content', array(&$this, 'msbd_adsmp_monetize_content'), 99);
        
    }



    function msbd_adsmp_monetize_content($content) {
        
        if( is_page() ) {
            return $content;
        }
        
        $top_ad = '';
        $bottom_ad = '';
        
        $var_ad = $this->adsmp_options['msbd_adsmp_content_top_script'];
        if( !empty($var_ad) ) {
            $top_ad = sprintf('<div class="sponsor-ads content-top">%s</div><!-- /.sponsor-ads -->', stripslashes($var_ad));
        }
        
        $var_ad = $this->adsmp_options['msbd_adsmp_content_bottom_script'];
        if( !empty($var_ad) ) {
            $bottom_ad = sprintf('<div class="sponsor-ads content-bottom">%s</div><!-- /.sponsor-ads -->', stripslashes($var_ad));
        }
        
        return sprintf('%s%s%s', $top_ad, $content, $bottom_ad);            
    }



    function msbd_shortcode_manage_advertisement($atts, $content, $shortcode) {       
        
        $return_html = '';
        
        extract(shortcode_atts(
            array(
                'sponsor' => 'adsense',
                'width' => 0,
                'height' => 0,
                'size' => 'banner',
                'type' => 'mix',
                'wrap_class' => ''
            )
        , $atts));

        
        $sponsor = strtolower($sponsor);
        $content_type = strtolower($type);
        
        $wrap_class .= empty($wrap_class) ? "sponsor-ads" : " sponsor-ads";
        $wrap_class .= " ".$sponsor."-adv ".$content_type."-adv";              
                
        if( $width!="" && $height!="" ) {
            $this->db->where("height", $height);
            $this->db->where("width", $width);
            $wrap_class .= " adv-size-".$width."x".$height;
        } else {
            $this->db->where("adv_sizes", $size);
            $wrap_class .= " adv-size-".$width."x".$height;
        }
        $this->db->where("sponsor_type", $sponsor);
        $this->db->where("content_type", $content_type);
        $this->db->where("status", 'active');
        
        $record =(array) $this->db->get(NULL, TRUE);        
        
        if(!empty($record)){
            $return_html = sprintf('<div class="%s">%s</div><!-- /.sponsor-ads -->', $wrap_class, stripslashes($record['script']));
        }        
        
        return $return_html;
    }
    




    function init() {
        $this->msbd_adsmp_options_obj->update_options();
        $this->adsmp_options = $this->msbd_adsmp_options_obj->get_option();
    }


    function load_scripts_styles() {
        
        $var_add_styles = $this->adsmp_options['msbd_adsmp_add_styles'];
        if($var_add_styles=="checked") {
            wp_enqueue_style( "msbd-adsmp", MSBD_ADSMP_URL . 'css/msbd-adsmp.css', false, false );
        }
    }
    




    function on_load() {
        //$plugin_dir = basename(dirname(__FILE__));
        //load_plugin_textdomain( 'msbd-adsmp', false, $plugin_dir );
    }



    /*
     * @ $field_type = text, email, number, html, no_html, custom_html, html_js default text
     */
    function msbd_sanitization($data, $field_type='text', $oArray=array()) {        
        
        $output = '';

        switch($field_type) {           
            
            case 'number':
                $output = sanitize_text_field($data);
                $output = intval($output);
                break;
            
            case 'email':
                $output = sanitize_email($data);
                $output = is_email($output);//returned false if not valid
                break;
                
            case 'textarea': 
                $output = esc_textarea($data);
                break;
            
            case 'html':                                         
                $output = wp_kses_post($data);
                break;
            
            case 'custom_html':                    
                $allowedTags = isset($oArray['allowedTags']) ? $oArray['allowedTags'] : "";                                        
                $output = wp_kses($data, $allowedTags);
                break;
            
            case 'no_html':                                        
                $output = strip_tags( $data );
                //$output = stripslashes( $output );
                break;
            
            
            case 'html_js':
                $output = $data;
                break;
            
            
            case 'text':
            default:
                $output = sanitize_text_field($data);
                break;
        }
        
        return $output;

    }

}
/* end of class AdsManagement */





// Define the "dump" function, a debug helper.
if (!function_exists('dump')) {
    function dump ($var, $label = 'Dump', $echo = TRUE) {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
        $output = '<pre style="background: #FFFEEF; color: #000; border: 1px dotted #000; padding: 10px; margin: 10px 0; text-align: left;">' . $label . ' => ' . $output . '</pre>';
        
        if ($echo == TRUE) {echo $output;}else {return $output;}
    }
}
if (!function_exists('dump_exit')) {
    function dump_exit($var, $label = 'Dump', $echo = TRUE) {
        dump ($var, $label, $echo);
        exit;
    }
}


// Admin Markup Helper
if (!class_exists('MsbdAdsMAdminHelper')) {
    require_once('views/view-helper/admin-view-helper-functions.php');
}

// Database Crud Library
if (!class_exists('MsbdCrud')) {
    require_once('libs/msbd-crud.php');
}

//
if (!class_exists('MsbdAdsMOptions')) {
    require_once('libs/msbd-adsm-options.php');
}


require_once('libs/msbd-adsm-admin.php');
require_once('libs/msbd-adsm-db.php');
require_once("views/msbd-ads-admin-add-edit.php");

global $msbdAdsMang;
$msbdAdsMang = new AdsManagement();

/* end of file ads-management.php */
