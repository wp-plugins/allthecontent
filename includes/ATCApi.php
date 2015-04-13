<?php
/*
 * ATCApi
 * ATC Api Helper
 * @autor AllTheContent, Vincent Buzzano
 */
if (!class_exists('ATCApi')) { class ATCApi {

    public $curl_exists = false;

    public function __construct() {
        $this->curl_exists = function_exists('curl_init');

    }
}}

$api = new ATCApi();

echo $api->curl_exists;
?>
