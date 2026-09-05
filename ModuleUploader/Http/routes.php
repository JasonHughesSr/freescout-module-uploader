<?php

// There is a core /modules/list route already, so this module uses its own
// path and simply hands off to it once a module has been extracted.
Route::group([
    'middleware' => 'web',
    'prefix'     => \Helper::getSubdirectory(),
    'namespace'  => 'Modules\ModuleUploader\Http\Controllers',
], function () {
    Route::get('/modules/upload', [
        'uses'       => 'ModuleUploaderController@index',
        'middleware' => ['auth', 'roles'],
        'roles'      => ['admin'],
    ])->name('moduleuploader');

    Route::post('/modules/upload', [
        'uses'       => 'ModuleUploaderController@upload',
        'middleware' => ['auth', 'roles'],
        'roles'      => ['admin'],
    ])->name('moduleuploader.upload');
});
