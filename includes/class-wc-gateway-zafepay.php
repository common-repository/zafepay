<?php
if (!defined('ABSPATH')) {
  exit;
}

class WC_Gateway_Zafepay extends WC_Payment_Gateway
{
  public function __construct()
  {
    $this->id = 'zafepay';
    $this->icon = '../assets/zafepay_logo_100px.png';
    $this->has_fields = false;
    $this->method_title = __('Zafepay', 'zafepay');
    $this->method_description = __('Paga como quieras', 'zafepay');
    $this->supports = ['products'];

    $this->init_form_fields();
    $this->init_settings();

    $this->title = $this->get_option('title');
    $this->description = $this->get_option('description');
    $this->enabled = $this->get_option('enabled');
    $this->testmode = 'yes' === $this->get_option('testmode');
    $this->api_key = $this->testmode ? $this->get_option('test_api_key') : $this->get_option('api_key');
    $this->api_secret = $this->testmode ? $this->get_option('test_api_secret') : $this->get_option('api_secret');
    $this->base_url = $this->testmode ? 'http://api.staging.zafepay.com/api' : 'https://api.zafepay.com/api';

    add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    add_action('woocommerce_api_zafepay_callback', [$this, 'zafepay_callback']);
    add_action('woocommerce_thankyou_order_received_text', [$this, 'zafepay_thank_you'], 10, 2);
    add_action('woocommerce_checkout_terms_and_conditions', [$this, 'wc_checkout_privacy_policy_text'], 20);

  }

  public function init_form_fields()
  {
    $this->form_fields = [
      'enabled' => [
        'title' => __('Activar/Desactivar', 'zafepay'),
        'type' => 'checkbox',
        'label' => __('¿Quieres activar Zafepay en tu checkout?', 'zafepay'),
        'default' => 'yes'
      ],
      'title' => [
        'title' => __('Nombre del medio de pago', 'zafepay'),
        'type' => 'text',
        'description' => __('Título que tus clientes verán al pagar con Zafepay', 'zafepay'),
        'default' => __('Zafepay', 'zafepay'),
      ],
      'description' => [
        'title' => __('Descripción que tus clientes verán al pagar con Zafepay', 'zafepay'),
        'type' => 'textarea',
        'default' => __('Paga con Transbank, Fintoc o Mach', 'zafepay')
      ],
      'api_key' => [
        'title' => __('Api Key para utilizar Zafepay', 'zafepay'),
        'type' => 'text',
        'description' => __('Si no tienes una Api Key, contáctate con <a href="mailto:credenciales@zafepay.com">credenciales@zafepay.com</a>.', 'zafepay')
      ],
      'api_secret' => [
        'title' => __('Api Secret para utilizar Zafepay', 'zafepay'),
        'type' => 'text',
        'description' => __('Si no tienes una Api Secret, contáctate con <a href="mailto:credenciales@zafepay.com">credenciales@zafepay.com</a>.', 'zafepay')
      ],
      'test_api_key' => [
        'title' => __('Api Key para utilizar Zafepay en modo prueba', 'zafepay'),
        'type' => 'text',
        'description' => __('Si no tienes una Api Key, contáctate con <a href="mailto:credenciales@zafepay.com">credenciales@zafepay.com</a>.', 'zafepay')
      ],
      'test_api_secret' => [
        'title' => __('Api Secret para utilizar Zafepay en modo prueba', 'zafepay'),
        'type' => 'text',
        'description' => __('Si no tienes una Api Secret, contáctate con <a href="mailto:credenciales@zafepay.com">credenciales@zafepay.com</a>.', 'zafepay')
      ],
      'testmode' => [
        'title' => __('Habilitar Modo Pruebas', 'zafepay'),
        'label' => __('Atención: Si habilitas esta opción no se realizarán cargos reales a los clientes. Habilita este modo solo si necesitas realizar pruebas de integración.', 'zafepay'),
        'type' => 'checkbox',
        'default' => 'yes',
      ],
    ];
  }

  public function get_supported_currency()
  {
    return ['CLP'];
  }

