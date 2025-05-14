<?php
/**
* Clase para interactuar con CURL dentro de PHP
*
* Objeto utilizado para cada llamada del API
*
* @copyright  2025 - tipi(code)
* @since      1.0.0
*/ 
class VanaCurl{
    private $ch;
    private $token;

    /**
    * Constructor
    *
    * @param string $token  Token de autenticación que provee Recurrente.
    * 
    */
    function __construct($token) {
        $this->ch = curl_init();
        $this->header  = Array(
            'X-TOKEN:' . $token,
            'Content-type: application/json'
          );
        $this->token = $token;
    }

    /**
    * Procesa el metodo de POST
    * 
    * @param string   $url  Url donde se llevara acabo la llamada.
    * @param string   $body Objeto para colocar en el cuerpo del Request.
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @return Array Arreglo que contiene el código HTTP de la respuesta y el cuerpo de la respuesta.
    * @link https://codingtipi.com/project/vanapay
    * @since 1.0.0
    */
    function execute_post($url, $body){
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $this->header );
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($body));
        $response = curl_exec($this->ch);
        $response_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        
        if(curl_errno($this->ch)){
            $error = curl_error($this->ch);
            throw new Exception($error);
        }

        return Array(
            "code" => $response_code,
            "body" => json_decode($response)
        );
    }

    /**
    * Cierra la conexión de CURL
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @link https://codingtipi.com/project/vanapay
    * @since 1.2.0
    */
    function terminate(){
        curl_close($this->ch);
    }
}