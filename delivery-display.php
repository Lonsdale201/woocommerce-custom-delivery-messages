<?php

// delivery-display.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (!class_exists('Woo_Delivery_Display')) {

    class Woo_Delivery_Display {

        public function __construct() {
            add_action('woocommerce_after_add_to_cart_form', array($this, 'display_notification_message'));
            add_shortcode('woo_delivery_notification', array($this, 'delivery_notification_shortcode'));
            add_action('woocommerce_before_cart', array($this, 'display_cart_notification'));
        }
        
        // Megjelenítjük az értesítést, ha a termék a kiválasztottak között van
        public function display_notification_message() {
            // Beállítások lekérdezése
            $selected_products = get_option('wc_delivery_selected_products');
            $notification_title = get_option('wc_delivery_notification_title');
            $notification_message = get_option('wc_delivery_notification_message');
            $hide_if_out_of_stock = get_option('wc_delivery_hide_if_out_of_stock') === 'yes';
            $notification_placement = get_option('wc_delivery_notification_placement', 'below_add_to_cart');

            // Ha nincsenek kiválasztva termékek vagy a szöveg üres, akkor off
            if (empty($selected_products) || empty($notification_message)) {
                return;
            }

            if ($notification_placement === 'shortcode') {
                return;
            }

            // Aktuális termék ID-jének lekérdezése
            global $product;
            $current_product_id = $product->get_id();

            // Ha az aktuális termék azonosítója a kiválasztott termékek között van
            if (in_array($current_product_id, $selected_products)) {
                // Ha be van kapcsolva a "Hide if out of stock" beállítás és a termék nincs készleten, akkor off
                if ($hide_if_out_of_stock && !$product->is_in_stock()) {
                    return;
                }

                echo '<div class="woo-delivery-notification-wrapper">';

                // Cím megjelenítése, ha van beállítva
                if (!empty($notification_title)) {
                    echo '<div class="woo-delivery-title">' . esc_html($notification_title) . '</div>';
                }

                echo '<span class="woo-delivery-message">' . esc_html($notification_message) . '</span>';
                echo '</div>';
            }
        }
        public function delivery_notification_shortcode() {
            // Beállítások lekérdezése
            $selected_products = get_option('wc_delivery_selected_products');
            $notification_message = get_option('wc_delivery_notification_message');
            $hide_if_out_of_stock = get_option('wc_delivery_hide_if_out_of_stock') === 'yes';
            $notification_placement = get_option('wc_delivery_notification_placement', 'below_add_to_cart');
        
            // Ha nincsenek kiválasztva termékek vagy a szöveg üres, akkor off
            if (empty($selected_products) || empty($notification_message)) {
                return '';
            }
               // Ha a beállítás nem a shortcode-ra van állítva, akkor off
            if ($notification_placement !== 'shortcode') {
                return '';
            }

            // Aktuális termék ID-jének lekérdezése
            global $product;
            if (!$product) {
                return '';
            }
        
            $current_product_id = $product->get_id();
        
            // Ha az aktuális termék azonosítója nem a kiválasztott termékek között van akkor off
            if (!in_array($current_product_id, $selected_products)) {
                return '';
            }
        
            // Ha be van kapcsolva a "Hide if out of stock" beállítás és a termék nincs készleten, akkor off
            if ($hide_if_out_of_stock && !$product->is_in_stock()) {
                return '';
            }
        
            return '<div class="woo-delivery-notification-wrapper">
                <span class="woo-delivery-message">' . esc_html($notification_message) . '</span>
            </div>';
        }        

        public function display_cart_notification() {
            $cart_reminder = get_option('wc_delivery_cart_reminder', 'no');
            
            // Ha a Cart Reminder nincs bekapcsolva, akkor off
            if ($cart_reminder !== 'yes') {
                return;
            }
            
            $selected_products = get_option('wc_delivery_selected_products');
            $cart_reminder_message = get_option('wc_delivery_cart_reminder_message');
            
            if (empty($selected_products) || empty($cart_reminder_message)) {
                return;
            }
            
            $matching_products = [];
            
            // Ellenőrizzük, hogy a kosárban van-e olyan termék, ami a kiválasztott termékek között van
            $cart_items = WC()->cart->get_cart();
            foreach ($cart_items as $item) {
                if (in_array($item['product_id'], $selected_products)) {
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
