<?php
class MsbdAdsmAdmin {

    var $parent;
    //var $db;

    function __construct($parent) {
        $this->parent = $parent;
        //$this->db = $this->parent->db;
        
        add_action('admin_menu', array(&$this, 'init_admin_menu'));
        
        //Loading Styles and Scripts for admin
        add_action( 'admin_enqueue_scripts', array(&$this, 'load_admin_scripts_styles'), 100);
        

        // Adding Setting Link to plugins list at "Installed Plugins" page
        add_filter('plugin_action_links_ads-management/ads-management.php', array(&$this, 'add_plugin_settings_link'));
    }





    function init_admin_menu() {
        global $wpdb;

        $var_manage_authority = $this->parent->msbd_adsmp_options_obj->get_option('msbd_adsmp_manage_authority');
        $var_view_authority = $this->parent->msbd_adsmp_options_obj->get_option('msbd_adsmp_manage_authority');
        
        add_menu_page(
            'Management Advertisement',
            'Management Advertisement',
            $var_view_authority,
            'msbd_adsmp_manage',
            array(&$this, 'render_adsmp_manage_page'),
            MSBD_ADSMP_URL.'images/msbd_favicon_16.png',
            '11.11'
        );
        
        add_submenu_page(
            'msbd_adsmp_manage', // ID of menu with which to register this submenu
            'Add New', //text to display in browser when selected
            'Add New', // the text for this item
            $var_manage_authority, // which type of users can see this menu
            'msbd_adsmp_add_edit', // unique ID (the slug) for this menu item
            array(&$this, 'render_adsmp_add_edit_page') // callback function
        );
        
        add_submenu_page(
            'msbd_adsmp_manage',
            'Settings',
            'Settings',
            $var_manage_authority,
            'msbd_adsmp_settings',
            array(&$this, 'render_adsmp_settings_page')
        );
        
        add_submenu_page(
            'msbd_adsmp_manage',
            'Instructions',
            'Instructions',
            $var_view_authority,
            'msbd_adsmp_instructions',
            array(&$this, 'render_adsmp_instructions_page')
        );
        
    }



    function load_admin_scripts_styles() {
        wp_enqueue_style( "msbd-adsmp-admin", MSBD_ADSMP_URL . 'css/msbd-adsmp-admin.css', false, false );
        
        //Add Masonary library only on adv add or edit page
        if( isset($_REQUEST['page']) && $_REQUEST['page']=="msbd_adsmp_add_edit" ) {
            wp_enqueue_script( "msbd-adsm-admin-masonry-pkgd", MSBD_ADSMP_URL ."js/masonry.pkgd.min.js", "jquery", false, true);
        }
           
        wp_enqueue_script( "msbd-adsm-admin-script", MSBD_ADSMP_URL ."js/msbd-adsm-admin-script.js", "jquery", false, true);
    }




    function wrap_admin_page($page = null) {
        
        $page_header = '';
        switch($page) {
            case 'main':
                $page_header = $this->parent->plugin_name.' Dashboard';
                break;
                
            case 'add_edit':
                $page_header = $this->parent->plugin_name.' Form';
                break;
                
            case 'settings':
                $page_header = $this->parent->plugin_name.' Settings';
                break;
                
            case 'instructions':
                $page_header = $this->parent->plugin_name.' Instructions';
                break;
        }
        
        echo '<div class="wrap msbd-adsmp">';
        echo '<h2><img src="' . MSBD_ADSMP_URL . 'images/msbd_favicon_32.png" /> '.$page_header.' </h2>';
        
        echo '<div class="adsmp-body-content">';
        
        MsbdAdsMAdminHelper::render_container_open('content-container');
        
        if ($page == 'main') {
            MsbdAdsMAdminHelper::render_postbox_open('Dashboard');
            echo $this->render_adsmp_manage_page(TRUE);
            MsbdAdsMAdminHelper::render_postbox_close();
        }
        
        if ($page == 'add_edit') {
            MsbdAdsMAdminHelper::render_postbox_open('Add/Edit Form');
            echo $this->render_adsmp_add_edit_page(TRUE);
            MsbdAdsMAdminHelper::render_postbox_close();
        }        
        
        
        if ($page == 'settings') {
            MsbdAdsMAdminHelper::render_postbox_open('Settings');
            echo $this->render_adsmp_settings_page(TRUE);
            MsbdAdsMAdminHelper::render_postbox_close();
        }
        
        
        if ($page == 'instructions') {
            MsbdAdsMAdminHelper::render_postbox_open('Instructions');
            echo $this->render_adsmp_instructions_page(TRUE);
            MsbdAdsMAdminHelper::render_postbox_close();
        }

        MsbdAdsMAdminHelper::render_container_close();
        
        MsbdAdsMAdminHelper::render_container_open('sidebar-container');        
        MsbdAdsMAdminHelper::render_sidebar();
        MsbdAdsMAdminHelper::render_container_close();
        
        echo '</div>'; /* .adsmp-body-content */
        echo '</div>'; /* .wrap msbd-adsmp */
    }


