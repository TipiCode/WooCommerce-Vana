<?php
/**
* Clase encargada del manejo de las respuestas del Webhook de VanaPay
*
* Contiene una serie de validaciónes para todos los esenarios propuestos por el plugin de vana.
*
* @copyright  2025 - tipi(code)
* @since 1.0.0
*/ 

class VanaResponse 
{
    public $status;
    public $settings;

    /**
    * Constructor
    *
    * @param string   $intent  Representa el estado de la transacción
    * 
    */ 
    function __construct($status) {
        $this->status = $status;
        $this->settings = get_option( 'vana_settings', [] );
    }

    /**
    * Ejecuta la respuesta y procesa la orden según el resultado del evento del WebHook
    * 
    * @param Object   $data  Objeto que contiene la respuesta del webhook de Vana.
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @since 1.1.0
    */ 
    public function execute($data){
        if($this->status === 'confirmed'){
            $this->payment_succeeded($data);
        }
        else{
            $this->payment_failed($data);
        }
    }

    /**
    * Procesa el resultado fallido del intento de pago con tarjeta de crédito o débito
    * 
    * @param Object   $data  Objeto que contiene la respuesta del webhook de Vana.
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @return string HTTP Response Code de la llamada
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */  
    private function payment_failed($data){
        //PROCESAR FAIL
    }

    /**
    * Procesa el resultado satisfactorio del intento de pago con tarjeta de crédito o débito
    * 
    * @param Object   $data  Objeto que contiene la respuesta del webhook de Vana.
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @return string HTTP Response Code de la llamada
    * @link https://codingtipi.com/project/vanapay
    * @since 1.2.0
    */ 
    private function payment_succeeded($data){
        $checkout_id = $data->id;
        $success_message = 'Se completo correctamente el pago con tarjeta.';

        $order_status = isset($this->settings['order_status']) ? $this->settings['order_status'] : 'wc-completed';

        $this->process_order($checkout_id, $order_status, 'VanaPay: '.$success_message);
    }

    /**
    * Procesa el estado de la orden dentro de WooCommerce
    * 
    * @param string   $checkout_id  Id del checkout de VanaPay.
    * @param string   $status  Estado al cual se cambiara al pedido.
    * @param string   $note  Nota que se le sera agregada al pedido.
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @return string HTTP Response Code de la llamada
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */
    private function process_order($checkout_id, $status, $note){
        $args = array(
            'meta_key'      => 'vana_checkout_id', 
            'meta_value'    => $checkout_id, 
            'return'        => 'objects' 
        );
        $orders = wc_get_orders( $args );
        $order = $orders[0];
        $order->add_order_note( $note );
        $order->update_status( $status );
    }
}