<?php

/**
 * Created by PhpStorm.
 * User: wailovet
 * Date: 2017/5/6
 * Time: 17:00
 */
require_once(__DIR__ . "/Http.php");

class Proxy
{

    public static function httpGet($host, $path = "/", $ip = "127.0.0.1", $params = array(), $options = array())
    {
        $http = new Http();
        $options['headers'][] = "host:{$host}";
        $data = $http->get("http://{$ip}{$path}", $params, $options)->getRequest();
    }
}


//require_once __DIR__ . '/Workerman/Autoloader.php';
//use Workerman\Worker;
//
//$ws_worker = new Worker("websocket://0.0.0.0:2346");
//
//$ws_worker->onConnect = function ($connection) {
//    echo "New connection\n";
//};
//
//
//$ws_worker->onMessage = function ($connection, $data) {
//    // Send hello $data
//    $connection->send('hello ' . $data);
//};
//
//// Emitted when connection closed
//$ws_worker->onClose = function ($connection) {
//    echo "Connection closed\n";
//};
