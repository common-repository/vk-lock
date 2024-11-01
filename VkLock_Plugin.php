<?php

include_once('VkLock_LifeCycle.php');
require_once('VK/VK.php');
require_once('VK/VKException.php');

//define('VK_LOCK_DEBUG', true);

class VkLock_Plugin extends VkLock_LifeCycle {

    /**
     * See: http://plugin.michael-simpson.com/?page_id=31
     * @return array of option meta data.
     */
    public function getOptionMetaData() {
        //  http://plugin.michael-simpson.com/?page_id=31
        return array(
            //'_version' => array('Installed Version'), // Leave this one commented-out. Uncomment to test upgrades.
            'siteApplicationID' => array( __("VKontakte Application ID", 'vk-lock') ),
            'siteSecureKey' => array( __("VKontakte Application secure key", 'vk-lock') ),
            'showDefaultPasswordForm' => array( __('Show default Password box [0\1]', 'vk-lock'), 0 ),
            'noAccessNoticeHeader' => array( __('No access notice Header', 'vk-lock'), __('ATTENSION', 'vk-lock') ),
            'noAccessNoticeText' => array( __('No access notice text', 'vk-lock'), __('Access to this page is allowed to the members of VKontakte social network group [%s] only.<br/>If your are a member of the group - please click on SingIn button below to proceed.', 'vk-lock') ),
            'signinVKButtonText' => array( __('SingIn Button Text', 'vk-lock'), __('SingIn via VK.COM', 'vk-lock') ),
            'noAccessTimelimit' =>array( __('No access after expiration time notice', 'vk-lock'), __('Access to this page is expired since %s.', 'vk-lock') ),
            'cssClassArea' => array( __('CSS class for SingIn <div> area', 'vk-lock') ),
            'cssClassButton' => array( __('CSS class for VK SingIn anchor', 'vk-lock'), 'button' )
        );
    }

//    protected function getOptionValueI18nString($optionValue) {
//        $i18nValue = parent::getOptionValueI18nString($optionValue);
//        return $i18nValue;
//    }

    protected function initOptions() {
        $options = $this->getOptionMetaData();
        if (!empty($options)) {
            foreach ($options as $key => $arr) {
                if (is_array($arr) && count($arr > 1)) {
                    $this->addOption($key, $arr[1]);
                }
            }
        }
    }

    public function getPluginDisplayName() {
        return 'VK Lock';
    }

