# phpmysqlmigration
Automate migration of raw MySQL files.

## Install

composer require jens/phpmysqlmigration

## Run migrations

$res = \Phpmysqlmigration\Phpmysqlmigration::start(\_\_DIR\_\_.'/database_migrations/', array('host' => 'localhost', 'username' => 'root', 'password' => '', 'database' => 'db'));

## Mark everything as up-to-date

If everything is migrated, but not marked as such, you can use the reset-function to mark every files as migrated.

$res = \Phpmysqlmigration\Phpmysqlmigration::reset(\_\_DIR\_\_.'/database_migrations/', array('host' => 'localhost', 'username' => 'root', 'password' => '', 'database' => 'db'));
