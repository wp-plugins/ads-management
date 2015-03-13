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
        $this->sqltable = $this->parent->sqltable;
        
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

    function create_update_database() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                
        $sql = "CREATE TABLE $this->sqltable (
                 id int(11) NOT NULL AUTO_INCREMENT,
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
                PRIMARY KEY (id)
                )
                CHARACTER SET utf8
                COLLATE utf8_general_ci;";
        dbDelta($sql);
    }



    function check_exist($filter, $isDebug=false) {
        global $wpdb;
        $var_query = 'SELECT * FROM ' . $this->sqltable . ' WHERE '.$filter.' LIMIT 1';
        if($isDebug)
            echo 'Query: '.$var_query."<br>";
        
        $output = $wpdb->get_row($var_query);
        
        //print_r($output);
        
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
