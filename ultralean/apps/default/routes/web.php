<?php

use UltraLean\Core\Router;

Router::get('/', 'HomeController@index');
Router::get('/about', 'HomeController@about');
Router::get('/contact', 'HomeController@contact');
Router::get('/admin/home', 'Admin\HomeControllers@index', ['AdminAuth']);
Router::get('/admin/about', 'Admin\HomeController@about');
Router::get('/admin/login', 'HomeController@login');
Router::get('/admin/logout', 'HomeController@logout');

Router::group(['prefix' => '/admin', 'middleware' => ['AdminAuth']], function () {
    Router::get('/', 'Admin\DashboardController@index');
    Router::get('/users', 'Admin\UserController@index');
    Router::get('/users/create', 'Admin\UserController@create');

    Router::get('/users/{id}', 'Admin\UserController@show');
    Router::get('/users/{id}/delete', 'Admin\UserController@delete');
});
