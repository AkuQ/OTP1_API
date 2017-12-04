<?php
use StormChat\API_Handler;
use StormChat\Controller;
use StormChat\DB_Handler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RequestContext;

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/src/autoload.php';


$app = new Silex\Application();


# SERVICES:

$app['api_handler'] = function () {
    return new API_Handler();
};

$app['controller'] = function () {
    return new Controller(new DB_Handler(parse_ini_file(__DIR__.'/config/db.ini')));
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

//$app->post('/users/auth', function (Request $request) use ($app) {
//    $params = $request->request->all();
//    return json_encode(['result' => 1]);
//});

$app->post('/rooms/auth', function (Request $request) use ($app) {
    $params = $request->request->all();
    if($params['token'] == 'fail')
        return json_encode(['result' => 0]);
    return json_encode(['result' => 1]);

//    return $app['api_handler']->respond(
//        $request,
//        [$app['controller'], 'is_user_in_room']
//    );
});


$app->post('/get_time', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        function () { return date('Y-m-d H:i:s'); }
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

$app->post('/rooms/join', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'join_room']
    );
});

$app->post('/rooms/leave', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'leave_room']
    );
});

$app->post('/users/list', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'list_users']
    );
});

$app->post('/messages/list', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'list_messages']
    );
});

$app->post('/messages/post', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'post_message']
    );
});

$app->post('/workspaces/content', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'get_workspace_content']
    );
});

$app->post('/workspaces/updates', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'get_workspace_updates']
    );
});

$app->post('/workspaces/insert', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'workspace_insert']
    );
});

$app->post('/workspaces/remove', function (Request $request) use ($app) {
    return $app['api_handler']->respond(
        $request,
        [$app['controller'], 'workspace_remove']
    );
});


$app->run();

return $app;