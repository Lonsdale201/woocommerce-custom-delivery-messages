<?php

// delivery-display.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Woo_Delivery_Display')) {

    class Woo_Delivery_Display {

        public function __construct() {
            $this->notification_placement = get_option('wc_delivery_notification_placement', 'below_add_to_cart');
        
            if ($this->notification_placement === 'above_add_to_cart') {
                add_action('woocommerce_before_add_to_cart_form', array($this, 'display_notification_message'));
            } elseif ($this->notification_placement === 'below_add_to_cart') {
                add_action('woocommerce_after_add_to_cart_form', array($this, 'display_notification_message'));
            }
        
            add_shortcode('woo_delivery_notification', array($this, 'delivery_notification_shortcode'));
            add_action('woocommerce_before_cart', array($this, 'display_cart_notification'));
        }
        
        public function display_notification_message() {
            global $product;
            $current_product_id = $product->get_id();
        
            $selected_products = get_option('wc_delivery_selected_products', []);
            $selected_categories = get_option('wc_delivery_selected_categories', []);
        
            if (in_array($current_product_id, $selected_products) || $this->is_product_in_selected_categories($product, $selected_categories)) {
                $this->display_selected_product_notification($current_product_id);
            } else {
                $this->display_regular_product_notification($current_product_id);
            }
        }
        
        protected function is_product_in_selected_categories($product, $selected_categories) {
            $product_categories = $product->get_category_ids();
            return array_intersect($product_categories, $selected_categories);
        }
    
        protected function display_selected_product_notification($product_id) {
            $notification_title = get_option('wc_delivery_notification_title');
            $notification_message = get_option('wc_delivery_notification_message');
    
            if (empty($notification_message)) {
                return;
            }
    
            global $product;
            if (get_option('wc_delivery_hide_if_out_of_stock') === 'yes' && !$product->is_in_stock()) {
                return;
            }
    
            echo '<div class="woo-delivery-notification-wrapper">';
            if (!empty($notification_title)) {
                echo '<div class="woo-delivery-title">' . esc_html($notification_title) . '</div>';
            }
            echo '<span class="woo-delivery-message">' . esc_html($notification_message) . '</span>';
            echo '</div>';
        }
    
        protected function display_regular_product_notification($product_id) {
            if (get_option('wc_delivery_enable_regular_notification') !== 'yes') {
                return;
            }
        
            $regular_notification_title = get_option('wc_delivery_regular_notification_title');
            $regular_notification_message = get_option('wc_delivery_regular_notification_message');
        
            if (empty($regular_notification_message)) {
                return;
            }
        
            global $product;
            if (get_option('wc_delivery_hide_if_out_of_stock') === 'yes' && !$product->is_in_stock()) {
                return;
            }
        
            echo '<div class="woo-delivery-notification-regular-wrapper">';
            if (!empty($regular_notification_title)) {
                echo '<div class="woo-delivery-title-regular">' . esc_html($regular_notification_title) . '</div>';
            }
            echo '<span class="woo-delivery-message-regular">' . esc_html($regular_notification_message) . '</span>';
            echo '</div>';
        }
    
    

        /*
        //=============================================================================
        // ** Elhelyezés: Manuális (SHORTCODE) [woo_delivery_notification]
        //=============================================================================
        */	


        public function delivery_notification_shortcode() {
            global $product;
        
            // Ha nincs termék, ne jelenjen meg semmi
            if (!$product) {
                return '';
            }
        
            $current_product_id = $product->get_id();
            
            $selected_products = get_option('wc_delivery_selected_products', []);
            $selected_categories = get_option('wc_delivery_selected_categories', []);
        
            $notification_title = get_option('wc_delivery_notification_title');
            $notification_message = get_option('wc_delivery_notification_message');
            $hide_if_out_of_stock = get_option('wc_delivery_hide_if_out_of_stock') === 'yes';
        
            // Regular beállítások lekérdezése
            $enable_regular_notification = get_option('wc_delivery_enable_regular_notification') === 'yes';
            $regular_notification_message = get_option('wc_delivery_regular_notification_message');
            $regular_notification_title = get_option('wc_delivery_regular_notification_title'); 
        
            $output = '';
        
            // Ha az aktuális termék azonosítója a kiválasztott termékek között van vagy a termék kiválasztott kategóriában szerepel
            if (in_array($current_product_id, $selected_products) || $this->is_product_in_selected_categories($product, $selected_categories)) {
                if ($hide_if_out_of_stock && !$product->is_in_stock()) {
                    return '';
                }
        
                $output .= '<div class="woo-delivery-notification-wrapper">';
                if (!empty($notification_title)) {
                    $output .= '<div class="woo-delivery-title">' . esc_html($notification_title) . '</div>';
                }
                $output .= '<span class="woo-delivery-message">' . esc_html($notification_message) . '</span></div>';
            } elseif ($enable_regular_notification) {
                if ($hide_if_out_of_stock && !$product->is_in_stock()) {
                    return '';
                }
        
                $output .= '<div class="woo-delivery-notification-regular-wrapper">';
                if (!empty($regular_notification_title)) {
                    $output .= '<div class="woo-delivery-title-regular">' . esc_html($regular_notification_title) . '</div>';
                }
                $output .= '<span class="woo-delivery-message-regular">' . esc_html($regular_notification_message) . '</span></div>';
            }
        
            return $output;
        }
        
        
        /*
        //=============================================================================
        // ** Kosár oldal
        //=============================================================================
        */	

        
        public function display_cart_notification() {
            $cart_reminder = get_option('wc_delivery_cart_reminder', 'no');
            
            // Ha a Cart Reminder nincs bekapcsolva, akkor off
            if ($cart_reminder !== 'yes') {
                return;
            }
            
            $selected_products = get_option('wc_delivery_selected_products', []);
            $selected_categories = get_option('wc_delivery_selected_categories', []);
            $cart_reminder_message = get_option('wc_delivery_cart_reminder_message');
            
            if (empty($cart_reminder_message)) {
                return;
            }
            
            $matching_products = [];
            
            // Ellenőrizzük, hogy a kosárban van-e olyan termék, ami a kiválasztott termékek vagy kiválasztott kategóriák között van
            $cart_items = WC()->cart->get_cart();
            foreach ($cart_items as $item) {
                $product = wc_get_product($item['product_id']);
                $product_categories = $product->get_category_ids();
                
                if (in_array($item['product_id'], $selected_products) || array_intersect($product_categories, $selected_categories)) {
                    $product_name = sprintf('<a href="%s" target="_blank">%s</a>', get_permalink($item['product_id']), $item['data']->get_name());
                    $matching_products[] = $product_name;
                }
            }
        
            if (!empty($matching_products)) {
                $product_links = implode(', ', $matching_products);
                $final_message = sprintf($cart_reminder_message, $product_links);
                wc_print_notice($final_message, 'notice');
            }
        }        
        
    }

}

?>
