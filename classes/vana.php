<?php
/**
* Clase principal para interactuar con el API de Vana
*
* Esta clase es la principal para interactuar con la pasarela de pagos.
*
* @copyright  2025 - tipi(code)
* @since      1.2.0
*/ 
class VanaPay extends WC_Payment_Gateway {
    public $client_id;
    public $client_secret;
    public $merchant_id;
    public $order_status;
    private static $instance;

    /**
    * Constructor
    */ 
    function __construct() {
        // Id global
        $this->id = "vana_pay";
        // titulo a mostrar
        $this->method_title = __( "VanaPay", 'vana_pay' );
        // Descripcion a mostrar
        $this->method_description = __( "Plugin de VanaPay para WooCommerce", 'vana_pay' );
        // Seccion de tabs verticales
        $this->title = __( "VanaPay", 'vana_pay' );
        $this->icon = $this->get_option('icon');
        $this->has_fields = false;
        $this->description = "<img src".$this->icon."/>";

        // Define los campos a utilizar en el formulario de configuración
        $this->init_form_fields();
        // Carga de Variables
        $this->init_settings();
        // Se agregan las acciónes a los plugins
        $this->init_actions();
        
        // Proceso para convertir las configuraciones a variables.
        foreach ( $this->settings as $setting_key => $value ) {
          $this->$setting_key = $value;
        }
    }

    /**
    * Función para patron de singleton
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @return VanaPay Clase inicializada
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */ 
    public static function get_instance() {
      if (!isset(self::$instance)) {
        self::$instance = new self();
      }
      return self::$instance;
    }

    /**
    * Función que inicializa las acciones
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */ 
    public function init_actions(){
      add_action( 'admin_notices', array( $this,  'validate_activation' ) );
      add_action('woocommerce_api_vana_pay', array($this, 'redirect_callback'));
      if ( is_admin() && !has_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' )) ) {
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));
      }
    }

    /**
    * Función encargada del manejo del token al momento de guardar los settings
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */ 
    function process_admin_options() {
      parent::process_admin_options();

      $client_id = $this->get_option('client_id');
      $client_secret = $this->get_option('client_secret');

      try {
          $url = 'https://aurora.codingtipi.com/pay/v2/vana/setup';
          $body = array(
              'clientId' => $client_id,
              'clientSecret' => $client_secret
          );
          $curl = new VanaCurl();
          $response = $curl->execute_post($url, $body);
          $curl->terminate();
          if($response['code'] != 200){
              VanaSupport::log_error('98', 'vana.php', 'Ocurrio un error obteniendo el Token para uso del API.', print_r($response['body'], true));
          }else{
              update_option('vana_api_token', $response['body']->token);
              return $response['body']->token;
          }
      } catch (Exception $e) {
          VanaSupport::log_error('105', 'vana.php', 'Ocurrio un error obteniendo el Token para uso del API.', $e->getMessage());
      }
    }

    /**
    * Función encargada de inicializar el formulario de configuración del pugin
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @since 1.2.0
    */
    public function init_form_fields() {
      include_once dirname(__FILE__) . '/../includes/vana-settings.php';
      $this->form_fields = VanaPaySettings::get_settings();
    }

    /**
    * Función encargada del manejo de Callbacks por parte de la pasarela de pago
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */
    public function redirect_callback(){
      if (isset($_GET["status"])) {
        $this->answer_redirect(); //Esto quiere decir que es el redirect URL del checkout
      }else{
        $this->process_webhook(); //Esto quiere decir que es el Webhook
      }
    }

    /**
    * Función encargada del manejo de la redirección
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */
    public function answer_redirect(){
      $status_id = $_GET["status"];
      $order_id = $_GET["order"];
      $order = wc_get_order( $order_id );

      if($status_id == 1){ //El pago fue exitoso
        $redirect_url = $order->get_checkout_order_received_url();
        $order->add_order_note( 'VanaPay: '.'La transacción fue completada por el usuario.' );
        wp_safe_redirect($redirect_url);
      }
    }

    /**
    * Función encargada de procesar las respuesta del WebHook.
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @since 1.1.0
    */
    public function process_webhook(){
      try {
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData); //Convertir de JSON a objeto
        
        include_once dirname(__FILE__) . '/../includes/vana-response.php';
        $response = new VanaResponse($data->data->status);
        $response->execute($data->data);
      } catch (Exception $e) {
        VanaSupport::log_error('171', 'vana.php', 'Ocurrio un error procesando el webhook.', $e->getMessage());
      }
    }

    /**
    * Función encargada de procesar el pago de WooCommerce
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @return Array Arreglo que contiene el resultado del proceso de la transacción y el URL para redirigir
    * @since 1.0.0
    */
    public function process_payment($order_id){
      global $woocommerce;
      $customer_order = new WC_Order( $order_id );
      $single_checkout = new Single_Checkout($customer_order);
      $checkout_transaction = $single_checkout->create(); 

      if ( is_wp_error( $checkout_transaction ) ) //Valida por error en la llamada del API
        $this->fail($checkout_transaction);
      if ( $single_checkout->code != 201 ) //Valida el return del status code 
        $this->fail($checkout_transaction);

      $customer_order->add_order_note( 'VanaPay: '.'Se inicializó el proceso de pago.' ); //Actualizar los comentarios 
      $customer_order->update_meta_data( 'vana_checkout_id', $single_checkout->id ); //Agregar el Id del checkout en la orden.
      $customer_order->update_meta_data( 'vana_checkout_url', $single_checkout->url ); //Agregar el URL del checkout en la orden.
      $customer_order->update_meta_data( 'vana_client_token', $single_checkout->client_token ); //Agregar el Token del cliente

      $customer_order->save();
      return array(
        'result'   => 'success',
        'redirect' => $single_checkout->url,
      );
    }

    /**
    * Función encargada de validar los campos de la configuración.
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @since 1.2.0
    */
    public function validate_fields() {
      return true;
    }

    /**
    * Función encargada de mostrar el mensaje de error al usuario.
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/recurrente
    * @since 1.2.0
    */
    public function fail($message){
      throw new Exception( __( $message, 'vana_pay' ) );
    }

    /**
    * Función encargada de validar la correcta activación del plugin.
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */
    public function validate_activation(){
      if( $this->enabled == "yes" ) {
        if( empty( $this->client_id ) || empty( $this->client_secret  ) ) {
          echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> No tienes configurado correctamente el plugin, <a href=\"%s\">porfavor dirigete a la configuracion.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout&section=recurrente' ) ) ."</p></div>";  
        }
      }   
    }
}