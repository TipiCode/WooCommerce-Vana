<?php
/**
* Clase para loggear errores
*
* @copyright  2024 - tipi(code)
* @since      1.0.0
*/ 
class Support {
    /**
    * Envia el error al API de Aurora
    * 
    * @author Luis E. Mendoza <lmendoza@codingtipi.com>
    * @since 1.0.0
    */ 
    public static function log_error($line, $file, $error, $exception){
        $body = [
            "Line" => $line,
            "File" => $file,
            "FriendlyMsg" => $error,
            "exception" => $exception,
            "url" => "oxexpeditions.com",
            "version" => PLUGIN_VERSION,
        ];

        $ch = curl_init();

        $completeUrl = 'https://aurora.codingtipi.com/support/v1/issues';
        curl_setopt($ch, CURLOPT_URL, $completeUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'accept: application/json',
            'Content-Type: application/json',
            'X-App-Id: ' . APP_ID
        ));
        curl_exec($ch);
    }
}