<?php

namespace NovaItemsField;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\ServiceProvider;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Nova::serving(function (ServingNova $event) {
            $mixManifest = json_decode(file_get_contents(__DIR__ . '/../dist/mix-manifest.json'), true);
            $script = explode('=', $mixManifest['/js/field.js'])[1];

            Nova::script('nova-items-field-' . $script, __DIR__.'/../dist/js/field.js');
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
