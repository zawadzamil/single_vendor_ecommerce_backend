<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        //

       if(!defined('ALLOWED_COLORS'))  define('ALLOWED_COLORS', ['red', 'green', 'blue', 'white', 'purple','black', 'orange','gray', 'yellow','navy','brown','cream','maroon','pink','purple']);

        if(!defined('ALLOWED_SIZES')) define('ALLOWED_SIZES',['XS','S','M','L','XL','XXL','XXXL']);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
