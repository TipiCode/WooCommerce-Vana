<?php
/**
* Plugin Name: VanaPay - WooCommerce
* Plugin URI: https://github.com/TipiCode/Woocommerce-Vana
* Description: Plugin para Woocommerce que habilita la pasarela de pago Vana Pay como método de pago en el checkout de tú sitio web.
* Version:     1.0.0
* Requires PHP: 7.4
* Author:      tipi(code)
* Author URI: https://codingtipi.com
* License:     MIT
* WC requires at least: 7.4.0
* WC tested up to: 9.8.5
*
* @package WoocommerceVana
*/
define('VANA_PLUGIN_VERSION', '1.0.0');
define('VANA_APP_ID', '323e615b-323e-48bf-bea7-f20b79748a4f');

if ( ! defined( 'ABSPATH' ) ) { 
  exit; // No permitir acceder el plugin directamente
}

/**
* Función encargada de inicializar la pasarela de pagos de VanaPay
* 
* @author Luis E. Mendoza <lmendoza@codingtipi.com>
* @link https://codingtipi.com/project/vanapay
* @since 1.0.0
*/
function vana_init() {
    if (!class_exists('WC_Payment_Gateway')) {
      return;
    }

    // Definir la ruta base del plugin
    define('VANA_PLUGIN_DIR', dirname(__FILE__));

    // Cargar todos los archivos necesarios en el orden correcto
    if (!class_exists('VanCurl')) {
        require_once VANA_PLUGIN_DIR . '/utils/curl.php';
    }

    require_once VANA_PLUGIN_DIR . '/classes/vana.php';
    require_once VANA_PLUGIN_DIR . '/classes/single-checkout.php';
    require_once VANA_PLUGIN_DIR . '/includes/vana-response.php';
    require_once VANA_PLUGIN_DIR . '/includes/vana-settings.php';
    require_once VANA_PLUGIN_DIR . '/includes/vana-block-checkout.php';
    require_once VANA_PLUGIN_DIR . '/includes/support.php';

    VanaPay::get_instance();

    include_once( 'includes/plugin-update-checker/plugin-update-checker.php');

    $myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://tipi-pod.sfo3.digitaloceanspaces.com/plugins/vanapay/details.json',
        __FILE__, //Full path to the main plugin file or functions.php.
        'woocommerce-vana'
    );
}

// Primero verificamos que WooCommerce esté activo
add_action( 'plugins_loaded', function() {
  if ( class_exists( 'WooCommerce' ) ) {
    // Luego inicializamos el plugin en el hook init
    add_action( 'init', 'vana_init', 0 );
  }
}, 0 );

/**
* Función encargada de agregar VanaPay en la lista de pasarelas de pago
* 
* @return Array Arreglo que contiene los metodos de pago disponibles.
* @author Luis E. Mendoza <lmendoza@codingtipi.com>
* @link https://codingtipi.com/project/vanapay
* @since 1.2.0
*/
function add_vana_gateway( $methods ) {
	$methods[] = 'VanaPay';
	return $methods;
}
add_filter( 'woocommerce_payment_gateways', 'add_vana_gateway' );

/**
* Función encargada de agregar el link hacia la configuración de VanaPay
* 
* @return Array Arreglo que contiene los links de plugins disponibles
* @author Luis E. Mendoza <lmendoza@codingtipi.com>
* @link https://codingtipi.com/project/vanapay
* @since 1.0.0
*/
function vana_action_links( $links ) {
  $plugin_links = array(
	'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'vana_pay' ) . '</a>',
  );
  return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'vana_action_links' );

/**
* Añade funcionalidad para compatibilidad con HPO de WooCommerce
* 
* @author Luis E. Mendoza <lmendoza@codingtipi.com>
* @link https://codingtipi.com/project/vanapay
* @since 1.0.0
*/
function vana_hpo(){
  if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
  }
} 
add_action('before_woocommerce_init', 'vana_hpo');

/**
* Añade funcionalidad para compatibilidad con Blocks de WooCommerce
* 
* @author Luis E. Mendoza <lmendoza@codingtipi.com>
* @link https://codingtipi.com/project/vanapay
* @since 1.0.0
*/
function declare_vana_cart_checkout_blocks_compatibility() {
  // Check if the required class exists
  if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
      // Declare compatibility for 'cart_checkout_blocks'
      \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
  }
}
add_action('before_woocommerce_init', 'declare_vana_cart_checkout_blocks_compatibility');

