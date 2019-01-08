<?php

$router = new \Phalcon\Mvc\Router(false);
$router->setUriSource(\Phalcon\Mvc\Router::URI_SOURCE_SERVER_REQUEST_URI);

$router->notFound(array('controller' => 'index', 'action' => 'route404'));

$router->add(
    '/workload/{month_id:(1|2|3|4|5|6|7|8|9|10|11|12)}/{year:[0-9]{4}}',
    [
        'controller'  => 'index',
        'action'      => 'workload'
    ]);

$router->add(
    '/sprint/{sprint_id:[0-9]+}',
    [
        'controller'  => 'index',
        'action'      => 'sprintInfo'
    ]);

$router->add(
    '/team/{year:[0-9]{4}}',
    [
        'controller'  => 'index',
        'action'      => 'teamStatistics'
    ]);

$router->add(
    '/',
    [
        'controller'  => 'index',
        'action'      => 'index'
    ]);

$router->add(
    '/generate/license',
    [
        'controller'  => 'utils',
        'action'      => 'generateLicense'
    ])
    ->via( [ 'GET', 'POST' ] );

return $router;