    protected function getMainPluginFileName() {
        return 'vk-lock.php';
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Called by install() to create any database tables if needed.
     * Best Practice:
     * (1) Prefix all table names with $wpdb->prefix
     * (2) make table names lower case only
     * @return void
     */
    protected function installDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("CREATE TABLE IF NOT EXISTS `$tableName` (
        //            `id` INTEGER NOT NULL");
    }

    /**
     * See: http://plugin.michael-simpson.com/?page_id=101
     * Drop plugin-created tables on uninstall.
     * @return void
     */
    protected function unInstallDatabaseTables() {
        //        global $wpdb;
        //        $tableName = $this->prefixTableName('mytable');
        //        $wpdb->query("DROP TABLE IF EXISTS `$tableName`");
    }


    /**
     * Perform actions when upgrading from version X to version Y
     * See: http://plugin.michael-simpson.com/?page_id=35
     * @return void
     */
    public function upgrade() {
    }

    //prints settingsPage (overrides base class)
    public function settingsPage() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'vk-lock'));
        }

        parent::settingsPage();

        echo '<h2>' . __('Page/Post Settings'). '</h2>';
        echo __("Please also refer to the settings for each page/post available at [VK Lock] metabox at each page/post.");
    }

    public function addActionsAndFilters() {

        // Add options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        add_action('admin_menu', array(&$this, 'addSettingsSubMenuPage'));

        // Example adding a script & style just for the options administration page
        // http://plugin.michael-simpson.com/?page_id=47
        //        if (strpos($_SERVER['REQUEST_URI'], $this->getSettingsSlug()) !== false) {
        //            wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));
        //            wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        }


        // Add Actions & Filters
        // http://plugin.michael-simpson.com/?page_id=37

        add_action( 'wp', array(&$this, 'actionInitCheckAccess') );

        //meta boxes
        add_action( 'add_meta_boxes', array(&$this, 'actionAddMetaBoxes') );
        add_action( 'save_post', array(&$this, 'actionSaveMetaBoxesData'), 10, 3 );

        //custom processing for password forms (used in front-end to show site visitors)
        add_filter( 'the_password_form', array( &$this, 'filterCheckPasswordForm' ) ); 


        // Adding scripts & styles to all pages
        // Examples:
        //        wp_enqueue_script('jquery');
        //        wp_enqueue_style('my-style', plugins_url('/css/my-style.css', __FILE__));
        //        wp_enqueue_script('my-script', plugins_url('/js/my-script.js', __FILE__));

        add_action( 'wp_enqueue_scripts', array( &$this, 'actionEnqueueScripts') ); 

        // Register short codes
        // http://plugin.michael-simpson.com/?page_id=39


        // Register AJAX hooks
        // http://plugin.michael-simpson.com/?page_id=41

    }

    //
    // Debug logging
    //

    protected function errorLog( $message )
    {
        $message = $this->getPluginDisplayName() . ": " . $message;
        error_log( $message );
    }

    protected function debugLog( $message )
    {
        if (defined('VK_LOCK_DEBUG')) {
            $message = $this->getPluginDisplayName() . ": DEBUG - " . $message;
            error_log( $message );         
        }
    }

    // 
    //
    //

    public function actionEnqueueScripts()
    {
        //check if '_vk-lock-group-url' is set for the page

        //if it is set - include some JScripts
        //NOTE:
        //  here I use some trick - to make VkLock stateless and not use $_SESSION
        //  I inject JS code into page that creates cookie on client side (this cookie in checked by server in order to minimize unnecessary calls to VK.COM about access rigths)
        $vk_item_url = get_post_meta( get_the_ID() , '_vk-lock-group-url', true );

        if (!empty($vk_item_url)) {
            $this->debugLog('Enqueuing scripts for page (ID='. get_the_ID() .') with VK URL = ' . $vk_item_url);

            wp_register_script('vk-lock-script', plugins_url('/js/vk-lock.js', __FILE__), null, $this->getVersion() );
            wp_localize_script('vk-lock-script', 'vk_lock',
                array(
                        'time' => 'unknown'
                    )
                  );
            wp_enqueue_script('vk-lock-script');
        }        
    }


    //checks for VK access and sets user's cookies (in order not query VK.COM later)
    public function actionInitCheckAccess()
    {
        $this->debugLog('ENTERING function actionInitCheckAccess');

        $allowAccess = false;

        //
        //check if '_vk-lock-group-url' is set for the page
        //
        $vk_item_url = get_post_meta( get_the_ID(), '_vk-lock-group-url', true );

        //if not set - we do not need to check VKontakte credentials at all, just pass through execution
        if (empty($vk_item_url)) {
            return;
        }

        $timelimit = get_post_meta( get_the_ID(), '_vk-lock-timelimit', true );
        $dt_timelimit = 0;
        if (!empty($timelimit)) {
            if (!empty(trim($timelimit))) {
                $dt_timelimit = strtotime( $timelimit );
            }
        }
        $this->debugLog('_vk-lock-group-url = ' . $vk_item_url . ', _vk-lock-timelimit (raw / formatted) = ' . $timelimit . ' / ' . date('Y-m-d', $dt_timelimit) );

        $vk_group_screen_name = $this->helper_VkGetScreenName( $vk_item_url );

        //check timelimit
        if ($dt_timelimit != 0 ) {
            if ($dt_timelimit < time()) {
                $this->debugLog('timelimit reached, this post is not accessible after ' . date('Y-m-d', $dt_timelimit));
                return;
            }
        }

        $salt = $this->getOption('siteApplicationID');

        //
        // check user access (first by validating the cookie, if not - by VKontakte request )
        //

        if ($this->helper_CheckCookie( $vk_group_screen_name, $salt )) {
            //user has been authenicated before, just pass it
            $allowAccess = true;

            $this->debugLog('Access cookie validated. Access allowed');
        }
        else
        {
            //check if user is properly authenticated in VKontakte

            $this->debugLog('There is no access cookie, checking for VKontakte response parameters');

            //check if we there are params from redirected VKontake response
            $vk_code = isset($_GET['code']) ? $_GET['code'] : '';
            $vk_access_token = '';

            if (!empty($vk_code)) {
                
                // this is redirected VKontakte response with 'code' value, need to check group access
                $this->debugLog('There are VKontakte response parameters found !');

                try {
                    $vk = new VK\VK($this->getOption('siteApplicationID'), $this->getOption('siteSecureKey'));

                    $res = $vk->getAccessToken( $vk_code, get_page_link() );
                    $vk_user_id = $res['user_id'];

                    $this->debugLog('Access_token for VKontakte API has been properly obtained. Need to check group access.');

                    $vk_group_id = 0;
                    if (!is_numeric($vk_group_screen_name)) {
                        //need to resolve group screen name into ID
                        $params = array( 'screen_name'   => $vk_group_screen_name );
                        $res = $vk->api('utils.resolveScreenName', $params);
                        
                        $this->helper_DumpVKAPIErrorResponse($res);
                        
                        if (isset($res['response']['object_id'])) {
                            if ($res['response']['type'] == 'group') {
                                $vk_group_id = $res['response']['object_id'];
                            }
                        }
                    }
                    else {
                        $vk_group_id = $vk_group_screen_name;
                    }

                    $params = array( 'group_id'   => $vk_group_id, 'user_id' => $vk_user_id );
                    $res = $vk->api('groups.isMember', $params);

                    $this->helper_DumpVKAPIErrorResponse($res);

                    if (isset($res['response'])) {
                        if (1 == $res['response']) {
                            $allowAccess = true;
                            $this->debugLog('VKontakte group access verified, setting cookie...');
                            $this->helper_UpdateCookie( $vk_group_screen_name, $salt );                         
                        }
                    }
                }
                catch (VK\VKException $error) {
                    $this->debugLog('Access_token for VKontakte API could not be obtained.');
                    $this->errorLog( $error->getMessage() );
                    $allowAccess = false;
                }
            }
            else
            {
                //this is direct access to protected page, do nothing (SingIn link will be constructed later during PasswordForm creation)
                $allowAccess = false;
            }
        }

        //by this point there access cookie should be set in case requester has been validate with VK.COM
        return;
    }

    //
    // Metaboxes
    //

    public function actionAddMetaBoxes () 
    {
        $screens = array( 'post', 'page' );

        foreach ( $screens as $screen ) {
            add_meta_box(
                'vk-lock-metabox',
                __( 'VK Lock', 'vk-lock'),
                array(&$this, 'metaBoxContent'),
                $screen
            );
        }

    }

    public function metaBoxContent( $post )
    {
        $no_password = empty($post->post_password);

        echo '<p>' . __('Visitor will see the page/post only in case he(she) is a member of specified VKontakte group.', 'vk-lock') . '<br/>';

        if ($no_password) {
            echo '<p><b>' . __('NOTE:', 'vk-lock' ) . '</b> ' . __('This post(page) <b>is not "password-protected"</b>. VKontakte access lock works with "password-protected" posts(pages) only', 'vk-lock') . '<br/>';
            echo '<b>Please set up post(page) password to proceed.</b></p>';
        }

        //VK group URL
        $vk_group = get_post_meta( $post->ID, '_vk-lock-group-url', true );
        echo '<p>';
        echo '<label for="vk-lock-group-url">' . __('URL of VKontakte group:', 'vk-lock');
        echo '</label> ';
        echo '<input type="text" class="code" id="vk-lock-group-url" name="vk-lock-group-url" value="' . esc_attr( $vk_group ) . '" style = "width:99%;"/><br/>';

        //Timelimit
        $timelimit = get_post_meta( $post->ID, '_vk-lock-timelimit', true );
        echo '<p>';
        echo '<label for="vk-lock-timelimit">' . __('Block access after YYYY-MM-DD (leave black for unlimited):', 'vk-lock');
        echo '</label> ';
        echo '<input type="text" class="code" id="vk-lock-timelimit" name="vk-lock-timelimit" value="' . esc_attr( $timelimit ) . '" style = "width:99%;"/><br/>';

        echo '</p>';
    }

    public function actionSaveMetaBoxesData( $post_id, $post, $update ) 
    {
        // TODO !!! check nonce ...
        
        // Check permissions to edit pages and/or posts
        if ( ($post->post_type != 'page') && ($post->post_type != 'post')) {
            return $post_id;
        }

        if (!(current_user_can( 'edit_page', $post_id ) || current_user_can( 'edit_post', $post_id ))) {
            return $post_id;
        }

        if (array_key_exists('vk-lock-group-url', $_POST)) {
            // save meta box input ...
            update_post_meta( $post_id, '_vk-lock-group-url', sanitize_text_field($_POST['vk-lock-group-url']) );
        }

        if (array_key_exists('vk-lock-timelimit', $_POST)) {
            // save meta box input ...
            update_post_meta( $post_id, '_vk-lock-timelimit', sanitize_text_field($_POST['vk-lock-timelimit']) );
        }
    }

    //
    // Password form
    //

    /**
     *
     * @param string $text The password form markup.
     * @return string The post content (if access allowed) or the password form.
     */
    public function filterCheckPasswordForm( $text ) {

        $this->debugLog('ENTERING function filterCheckPasswordForm');

        $allowAccess = false;

        //
        //check if '_vk-lock-group-url' is set for the page
        //
        $vk_item_url = get_post_meta( get_the_ID(), '_vk-lock-group-url', true );

        //if not set - we do not need to check VKontakte credentials at all, just pass through execution
        if (empty($vk_item_url)) {
            $this->debugLog('_vk-lock-group-url is not set for the current post');
            return $text;
        }

        $timelimit = get_post_meta( get_the_ID(), '_vk-lock-timelimit', true );
        $dt_timelimit = 0;
        if (!empty($timelimit)) {
            if (!empty(trim($timelimit))) {
                $dt_timelimit = strtotime( $timelimit );
            }
        }
        $this->debugLog('_vk-lock-group-url = ' . $vk_item_url . ', _vk-lock-timelimit (raw / formatted) = ' . $timelimit . ' / ' . date('Y-m-d', $dt_timelimit) );

        $vk_group_screen_name = $this->helper_VkGetScreenName( $vk_item_url );

        //check timelimit
        if ($dt_timelimit != 0 ) {
            if ($dt_timelimit < time()) {
                $this->debugLog('timelimit reached, this post is not accessible since ' . date('Y-m-d', $dt_timelimit));

                $html_to_add = '<div class="' . $this->getOption('cssClassArea') .'">';
                
                if (!empty($this->getOption('noAccessNoticeHeader'))) {
                    $html_to_add .= "<h3>" . $this->getOption('noAccessNoticeHeader'). "</h3>";         
                }
                
                $notice = $this->getOption('noAccessTimelimit');
                if (!empty($notice)) {
                    $group_link = '<a href="' . $vk_item_url .'">' . $vk_item_url . '</a>';
                    $notice = sprintf($notice, $group_link, date('Y-m-d', $dt_timelimit ) );
                    $html_to_add .= '<div style="margin-bottom: 0.5cm; margin-top: 0.5cm;">' . $notice .'</div>';
                }

                $html_to_add .= '</div>';

                //hide default password form
                if (0 == $this->getOption('showDefaultPasswordForm') ) {
                    $text = '';
                }

                $text = $text . $html_to_add;

                return $text;
            }
        }

        $salt = $this->getOption('siteApplicationID');

        //
        // check user access (again by validating the cookie that has been checked on 'init' page load)
        //

        if ($this->helper_CheckCookie( $vk_group_screen_name, $salt )) {
            //user has been authenicated before, just pass it
            $allowAccess = true;

            $this->debugLog('Access cookie validated. Access allowed');
        }

        if ( $allowAccess ) {
            $this->debugLog('Access allowed');

            return apply_filters( 'the_content', get_page( get_the_ID() )->post_content );

        } else {

            $this->debugLog('Access not allowed - showing VK login button');

            try {
                $vk = new VK\VK($this->getOption('siteApplicationID'), $this->getOption('siteSecureKey'));

                //get authorize URL for groups access
                $authorize_url = $vk->getAuthorizeURL( 0+262144, get_page_link() );
                
                $html_to_add = '<div class="' . $this->getOption('cssClassArea') .'">';
                
                if (!empty($this->getOption('noAccessNoticeHeader'))) {
                    $html_to_add .= "<h3>" . $this->getOption('noAccessNoticeHeader'). "</h3>";         
                }
                
                $notice = $this->getOption('noAccessNoticeText');
                if (!empty($notice)) {
                    $group_link = '<a href="' . $vk_item_url .'">' . $vk_item_url . '</a>';
                    $notice = sprintf($notice, $group_link);
                    $html_to_add .= '<div style="margin-bottom: 0.5cm; margin-top: 0.5cm;">' . $notice .'</div>';
                }

                $html_to_add .= "<div>";
                $html_to_add .= '<a class="' . $this->getOption('cssClassButton') . '" style="padding: 20px;" href="' . $authorize_url . '">' . $this->getOption('signinVKButtonText') .'</a>';
                $html_to_add .= '</div>';

                $html_to_add .= '</div>';
            }
            catch (VK\VKException $error) {
                $this->errorLog( $error->getMessage() );
                return $text;
            }

            //hide default password form
            if (0 == $this->getOption('showDefaultPasswordForm') ) {
                $text = '';
            }

            $text = $text . $html_to_add;

            // Else return password form
            return $text;
        }
    }

    //
    // Helper routines for access checking
    //


    /*
        Checks if there is proper access cookie set for current session
    */
    protected function helper_CheckCookie( $group_screen_name, $salt )
    {
        $this->debugLog('Checking access cookie...' . ' group=' . $group_screen_name . ' salt='.$salt);
        $cookie_name = 'vk_lock_' . md5( $group_screen_name );

        //check if access cookie is properly set for the request
        if (isset($_COOKIE[$cookie_name]) ) {

            $this->debugLog('Cookie is set ! Checking cookie value...');

            //check cookie value (max 24 hours)
            if ( wp_verify_nonce( $_COOKIE[$cookie_name], $salt ) > 0) {

                $this->debugLog('Cookie is correct');

                //if cookie is correct - allow access
                return true;
            }

            $this->debugLog('Cookie is wrong - invalidating cookie');
            
            //cookie is wrong - we need invalidate the cookie
            unset($_COOKIE[$cookie_name]);
            setcookie($cookie_name, time() - MINUTE_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, false);
        }
        else {
            $this->debugLog('Access cookie is not set.');
        }

        return false;
    }


    /*
        Sets proper cookie for further acceess requests
    */
    protected function helper_UpdateCookie( $group_screen_name, $salt )
    {
        $this->debugLog('Updating (setting) access cookie...');
        $cookie_name = 'vk_lock_' . md5( $group_screen_name );

        $cookie_value = wp_create_nonce( $salt );

        $grace_period = MINUTE_IN_SECONDS*60*2;   //allowed life-time for the cookie value (after it is expired - VK.com will be enquired again (max is 24 hours, because in 24 nonce become invalid)

        setcookie($cookie_name, $cookie_value, time() + $grace_period, COOKIEPATH, COOKIE_DOMAIN, false);
        $_COOKIE[$cookie_name] = $cookie_value; //this is trick to have access to the cookie on same page load (without redirecting)

        return true;
    }

    /*
        Returns VK item ID for the given VK item URL
    */
    protected function helper_VkGetScreenName($vk_item_url = '')
    {
        $screen_name = '';

        $urla = explode ('/', $vk_item_url);
        if (is_array($urla) && !empty($urla)) {
            $screen_name = array_pop($urla);
        }

        return $screen_name;
    }

    protected function helper_DumpVKAPIErrorResponse( $res )
    {
        if (isset($res['error'])) {
            if (isset($res['error']['error_code']))
                $this->errorLog('VK API Error. ' . $res['error']['error_code'] . ' '. $res['error']['error_msg']); 
            else
                $this->errorLog('VK API Error. ' . $res['error']);        
        }
    }

}