<?php

/*
|--------------------------------------------------------------------------
| Register Namespaces And Routes
|--------------------------------------------------------------------------
|
| Executed automatically when the module boots (see module.json "files").
|
*/

if (!app()->routesAreCached()) {
    require __DIR__.'/Http/routes.php';
}
