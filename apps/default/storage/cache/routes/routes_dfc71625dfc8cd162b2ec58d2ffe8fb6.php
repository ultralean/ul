<?php return array (
  0 => 
  array (
    'GET' => 
    array (
      '/' => 
      array (
        'handler' => 
        array (
          0 => 'HomeController',
          1 => 'index',
        ),
        'middleware' => 
        array (
        ),
      ),
      '/about' => 
      array (
        'handler' => 
        array (
          0 => 'HomeController',
          1 => 'about',
        ),
        'middleware' => 
        array (
        ),
      ),
      '/contact' => 
      array (
        'handler' => 
        array (
          0 => 'HomeController',
          1 => 'contact',
        ),
        'middleware' => 
        array (
        ),
      ),
      '/admin/home' => 
      array (
        'handler' => 
        array (
          0 => 'Admin\\HomeControllers',
          1 => 'index',
        ),
        'middleware' => 
        array (
          0 => 'AdminAuth',
        ),
      ),
      '/admin/about' => 
      array (
        'handler' => 
        array (
          0 => 'Admin\\HomeController',
          1 => 'about',
        ),
        'middleware' => 
        array (
        ),
      ),
      '/admin/login' => 
      array (
        'handler' => 
        array (
          0 => 'HomeController',
          1 => 'login',
        ),
        'middleware' => 
        array (
        ),
      ),
      '/admin/logout' => 
      array (
        'handler' => 
        array (
          0 => 'HomeController',
          1 => 'logout',
        ),
        'middleware' => 
        array (
        ),
      ),
      '/admin/' => 
      array (
        'handler' => 
        array (
          0 => 'Admin\\DashboardController',
          1 => 'index',
        ),
        'middleware' => 
        array (
          0 => 'AdminAuth',
        ),
      ),
      '/admin/users' => 
      array (
        'handler' => 
        array (
          0 => 'Admin\\UserController',
          1 => 'index',
        ),
        'middleware' => 
        array (
          0 => 'AdminAuth',
        ),
      ),
      '/admin/users/create' => 
      array (
        'handler' => 
        array (
          0 => 'Admin\\UserController',
          1 => 'create',
        ),
        'middleware' => 
        array (
          0 => 'AdminAuth',
        ),
      ),
    ),
  ),
  1 => 
  array (
    'GET' => 
    array (
      0 => 
      array (
        'regex' => '~^(?|/admin/users/([^/]+)|/admin/users/([^/]+)/delete())$~',
        'routeMap' => 
        array (
          2 => 
          array (
            0 => 
            array (
              'handler' => 
              array (
                0 => 'Admin\\UserController',
                1 => 'show',
              ),
              'middleware' => 
              array (
                0 => 'AdminAuth',
              ),
            ),
            1 => 
            array (
              'id' => 'id',
            ),
          ),
          3 => 
          array (
            0 => 
            array (
              'handler' => 
              array (
                0 => 'Admin\\UserController',
                1 => 'delete',
              ),
              'middleware' => 
              array (
                0 => 'AdminAuth',
              ),
            ),
            1 => 
            array (
              'id' => 'id',
            ),
          ),
        ),
      ),
    ),
  ),
);