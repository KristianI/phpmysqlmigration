# phpmysqlmigration
Automate migration of raw MySQL files.

# Install

composer require jens/phpmysqlmigration

# Run migrations

$res = \phpmysqlmigrate\phpmysqlmigrate::start(__DIR__.'/database_migrations/', array('host' => 'localhost', 'username' => 'root', 'password' => '', 'database' => 'db'));