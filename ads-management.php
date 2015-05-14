<?php
/*
Plugin Name: Ads Management
Plugin URI: http://microsolutionsbd.com/
Description: Ads Management Plugin Description.
Version: 0.2.2
Author: Micro Solutions Bangladesh
Author URI: http://microsolutionsbd.com/
Text Domain: msbd-adsmp
License: GPL2
*/

define('MSBD_ADSMP_URL', trailingslashit(plugins_url(basename(dirname(__FILE__)))));

class AdsManagement {
    
    var $version = '0.2.2';
    var $plugin_name = 'Ads Management';

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
        
        $this->adsm_options_name = 'msbd_adsmp_options';
        
        $this->msbd_adsmp_options_obj = new MsbdAdsMOptions($this);
        
        $this->db = new MsbdAdsMDb($this);
        $this->admin = new MsbdAdsmAdmin($this);

        register_activation_hook( __FILE__, array( 'AdsManagement', 'msbd_adsmp_activation_actions' ) );
        
        // Runs after WordPress has finished loading but before any headers are sent. Useful for intercepting $_GET or $_POST triggers. 
        add_action('init', array(&$this, 'init'), 1);
        add_action( 'switch_blog', array(&$this, 'init') );
        
        //Loading Styles and Scripts for this Frontend
        add_action('wp_enqueue_scripts', array(&$this, 'load_scripts_styles'), 100);

        add_shortcode( 'manage_adv' , array(&$this, 'msbd_shortcode_manage_advertisement') );
        add_shortcode( 'adsmp' , array(&$this, 'msbd_shortcode_manage_advertisement') );
        
