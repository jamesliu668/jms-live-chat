<?php
/* 
Plugin Name: JMS Live Chat
Plugin URI: http://www.jmsliu.com/products/jms-rss-feed
Description: Create profile for each patient. This will help doctors to keep patient record for each patient.
Author: James Liu
Version: 1.0.1
Author URI: http://jmsliu.com/
License: GPL2

{Plugin Name} is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

{Plugin Name} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with {Plugin Name}. If not, see {License URI}.
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

//ali sms
date_default_timezone_set('Asia/Shanghai');

global $jms_live_chat_db_version;
$jms_live_chat_db_version = '1.0';

//install database
register_activation_hook( __FILE__, 'installJMSLiveChat' );

add_shortcode( 'jms-live-chat-client', 'jmsLiveChatClientForm');

add_action( 'admin_init', 'jmsLiveChatAdminInit' );
add_action( 'admin_menu', 'jmsLiveChatAdminPage' );

add_action( 'init', 'jmsLiveChatLoadTextDomain' );

add_filter('query_vars', 'addJMSLiveChatAjaxVar', 10, 1);
add_action('wp_ajax_jms_livechat', 'jms_livechat_ajax');
add_action('wp_ajax_nopriv_jms_livechat', 'jms_livechat_ajax');

function jmsLiveChatLoadTextDomain() {
    if(!session_id()) {
        session_start();
    }

    load_plugin_textdomain( 'jms-live-chat', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}

function jmsLiveChatClientForm($atts) {
    global $wpdb;
    
    wp_enqueue_style( 'jms_live_chat_style', plugins_url('css/my.css?version=1.1.1', __FILE__));
    require_once(dirname(__FILE__)."/template/livechat-client-form.php");
}

function addJMSLiveChatAjaxVar($vars) {
    $vars[] = 'message';
    $vars[] = 'name';
    $vars[] = 'chat';
    $vars[] = 'last';
    $vars[] = 'jms-chat-task';
    return $vars;
}

function jms_livechat_ajax($wp) {
    if($_REQUEST["task"] == "new") {
        require_once(dirname(__FILE__)."/controllers/JMSLiveChatController.php");
        $controller = new JMSLiveChatController();
        $controller->postMessageByClient();
    } else if($_REQUEST["task"] == "get") {
        require_once(dirname(__FILE__)."/controllers/JMSLiveChatController.php");
        $controller = new JMSLiveChatController();
        $controller->getMessage();
    } else if($_REQUEST["task"] == "get-by-agent") {
        require_once(dirname(__FILE__)."/controllers/JMSLiveChatController.php");
        $controller = new JMSLiveChatController();
        $controller->getMessage(true);
    } else if($_REQUEST["task"] == "getroom") {
        require_once(dirname(__FILE__)."/controllers/JMSLiveChatController.php");
        $controller = new JMSLiveChatController();
        $controller->refreshChatRoom();
    } else if($_REQUEST["task"] == "reply-by-agent") {
        require_once(dirname(__FILE__)."/controllers/JMSLiveChatController.php");
        $controller = new JMSLiveChatController();
        $controller->postMessageByAdmin();
    } else if($_REQUEST["task"] == "finish-by-agent") {
        require_once(dirname(__FILE__)."/controllers/JMSLiveChatController.php");
        $controller = new JMSLiveChatController();
        $controller->finishChatSession();
    }
}

function installJMSLiveChat() {
    global $jms_live_chat_db_version;
    global $wpdb;
    
    $jms_live_chat_db_version = get_option( "jms_live_chat_db_version", null );
    if ( $jms_live_chat_db_version == null ) {
        //build database table here
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . "LivechatMessage";
        $sql = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                `message_id` int(11) NOT NULL auto_increment,
                `chat_id` int(11) NOT NULL,
                `user_id` int(11) NOT NULL default '0',
                `user_name` varchar(64) default NULL,
                `message` text,
                `post_time` datetime default NULL,
                `status` TINYINT NOT NULL DEFAULT 0 COMMENT '0: new\n1: read by admin' ,
                PRIMARY KEY  (`message_id`))
                ENGINE = InnoDB ".$charset_collate." AUTO_INCREMENT=1;";
        dbDelta( $sql );

        $table_name = $wpdb->prefix . "LivechatSession";
        $sql = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                `chat_id` int(11) NOT NULL auto_increment,
                `chat_name` varchar(64) default NULL,
                `start_time` datetime default NULL,
                `status` int(11) NOT NULL,
                `reply_by` INT NULL ,
                PRIMARY KEY  (`chat_id`))
                ENGINE = InnoDB ".$charset_collate." AUTO_INCREMENT=1;";
        dbDelta( $sql );

        $table_name = $wpdb->prefix . "LivechatTemplate";
        $sql = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
            `id` int(11) NOT NULL auto_increment,
            `cat_id` int(11) NOT NULL,
            `title` varchar(255) NOT NULL,
            `message` text,
            `created_on` datetime default NULL,
            PRIMARY KEY  (`id`))
            ENGINE = InnoDB ".$charset_collate." AUTO_INCREMENT=1;";
        dbDelta( $sql );

        $table_name = $wpdb->prefix . "LivechatRoom";
        $msgTableName = $wpdb->prefix . "LivechatMessage";
        $sessionTableName = $wpdb->prefix . "LivechatSession";
        $sql = "CREATE VIEW `".$table_name."` AS
                Select a.message_id, a.chat_id, a.user_id, a.user_name, a.message, a.post_time, a.status, b.status as session_status, b.reply_by, b.chat_name
                From `".$msgTableName."` as a 
                LEFT JOIN `".$sessionTableName."` as b 
                on a.chat_id=b.chat_id";
        $wpdb->query($sql);

        add_option( "jms_live_chat_db_version", $jms_live_chat_db_version );
    }
}

function jmsLiveChatAdminInit() {
    wp_enqueue_style( 'jms_live_chat_style', plugins_url('css/my.css?version=1.1.1', __FILE__));
}

function jmsLiveChatAdminPage() {
    add_menu_page(
        __("Live Chat", 'jms-live-chat' ),
        __('Live Chat','jms-live-chat'),
        'manage_options',
        'jms-live-chat-top',
        'jmsLiveChatAdminPageOptions' );

    // Add a submenu to the custom top-level menu:
    add_submenu_page(
        'jms-live-chat-top',
        __('Chat History','jms-live-chat'),
        __('Chat History','jms-live-chat'),
        'manage_options',
        'jms-live-chat-sub1',
        'jmsLiveChatAdminPageSub1');
}

function jmsLiveChatAdminPageOptions() {
    global $wpdb, $wp;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	} else {
        wp_enqueue_style('jms_live_chat_style', plugins_url('css/my.css?version=1.1.1', __FILE__));
        wp_enqueue_script('jquery');
        require_once(dirname(__FILE__)."/template/livechat-agent-form.php");
    }
}

function jmsLiveChatAdminPageSub1() {
    global $wpdb, $wp;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    } else {
        wp_enqueue_style('jms_live_chat_style', plugins_url('css/my.css?version=1.1.1', __FILE__));

        if(isset($_GET["action"])) {
            if($_GET["action"] == "details") {
                require_once(dirname(__FILE__)."/models/JMSLiveChatModel.php");
                $model = new JMSLiveChatModel();
                $result = $model->getAllChatMessage($_GET["id"]);
                
                require_once(dirname(__FILE__)."/template/livechat-history-details.php");
            } else {
                echo __('Invalid Request.','jms-live-chat');
            }
        } else {
            require_once(dirname(__FILE__)."/models/JMSLiveChatModel.php");
            $model = new JMSLiveChatModel();
            $result = $model->getAllClientList();
            
            require_once(dirname(__FILE__)."/template/livechat-history.php");
        }
    }
}
?>