A very early version of a CMS. Not intended for public or general use.

You'll need to add this to the 'providers' array in config/app.php:

`Sevenpointsix\Ctrl\CtrlServiceProvider::class`

In order to run this as a package (while developing), you'll also need to update the main `composer.json` file in the document root:

`
"require": {
    "php": ">=5.5.9",
    "laravel/framework": "5.2.*",
    "yajra/laravel-datatables-oracle": "~6.0"
},
[...]
"autoload": {
    "classmap": [
        "database"
    ],
    "psr-4": {
        "App\\": "app/",
        "Sevenpointsix\\Ctrl\\": "packages/sevenpointsix/ctrl/src"
    }
},
`
