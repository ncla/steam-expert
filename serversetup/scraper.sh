#!/bin/bash
source helpers.sh

read -e -p "Enter default non-root user: " -i "forge" USER
read -e -p "Enter database user name: " -i "forge" DB_USER
read -s -p "Enter database password for user: " DB_PASS
echo

while ! mysql -u ${DB_USER} -p${DB_PASS}  -e ";" ; do
    echo "Cannot connect to database! Try again"
    read -e -p "Enter database user name: " -i "forge" DB_USER
    read -s -p "Enter database password for user $DB_USER: " DB_PASS
    echo
done

read -e -p "Enter project root directory: " -i "/home/forge/steam.expert" PROJECT_ROOT_DIR
ask "Create swap file?" && CREATE_SWAP=1

THIS_ROOT_DIR=`pwd`;
APP_KEY="SomeRandomString";

clear
read -p $'\e[91;1mPRESS RETURN TO RUN\e[0m'
clear

# .env setup
echo "APP_ENV=production
APP_DEBUG=false
APP_KEY=${APP_KEY}

DB_HOST=localhost
DB_DATABASE=steamexpert
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}
DB_SSL=FALSE

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=sync

MAIL_DRIVER=smtp
MAIL_HOST=mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null

STEAM_USERNAME=
STEAM_PASSWORD=

SLACK_API_KEY=
SLACK_CHANNEL=
SLACK_BOT_NAME=
SLACK_BOT_IMG=https://emoji.slack-edge.com/T0QMSLTAT/3/32480b72eba23800.jpg" > ${PROJECT_ROOT_DIR}/.env


# install project dependencies
echoStatus "Installing project dependencies"
apt-get update >/dev/null
apt-get install -y htop php-xml php-intl php-mbstring mcrypt php-mcrypt curl unzip >/dev/null


# scraper specific commands
echoStatus "Installing scraper dependencies"
sudo apt-get install -y iceweasel xvfb python-dev libmariadbclient-dev python-mysqldb >/dev/null
pip2 install --user selenium pyvirtualdisplay decorator mysql >/dev/null
service nginx stop >/dev/null
apt-get autoremove -y >/dev/null

if [[ $CREATE_SWAP -eq 1 ]]; then
    echoStatus "Creating swap"
    dd if=/dev/zero of=/swp bs=1024 count=2097152 >/dev/null
    mkswap /swp >/dev/null
    swapon /swp >/dev/null
    chown root:root /swp >/dev/null
    chmod 0600 /swp >/dev/null
    echo $'\n/swp none swap sw 0 0' >> /etc/fstab
fi

echoStatus "Installing node"
apt-get purge -y nodejs* >/dev/null
curl -sL https://deb.nodesource.com/setup_4.x | bash - >/dev/null
apt-get install -y nodejs >/dev/null
cd ${PROJECT_ROOT_DIR} >/dev/null
echoStatus "Installing node dependencies"
npm install >/dev/null


echoStatus "Generating certificates"
CERT_DIR=/etc/ssl/mysql
MASTER=$(ip route get 8.8.8.8 | head -1 | cut -d' ' -f8)
mkdir ${CERT_DIR}
cd ${CERT_DIR}

openssl genrsa 2048 > ca-key.pem
openssl req -new -x509 -nodes -days 3600 -key ca-key.pem -out ca.pem -subj '/CA=steam.expert/CN=cakey/C=LV'
eval "openssl req -newkey rsa:2048 -days 3600 -nodes -keyout server-key.pem -out server-req.pem -subj '/CA=steam.expert/CN=$MASTER/C=LV'"
openssl rsa -in server-key.pem -out server-key.pem
openssl x509 -req -in server-req.pem -days 3600 -CA ca.pem -CAkey ca-key.pem -set_serial 01 -out server-cert.pem
openssl req -newkey rsa:2048 -days 3600 -nodes -keyout client-key.pem -out client-req.pem -subj '/CA=steam.expert/CN=clientkey/C=LV'
openssl rsa -in client-key.pem -out client-key.pem
openssl x509 -req -in client-req.pem -days 3600 -CA ca.pem -CAkey ca-key.pem -set_serial 01 -out client-cert.pem

