<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Woo_Delivery_Notifications_Settings')) {

    class Woo_Delivery_Notifications_Settings {

        public function __construct() {
            // WooCommerce beállítások fülének hozzáadása
            add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
            add_action('woocommerce_settings_tabs_delivery_notifications', array($this, 'settings_tab'));
            add_action('woocommerce_update_options_delivery_notifications', array($this, 'update_settings'));
        }

        // Új fül hozzáadása
        public function add_settings_tab($settings_tabs) {
            $settings_tabs['delivery_notifications'] = __('Delivery Notifications', 'woocommerce');
            return $settings_tabs;
        }

        // Beállítások hozzáadása az új fülhöz
        public function settings_tab() {
            woocommerce_admin_fields($this->get_settings());
        }

        // Beállítások mentése
        public function update_settings() {
            woocommerce_update_options($this->get_settings());
        }

                // Termékek lekérdezése az adatbázisból
        public function get_products() {
            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'post_status'    => 'publish'
            );

            $products = get_posts($args);
            $product_options = array();

            foreach ($products as $product) {
                $product_options[$product->ID] = $product->post_title . ' (#' . $product->ID . ')';
            }

            return $product_options;
        }

        // A beállítások definiálása
        public function get_settings() {
            $settings = array(
                'section_title' => array(
                    'name'     => __('Delivery Notifications Settings', 'woocommerce'),
                    'type'     => 'title',
                    'desc'     => '',
                    'id'       => 'wc_delivery_notifications_section_title'
                ),
                'selected_products' => array(
                    'name'     => __('Select Products', 'woocommerce'),
                    'type'     => 'multiselect',
                    'class'    => 'wc-enhanced-select',  
                    'desc_tip' => __('Select products for delivery notifications.', 'woocommerce'),
                    'id'       => 'wc_delivery_selected_products',
                    'options'  => $this->get_products()  // Termékek lekérdezése
                ),
                'hide_if_out_of_stock' => array(
                    'name'     => __('Hide if out of stock', 'woocommerce'),
                    'type'     => 'checkbox',
                    'desc_tip' => __('Hide the notification if the product is out of stock.', 'woocommerce'),
                    'id'       => 'wc_delivery_hide_if_out_of_stock',
                    'default'  => 'no'
                ),                
                'notification_placement' => array(
                    'name'     => __('Notification Placement', 'woocommerce'),
                    'type'     => 'select',
                    'desc_tip' => __('Choose where the notification should appear.', 'woocommerce'),
                    'id'       => 'wc_delivery_notification_placement',
                    'default'  => 'below_add_to_cart',
                    'options'  => array(
                        'below_add_to_cart' => __('Below Add to Cart', 'woocommerce'),
                        'shortcode' => __('Shortcode', 'woocommerce')
                    )
                ),                
                'notification_title' => array(
                    'name' => __('Notification Title', 'woocommerce'),
                    'type' => 'text',
                    'desc_tip' => __('Enter the title for the delivery notification.', 'woocommerce'),
                    'id'   => 'wc_delivery_notification_title'
                ),
                'notification_message' => array(
                    'name' => __('Notification Message', 'woocommerce'),
                    'type' => 'textarea',
                    'desc_tip' => __('Enter the long notification message for deliveries.', 'woocommerce'),
                    'id'   => 'wc_delivery_notification_message',
                    'css'  => 'min-width:300px; min-height:200px;'
                ),
                'cart_reminder' => array(
                    'name' => __('Cart Reminder', 'woocommerce'),
                    'type' => 'checkbox',
                    'desc' => __('Display the notification on the cart page if applicable.', 'woocommerce'),
                    'id'   => 'wc_delivery_cart_reminder',
                    'default' => 'no'
                ),               
                'cart_reminder_message' => array(
                    'name' => __('Cart Reminder Message', 'woocommerce'),
                    'type' => 'textarea',
                    'desc_tip' => __('Enter the reminder message for the cart page. Use %s to display product names.', 'woocommerce'),
                    'id'   => 'wc_delivery_cart_reminder_message',
                    'css'  => 'min-width:300px; min-height:200px;'
                ),                 
                'section_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_delivery_notifications_section_end'
                )
            );

            return apply_filters('wc_delivery_notifications_settings', $settings);
        }

    }

}

?>
