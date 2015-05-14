<?php
/*
 * Replicates WP post tables for anything ya want.
 */

if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Msbd_Adsm_Table extends WP_List_Table {
    var $flag = 'all';

    function _construct() {
        global $status, $page;
        parent::__construct( array(
            'singular'  => 'advertisement',
            'plural'    => 'advertisements',
            'ajax'      => false
        ));
    }

    function column_default($item, $column_name){
        return print_r($item,true); //Show the whole array for troubleshooting purposes
    }
    
    
    function column_id($item){
        $actions = array(
            'edit' => sprintf('<a href="?page=%s&action=%s&advertisement=%s">Edit</a>',$_REQUEST['page'],'pending',$item->id),
            'delete' => sprintf('<a href="?page=%s&action=%s&advertisement=%s">Delete</a>',$_REQUEST['page'],'delete',$item->id),
        );
        return sprintf('%1$s%2$s',$item->id,$this->row_actions($actions));
    }
    
    
    function column_date_time($item) {
        return $item->date_time;
    }
    function column_sponsor_type($item) {
        return $item->sponsor_type;
    }
    function column_content_type($item) {
        return $item->content_type;
    }
    function column_adv_sizes($item) {
        return $item->adv_sizes;
    }
    function column_width($item) {
        return $item->width;
    }
    function column_height($item) {
        return $item->height;
    }
    function column_remark($item) {
        return $item->remark;
    }
    function column_script($item) {
        
        $var_short_code = '<span class="msbd-shortcode">[adsmp';
        $var_short_code .= ' type="'.$item->content_type.'"';
        
        if( !empty($item->sponsor_type) ) {
            $var_short_code .= ' sponsor="'.$item->sponsor_type.'"';
        }
        
        if( !empty($item->width) && !empty($item->height) ) {        
            $var_short_code .= ' width="'.$item->width.'" height="'.$item->height.'"';
            
            //$output = sprintf('<span class="msbd-shortcode">[adsmp width="%s" height="%s" sponsor="%s" type="%s"]</div>', $item->width, $item->height, $item->sponsor_type, $item->content_type );
        } else {
            //$output = sprintf('<span class="msbd-shortcode">[adsmp size="%s" sponsor="%s" type="%s"]</div>', $item->adv_sizes, $item->sponsor_type, $item->content_type );
            
            $var_short_code .= ' size="'.$item->adv_sizes.'"';
        }
        
        $var_short_code .= ']</div>';
        
        return $var_short_code;
    }
    function column_status($item) {
        return $item->status;
    }
    function column_action_by_ip($item) {
        return $item->action_by_ip;
    }
    
    
    function column_cb($item){
        return sprintf('<input type="checkbox" name="advertisement[]" value="%1$s" />',$item->id);
    }

    function column_edit($item){
        return sprintf('<a href="'.get_site_url().'/wp-admin/admin.php?page=msbd_adsmp_add_edit&ad_id=' . $item->id . '"><span class="button rr-button">Edit</span></a>',$item->id);
    }

    function get_columns() {
        return $columns = array(
            'cb'                  => '<input type="checkbox" />',
            'sponsor_type'   => 'Sponsor Type',            
            'script'         => 'Short Code',
            'remark'         => 'Remark',
            'status'   => 'Status',
            'edit'            => 'Edit',
        );
    }

    function get_sortable_columns() {
        return $sortable = array(
            'sponsor_type'   => array('sponsor_type',false),
            'status'   => array('status',false),
        );
    }



    function get_bulk_actions() {
        $actions = array();
        $actions['inactive'] = 'Set to Inactive';
        $actions['active'] = 'Set to Active';        
        $actions['delete'] = 'Delete';
        return $actions;
    }



    function process_bulk_action() {
        global $wpdb, $msbdAdsMang;
        
        $output = '';
        
        if (isset($_REQUEST['advertisement'])) {
            
            $ids = is_array($_REQUEST['advertisement']) ? $_REQUEST['advertisement'] : array($_REQUEST['advertisement']);
            
            $this_action = '';
            if ('active' === $this->current_action()) {
                $this_action = 'active';
                $action_alert_type = 'set to active';
            } else if ('inactive' === $this->current_action()) {
                $this_action = 'inactive';
                $action_alert_type = 'set to inactive';
            } else if ('delete' === $this->current_action()) {
                $this_action = 'delete';
                $action_alert_type = 'Deleted';
            } else if (false === $this->current_action()) {
                $this_action = 'false';
                $action_alert_type = 'false';
            }
            
            if (!empty($ids)) {
                foreach ($ids as $id) {
                    $output .= $id . ' ';
                    switch ($this_action) {
                        case 'active':
                        case 'inactive':
                            $wpdb->update($wpdb->msbd_adsmp_main_tbl, array('status' => $this_action), array('id' => $id));
                            break;

                        case 'delete':
                            $wpdb->query("DELETE FROM $wpdb->msbd_adsmp_main_tbl WHERE id=\"$id\"");
                            break;
                    }
                }
                if (count($ids) == 1) {
                    $action_alert = '1 advertisement has been successfully ' . $action_alert_type . '.';
                } else {
                    $action_alert = count($ids) . ' advertisements have been successfully ' . $action_alert_type . '.';
                }
                if ($this_action === 'false') { 
                    $action_alert = 'You must select an action.';
                }
                echo '<div class="updated" style="padding: 10px;">' . $action_alert . '</div>';
            }
        }
    }
    
    
    function prepare_items($flag = 'pending') {
        
        global $wpdb, $msbdAdsMang;
        
        switch($flag) {
            case "pending":
            case "trash":
            case "approve":
                $this->flag = $flag;
                break;
                
            case "all":
            case "":
                $this->flag = "";
                break;
        }
        
        $per_page = 10;
        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->process_bulk_action();
        
        $whereStatement = ($this->flag !== '') ? " WHERE status='{$this->flag}'" : "";
        
        $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'id';
        $order = (!empty($_GET['order'])) ? $_GET['order'] : 'desc';
        $orderStatement = ' ORDER BY ' . $orderby . ' ' . $order;
        
        $data = $wpdb->get_results("SELECT * FROM " . $wpdb->msbd_adsmp_main_tbl . $whereStatement . $orderStatement);
        $current_page = $this->get_pagenum();
        $total_items = count($data);
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        $this->items = $data;
        
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items/$per_page)
        ));
    }
    
}
/* end of msbd-adsm-admin-tables.php */
