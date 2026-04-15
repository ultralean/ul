<?php

namespace App\Middleware;

class AdminAuth
{
    public function handle(): bool
    {
        if (!session('admin_logged_in')) {
            redirect('/admin/login', 302);
            exit;
        }

        return true;
    }
}
