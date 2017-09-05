<?php
require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/autoload.php';

$app = new Silex\Application();


$app->get('/', function () {
   return "StormChat API";
});

$app->post('/', function () {
    return "StormChat API";
});

$app->get('/get_time', function () use ($app) {
    return date('Y-m-d H:i:s');
});

$app->post('/get_time', function () use ($app) {
    return json_encode(['time' => date('Y-m-d H:i:s')]);
});




$app->run();