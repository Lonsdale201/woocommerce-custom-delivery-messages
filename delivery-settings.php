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
        
            $settings_tabs['delivery_notifications'] = __('Delivery Notifications', 'woocommerce-delivery-notifications');
            return $settings_tabs;
        }
        // Beállítások hozzáadása az új fülhöz
        public function settings_tab() {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'woocommerce-delivery-notifications'));
            }
            woocommerce_admin_fields($this->get_settings());
        }
        // Beállítások mentése
        public function update_settings() {
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to update these settings.', 'woocommerce-delivery-notifications'));
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
        // Termékkategóriák lekérdezése az adatbázisból
        public function get_product_categories() {
            $categories = get_terms( array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ) );

            $category_options = array();

            if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
                foreach ( $categories as $category ) {
                    $category_options[ $category->term_id ] = $category->name;
                }
            }

            return $category_options;
        }
        // admin oszlop
        public function add_admin_column($columns) {
            $new_columns = array();
            foreach ($columns as $key => $value) {
                $new_columns[$key] = $value;
                if ($key == 'price') {
                    $new_columns['delivery_status'] = __('Delivery Status', 'woocommerce-delivery-notifications');
                }
            }
            return $new_columns;
        }

        public function display_admin_column_data($column, $post_id) {
            if ($column == 'delivery_status') {
                $selected_products = get_option('wc_delivery_selected_products', array());
                $selected_categories = get_option('wc_delivery_selected_categories', array());
                $product = wc_get_product($post_id);
                $product_categories = $product->get_category_ids();
                
                if (in_array($post_id, $selected_products) || array_intersect($product_categories, $selected_categories)) {
                    echo __('Extend delivery time', 'woocommerce-delivery-notifications');
                }
            }
        }
     
        // A beállítások definiálása
        public function get_settings() {
            $settings = array(
                'section_title' => array(
                    'name' => __('Delivery Notifications Settings', 'woocommerce-delivery-notifications'),
                    'type' => 'title',
                    'desc' => 'Show delivery informations for your customers. use the <code>[woo_delivery_notification]</code> if the placement set to shortcode',
                    'id' => 'wc_delivery_notifications_section_title'
                ),
                'selected_products' => array(
                    'name' => __('Select Products', 'woocommerce-delivery-notifications'),
                    'type' => 'multiselect',
                    'class' => 'wc-enhanced-select',
                    'desc_tip' => __('Select products for delivery notifications.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_selected_products',
                    'options' => $this->get_products()  // Termékek lekérdezése
                ),
                'selected_categories' => array(
                    'name'     => __('Select Product Categories', 'woocommerce-delivery-notifications'),
                    'type'     => 'multiselect',
                    'class'    => 'wc-enhanced-select',  
                    'desc_tip' => __('Select product categories for delivery notifications.', 'woocommerce-delivery-notifications'),
                    'id'       => 'wc_delivery_selected_categories',
                    'options'  => $this->get_product_categories()  // Termékkategóriák lekérdezése
                ),                
                'hide_if_out_of_stock' => array(
                    'name' => __('Hide if out of stock', 'woocommerce-delivery-notifications'),
                    'type' => 'checkbox',
                    'desc_tip' => __('Hide the notification if the product is out of stock.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_hide_if_out_of_stock',
                    'default' => 'no'
                ),
                'product_admin_column_delivery' => array(
                    'name' => __('Show Delivery Admin Column', 'woocommerce-delivery-notifications'),
                    'type' => 'checkbox',
                    'desc_tip' => __('Enable to show the Delivery admin column.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_admin_column',
                    'default' => 'no'
                ),
                'notification_placement' => array(
                    'name' => __('Notification Placement', 'woocommerce-delivery-notifications'),
                    'type' => 'select',
                    'desc_tip' => __('Choose where the notification should appear.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_notification_placement',
                    'default' => 'below_add_to_cart',
                    'options' => array(
                        'below_add_to_cart' => __('Below Add to Cart', 'woocommerce-delivery-notifications'),
                        'above_add_to_cart' => __('Above Add to Cart', 'woocommerce-delivery-notifications'),
                        'shortcode' => __('Shortcode', 'woocommerce-delivery-notifications')
                    )
                ),
                'notification_title' => array(
                    'name' => __('Notification Title', 'woocommerce-delivery-notifications'),
                    'type' => 'text',
                    'desc_tip' => __('Enter the title for the delivery notification.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_notification_title'
                ),
                'notification_message' => array(
                    'name' => __('Notification Message', 'woocommerce-delivery-notifications'),
                    'type' => 'textarea',
                    'desc_tip' => __('Enter the long notification message for deliveries.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_notification_message',
                    'css' => 'min-width:300px; min-height:200px;'
                ),
                'cart_reminder' => array(
                    'name' => __('Cart Reminder', 'woocommerce-delivery-notifications'),
                    'type' => 'checkbox',
                    'desc' => __('Display the notification on the cart page if applicable.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_cart_reminder',
                    'default' => 'no'
                ),
                'cart_reminder_message' => array(
                    'name' => __('Cart Reminder Message', 'woocommerce-delivery-notifications'),
                    'type' => 'textarea',
                    'desc_tip' => __('Enter the reminder message for the cart page. Use %s to display product names.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_cart_reminder_message',
                    'css' => 'min-width:300px; min-height:200px;'
                ),
                'section_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_delivery_notifications_section_end'
                ),
                'regular_section_title' => array(
                    'name' => __('Delivery notification settings for regular', 'woocommerce-delivery-notifications'),
                    'type' => 'title',
                    'desc' => '',
                    'id' => 'wc_delivery_regular_section_title'
                ),
                'enable_regular_notification' => array(
                    'name' => __('Enable regular delivery notification', 'woocommerce-delivery-notifications'),
                    'type' => 'checkbox',
                    'desc_tip' => __('Enable notification for products not in the selected list.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_enable_regular_notification',
                    'default' => 'no'
                ),
                'regular_notification_title' => array(
                    'name' => __('Notification Title for Regular Products', 'woocommerce-delivery-notifications'),
                    'type' => 'text',
                    'desc_tip' => __('Enter the title for the delivery notification for regular products.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_regular_notification_title'
                ),
                'regular_notification_message' => array(
                    'name' => __('Notification Message for Regular Products', 'woocommerce-delivery-notifications'),
                    'type' => 'textarea',
                    'desc_tip' => __('Enter the long notification message for regular product deliveries.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_regular_notification_message',
                    'css' => 'min-width:300px; min-height:200px;'
                ),
                'regular_section_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_delivery_regular_section_end'
                ),
                'delivery_product_tab_section_title' => array(
                    'name' => __('Delivery product tab', 'woocommerce-delivery-notifications'),
                    'type' => 'title',
                    'desc' => __('Create a new Delivery Tab of every product to inform the customers about the shipping', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_product_tab_section_title'
                ),
                'enable_delivery_product_tab' => array(
                    'name' => __('Enable Delivery Product tab', 'woocommerce-delivery-notifications'),
                    'type' => 'checkbox',
                    'desc' => '',
                    'id' => 'wc_enable_delivery_product_tab',
                    'default' => 'no'
                ),
                'delivery_tab_label' => array(
                    'name' => __('Delivery tab Label', 'woocommerce-delivery-notifications'),
                    'type' => 'text',
                    'desc_tip' => __('Label for the Delivery tab on the product page.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_tab_label',
                    'css' => 'min-width:300px;'
                ),
                'delivery_tab_priority' => array(
                    'name' => __('Delivery Tab Priority', 'woocommerce-delivery-notifications'),
                    'type' => 'number',
                    'desc_tip' => __('Set the priority for the Delivery tab. Lower numbers correspond to higher priority and will display the tab earlier.', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_tab_priority',
                    'default' => '50',  // alapértelmezett érték
                    'custom_attributes' => array(
                        'min' => '1',    // Min érték
                        'step' => '1'    // Stepper
                    )
                ),
                'delivery_tab_content' => array(
                    'name' => __('Delivery tab content', 'woocommerce-delivery-notifications'),
                    'type' => 'textarea',
                    'desc' => 'Content for the Delivery tab on the product page. You can use SHORTCODE, or HTML.',
                    'desc_tip' => __('Content for the Delivery tab on the product page. You can use SHORTCODE, or HTML. ', 'woocommerce-delivery-notifications'),
                    'id' => 'wc_delivery_tab_content',
                    'css' => 'min-width:300px; min-height:200px;'
                ),
                'delivery_product_tab_section_end' => array(
                    'type' => 'sectionend',
                    'id' => 'wc_delivery_product_tab_section_end'
                )                
            );            

            return apply_filters('wc_delivery_notifications_settings', $settings);
        }

    }

}

?>
