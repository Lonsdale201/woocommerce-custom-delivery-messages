
<?php 
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// delivery-product-tab.php

if (!class_exists('Woo_Delivery_Product_Tab')) {

    class Woo_Delivery_Product_Tab {

        public function __construct() {
            add_filter('woocommerce_product_tabs', array($this, 'add_custom_product_tab'));
        }

        public function add_custom_product_tab($tabs) {
            $enable_tab = get_option('wc_enable_delivery_product_tab', 'no');
            $tab_label = get_option('wc_delivery_tab_label', '');
            $tab_content = get_option('wc_delivery_tab_content', '');
            $tab_priority = get_option('wc_delivery_tab_priority', '50');

            // Az aktuális termék lekérése
            global $product;
            if (!$product) {
                return $tabs;
            }

            // Virtuális termék ellenőrzése
            if ($product->is_virtual()) {
                return $tabs;
            }

            // Ha a checkbox be van jelölve és a label mezőben is van adat
            if ($enable_tab === 'yes' && !empty($tab_label)) {
                $tabs['delivery_tab'] = array(
                    'title'    => esc_html($tab_label),
                    'priority' => (int) $tab_priority,
                    'callback' => array($this, 'render_custom_product_tab_content')
                );
            }

            return $tabs;
        }

        public function render_custom_product_tab_content() {
            $tab_content = get_option('wc_delivery_tab_content', '');
            echo do_shortcode(wp_kses_post($tab_content));
        }

    }

}

