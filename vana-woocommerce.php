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
}