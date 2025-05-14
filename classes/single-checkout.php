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
    private $curl;
    public $id;
    public $url;

    /**
    * Constructor
    *
    * @param WC_Order  $customer_order  Orden de WooCommerce para procesar los datos del producto.
    * 
    */ 
    function __construct($customer_order) {
        $this->gateway = VanaPay::get_instance();
        $this->customer_order = $customer_order;
        $this->curl = null;
    }

    /**
    * Obtiene una instancia de Curl
    */
    private function get_curl() {
        if ($this->curl === null) {
            $token = get_option('vana_api_token');
            $this->curl = new VanaCurl($token);
        }
        return $this->curl;
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
            $url = 'https://aurora.codingtipi.com/pay/v2/vana/checkouts/hosted/single';
            $curl = $this->get_curl();
            $checkout = $this->get_api_model();
            $response = $curl->execute_post($url, $checkout);
            
            $this->code = $response['code'];
            if($this->code == 201){
                $this->id = $response['body']->id;
                $this->url = $response['body']->url;
                return true;
            } else {
                return $response['body']->message;
            }
        } catch (Exception $e) {
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
                    "successUrl" => get_site_url().'?wc-api=vana&status=1&order='. $this->customer_order->get_order_number()
                ),
                "options" => get_order_items_custom_data(),
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
                "id"       => $product->get_id(),
                "name"     => $product->get_name(),
                "price"    => $product->get_price(),
                "quantity" => $item->get_quantity()
            );
        
            $product_data_array[] = $product_object;
        }
        return $product_data_array;
    }
}