<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'MapController::index');

// API Arrêts
$routes->get('api/arrets', 'Api\ArretController::getAll');
$routes->post('api/arrets', 'Api\ArretController::create');

// API Bus
$routes->get('api/bus', 'Api\BusController::index');
$routes->post('api/bus', 'Api\BusController::create');

// API Trajets
$routes->get('api/trajets', 'Api\TrajetController::index');
$routes->get('api/trajets/(:num)', 'Api\TrajetController::get/$1');
$routes->post('api/trajets', 'Api\TrajetController::create');