  public function process_payment($order_id)
  {
    global $woocommerce;
    $order = new WC_Order($order_id);

    try {
      $order = wc_get_order($order_id);
      $amount = (int) number_format($order->get_total(), 0, ',', '');
      $currency = $order->get_currency();
      $callback_url = add_query_arg(
        'order_id',
        $order_id,
        add_query_arg('wc-api', 'zafepay_callback', home_url('/wc-api/zafepay_callback'))
      );
      $return_url = $this->get_return_url($order);
      $cancel_url = $order->get_cancel_order_url_raw();

      $products = array();
      foreach ($order->get_items() as $item_id => $item) {
        $temporary_array = array();
        $product = $item->get_product();
        $temporary_array['product_id'] = $item->get_product_id();
        $temporary_array['product_name'] = $item->get_name();
        $temporary_array['product_quantity'] = $item->get_quantity();
        $temporary_array['product_price'] = $product->get_price();
        array_push($products, $temporary_array);
      }
      $customer = new WC_Customer($order->get_user_id());

      $first_name = $customer->get_first_name() ? $customer->get_first_name() : $order->get_shipping_first_name();
      $first_name = $first_name ? $first_name : $order->get_billing_first_name();
      $first_name = $first_name ? $first_name : '';

      error_log($first_name);
      error_log($customer->get_first_name());
      error_log($order->get_shipping_first_name());
      error_log($order->get_billing_first_name());

      $last_name = $customer->get_last_name() ? $customer->get_last_name() : $order->get_shipping_last_name();
      $last_name = $last_name ? $last_name : $order->get_billing_last_name();
      $last_name = $last_name ? $last_name : '';

      $email = $customer->get_email() ? $customer->get_email() : $order->get_billing_email();
      $email = $email ? $email : 'no_email@zafepay.com';

      $create_payment = wp_remote_post(
        $this->base_url . '/v1/external/woocommerce/orders',
        [
          'headers' => [
            'Authorization' => 'Basic ' . base64_encode($this->api_key . ':' . $this->api_secret),
            'Content-Type' => 'application/json',
          ],
          'timeout' => 15,
          'body' => wp_json_encode([
            'order_id' => $order_id,
            'currency' => $currency,
            'cancel_url' => $cancel_url,
            'total_amount' => $amount,
            'products' => $products,
            'callback_url' => $callback_url,
            'success_url' => $return_url,
            'buyer_name' => $first_name . ' ' . $last_name,
            'buyer_email' => $email,
          ])
        ]
      );

      if (!is_wp_error($create_payment)) {
        $new_payment = json_decode(wp_remote_retrieve_body($create_payment));

        if (
          isset($new_payment)
          && !empty($new_payment->zafepay_id)
          && !empty($new_payment->url)
          && !empty($new_payment->verification_token)
        ) {

          $order->add_meta_data(
            'zafepay_id',
            $new_payment->zafepay_id,
            true
          );
          $order->add_meta_data(
            'zafepay_verification_token',
            $new_payment->verification_token,
            true,
          );

          $order->update_status(
            'pending',
            __(
              'Esperando a que el cliente realize el pago',
              'zafepay'
            )
          );

          return [
            'result' => 'success',
            'redirect' => esc_url_raw($new_payment->url),
          ];
        }
      }

      wc_add_notice(
        __(
          'Ocurrió un error al procesar tu pago, por favor intenta nuevamente. Si el error persiste, contáctanos.',
          'zafepay'
        ),
        'error'
      );
      return ['result' => 'fail', 'redirect' => ''];
    } catch (Exception $e) {
      wc_add_notice(
        __(
          'Ocurrió un error al procesar tu pago, por favor intenta nuevamente. Si el error persiste, contáctanos.',
          'zafepay'
        ),
        'error'
      );
      return ['result' => 'fail', 'redirect' => ''];
    }
  }

  public function zafepay_callback()
  {
    $order_id = sanitize_key($_GET['order_id']);
    $order = wc_get_order($order_id); # ARRREGLAR

    if (!isset($order) || !$order || !is_a($order, 'WC_Order') || !$order->get_id()) {
      header('HTTP/1.1 404 Not Found (Order Not Found)');
      return;
    }

    if ($order->is_paid()) {
      header('HTTP/1.1 200 OK (Order is Paid)');
      return;
    }

    if ($order->needs_payment()) {
      // Comparar lo guardado con lo obtenido en la request?
      $meta_zafepay_id = $order->get_meta('zafepay_id');
      $meta_verification_token = $order->get_meta('zafepay_verification_token');

      $params_order_id = sanitize_key($_GET['zafepay_id']); # ARREGLAR
      $params_verification_token = sanitize_text_field($_GET['verification_token']); # ARREGLAR

      if (
        !empty($meta_zafepay_id)
        && !empty($meta_verification_token)
        && !empty($params_order_id)
        && !empty($params_verification_token)
        && $meta_zafepay_id === $params_order_id
        && $meta_verification_token === $params_verification_token
      ) {
        $order->payment_complete();
        header('HTTP/1.1 200 OK (Payment Completed)');
        return;
      }

      header('HTTP/1.1 400 Bad Request (Meta And Request IDs Does Not Match)');
      return;

    }
    header('HTTP/1.1 400 Bad Request (Order Does Not Need Payment)');
    return;
  }

  public function zafepay_thank_you($var, $order_id)
  {
    $order = wc_get_order($order_id);
    if (isset($order) && $order && is_a($order, 'WC_Order') && ($order->get_id())) {
      $meta_zafepay_id = $order->get_meta('zafepay_id');
      if (!empty($meta_zafepay_id)) {
        if ($order->is_paid()) {
          $message = [
            '<div class="woocommerce-message">',
            '<span>',
            __(
              'Muchas gracias, tu pago ha sido exitoso.',
              'zafepay'
            ),
            '</span>',
            '</div>',
          ];
          return implode("\n", $message);
        }
      }
    }
    $message = [
      '<div class="woocommerce-error">',
      '<span>',
      __(
        'Ha ocurrido un error, por favor ponte en contacto con nosotros.',
        'zafepay'
      ),
      '</span>',
      '</div>',
    ];
    return implode("\n", $message);
  }

  public function wc_checkout_privacy_policy_text()
  {
    $message = [
      '<div class="woocommerce-privacy-policy-text">',
      '<span>',
      __('Al pagar con Zafepay, aceptas que usemos tu información personal de acuerdo con la', 'zafepay'),
      '<a href="https://www.zafepay.com/politica-de-privacidad-de-datos" target="_blank" rel="noopener noreferrer">',
      __('política de privacidad de datos', 'zafepay'),
      '</a>',
      __('.', 'zafepay'),
      '</span>',
      '</div>',
    ];
  }

}
;
?>