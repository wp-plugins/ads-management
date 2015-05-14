<?php
/*
 * Plugin specific Database functions
 */

class MsbdAdsMDb extends MsbdCrud {

    var $parent;
    var $debug_queries = FALSE;
    var $adv_sizes;

    function __construct($parent) {
        $this->parent = $parent;
        
        $this->adv_sizes = array(
            "responsive" => array("name"=>"responsive", "width"=>"0", "height"=>"0"),
            "large-leaderboard" => array("name"=>"large-leaderboard", "width"=>"970", "height"=>"90"),
            "leaderboard" => array("name"=>"leaderboard", "width"=>"728", "height"=>"90"),
            "billboard" => array("name"=>"billboard", "width"=>"970", "height"=>250),
            "banner" => array("name"=>"banner", "width"=>"468", "height"=>"60"),
            "large-skyscraper" => array("name"=>"large-skyscraper", "width"=>"300", "height"=>"600"),
            "wide-skyscraper" => array("name"=>"wide-skyscraper", "width"=>"160", "height"=>"600"),
            "skyscraper" => array("name"=>"skyscraper", "width"=>"120", "height"=>"600"),
            "portrait" => array("name"=>"portrait", "width"=>"300", "height"=>"1050"),
            "vertical-banner" => array("name"=>"vertical-banner", "width"=>"120", "height"=>"240"),
            "large-rectangle" => array("name"=>"large-rectangle", "width"=>"336", "height"=>"280"),
            "medium-rectangle" => array("name"=>"medium-rectangle", "width"=>"300", "height"=>"250"),
            "small-rectangle" => array("name"=>"small-rectangle", "width"=>"180", "height"=>"150"),
            "square" => array("name"=>"square", "width"=>"250", "height"=>"250"),
            "small-square" => array("name"=>"small-square", "width"=>"200", "height"=>"200"),
            "mini-square" => array("name"=>"mini-square", "width"=>"125", "height"=>"125"),
            "line-links-big" => array("name"=>"line-links-big", "width"=>"728", "height"=>"15"),
            "line-links" => array("name"=>"line-links", "width"=>"468", "height"=>"15"),
        );
        
        parent::__construct();
    }



    function adsmp_serve_adv($oArray) {
        global $wpdb, $post;
        
        extract($oArray);
        
        $query = "SELECT main.*, terms_rel.term_slug FROM {$wpdb->msbd_adsmp_main_tbl} main";
        $query .= " LEFT OUTER JOIN {$wpdb->msbd_adsmp_terms_rel_tbl} terms_rel";
        $query .= " ON main.id = terms_rel.adv_id";
        $query .= " WHERE status='active' AND content_type='{$content_type}'";
        
        if( !empty($sponsor_type) ) {
            $query .= " AND sponsor_type='{$sponsor_type}'";
        }
        
        if( $width!="" && $height!="" ) {
            $query .= " AND height='{$height}' AND width='{$width}'";
        } else {
            $query .= " AND adv_sizes='{$adv_sizes}'";
        }


        if( !is_page() && isset($post->ID) ) {
            $post_categories = wp_get_post_categories( $post->ID );
            $term_slugs = "";
            
            
            //$query .= " AND term_slug IN (";
            
            $isFirst=true;
            foreach($post_categories as $c){
                $cat = get_category( $c );
                
                if(!$isFirst) {
                    //$query .= ",";
                    $term_slugs .= ","; 
                }
                
                $term_slugs .= "'".$cat->slug."'";
                //$query .= "'".$cat->slug."'";
                $isFirst = false;            
            }
            //$query .= ")";
            
            if(!empty($term_slugs)) {
                $query .= " AND term_slug IN ($term_slugs)";
            }
        }
    
        $query .= " ORDER BY rand() LIMIT 1";
        
        
        //echo "<br>".$query."<br>";
        
        $output = $wpdb->get_row($query);
        //$output = $wpdb->get_results($query);
        
        //print_r($output);
        
        return (array)$output;
        
    }
    
    


    function delete_terms_rel($adv_id) {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->msbd_adsmp_terms_rel_tbl} WHERE adv_id='{$adv_id}'");
    }
    
    

    function get_adv_record($id, $isDebug=false) {
        global $wpdb;
        $var_query = "SELECT * FROM {$wpdb->msbd_adsmp_main_tbl} WHERE id='{$id}' LIMIT 1";
        
        if($isDebug)
            echo 'Query: '.$var_query."<br>";
        
        $var_adv = $wpdb->get_row($var_query);
        
        if ( empty($var_adv) || is_null($var_adv) ) {
            return array();
        }
        
        
        $var_query = "SELECT * FROM {$wpdb->msbd_adsmp_terms_rel_tbl} WHERE adv_id='{$id}'";
        $var_terms_rel = $wpdb->get_results($var_query);
        
        $rs = array(
            "adv" => (array)$var_adv,
            "terms_rel" => (array)$var_terms_rel
        );
        
        
        if($isDebug) {
            echo "RS:: <br>";
            print_r($rs);
        }   
            
        return $rs;
    }
    


    function check_exist($filter, $isDebug=false) {
        global $wpdb;
        $var_query = 'SELECT * FROM ' . $wpdb->msbd_adsmp_main_tbl . ' WHERE '.$filter.' LIMIT 1';
        if($isDebug)
            echo 'Query: '.$var_query."<br>";
        
        $output = $wpdb->get_row($var_query);
        
        $rs = false;
        if($output)
            $rs = true;
                
        if($isDebug)
            echo 'RS:: '.$rs."<br>";
        
        return $rs;
    }
    

    function get_sponsor_types($uid='') {
        
        $sponsor_types = array(
            0 => array("id"=>1, "title"=>"adsense"),
            1 => array("id"=>2, "title"=>"amazon"),
            2 => array("id"=>3, "title"=>"clickbank"),
            3 => array("id"=>99, "title"=>"affiliate"),
        );
        
        if( empty($uid) ) {
            return $sponsor_types;
        } else if( isset($sponsor_types[$uid]) ) {
            return $sponsor_types[$uid];
        }

        return false;
    }



    function get_adv_sizes($name='') {        
        $rs = array();
        if(empty($name))
            $rs = $this->adv_sizes;
        else if(isset($this->adv_sizes[$name]))
            $rs = $this->adv_sizes[$name];
            
        return $rs;
            
    }

}
/* end of file msbd-adsm-db.php */


