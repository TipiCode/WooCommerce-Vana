<?php
/**
* Clase para obtener la configuración de VanaPay
*
* Clase encargada de obtener el arreglo que define los campos a utilizar dentro de la configuración del plugin.
*
* @copyright  2025 - tipi(code)
* @since      1.0.0
*/ 
class VanaPaySettings 
{
    private $curl;
    /**
    * Obtiene el arreglo de configuraciones
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @return Array  Arreglo de campos para la vista de configuración
    * @since 1.0.0
    */ 
    public static function get_settings(){
        return array(
            'enabled' => array(
              'title'    => __( 'Activar  / Desactivar', 'vana_pay' ),
              'label'    => __( 'Activa la pasarela de pago', 'vana_pay' ),
              'type'    => 'checkbox',
              'default'  => 'no',
            ),
            'title' => array(
              'title'    => __( 'Título', 'vana_pay' ),
              'type'    => 'text',
              'desc_tip'  => __( 'Titulo a mostrar en el checkout.', 'vana_pay' ),
              'default'  => __( 'Paga con VanaPay', 'vana_pay' ),
            ),
            'description' => array(
              'title'    => __( 'Descripcion', 'vana_pay' ),
              'type'    => 'text',
              'desc_tip'  => __( 'Descripcion a mostrar en el checkout.', 'vana_pay' ),
              'default'  => __( 'Procesa tu pago a travez de VanaPay', 'vana_pay' )
            ),
            'client_id' => array(
              'title'    => __( 'Client Id', 'vana_pay' ),
              'type'    => 'text',
              'desc_tip'  => __( 'Este Id te lo proporcionara VanaPay.', 'vana_pay' ),
            ),
            'client_secret' => array(
              'title'    => __( 'Client Secret', 'vana_pay' ),
              'type'    => 'text',
              'desc_tip'  => __( 'Esta llave te la proporcionará VanaPay.', 'vana_pay' ),
            ),
            'merchant_id' => array(
              'title'    => __( 'Merchant Id', 'vana_pay' ),
              'type'    => 'text',
              'desc_tip'  => __( 'Este Id te lo proporcionara VanaPay.', 'vana_pay' ),
            ),
            'order_status' => array(
                'title'       => __( 'Estado Predeterminado de la orden', 'vana_pay' ),
                'type'        => 'select',
                'description' => __( 'Selecciona el estado predeterminado para las órdenes procesadas.', 'vana_pay' ),
                'options'     => array( 
                    'wc-completed'  => __( 'Completada', 'vana_pay' ),
                    'wc-on-hold'    => __( 'En espera', 'vana_pay' ),
                    'wc-processing'  => __( 'Procesando', 'vana_pay' ),
                    'wc-draft'  => __( 'Draft', 'vana_pay' ),
                ),
                'default'     => 'wc-completed',
            ),
        );    
    }

    /**
    * Obtiene la instancia de Curl
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @since 1.0.0
    */ 
    private function get_curl() {
        if ($this->curl === null) {
            $token = get_option('vana_api_token');
            $this->curl = new VanaCurl($token);
        }
        return $this->curl;
    }

    /**
    * Obtiene el token de autenticación
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @since 1.0.0
    */ 
    public static function obtain_token($client_id, $client_secret, $merchant_id) {
        if (empty($client_id) || empty($client_secret) || empty($merchant_id)) {
            return new WP_Error('invalid_credentials', 'Las credenciales de VanaPay son inválidas');
        }

        try {
            $url = 'https://aurora.codingtipi.com/pay/v2/vana/setup';
            $body = array(
                'clientId' => $client_id,
                'clientSecret' => $client_secret
            );
            $curl = $this->get_curl();
            $response = $curl->execute_post($url, $checkout);
            if($response['code'] != 200){
                Support::log_error('105', 'vana-settings.php', 'Ocurrio un error obteniendo el Token para uso del API.', print_r($response['body'], true));
                return new WP_Error('invalid_token', 'El token es invalido');
            }else{
                return $response['body']->token;
            }
        } catch (Exception $e) {
            Support::log_error('107', 'vana-settings.php', 'Ocurrio un error obteniendo el Token para uso del API.', $e->getMessage());
        }
    }

    /**
    * Inicializa los settings
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @since 1.0.0
    */
    public static function init_actions() {
        add_action('update_option_woocommerce_vana_settings', function($old_value, $value, $option) {
            
            if (isset($value['client_id']) && isset($value['client_secret']) && isset($value['merchant_id'])) {
                // Guardar las credenciales en ambas opciones para compatibilidad
                update_option('vana_settings', array(
                    'client_id' => $value['client_id'],
                    'client_secret' => $value['client_secret'],
                    'merchant_id' => $value['merchant_id']
                ));
                
                VanaPaySettings::obtain_token($value['client_id'], $value['client_secret'], $value['merchant_id']);
            }
        }, 10, 3);
        
        // Verificar si las credenciales existen al cargar el plugin
        add_action('plugins_loaded', function() {
            $woocommerce_settings = get_option('woocommerce_vana_settings');
            $vana_settings = get_option('vana_settings');

            // Si no hay configuración en vana_settings pero sí en woocommerce_vana_settings
            if (empty($vana_settings) && !empty($woocommerce_settings)) {
                if (isset($woocommerce_settings['client_id']) && isset($woocommerce_settings['client_secret']) && isset($woocommerce_settings['merchant_id'])) {
                    update_option('vana_settings', array(
                        'client_id' => $woocommerce_settings['public_key'],
                        'client_secret' => $woocommerce_settings['secret_key'],
                        'merchant_id' => $woocommerce_settings['merchant_id']
                    ));
                }
            }
        });
    }
}

VanaPaySettings::init_actions();