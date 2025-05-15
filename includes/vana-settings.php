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
}
