<?php

namespace App\Controllers;

class HomeController extends BaseController
{
    public function index()
    {
        $name = $this->input('name', 'Guest');

        $this->view('home', [
            'name' => $name
        ]);
    }

    public function login()
    {
        session('admin_logged_in', true);
        redirect('/admin/home', 302);
    }

    public function logout()
    {
        session('admin_logged_in', false);
        redirect('/admin/login', 302);
    }

    public function api()
    {
        $this->json([
            'status' => 'ok'
        ]);
    }
}
