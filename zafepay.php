<?php
/*
 * Plugin Name: Zafepay
 * Plugin URI: https://bitbucket.org/plaglabs/zafepay-plugin/src/master/
 * Description: Paga como quieras
 * Author: Zafepay
 * Author URI: https://zafepay.com
 * Version: 1.0.5
 * Requires at least: 5.7
 * Tested up to: 6.6
 * Text Domain: zafepay
 * Domain Path: /languages
 * License: MIT
 */

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if (!defined('ABSPATH')) {
  exit();
}

define('WC_ZAFEPAY_PLUGIN_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('WC_ZAFEPAY_PLUGIN_URL', untrailingslashit(plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__))));

function zafepay_add_zafepay_gateway_class($methods)
{
  $methods[] = WC_Gateway_Zafepay::class;
  return $methods;
}

add_filter('woocommerce_payment_gateways', 'zafepay_add_zafepay_gateway_class');
add_action('plugins_loaded', 'zafepay_initialize_zafepay_gateway_class');
add_action('woocommerce_blocks_loaded', 'zafepay_gateway_woocommerce_block_support');

function zafepay_initialize_zafepay_gateway_class()
{
  require_once dirname(__FILE__) . '/includes/class-wc-gateway-zafepay.php';
}

function zafepay_gateway_woocommerce_block_support()
{
  if (class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
    require_once dirname(__FILE__) . '/includes/class-wc-zafepay-blocks-support.php';
    add_action(
      'woocommerce_blocks_payment_method_type_registration',
      function (PaymentMethodRegistry $payment_method_registry) {
        $payment_method_registry->register(new WC_Zafepay_Blocks_Support);
      },
      5
    );
  }
}
?>