<?php namespace Sedehi\Searchable;

use Illuminate\Support\ServiceProvider;

class SearchableServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */

    public function boot()
    {
        $this->publishes([__DIR__.'/../../config/searchable.php' => config_path('searchable.php')]);

    }


    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/searchable.php', 'searchable');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
    }

}