    function render_adsmp_manage_page($wrapped = false) {
        if (!$wrapped) {
            $this->wrap_admin_page('main');
            return;
        }

        require_once('msbd-adsm-admin-tables.php');
        $adsmp_table = new Msbd_Adsm_Table();
        $adsmp_table->prepare_items('all');
        echo '<form id="form" method="POST">';
        $adsmp_table->display();
        echo '</form>';        
    }





    function render_adsmp_add_edit_page($wrapped) {
                
        if (!$wrapped) {
            $this->wrap_admin_page('add_edit');
            return;
        }
        
        $var_manage_authority = $this->parent->adsmp_options['msbd_adsmp_manage_authority'];
        if (!current_user_can($var_manage_authority)) {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }
        
        $view = new MsbdAdsAdminAddEdit($this->parent);
    }
    
    
    


    function render_adsmp_settings_page($wrapped = false) {
        
        $options = $this->parent->msbd_adsmp_options_obj->get_option();
        
        if (!$wrapped) {
            $this->wrap_admin_page('settings');
            return;
        }

        //Check User Permission
        $var_manage_authority = $this->parent->adsmp_options['msbd_adsmp_manage_authority'];
        if (!current_user_can($var_manage_authority)) {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }
        
        ?>
<form id="msbd-adsmp-settings-form" action="" method="post">
    <input type="hidden" name="action" value="msbd-adsmp-update-options">
    <input type="hidden" name="msbd_adsmp_view_authority" value="edit_published_posts">
    
    <div class="form-table">        
        <div class="form-row">
            <div class="grid_3">
                <select name="msbd_adsmp_manage_authority" id="msbd_adsmp_manage_authority">
                <?php
                    if ($options['msbd_adsmp_manage_authority']==="manage_options"){ ?><option value="manage_options" selected="selected">Admin</option><?php }else {?><option value="manage_options" >Admin</option><?php }
                    if ($options['msbd_adsmp_manage_authority']==="moderate_comments"){ ?><option value="moderate_comments" selected="selected">Editor</option><?php }else {?><option value="moderate_comments" >Editor</option><?php }
                    if ($options['msbd_adsmp_manage_authority']==="edit_published_posts"){ ?><option value="edit_published_posts" selected="selected">author</option><?php }else {?><option value="edit_published_posts" >Author</option><?php }
                    if ($options['msbd_adsmp_manage_authority']==="edit_posts"){ ?><option value="edit_posts" selected="selected">Contributor</option><?php }else {?><option value="edit_posts" >Contributor</option><?php }
                    if ($options['msbd_adsmp_manage_authority']==="read"){ ?><option value="read" selected="selected">Subscriber</option><?php }else {?><option value="read" >Subscriber</option><?php }
                ?>
                </select>
            </div>
            <div class="grid_9"><label for="msbd_adsmp_manage_authority"> Authority level required to Manage advertisement</label></div>
        </div>        
        
        <div class="form-row">
            <div class="grid_12">
                <label for="msbd_adsmp_add_styles"><input type="checkbox" name="msbd_adsmp_add_styles" id="msbd_adsmp_add_styles" value="checked" <?php echo $options['msbd_adsmp_add_styles'] ?> /> Use styles from theme. Unselect this option will skip adding styles from this plugin!</label>
            </div>
        </div>
        
        <div class="form-row">
            <div class="grid_12"><label for="msbd_adsmp_content_top_script">Script to add advertisement at the top of post</label></div>
        </div>        
        <div class="form-row">
            <div class="grid_3">&nbsp;</div>
            <div class="grid_9">
                <textarea class="text" name="msbd_adsmp_content_top_script" id="msbd_adsmp_content_top_script" rows="6"><?php echo stripslashes($options['msbd_adsmp_content_top_script']); ?></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="grid_12"><label for="msbd_adsmp_content_bottom_script">Script to add advertisement at the bottom of post</label></div>
        </div>        
        <div class="form-row">
            <div class="grid_3">&nbsp;</div>
            <div class="grid_9">
                <textarea class="text" name="msbd_adsmp_content_bottom_script" id="msbd_adsmp_content_bottom_script" rows="6"><?php echo stripslashes($options['msbd_adsmp_content_bottom_script']); ?></textarea>
            </div>
        </div>
        
        <div class="form-row">
            <div class="grid_6">
                <input name="resetButton" type="reset" value="Reset" />
                <input type="submit" class="button" value="Save Settngs">
            </div>
        </div>
    </div>
</form>
        <?php
    }
    
    
    
    
    function render_adsmp_instructions_page($wrapped = false) {
        if (!$wrapped) {
            $this->wrap_admin_page('instructions');
            return;
        }
        
        $output = '
            <div class="instructions">                
                <p>Currently we are peoviding two shortcodes <strong>[adsmp]</strong> &amp; <strong>[manage_adv]</strong> to show advertisement on diffenent place where shortcode tag is allowed by wordpress.</p>
                
                <p><strong>New Feature</strong>: We have added a feature to add multiple advertisement with same size, sponsor beacause now you can select category/categories of post for any script! If the plugin found more then one script for any shortcode, then random script will publish.</p>
                
                <p><strong>Supported attributes for <i>[manage_adv]</i> shortcode are:</strong></p>
                
                <ul>
                    <li>width and height: number (default 0 or empty).</li>
                    <li>size : string (default banner)</li>
                    <li>sponsor : enum value as adsense, amazon, clickbank, affiliate (default empty)</li>
                    <li>type : enum value as mix, image, text (default mix)</li>
                    <li>wrap_class : text as style class (default empty)</li>
                </ul>
                
                <p>width &amp; height combination or only size should use to detect ad size. If width &amp; height combination are provided then the size parameter will be ignored!</p>                
                <p><strong>Few examles:</strong> the following examples are same. because of the default value set by the plugin.</p>                
                <p><strong>[adsmp]</strong>
                <br><strong>[adsmp size="banner" type="mix"]</strong></p>
                
                
                <p>Now the sponsor attribute is not required. But if the sponsor attribute added then publishing the advertisement strictly by sponsor.</p>                
                <p><strong>Few examles:</strong>If you use this <strong>[adsmp width="468" height="60" type="mix"]</strong> shortcode and the plugin found script from different sponsor will publish randomly!</p>
                
                <p><strong>[** Let us know if you found something to be fix!]</strong></p>
                
            </div>';
        
        echo $output;
    }





    function add_plugin_settings_link($links) {
        $settings_link = '<a href="admin.php?page=msbd_adsmp_settings">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }







    function get_option($opt_name = '') {
        $options = get_option($this->parent->fp_admin_options);

        // maybe return the whole options array?
        if ($opt_name == '') {
            return $options;
        }

        // are the options already set at all?
        if ($options == FALSE) {
            return $options;
        }

        // the options are set, let's see if the specific one exists
        if (! isset($options[$opt_name])) {
            return FALSE;
        }

        // the options are set, that specific option exists. return it
        return $options[$opt_name];
    }

    function update_option($opt_name, $opt_val = '') {
        // allow a function override where we just use a key/val array
        if (is_array($opt_name) && $opt_val == '') {
            foreach ($opt_name as $real_opt_name => $real_opt_value) {
                $this->update_option($real_opt_name, $real_opt_value);
            }
        } else {
            $current_options = $this->get_option();

            // make sure we at least start with blank options
            if ($current_options == FALSE) {
                $current_options = array();
            }

            $new_option = array($opt_name => $opt_val);
            update_option($this->parent->fp_admin_options, array_merge($current_options, $new_option));
        }
    }

}
/* end of file msbd-adsm-admin.php */
