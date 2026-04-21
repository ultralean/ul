<?php

use UltraLean\Core\Router;

Router::get('/', 'HomeController@index');
Router::get('/about', 'HomeController@about');
Router::get('/contact', 'HomeController@contact');
Router::get('/admin/home', 'Admin\HomeController@index', ['AdminAuth']);
Router::get('/admin/about', 'Admin\HomeController@about');
Router::get('/admin/login', 'HomeController@login');
Router::get('/admin/logout', 'HomeController@logout');
Router::get('/admin2', 'Admin\HomeController@index', ['AdminAuth', 'throttle:6,60']);

Router::group(['prefix' => '/admin', 'middleware' => ['AdminAuth']], function () {
    Router::get('/', 'Admin\HomeController@index');
    Router::get('/users', 'Admin\HomeController@about');
    Router::get('/users/create', 'Admin\HomeController@contact');

    Router::get('/users/{id}', 'Admin\HomeController@index');
    Router::get('/users/{id}/delete', 'Admin\HomeController@about');
});
