<?php
class MsbdAdsAdminAddEdit {

    var $core;

    var $ad_id;    
    var $date_time;
    var $sponsor_type;
    var $content_type;
    var $script;
    var $remark;
    var $adv_sizes;  
    var $width;
    var $height;
    var $status;
    var $action_by_ip;


    function __construct($core) {
        $this->core = $core;
        
        if (isset($_GET['ad_id'])) {
            $this->ad_id = $_GET['ad_id'];
        }
        $this->date_time = $rDateTime = date('Y-m-d H:i:s');
        $this->action_by_ip = $_SERVER['REMOTE_ADDR'];
        
        $this->check_add_update();
        $this->display_form();
    }




    function check_add_update() {
        $output = '';
        
        if (isset($_POST['msbd_adsmp_save']) && $_POST['msbd_adsmp_save'] == 'save') {            
            
            $this->sponsor_type = $this->core->msbd_sanitization($_POST['sponsor_type'], 'text');
            
            $this->content_type = $this->core->msbd_sanitization($_POST['content_type'], 'text');
            
            $this->script    = $this->core->msbd_sanitization($_POST['script'], 'html_js');
            
            $this->remark    = $this->core->msbd_sanitization($_POST['remark'], 'html');
            
            $this->adv_sizes = $this->core->msbd_sanitization($_POST['adv_sizes'], 'text');
            $this->width    = $this->core->msbd_sanitization($_POST['width'], 'number');                    
            $this->height   = $this->core->msbd_sanitization($_POST['height'], 'number');
            
            $this->status   = $this->core->msbd_sanitization($_POST['status'], 'text');
            
            $newdata = array(
                    'date_time'       => $this->date_time,
                    'sponsor_type'   => $this->sponsor_type,
                    'content_type'   => $this->content_type,
                    'script'  => $this->script,
                    'remark'  => $this->remark,
                    'adv_sizes'  => $this->adv_sizes,
                    'width'    => $this->width,
                    'height'   => $this->height,
                    'status'   => $this->status,
                    'action_by_ip'     => $this->action_by_ip
            );
            //dump($newdata, 'NEW DATA');
            
            
            $validData = true;
            
            if ($this->adv_sizes=='' && empty($this->width) && empty($this->height) ) {
                $output .= '<div class="notice error">You must choose a predefined size of advertisement or provide width and height combination.</div>';
                $validData = false;
            }            
            if ($this->sponsor_type  == '') {
                $output .= '<div class="notice error">You must choose a type of advertisement.</div>';
                $validData = false;
            }
            if ($this->content_type == '') {
                $output .= '<div class="notice error">You must choose a type of advertisement content.</div>';
                $validData = false;
            }
            if ($this->script == '') {
                $output .= '<div class="notice error">You must add the script of advertisement.</div>';
                $validData = false;
            }           
            
            $var_filter = "sponsor_type='$this->sponsor_type' AND content_type='$this->content_type' AND width='$this->width' AND height='$this->height' AND adv_sizes='$this->adv_sizes'";
            if($this->ad_id!="") {
                $var_filter .= " AND id!='$this->ad_id'";
            }
            
            /*
            // After the 0.1 version plugin can create multiple advertisement with same configuration.
            // So the unique 
            $isExist = $this->core->db->check_exist($var_filter, false);            
            if ($isExist) {
                $output .= '<div class="notice error">Advertisement from the sponsor with same size, and type is exist!</div>';
                $validData = false;
            }
            */
            
            if ($validData) {
                $this->core->db->set_table("main_tbl");
                $rs = $this->core->db->save($newdata, $this->ad_id);
                $output .= '<div class="notice success">The advertisement has been saved.</div>';
                
                $categories = isset($_POST['categories']) ? $_POST['categories'] : array();
                if( !empty($categories) ) {
                    
                    if( !empty($this->ad_id) ) {
                        $this->core->db->delete_terms_rel($this->ad_id);
                    } else {
                        $this->ad_id = $rs;
                    }
                    
                    foreach($categories as $i=>$v) {
                        $terms_rel_data = array("adv_id"=>$this->ad_id, "term_id"=>$i, "term_slug"=>$v);
                        $this->core->db->set_table("terms_rel_tbl");
                        $this->core->db->save($terms_rel_data);
                    }
                }
            }
            
            //wp_redirect( MSBD_ADSMP_URL."wp-admin/admin.php?page=msbd_adsmp_manage" );
            //exit;
            
        }
        echo $output;
    }




