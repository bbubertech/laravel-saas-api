<?php

return [

    /*
    | Every permission the API checks. Gates are registered from this list.
    */

    'all' => [
        'customers.view',
        'customers.create',
        'customers.update',
        'customers.delete',
        'users.view',
        'users.manage',
        'audit.view',
        'imports.manage',
        'webhooks.manage',
        'billing.manage',
    ],

    /*
    | Roles created for every new company. "*" grants everything.
    */

    'default_roles' => [
        'owner' => [
            'name' => 'Owner',
            'permissions' => ['*'],
        ],
        'admin' => [
            'name' => 'Admin',
            'permissions' => [
                'customers.view', 'customers.create', 'customers.update', 'customers.delete',
                'users.view', 'users.manage', 'audit.view', 'imports.manage', 'webhooks.manage',
            ],
        ],
        'member' => [
            'name' => 'Member',
            'permissions' => ['customers.view', 'customers.create', 'customers.update'],
        ],
    ],

];