echoStatus "Setting up MariaDB"
cp /etc/mysql/my.cnf /etc/mysql/my.save
MASTER_LOCAL=$(ifconfig | grep -m 1 'inet addr:' | cut -d: -f2 | awk '{ print $1}')
echo "
# MariaDB database server configuration file.
#
# You can copy this file to one of:
# - /etc/mysql/my.cnf to set global options,
# - ~/.my.cnf to set user-specific options.
#
# One can use all long options that the program supports.
# Run program with --help to get a list of available options and with
# --print-defaults to see which it would actually understand and use.
#
# For explanations see
# http://dev.mysql.com/doc/mysql/en/server-system-variables.html

# This will be passed to all mysql clients
# It has been reported that passwords should be enclosed with ticks/quotes
# escpecially if they contain # chars...
# Remember to edit /etc/mysql/debian.cnf when changing the socket location.
[client]
port            = 3306
socket          = /var/run/mysqld/mysqld.sock

# Here is entries for some specific programs
# The following values assume you have at least 32M ram

# This was formally known as [safe_mysqld]. Both versions are currently parsed.
[mysqld_safe]
socket          = /var/run/mysqld/mysqld.sock
nice            = 0

[mysqld]
ssl-ca=/etc/ssl/mysql/ca.pem
ssl-cert=/etc/ssl/mysql/server-cert.pem
ssl-key=/etc/ssl/mysql/server-key.pem
#
# * Basic Settings
#
user            = mysql
pid-file        = /var/run/mysqld/mysqld.pid
socket          = /var/run/mysqld/mysqld.sock
port            = 3306
basedir         = /usr
datadir         = /var/lib/mysql
tmpdir          = /tmp
lc_messages_dir = /usr/share/mysql
lc_messages     = en_US
skip-external-locking
#
# Instead of skip-networking the default is now to listen only on
# localhost which is more compatible and is not less secure.
bind-address            = ${MASTER_LOCAL}
#
# * Fine Tuning
#
max_connections         = 100
connect_timeout         = 5
wait_timeout            = 600
max_allowed_packet      = 16M
thread_cache_size       = 128
sort_buffer_size        = 4M
bulk_insert_buffer_size = 16M
tmp_table_size          = 32M
max_heap_table_size     = 32M
#
# * MyISAM
#
# This replaces the startup script and checks MyISAM tables if needed
# the first time they are touched. On error, make copy and try a repair.
myisam_recover_options = BACKUP
key_buffer_size         = 128M
#open-files-limit       = 2000
table_open_cache        = 400
myisam_sort_buffer_size = 512M
concurrent_insert       = 2
read_buffer_size        = 2M
read_rnd_buffer_size    = 1M
#
# * Query Cache Configuration
#
# Cache only tiny result sets, so we can fit more in the query cache.
query_cache_limit               = 128K
query_cache_size                = 64M
# for more write intensive setups, set to DEMAND or OFF
#query_cache_type               = DEMAND
#
# * Logging and Replication
#
# Both location gets rotated by the cronjob.
# Be aware that this log type is a performance killer.
# As of 5.1 you can enable the log at runtime!
#general_log_file        = /var/log/mysql/mysql.log
#general_log             = 1
#
# Error logging goes to syslog due to /etc/mysql/conf.d/mysqld_safe_syslog.cnf.
#
# we do want to know about network errors and such
log_warnings            = 2
#
# Enable the slow query log to see queries with especially long duration
#slow_query_log[={0|1}]
slow_query_log_file     = /var/log/mysql/mariadb-slow.log
long_query_time = 10
#log_slow_rate_limit    = 1000
log_slow_verbosity      = query_plan

