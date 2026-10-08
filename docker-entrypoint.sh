#!/bin/bash
set -e

APP_DIR="/var/www/html"
DB_NAME="nuahseasonalapp_db"
DB_USER="nuahn"
DB_PASS="nuahn_secret"

# ---------------------------------------------------------------------------
# Persistent storage
# On Fly a volume is mounted at /data (see [mounts] in fly.toml). MariaDB's
# datadir and the uploads directory live there so they survive restarts and
# deploys. Without a mount (e.g. a plain `docker run`) everything stays inside
# the container, as before.
# ---------------------------------------------------------------------------
DATA_ROOT="${DATA_ROOT:-/data}"
if [ -d "$DATA_ROOT" ]; then
    DATADIR="$DATA_ROOT/mysql"
    UPLOADS_DIR="$DATA_ROOT/uploads"
    echo "Persistent storage: $DATA_ROOT"
else
    DATADIR="/var/lib/mysql"
    UPLOADS_DIR="$APP_DIR/uploads"
    echo "No $DATA_ROOT mount: using container-local (ephemeral) storage"
fi

# Uploads (job images in uploads/jobs, profile photos in uploads/) -> volume
mkdir -p "$UPLOADS_DIR/jobs"
if [ "$UPLOADS_DIR" != "$APP_DIR/uploads" ]; then
    if [ -d "$APP_DIR/uploads" ] && [ ! -L "$APP_DIR/uploads" ]; then
        # Keep any files baked into the image, never overwrite ones on the volume
        cp -rn "$APP_DIR/uploads/." "$UPLOADS_DIR/" 2>/dev/null || true
        rm -rf "$APP_DIR/uploads"
    fi
    ln -sfn "$UPLOADS_DIR" "$APP_DIR/uploads"
fi
chown -R www-data:www-data "$UPLOADS_DIR"
chmod 755 "$DATA_ROOT" 2>/dev/null || true

# MariaDB config: low memory, datadir on the volume, auto-repair MyISAM tables
mkdir -p /etc/mysql/mariadb.conf.d
cat > /etc/mysql/mariadb.conf.d/99-low-mem.cnf <<EOF
[mysqld]
datadir = ${DATADIR}
performance_schema = OFF
innodb_buffer_pool_size = 32M
innodb_log_buffer_size = 4M
key_buffer_size = 16M
max_connections = 30
bind-address = 127.0.0.1
myisam_recover_options = BACKUP,FORCE
EOF

mkdir -p /var/run/mysqld "$DATADIR"
chown mysql:mysql /var/run/mysqld
chown -R mysql:mysql "$DATADIR"

# First boot only: create the MariaDB system tables in an empty datadir
FIRST_BOOT=0
if [ ! -d "$DATADIR/mysql" ]; then
    echo "Empty datadir: initializing MariaDB system tables in $DATADIR..."
    mariadb-install-db --user=mysql --datadir="$DATADIR" --skip-test-db > /dev/null
    FIRST_BOOT=1
fi

echo "Starting MariaDB..."
# Own session/process group: Apache's prefork shutdown signals its whole process
# group, which would otherwise race our clean MariaDB shutdown below.
setsid service mariadb start || setsid /etc/init.d/mariadb start

for i in {1..30}; do
    if mysqladmin ping --silent 2>/dev/null; then
        echo "MariaDB is ready."
        break
    fi
    echo "Waiting for MariaDB... ($i/30)"
    sleep 1
done

# Application user (idempotent)
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'%' WITH GRANT OPTION;"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'localhost' WITH GRANT OPTION;"
mysql -e "GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'127.0.0.1' WITH GRANT OPTION;"
mysql -e "FLUSH PRIVILEGES;"

# Seed import: ONLY when the app database has no tables (first boot / brand-new
# datadir). The seed file starts with DROP TABLE statements, so it must never
# run against a database that already holds data.
mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
TABLE_COUNT=$(mysql -N -s -e "SELECT count(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';")

if [ "$TABLE_COUNT" -eq "0" ]; then
    echo "Database ${DB_NAME} is empty (first boot=${FIRST_BOOT}): importing seed..."
    mysql "${DB_NAME}" < "$APP_DIR/database/nuahseasonalapp_db.sql"
    echo "✅ Database schema and seed data imported successfully!"
else
    echo "Database ${DB_NAME} already has ${TABLE_COUNT} tables: keeping existing data."
fi

# Idempotent schema updates (never deletes or overwrites user data)
if [ -f "$APP_DIR/database/migrations.sql" ]; then
    if mysql "${DB_NAME}" < "$APP_DIR/database/migrations.sql"; then
        echo "✅ Database migrations applied"
    else
        echo "⚠️  Database migrations failed (continuing)"
    fi
fi

# Generate config/db.php pointing to local MariaDB instance with dedicated user
cat > "$APP_DIR/config/db.php" <<EOF
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

# ---------------------------------------------------------------------------
# Run Apache, and shut MariaDB down cleanly when the machine stops. The app
# tables are MyISAM, which can be left marked as crashed if mysqld is killed;
# with data on a persistent volume that would carry over to the next boot.
# ---------------------------------------------------------------------------
shutdown_all() {
    echo "Stopping Apache and MariaDB..."
    [ -n "$APACHE_PID" ] && kill -TERM "$APACHE_PID" 2>/dev/null || true
    [ -n "$APACHE_PID" ] && wait "$APACHE_PID" 2>/dev/null || true
    # Ask MariaDB to shut down (it may already be doing so if it got the stop
    # signal too), then wait until mariadbd has really exited before PID 1 ends.
    mysqladmin shutdown 2>/dev/null || true
    for i in $(seq 1 25); do
        pgrep -x mariadbd > /dev/null || break
        sleep 1
    done
    if pgrep -x mariadbd > /dev/null; then
        echo "⚠️  MariaDB still running after 25s, forcing stop"
        service mariadb stop || true
    else
        echo "MariaDB stopped cleanly."
    fi
    echo "Shutdown complete."
    exit 0
}
trap shutdown_all SIGTERM SIGINT

apache2-foreground &
APACHE_PID=$!
wait "$APACHE_PID" || true
# Apache exited on its own: still close the database cleanly
APACHE_PID=""
shutdown_all