        //
        add_filter('the_content', array(&$this, 'msbd_adsmp_monetize_content'), 99);
        
    }



    function msbd_shortcode_manage_advertisement($atts, $content, $shortcode) {

        extract(shortcode_atts(
            array(
                'sponsor' => '',
                'width' => 0,
                'height' => 0,
                'size' => 'banner',
                'type' => 'mix',
                'wrap_class' => ''
            )
        , $atts));
        
        $sponsor = strtolower($sponsor);
        $content_type = strtolower($type);
        
        $newAtts = array(
            "sponsor_type"=>$sponsor, 
            "content_type"=>$content_type, 
            "width"=>$width, 
            "height"=>$height, 
            "adv_sizes"=>$size
        );
        $record = $this->db->adsmp_serve_adv($newAtts);

        if(!empty($record)){
            $wrap_class .= empty($wrap_class) ? "sponsor-ads" : " sponsor-ads";
            $wrap_class .= " ".$sponsor."-adv ".$content_type."-adv";
            
            $caption = !empty($content) ? '<div class="caption">'.$content.'</div>' : "";
            
            return sprintf('<div class="%s">%s%s</div><!-- /.sponsor-ads -->', $wrap_class, stripslashes($record['script']), $caption);
        }        
        
        return "";
    }


    function load_scripts_styles() {
        
        $var_add_styles = $this->adsmp_options['msbd_adsmp_add_styles'];
        if($var_add_styles=="checked") {
            wp_enqueue_style( "msbd-adsmp", MSBD_ADSMP_URL . 'css/msbd-adsmp.css', false, false );
        }
    }
    

    function init() {
        global $wpdb;
        $wpdb->msbd_adsmp_main_tbl = "{$wpdb->prefix}msbd_adsmp";
        $wpdb->msbd_adsmp_terms_rel_tbl = "{$wpdb->prefix}msbd_adsmp_adv_to_categories";
        
        $this->msbd_adsmp_options_obj->update_options();
        $this->adsmp_options = $this->msbd_adsmp_options_obj->get_option();
    }


    public static function msbd_adsmp_activation_actions() {
        global $wpdb;
        $wpdb->msbd_adsmp_main_tbl = "{$wpdb->prefix}msbd_adsmp";
        $wpdb->msbd_adsmp_terms_rel_tbl = "{$wpdb->prefix}msbd_adsmp_adv_to_categories";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        
        $plugin_data    = get_plugin_data( __FILE__ );
        $newest_version = $plugin_data['Version'];
        
        $options = get_option("msbd_adsmp_options");
        
        if (isset($options['version'])) {
            $current_version = $options['version'];
        } else { //we were in version 0.1, now we updated
            $current_version = '';
        }
        

        $sql = "CREATE TABLE {$wpdb->msbd_adsmp_main_tbl} (
                 id int(11) unsigned NOT NULL AUTO_INCREMENT,
                 sponsor_type varchar(50) DEFAULT '',
                 content_type enum('mix','image','text') NOT NULL DEFAULT 'mix',
                 script text,
                 remark text,
                 adv_sizes varchar(50) DEFAULT NULL,
                 width varchar(20) DEFAULT NULL,
                 height varchar(20) DEFAULT NULL,
                 date_time datetime NOT NULL,
                 action_by_ip varchar(15) DEFAULT NULL,
                 status enum('active','inactive') NOT NULL DEFAULT 'inactive',
                 PRIMARY KEY  (id)
                )
                CHARACTER SET utf8
                COLLATE utf8_general_ci;";
        dbDelta($sql);
    
        $sql = "CREATE TABLE {$wpdb->msbd_adsmp_terms_rel_tbl} (
                 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                 adv_id int(11) unsigned NOT NULL,
                 term_id bigint(20) unsigned NOT NULL,
                 term_slug varchar(200) NOT NULL,
                 PRIMARY KEY  (id)
                )
                CHARACTER SET utf8
                COLLATE utf8_general_ci;";
        dbDelta($sql);
        
        
        
        if( $current_version=="0.1" ) {
            
            
            $var_advs = $wpdb->get_results("SELECT * FROM {$wpdb->msbd_adsmp_main_tbl}");
            $var_terms_rel = $wpdb->get_results("SELECT * FROM {$wpdb->msbd_adsmp_terms_rel_tbl}");
            
            if( !empty($var_advs) && empty($var_terms_rel) ) {
            
                $var_cats = get_categories('hide_empty=0&orderby=id&order=ASC');
                
                foreach($var_advs as $adv) {
                    
                    foreach($var_cats as $cat) {
                        $var_data = array(
                            'adv_id'       => $adv->id,
                            'term_id'   => $cat->term_id,
                            'term_slug'   => $cat->slug,
                        );
                        
                        $var_rs = $wpdb->insert($wpdb->msbd_adsmp_terms_rel_tbl, $var_data);
                    }
                    
                }
            }
        }
    }



    /*
     * @$cat integer category id
     */
    function hierarchical_category_array( $cat ) {
        $rs = array();
        $next = get_categories('hide_empty=false&orderby=name&order=ASC&parent=' . $cat);

        if( $next ) :
            foreach( $next as $cat ) :
                $catArray = (array) $cat;
                $child = $this->hierarchical_category_array( $cat->term_id );
                $catArray["child"] = $child;
                array_push($rs, $catArray);
            endforeach;    
        endif;
        
        return $rs;
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



/*
 * @ Md. Shah Alom
 * @ shahalom.amin@gmail.com
 * @ used to echo array's element
************************************************************/
function adsmp_echo($data, $arrayKey='', $isEcho=true, $isHtml=false) {
        
    $arrayKeys = explode(".", $arrayKey);   
    
    $rs = $data;
    
    if( is_object($data) ) {
        $rs = "OBJECT";
    } else if( is_array($data) ) {
        
        if( count($arrayKeys)==1 && isset($data[$arrayKey]) ) {
            $rs = $data[$arrayKey];
            //echo '2: ';
        } else if( count($arrayKeys)==2 && ( isset( $data[$arrayKeys[0]] ) && isset( $data[$arrayKeys[0]][$arrayKeys[1]] ) ) ) {          
            $rs = $data[$arrayKeys[0]][$arrayKeys[1]];
            //echo '3: ';
        } else if( count($arrayKeys)==3 && ( isset( $data[$arrayKeys[0]] ) && isset( $data[$arrayKeys[0]][$arrayKeys[1]] )  && isset( $data[$arrayKeys[0]][$arrayKeys[1]][$arrayKeys[2]] ) ) ) {          
            $rs = $data[$arrayKeys[0]][$arrayKeys[1]][$arrayKeys[2]];
            //echo '4: ';
        } else {
            $rs = "";
        }
    } else {
        $rs = "";   
    }
    
            
    if($isHtml===true) {           
        if($isEcho)
            echo $rs;
        else
            return $rs;
            
    } else {
        //echo '**: '; print_r($rs);
        if($isEcho)
            echo htmlspecialchars($rs);
        else {
            if( is_array($rs) )
                return $rs;
            else
                return htmlspecialchars($rs);
        }            
    }    
}



  function adsmp_html_draw_checkbox($name, $id='', $value='', $checkedVal='', $parameters='') {
    
    $html = '<input type="checkbox" name="' . $name . '"';
    
    if (!empty($id)) {
        $html .= ' id="'.$id.'"';
    }

    $html .= ' value="' . $value . '"';

    if( is_array($checkedVal) && in_array($value, $checkedVal) ) {
        $html .= ' checked="checked"';
    } else if ( $value == $checkedVal ) {
      $html .= ' checked="checked"';
    }

    if (!empty($parameters)) {
        $html .= ' ' . $parameters;
    }

    $html .= ' />';

    return $html;
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