#log-queries-not-using-indexes
#log_slow_admin_statements
#
# The following can be used as easy to replay backup logs or for replication.
# note: if you are setting up a replication slave, see README.Debian about
#       other settings you may need to change.
server-id               = 1
#report_host            = master1
#auto_increment_increment = 2
#auto_increment_offset  = 1
log_bin                 = /var/log/mysql/mariadb-bin
log_bin_index           = /var/log/mysql/mariadb-bin.index
# not fab for performance, but safer
#sync_binlog            = 1
expire_logs_days        = 5
max_binlog_size         = 300M
binlog-do-db=steamexpert
# slaves
#relay_log              = /var/log/mysql/relay-bin
#relay_log_index        = /var/log/mysql/relay-bin.index
#relay_log_info_file    = /var/log/mysql/relay-bin.info
#log_slave_updates
#read_only
#
# If applications support it, this stricter sql_mode prevents some
# mistakes like inserting invalid dates etc.
#sql_mode               = NO_ENGINE_SUBSTITUTION,TRADITIONAL
#
# * InnoDB
#
# InnoDB is enabled by default with a 10MB datafile in /var/lib/mysql/.
# Read the manual for more InnoDB related options. There are many!
default_storage_engine  = InnoDB
# you cant just change log file size, requires special procedure
#innodb_log_file_size   = 50M
innodb_buffer_pool_size = 256M
innodb_log_buffer_size  = 8M
innodb_file_per_table   = 1
innodb_open_files       = 400
innodb_io_capacity      = 400
innodb_flush_method     = O_DIRECT
#
# * Security Features
#
# Read the manual, too, if you want chroot!
# chroot = /var/lib/mysql/
#
# For generating SSL certificates I recommend the OpenSSL GUI tinyca.
#
# ssl-ca=/etc/mysql/cacert.pem
# ssl-cert=/etc/mysql/server-cert.pem
# ssl-key=/etc/mysql/server-key.pem

#
# * Galera-related settings
#
[galera]
# Mandatory settings
#wsrep_on=ON
#wsrep_provider=
#wsrep_cluster_address=
#binlog_format=row
#default_storage_engine=InnoDB
#innodb_autoinc_lock_mode=2
#
# Allow server to accept connections on all interfaces.
#
#bind-address=0.0.0.0
#
# Optional setting
#wsrep_slave_threads=1
#innodb_flush_log_at_trx_commit=0

[mysqldump]
quick
quote-names
max_allowed_packet      = 16M

[mysql]
#no-auto-rehash # faster start of mysql but no tab completion

[isamchk]
key_buffer              = 16M

#
# * IMPORTANT: Additional settings that can override those from this file!
#   The files must end with '.cnf', otherwise they'll be ignored.
#
!includedir /etc/mysql/conf.d/
" > /etc/mysql/my.cnf

REPLICATION_PASS=$(date +%s|sha256sum|base64|head -c 16)

service mysql reload && service mysql restart
mysql -u forge -p${DB_PASS} -e 'CREATE USER "replicator"@"%" IDENTIFIED BY "'${REPLICATION_PASS}'"'
mysql -u forge -p${DB_PASS} -e 'GRANT REPLICATION SLAVE ON *.* TO "replicator"@"%"'

cd ${PROJECT_ROOT_DIR}
echoStatus "MySQL user 'replicator' password: "${REPLICATION_PASS}

if ask "Run migrate and update?"; then
    php artisan migrate
    php artisan db:seed
    php artisan update:steamapps
    php artisan update:proxies
    php artisan testproxies
    php artisan update:market
fi

ask "Run after-install(zsh, etc)?" && AFTER_INSTALL=1

if [[ $AFTER_INSTALL -eq 1 ]]; then
    echoStatus "Installing oh-my-zsh"
    apt-get install zsh >/dev/null
    sh -c "$(curl -fsSL https://raw.githubusercontent.com/robbyrussell/oh-my-zsh/master/tools/install.sh)" >/dev/null
fi
