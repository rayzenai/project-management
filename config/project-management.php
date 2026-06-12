<?php

use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | User model
    |--------------------------------------------------------------------------
    |
    | The host application's authenticatable model. Used by all package
    | relationships that belong to a user (assignments, notes, contacts,
    | subtasks, activities). Override in the host app's config if it lives
    | somewhere other than App\Models\User.
    |
    */

    'user_model' => User::class,

    /*
    |--------------------------------------------------------------------------
    | Route middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the workspace route group. The host application is
    | responsible for the listed aliases. By default only 'web' and 'auth' are
    | applied; add your own gate (e.g. 'workspace.access') here if you need to
    | restrict the workspace to a subset of authenticated users.
    |
    */

    'middleware' => ['web', 'auth'],

];
