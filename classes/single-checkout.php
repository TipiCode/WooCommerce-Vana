<?php
/**
* Clase para interactuar con un Checkou de Cobro único dentro de Vana
*
* Objeto principal para interactuar con un checkout de Cobro único dentro de vana.
*
* @copyright  2024 - tipi(code)
* @since      2.0.1
*/ 
class Single_Checkout {
    private $gateway;
    private $customer_order;
    public $id;
    public $url;
    public $client_token;

    /**
    * Constructor
    *
    * @param WC_Order  $customer_order  Orden de WooCommerce para procesar los datos del producto.
    * 
    */ 
    function __construct($customer_order) {
        $this->gateway = VanaPay::get_instance();
        $this->customer_order = $customer_order;
    }

    /**
    * Crea un nuevo Checkout de cobro único
    * 
    * @throws Exception Si la llamada a vana falla
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @return string HTTP Response Code de la llamada
    * @link https://codingtipi.com/project/vanapay
    * @since 2.0.0
    */
    public function create(){
        try {
            $token = get_option('vana_api_token');

            $url = 'https://aurora.codingtipi.com/pay/v2/vana/checkouts/hosted/single';
            $curl = new VanaCurl(
                $token
            );// Inicializar Curl
            $checkout = $this->get_api_model();
            $response = $curl->execute_post($url, $checkout);
            $curl->terminate();

            $this->code = $response['code'];
            if($this->code == 201){
                $this->id = $response['body']->id;
                $this->url = $response['body']->url;
                $this->client_token = $response['body']->metadata->clientToken;
            } else {
                VanaSupport::log_error('55', 'single-checkout.php', 'Ocurrio un creando la instancia del checkout.', print_r($response['body'], true));
                return $response['body']->message;
            }
        } catch (Exception $e) {
            VanaSupport::log_error('59', 'single-checkout.php', 'Ocurrio un error creando la instancia del checkout.', $e->getMessage());
            return new WP_Error('error', $e->getMessage());
        }
    }

    /**
    * Obtiene el modelo de un checkout para poder interactual con el API de vana
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @return Array Objeto para usar con el API de Vana
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */ 
    private function get_api_model(){

        return Array(
                "number"  => $this->customer_order->get_order_number(),
                "description"  => "Orden número ".$this->customer_order->get_order_number().'. al finalizar tu pago seras redirigido de vuelta al comerció para procesar tu orden.',
                "amount" => $this->customer_order->get_total(),
                "currency"  => $this->customer_order->get_currency(),
                "redirection" => Array(
                    "successUrl" => get_site_url().'?wc-api=vana_pay&status=1&order='. $this->customer_order->get_order_number()
                ),
                "order" => $this->get_order_items_custom_data(),
                "billing" => Array(
                    "name" => $this->customer_order->get_billing_first_name(),
                    "surname" => $this->customer_order->get_billing_last_name(),
                    "email" => $this->customer_order->get_billing_email(),
                    "phone" => $this->customer_order->get_billing_phone()
                )
        );
    }

    /**
    * Obtiene el modelo de un order_items para poder interactual con el API de vana
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @return Array Objeto para usar con el API de Vana
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */ 
    private function get_order_items_custom_data( ) {
        $items = $this->customer_order->get_items();
        $product_data_array = Array();

        foreach ( $items as $item ) {
            $product = $item->get_product();
        
            $product_object = (object) Array(
                "reference"       => (string) $product->get_id(),
                "name"     => $product->get_name(),
                "price"    => $product->get_price(),
                "qty" => $item->get_quantity()
            );
        
            $product_data_array[] = $product_object;
        }
        return $product_data_array;
    }
}