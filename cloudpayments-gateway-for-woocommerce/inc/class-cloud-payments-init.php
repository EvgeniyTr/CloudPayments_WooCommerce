<?php

class CloudPayments_Init
{
    
    public static function init()
    {
        add_filter('init', [__CLASS__, 'register_post_statuses']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'cloudPayments_scripts']);
        add_action('plugins_loaded', [__CLASS__, 'CloudPayments']);
        add_filter('wc_order_statuses', [__CLASS__, 'add_order_statuses']);
    }
    
    public static function cloudPayments_scripts()
    {
        if (is_checkout()) {
            wp_enqueue_script('CloudPayments_script', plugins_url('/assets/scripts.js', CPGWWC_PLUGIN_FILENAME), ['jquery'], time(), true);
            wp_enqueue_style('CloudPayments_style', plugins_url('/assets/style.css', CPGWWC_PLUGIN_FILENAME));
        }
    }
    
    public static function register_post_statuses()
    {
        register_post_status('wc-pay_au', array(
            'label'                     => _x('Платеж авторизован', 'WooCommerce Order status', 'text_domain'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Approved (%s)', 'Approved (%s)', 'text_domain')
        ));
        register_post_status('wc-pay_delivered', array(
            'label'                     => _x('Доставлен', 'WooCommerce Order status', 'text_domain'),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop('Approved (%s)', 'Approved (%s)', 'text_domain')
        ));
    }
    
    public static function add_order_statuses($order_statuses)
    {
        $order_statuses['wc-pay_au']        = _x('Платеж авторизован', 'WooCommerce Order status', 'text_domain');
        $order_statuses['wc-pay_delivered'] = _x('Доставлен', 'WooCommerce Order status', 'text_domain');
        
        return $order_statuses;
    }
    
    public static function CloudPayments()
    {
        if ( ! class_exists('WooCommerce')) {
            return;
        }
        
        add_filter('woocommerce_payment_gateways', [__CLASS__, 'add_gateway_class']);
        
        require(CPGWWC_PLUGIN_DIR . 'inc/class-cloud-payments-api.php');
        require(CPGWWC_PLUGIN_DIR . 'inc/wc-cloud-payments-gateway.php');
    }
    
    public static function add_gateway_class($methods)
    {
        $methods[] = 'WC_CloudPayments_Gateway';
        
        return $methods;
    }
    
}