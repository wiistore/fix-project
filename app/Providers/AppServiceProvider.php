<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Pagination tetap pakai query string ?page= seperti native lama
        Paginator::useBootstrap();

        // Sharing global ke semua view: $currentUser dan $currentRole
        View::composer('*', function ($view) {
            $authUser = auth()->user();

            $userArray = $authUser ? [
                'id' => $authUser->id,
                'username' => $authUser->username,
                'nama' => $authUser->username,
                'email' => $authUser->email,
                'role' => $authUser->role,
                'is_protected' => $authUser->is_protected,
                'status' => $authUser->status,
            ] : null;

            $view->with('currentUser', $userArray);
            $view->with('currentRole', $authUser?->role ?? 'guest');
        });
    }
}
