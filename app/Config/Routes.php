<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'MapController::index');

// API Arrêts
$routes->get('api/arrets', 'Api\ArretController::getAll');
$routes->post('api/arrets', 'Api\ArretController::create');
$routes->put('api/arrets/(:num)', 'Api\ArretController::update/$1');
$routes->delete('api/arrets/(:num)', 'Api\ArretController::delete/$1');

// API Bus
$routes->get('api/bus', 'Api\BusController::index');
$routes->post('api/bus', 'Api\BusController::create');
$routes->put('api/bus/(:num)', 'Api\BusController::update/$1');
$routes->delete('api/bus/(:num)', 'Api\BusController::delete/$1');

// API Trajets
$routes->get('api/trajets', 'Api\TrajetController::index');
$routes->get('api/trajets/(:num)', 'Api\TrajetController::get/$1');
$routes->post('api/trajets', 'Api\TrajetController::create');
$routes->put('api/trajets/(:num)', 'Api\TrajetController::update/$1');
$routes->delete('api/trajets/(:num)', 'Api\TrajetController::delete/$1');

// API Recherche
$routes->post('api/search', 'Api\SearchController::search');
