<?php
/*
Plugin Name: WooCommerce Delivery Notifications
Plugin URI: https://github.com/Lonsdale201/woocommerce-custom-delivery-messages
Description: Egy bővítmény a WooCommerce-hez, ami lehetővé teszi a szállítási értesítések beállításait.
Version: 1.0
Author: HelloWP!
Author URI: https://hellowp.io/hu/ 
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Woo_Delivery_Notifications')) {

    class Woo_Delivery_Notifications {

        public function __construct() {
            // Betöltjük a fájlokat
            require_once plugin_dir_path(__FILE__) . 'delivery-settings.php';
            require_once plugin_dir_path(__FILE__) . 'delivery-display.php';
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    
            // Inicializáljuk a beállításokat
            new Woo_Delivery_Notifications_Settings();
            new Woo_Delivery_Display();
    
            // Stílus betöltés
            add_action('wp_enqueue_scripts', array($this, 'enqueue_styles_only_on_product_page'));
        }   
    
        public function enqueue_styles_only_on_product_page() {
            if (is_product()) {
                wp_enqueue_style('woo-delivery-styles', plugin_dir_url(__FILE__) . 'assets/delivery.css', array(), '1.0.0');
            }
        }

        public function add_settings_link($links) {
            $settings_link = '<a href="admin.php?page=wc-settings&tab=delivery_notifications">' . __('Settings') . '</a>';
            array_unshift($links, $settings_link); 
            return $links;
        }
        
    }
    
    new Woo_Delivery_Notifications();

}

?>
