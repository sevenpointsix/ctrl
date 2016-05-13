#Documentation for the ctrl-c.ms

This is very much WIP, just jotting down random notes for now.

##Migrations

These are the database migrations we'll use; they'll need moving to the correct vendor folder when we install this via Composer, and the artisan ctrl:init script will therefore have to look in different locations as well (depending on whether we're developing the package, or using the Composer module).

    php artisan make:migration create_ctrl_classes_table --create=ctrl_classes --path=packages/sevenpointsix/Ctrl/database
    php artisan make:migration create_ctrl_properties_table --create=ctrl_properties --path=packages/sevenpointsix/Ctrl/database
    php artisan make:migration add_ctrl_group_to_users_table --table=users  --path=packages/sevenpointsix/Ctrl/database
    php artisan make:migration add_default_user_to_users_table --table=users --path=packages/sevenpointsix/Ctrl/database