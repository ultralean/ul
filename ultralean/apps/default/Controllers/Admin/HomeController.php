<?php

namespace App\Controllers\Admin;

use UltraLean\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        echo "UltraLean Admin Home Page";
    }

    public function about()
    {
        echo "UltraLean Admin About Page";
    }
}
