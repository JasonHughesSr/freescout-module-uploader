<?php

namespace Modules\ModuleUploader\Providers;

use Illuminate\Support\ServiceProvider;

class ModuleUploaderServiceProvider extends ServiceProvider
{
    /**
     * @var string
     */
    protected $moduleName = 'ModuleUploader';

    /**
     * @var string
     */
    protected $moduleNameLower = 'moduleuploader';

    /**
     * Boot the application events.
     */
    public function boot()
    {
        $this->registerViews();
        $this->overrideSidebarMenu();
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        //
    }

    /**
     * Register views.
     */
    public function registerViews()
    {
        $viewPath = __DIR__.'/../Resources/views';

        $this->loadViewsFrom($viewPath, $this->moduleNameLower);
    }

    /**
     * Add the "Upload Module" link to the core Modules page sidebar.
     *
     * Core has no filter/hook on that partial, so this makes our own copy
     * of modules/sidebar_menu.blade.php win by putting its folder ahead of
     * the app's default view path. If core ever changes that sidebar, this
     * override needs updating to match (see the file itself).
     */
    protected function overrideSidebarMenu()
    {
        $this->app['view']->getFinder()->prependLocation(__DIR__.'/../Resources/views/overrides');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
