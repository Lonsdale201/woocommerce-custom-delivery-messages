<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Woo_Delivery_Notifications_Settings')) {

    class Woo_Delivery_Notifications_Settings {

        public function __construct() {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
            add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
            add_action('woocommerce_settings_tabs_delivery_notifications', array($this, 'settings_tab'));
            add_action('woocommerce_update_options_delivery_notifications', array($this, 'update_settings'));

            // Admin oszlop és szűrő hozzáadása
            add_filter('manage_edit-product_columns', array($this, 'add_admin_column'));
            add_action('manage_product_posts_custom_column', array($this, 'display_admin_column_data'), 10, 2);
            add_action('restrict_manage_posts', array($this, 'add_admin_filter_dropdown'));
            add_filter('parse_query', array($this, 'filter_products_by_delivery_status'));
        }
        

        public function enqueue_admin_styles() {
            wp_enqueue_style('woo-delivery-admin-styles', plugin_dir_url(__FILE__) . 'assets/delivery-admin.css', array(), '1.0.0');
        }        

        // Új fül hozzáadása
        public function add_settings_tab($settings_tabs) {
            // Itt ellenőrizzük a jogosultságokat
            if (!current_user_can('manage_options')) {
                return $settings_tabs;  // Visszatérünk az eredeti $settings_tabs értékkel, ha nincs megfelelő jogosultság
            }
        
            $settings_tabs['delivery_notifications'] = __('Delivery Notifications', 'woocommerce');
            return $settings_tabs;
        }
        // Beállítások hozzáadása az új fülhöz
        public function settings_tab() {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.'));
            }
            woocommerce_admin_fields($this->get_settings());
        }
        // Beállítások mentése
        public function update_settings() {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to update these settings.'));
            }
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

        public function add_admin_column($columns) {
            $new_columns = array();
            foreach ($columns as $key => $value) {
                $new_columns[$key] = $value;
                if ($key == 'price') {
                    $new_columns['delivery_status'] = __('Delivery Status', 'woocommerce');
                }
            }
            return $new_columns;
        }
    
        public function display_admin_column_data($column, $post_id) {
            if ($column == 'delivery_status') {
                $selected_products = get_option('wc_delivery_selected_products', array());
                if (in_array($post_id, $selected_products)) {
                    echo __('Extend delivery time', 'woocommerce');
                }
            }
        }
    
        public function add_admin_filter_dropdown() {
            global $typenow;
    
            if ($typenow == 'product') {
                $selected = isset($_GET['delivery_status']) ? $_GET['delivery_status'] : '';
                ?>
                <select name="delivery_status">
                    <option value=""><?php _e('Filter by Delivery Status', 'woocommerce'); ?></option>
                    <option value="extended" <?php selected($selected, 'extended'); ?>><?php _e('Extended delivery time', 'woocommerce'); ?></option>
                </select>
                <?php
            }
        }
    
        public function filter_products_by_delivery_status($query) {
            global $pagenow, $typenow;
    
            if ($pagenow == 'edit.php' && $typenow == 'product' && isset($_GET['delivery_status']) && $_GET['delivery_status'] == 'extended') {
                $query->query_vars['post__in'] = get_option('wc_delivery_selected_products', array());
            }
    
            return $query;
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
                'product_admin_column_delivery' => array(
                    'name'     => __('Show Delivery Admin Column', 'woocommerce'),
                    'type'     => 'checkbox',
                    'desc_tip' => __('Enable to show the Delivery admin column.', 'woocommerce'),
                    'id'       => 'wc_delivery_admin_column',
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
                ),
                'regular_section_title' => array(
                    'name' => __('Delivery notification settings for regular', 'woocommerce'),
                    'type' => 'title',
                    'desc' => '',
                    'id'   => 'wc_delivery_regular_section_title'
                ),
                'enable_regular_notification' => array(
                    'name'     => __('Enable regular delivery notification', 'woocommerce'),
                    'type'     => 'checkbox',
                    'desc_tip' => __('Enable notification for products not in the selected list.', 'woocommerce'),
                    'id'       => 'wc_delivery_enable_regular_notification',
                    'default'  => 'no'
                ),
                'regular_notification_title' => array(
                    'name' => __('Notification Title for Regular Products', 'woocommerce'),
                    'type' => 'text',
                    'desc_tip' => __('Enter the title for the delivery notification for regular products.', 'woocommerce'),
                    'id'   => 'wc_delivery_regular_notification_title'
                ),
                'regular_notification_message' => array(
                    'name' => __('Notification Message for Regular Products', 'woocommerce'),
                    'type' => 'textarea',
                    'desc_tip' => __('Enter the long notification message for regular product deliveries.', 'woocommerce'),
                    'id'   => 'wc_delivery_regular_notification_message',
                    'css'  => 'min-width:300px; min-height:200px;'
                ),
                'regular_section_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_delivery_regular_section_end'
                )
            );

            return apply_filters('wc_delivery_notifications_settings', $settings);
        }

    }

}

?>