    function display_form($record = '') {
        
        
        if ($this->ad_id && empty($record)) {
            
            $rs = $this->core->db->get_adv_record($this->ad_id, false);            
            $this->display_form($rs);
            return;
        }
        
        $isEdit = !empty($this->ad_id) ? 1 : 0;
        
        $field_read_only = !empty($this->ad_id) ? ' readonly=""' : '';
        $field_disable = !empty($this->ad_id) ? ' disabled="true"' : '';
        
        $var_sponsor_type = adsmp_echo( $record, 'adv.sponsor_type', false);
        $var_content_type = adsmp_echo( $record, 'adv.content_type', false);
        $var_adv_sizes = adsmp_echo( $record, 'adv.adv_sizes', false);
        ?>
<form method="post" action="">
    <input type="hidden" name="ad_id" value="<?php echo $this->ad_id; ?>" />
    
    <input type="hidden" name="msbd_adsmp_save" value="save" />
    <input type="hidden" name="msbd_adsmp_edit" value="<?php echo $isEdit; ?>" />
    
    <?php
    if($isEdit) {
    ?>
    <input type="hidden" name="sponsor_type" value="<?php echo $var_sponsor_type; ?>" />
    <input type="hidden" name="content_type" value="<?php echo $var_content_type; ?>" />
    <input type="hidden" name="adv_sizes" value="<?php echo $var_adv_sizes; ?>" />
    <?php
    }
    ?>
    
    <div class="form-table">        
        <div class="form-row">
            <div class="grid_3"><label for="sponsor_type">Advertisement Type</label></div>
            <div class="grid_5">
                <?php  
                if($isEdit) {
                    echo $this->get_sponsor_options('class="select" type="text" id="sponsor_type"'.$field_disable, $var_sponsor_type );                 
                } else {
                    echo $this->get_sponsor_options('class="select" type="text" name="sponsor_type" id="sponsor_type"', $var_sponsor_type ); 
                }
                ?>
            </div>
        </div>        
        
        <div class="form-row">
            <div class="grid_3"><label for="content_type">Ad Content Type</label></div>
            <div class="grid_5">
                <?php  
                if($isEdit) {
                    echo $this->get_ad_content_type_options('class="select" type="text" id="content_type"'.$field_disable, $var_content_type );                 
                } else {
                    echo $this->get_ad_content_type_options('class="select" type="text" name="content_type" id="content_type"', $var_content_type ); 
                }
                ?>
                
                <?php //echo $this->get_ad_content_type_options('class="select" type="text" name="content_type" id="content_type"'.$field_disable, $var_content_type ); ?>
            </div>
        </div>       
        
        <div class="form-row">
            <div class="grid_3"><label for="adv_sizes">Predefined Sizes</label></div>
            <div class="grid_5">
                <?php  
                if($isEdit) {
                    echo $this->get_ad_size_options('class="select" type="text" id="adsmp_adv_sizes"'.$field_disable, $var_adv_sizes );                 
                } else {
                    echo $this->get_ad_size_options('class="select" type="text" name="adv_sizes" id="adsmp_adv_sizes"', $var_adv_sizes ); 
                }
                ?>
                
                <?php
                //echo $this->get_ad_size_options('class="select" type="text" name="adv_sizes" id="adsmp_adv_sizes"'.$field_disable, $var_adv_sizes ); 
                ?>
            </div>
        </div>
        
        <div class="form-row">
            <div class="grid_3"><label for="width">Width</label></div>
            <div class="grid_5"><input class="text size-wh" type="number" name="width" id="width" value="<?php adsmp_echo( $record, 'adv.width'); ?>"<?php echo $field_read_only;?> /></div>
        </div>
        
        <div class="form-row">
            <div class="grid_3"><label for="height">Height</label></div>
            <div class="grid_5"><input class="text size-wh" type="number" name="height" id="height" value="<?php adsmp_echo( $record, 'adv.height'); ?>"<?php echo $field_read_only;?> /></div>
        </div>
        
        <div class="form-row">
            <div class="grid_3"><label for="status">Status</label></div>
            <div class="grid_5">
                <?php echo $this->get_ad_status_options('class="select" type="text" name="status" id="status"', adsmp_echo( $record, 'adv.status', false) ); ?>
            </div>
        </div>
        
        <div class="form-row">
            <div class="grid_3"><label for="script">Script</label></div>
            <div class="grid_9">
                <textarea class="text" name="script" id="script" rows="10"><?php 
                    echo stripslashes( adsmp_echo($record, 'adv.script', false) );
                ?></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="grid_3"><label for="remark">Remark</label></div>
            <div class="grid_9">
                <textarea class="text" name="remark" id="remark" rows="10"><?php 
                echo stripslashes( adsmp_echo($record, 'adv.remark', false) );
                ?></textarea>
            </div>
        </div>
        
        
        <div class="form-row">
        <?php //print_r( $record['terms_rel'] ); ?>
        </div>
        
        <div class="form-row">
            <div class="grid_3"><label for="categories">Categories</label></div>
            <div class="grid_9 adsmp-masonry-wrapper">
                <?php
                    $var_terms_slug = array();
                    if( isset($record['terms_rel']) ) {
                        foreach($record['terms_rel'] as $v) {
                            $var_terms_slug[] = $v->term_slug;
                        }
                    }
                
                    $var_cat = $this->core->hierarchical_category_array( 0 );
                    //print_r( $var_cat );
                    
                    
                    foreach($var_cat as $cat) {
                        
                        echo '<div class="grid_6 cat_box"><ul>';
                        echo $this->create_cat_item($cat, $var_terms_slug);
                        echo '</ul></div>';
                        
                        
                    }
                ?>
            </div>
            <!-- /.adsmp-masonry-wrapper -->
        </div> 
        
        
        <div class="form-row">
            <div class="grid_6">
                <input name="resetButton" type="reset" value="Reset" />
                <input name="submitButton" type="submit" value="Submit" />
            </div>
        </div>
    </div>
</form>
        <?php
    }
    
    
    
    
    
function create_cat_item($cat, $var_terms_slug) {
    
    $var_selected = in_array($cat['slug'], $var_terms_slug) ? ' checked="checked"' : "";
    
    $var_field_id = 'cat-'.$cat['term_id'];
    $var_field_name = 'categories['.$cat['term_id'].']';
    
    //$html = '<ul>';
    
    $html = '<li><label for="'.$var_field_id.'">';
    $html .= '<input type="checkbox" name="'.$var_field_name.'" value="'.$cat['slug'].'"';
    $html .= ' id="'.$var_field_id.'" class="parent-'.$cat['category_parent'].'"'.$var_selected.' />';
    $html .= $cat['name'].'</label>';                      
    
    if(!empty($cat['child'])) {
        $html .= '<ul>';
        foreach($cat['child'] as $sub_cat) {
            $html .= $this->create_cat_item($sub_cat, $var_terms_slug);
        } 
        $html .= '</ul>';       
    }
    
    $html .= '</li>';         
    //$html .= '</ul>';
    
    return $html;
}  
    
    
    
    
    function get_sponsor_options($att, $selVal='') {
        
        $html = '<select '.$att.'><option value="">-- select --</option>';
        $record =(array) $this->core->db->get_sponsor_types();
        
        foreach($record as $row) {
            if($selVal==$row['title'])
                $html .= '<option value="'.$row['title'].'" selected="selected">'.ucfirst($row['title']).'</option>';
            else
                $html .= '<option value="'.$row['title'].'">'.ucfirst($row['title']).'</option>';
        }
        $html .= '</select>';
        
        return $html;
    }
    
    
    function get_ad_status_options($att, $selVal='') {
        
        $html = '<select '.$att.'><option value="">-- select --</option>';
        $record = array(
            1 => array("value"=>"active", "title"=>"Active"),
            0 => array("value"=>"inactive", "title"=>"Inactive"),
        );
        
        foreach($record as $row) {
            if($selVal==$row['value'])
                $html .= '<option value="'.$row['value'].'" selected="selected">'.ucfirst($row['title']).'</option>';
            else
                $html .= '<option value="'.$row['value'].'">'.ucfirst($row['title']).'</option>';
        }
        $html .= '</select>';
        
        return $html;
    }
    
    
    function get_ad_content_type_options($att, $selVal='') {
        
        $html = '<select '.$att.'><option value="">-- select --</option>';
        $record = array(
            0 => "mix",
            1 => "image",
            2=>"text"
        );
        
        foreach($record as $v) {
            if($selVal==$v)
                $html .= '<option value="'.$v.'" selected="selected">'.ucfirst($v).'</option>';
            else
                $html .= '<option value="'.$v.'">'.ucfirst($v).'</option>';
        }
        $html .= '</select>';
        
        return $html;
    }
    
    
    function get_ad_size_options($att, $selVal='') {
        
        $record =(array) $this->core->db->get_adv_sizes();
        
        $html = '<select '.$att.'><option value="">-- Custom Size --</option>';
        foreach($record as $row) {            
            
            $var_title = ucfirst(str_replace("-", " ", $row['name'])) . " (".$row['width']."X".$row['height'].")";
            if( $row['name']=="responsive" )
                $var_title = "Responsive size";            
            
            if($selVal==$row['name'])
                $html .= '<option value="'.$row['name'].'" selected="selected">'.$var_title.'</option>';
            else
                $html .= '<option value="'.$row['name'].'">'.$var_title.'</option>';
        }
        $html .= '</select>';
        
        return $html;
    }
    
    
}
/* end of file msbd-ads-admin-add-edit.php */
