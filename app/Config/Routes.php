<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'MapController::index');
$routes->get('api/arrets', 'Api\ArretController::getAll');
