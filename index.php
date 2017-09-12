<?php
use StormChat\API_Handler;
use StormChat\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/autoload.php';


$app = new Silex\Application();

# SERVICES:

$app['api_handler'] = function () {
    return new API_Handler();
};

$app['controller'] = function () {
    return new Controller();
};



# MIDDLEWARE:

$app->before(function (Request $request) use ($app){
    /** @var API_Handler $api_handler */
    $api_handler = $app['api_handler'];
    $api_handler->parse_request($request);
});

$app->after(function (Request $request, Response $response) use ($app){
    $response->setCharset('UTF-8');
});


# ROUTES:

$app->get('/', function () {
   return "StormChat API";
});

$app->post('/', function () {
    return "StormChat API";
});

$app->get('/get_time', function (Request $request) use ($app) {
    return date('Y-m-d H:i:s');
});

$app->post('/get_time', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'get_time']
    );
});

$app->post('/users/create', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'create_user']
    );
});

$app->post('/rooms/list', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'list_rooms']
    );
});

$app->post('/rooms/create', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'create_room']
    );
});


$app->run();