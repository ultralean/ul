<?php

namespace App\Controllers;

use UltraLean\Core\Controller;
use UltraLean\Core\Session;

class BaseController extends Controller
{
    protected $session;
    public function __construct()
    {
        parent::__construct();
        $this->session = new Session();

        // Initialize shared resources here (if needed in future)
    }

    // Common methods for controllers can be added here
}
