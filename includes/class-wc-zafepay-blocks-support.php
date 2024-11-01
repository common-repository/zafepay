<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined('ABSPATH') || exit;


final class WC_Zafepay_Blocks_Support extends AbstractPaymentMethodType
{

  protected $name = 'zafepay';


  public function initialize()
  {
    $this->settings = get_option('woocommerce_zafepay_settings', []);
  }

  public function is_active()
  {
    return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
  }

  public function get_payment_method_script_handles()
  {
    $asset_path = WC_ZAFEPAY_PLUGIN_PATH . '/build/index.asset.php';
    $dependencies = [];
    if (file_exists($asset_path)) {
      $asset = require $asset_path;
      $dependencies = is_array($asset) && isset($asset['dependencies'])
        ? $asset['dependencies']
        : $dependencies;
    }
    wp_register_script('wc-zafepay-blocks-integration', WC_ZAFEPAY_PLUGIN_URL . '/build/index.js', ['wc-blocks-registry']);

    return ['wc-zafepay-blocks-integration'];
  }
}
;

?>