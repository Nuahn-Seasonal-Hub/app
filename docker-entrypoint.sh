#!/bin/bash
set -e

# Configure MariaDB for low memory footprint
mkdir -p /etc/mysql/mariadb.conf.d
cat > /etc/mysql/mariadb.conf.d/99-low-mem.cnf <<EOF
[mysqld]
performance_schema = OFF
innodb_buffer_pool_size = 32M
innodb_log_buffer_size = 4M
key_buffer_size = 16M
max_connections = 30
bind-address = 127.0.0.1
EOF

# Ensure MariaDB runtime directories exist with correct permissions
mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

# Start MariaDB service
echo "Starting MariaDB..."
service mariadb start || /etc/init.d/mariadb start

# Wait for MariaDB to respond
for i in {1..30}; do
    if mysqladmin ping --silent 2>/dev/null; then
        echo "MariaDB is ready."
        break
    fi
    echo "Waiting for MariaDB... ($i/30)"
    sleep 1
done

DB_NAME="nuahseasonalapp_db"
DB_USER="nuahn"
DB_PASS="nuahn_secret"

# Create application user and grant permissions
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'%' WITH GRANT OPTION;"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'localhost' WITH GRANT OPTION;"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'127.0.0.1' WITH GRANT OPTION;"
mysql -e "FLUSH PRIVILEGES;"

# Create database and import initial schema if tables don't exist
TABLE_COUNT=$(mysql -N -s -e "SELECT count(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';" 2>/dev/null || echo "0")

if [ "$TABLE_COUNT" -eq "0" ]; then
    echo "Creating database ${DB_NAME} and importing schema..."
    mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    if [ -f "/var/www/html/database/nuahseasonalapp_db.sql" ]; then
        mysql "${DB_NAME}" < /var/www/html/database/nuahseasonalapp_db.sql
        echo "✅ Database schema and seed data imported successfully!"
    fi
else
    echo "Database ${DB_NAME} already initialized with ${TABLE_COUNT} tables."
fi

# Apply idempotent schema updates + demo-account passwords on every boot, so both
# fresh and existing databases (each Fly machine has its own) end up consistent.
if [ -f "/var/www/html/database/migrations.sql" ]; then
    if mysql "${DB_NAME}" < /var/www/html/database/migrations.sql; then
        echo "✅ Database migrations applied"
    else
        echo "⚠️  Database migrations failed (continuing)"
    fi
fi

# Generate config/db.php pointing to local MariaDB instance with dedicated user
cat > /var/www/html/config/db.php <<EOF
<?php
\$host    = '127.0.0.1';
\$db      = '${DB_NAME}';
\$user    = '${DB_USER}';
\$pass    = '${DB_PASS}';
\$charset = 'utf8mb4';

\$dsn = "mysql:host=\$host;dbname=\$db;charset=\$charset";
\$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    \$pdo = new PDO(\$dsn, \$user, \$pass, \$options);
} catch (\PDOException \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}
EOF

echo "✅ config/db.php generated"

# Start Apache in the foreground
exec apache2-foreground
