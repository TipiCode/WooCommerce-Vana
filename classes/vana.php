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
      if ( is_admin() ) {
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
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

}