/**
* Añade funcionalidad para mostrar la pasarela de pagos en el area de bloques de WooCommerce
* 
* @author Luis E. Mendoza <lmendoza@codingtipi.com>
* @link https://codingtipi.com/project/vanapay
* @since 1.0.0
*/
function vana_register_order_approval_payment_method_type() {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
      return;
    }

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
          $payment_method_registry->register( new WC_Vana_Blocks );
        }
    );
}
add_action( 'woocommerce_blocks_loaded', 'vana_register_order_approval_payment_method_type' );

/**
* Añade el ícono de tarjetas aceptadas a la pasarela de pago
* 
* @author Luis E. Mendoza <lmendoza@codingtipi.com>
* @link https://codingtipi.com/project/vanapay
* @since 1.0.0
*/
function filter_vana_woocommerce_gateway_icon( $icon, $this_id ) {	
	if($this_id == "vana_pay") {
		$icon = "<img style='max-width: 100px;' src='".plugins_url('assets/vanapay.svg', __FILE__)."' alt='Vana Logo' />";
	}
	return $icon;

}
add_filter( 'woocommerce_gateway_icon', 'filter_vana_woocommerce_gateway_icon', 10, 2 );

/**
* Cambia el mensaje de confirmación dentro de WooCommerce
* 
* @author Luis E. Mendoza <lmendoza@codingtipi.com>
* @link https://codingtipi.com/project/vanapay
* @since 1.0.0
*/
function woo_vana_change_order_received_text( $str, $order ) {
  $customer_order = wc_get_order( $order );
  return sprintf( "Gracias, %s!", esc_html( $customer_order->get_billing_first_name() ) );
}
add_filter('woocommerce_thankyou_order_received_text', 'woo_vana_change_order_received_text', 10, 2 );

/**
* Agrega el widget de Vana
* 
* @author Luis E. Mendoza <lmendoza@codingtipi.com>
* @link https://codingtipi.com/project/recurrente
* @since 1.2.0
*/
function vana_add_widget() {
  global $product;
  if (!is_product() || !is_a($product, 'WC_Product')) {
    return;
  }

  $sku = $product->get_sku();
  if (empty($sku)) {
      $sku = 'PRO-1234'; // Replace with your actual fallback SKU
  }
  if ($product->is_type('variable')) {
      $price = $product->get_variation_price('min', true); // true = including tax
  } else {
      $price = $product->get_price(); // Returns the raw price
  }
  // Optionally format it:
  $formatted_price = wc_price($price);

  $gateways = WC()->payment_gateways->get_available_payment_gateways();

  if (isset($gateways['vana_pay'])) {
    $gateway = $gateways['vana_pay'];
    $merchant_id = $gateway->get_option('merchant_id'); 
    
    ?>
        <div id="vana-financing-info">
          <script type="text/javascript">
            window.VanaPayRender = function({ merchantId: n, productPrice: e, sku: t, devMode: a, containerSelector: c }) {
              const o = `https://api.pay.vana.gt/v1/product/snippet`;
                    
              document.addEventListener("DOMContentLoaded", function() {
                  const container = c ? document.querySelector(c) : null;
                  if (n && e && t) {
                      fetch(`${o}/${n}/${e}`)
                          .then(res => res.json())
                          .then(res => {
                              if (res && res.data.html) {
                                  if (container) {
                                      container.innerHTML = res.data.html;
                                  } else {
                                      // Fallback: insert after the current script
                                      const script = document.currentScript || [...document.getElementsByTagName("script")].pop();
                                      script && script.insertAdjacentHTML("afterend", res.data.html);
                                  }
                              }
                          })
                          .catch(() => console.error("VanaPay: Error loading financing info."));
                  } else {
                      console.warn("VanaPay: Missing required parameters.");
                  }
              });
          };

          VanaPayRender({
              merchantId: "<?php echo $merchant_id ?>",
              productPrice: <?php echo esc_js($price); ?>,
              sku: "<?php echo esc_js($sku); ?>",
              devMode: true,
              containerSelector: "#vana-financing-info"
          });

          </script>
        </div>
        
        <?php
  } 

}
add_action('woocommerce_single_product_summary', 'vana_add_widget', 11);
