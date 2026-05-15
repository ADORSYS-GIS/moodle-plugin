#!/bin/sh
set -eu
#
## Copyright 2022 Google LLC
## Licensed under the Apache License, Version 2.0 (the "License");
## you may not use this file except in compliance with the License.
## You may obtain a copy of the License at
##
##     https://www.apache.org/licenses/LICENSE-2.0
##
## Unless required by applicable law or agreed to in writing, software
## distributed under the License is distributed on an "AS IS" BASIS,
## WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
## See the License for the specific language governing permissions and
## limitations under the License.

setupPath() {
  echo  "Checking if $1 exists ..."
  if [ ! -d $1 ] ; then
    echo "Does not, creating $1 and adjusting owner and permissions ..."
    mkdir -p $1
  else
    echo "Yes! All set with $1 ..."
  fi
  chown $2:$3 $1
  chmod 0775 $1
  echo -e "Done with setting up $1 ...\n"
}

download_and_extract_tarball() {
  archive_url="$1"
  destination_dir="$2"
  archive_file="$(mktemp)"

  echo "Downloading archive from $archive_url ..."
  curl --fail --location --retry 5 --retry-all-errors --output "$archive_file" "$archive_url"
  echo "Extracting archive into $destination_dir ..."
  tar -xzf "$archive_file" --strip-components=1 -C "$destination_dir"
  rm -f "$archive_file"
}

###
# Usage: setupPath /some/path user group
##

# moodle temp path
setupPath /tmp/moodle www www
setupPath /tmp/moodle/localcache www www

# nginx temp paths
setupPath /tmp/nginx www root
setupPath /tmp/nginx/client_temp www root
setupPath /tmp/nginx/proxy_temp www root
setupPath /tmp/nginx/fastcgi_temp www root
setupPath /tmp/nginx/uwsgi_temp www root
setupPath /tmp/nginx/scgi_temp www root

setupPath /etc/nginx root root
setupPath /etc/php82 root root

setupPath $MOODLE_ROOT_PATH www www
setupPath $MOODLE_PATH www www
setupPath $MOODLE_DATAROOT_PATH www www
setupPath $MOODLE_DATAROOT_PATH/log www www
setupPath $MOODLE_DATAROOT_PATH/moosh www www

generate_config_php() {
  echo "Generating config.php file ..."
  sudo -u www php82 -d max_input_vars=10000 \
    $MOODLE_PATH/admin/cli/install.php \
    --lang=$MOODLE_LANGUAGE \
    --wwwroot=$SITE_URL \
    --dataroot=$MOODLE_DATAROOT_PATH \
    --dbtype=$DB_TYPE \
    --dbhost=$DB_HOST \
    --dbport=$DB_HOST_PORT \
    --dbname=$DB_NAME \
    --dbuser=$DB_USER \
    --dbpass=$DB_PASS \
    --prefix=$DB_PREFIX \
    --fullname="$MOODLE_SITENAME" \
    --shortname="$MOODLE_SITENAME" \
    --summary="$MOODLE_SITESUMMARY" \
    --adminuser=$MOODLE_USERNAME \
    --adminpass=$MOODLE_PASSWORD \
    --adminemail=$MOODLE_EMAIL \
    --non-interactive \
    --agree-license \
    --skip-database \
    --allow-unstable
  echo "Done generating config.php file ..."
}

apply_config_php_customizations() {
  echo "Adding read replica settings if needed ..."
  if [ -n "$DB_READ_REPLICA_HOST" ]; then
    if [ -n "$DB_READ_REPLICA_USER" ] && [ -n "$DB_READ_REPLICA_PASSWORD" ] && [ -n "$DB_READ_REPLICA_PORT" ]; then
      sed -i "/\$CFG->dboptions/a \ \ "\''readonly'\'" => [ \'instance\' => [ \'dbhost\' => \'$DB_READ_REPLICA_HOST\', \'dbport\' => \'$DB_READ_REPLICA_PORT\', \'dbuser\' => \'$DB_READ_REPLICA_USER\', \'dbpass\' => \'$DB_READ_REPLICA_PASSWORD\' ] ]," $MOODLE_PATH/config.php
    else
      sed -i "/\$CFG->dboptions/a \ \ "\''readonly'\'" => [ \'instance\' => [ \'$DB_HOST_REPLICA\' ] ]," $MOODLE_PATH/config.php
    fi
  fi

  echo "Setting ssl proxy setting ..."
  if [ "$SSLPROXY" = 'true' ]; then
    sed -i '/require_once/i $CFG->sslproxy = true;' $MOODLE_PATH/config.php
  fi

  echo "Setting no email ever if true ..."
  if [ "$NOEMAIL_EVER" = 'true' ]; then
    sed -i '/require_once/i $CFG->noemailever = true;' $MOODLE_PATH/config.php
  fi

  echo "Forcing clam av executable path in config.php file ..."
  sed -i '/require_once/i $CFG->forced_plugin_settings = array("antivirus_clamav" => array("pathtoclam" => "/usr/bin/clamscan"));' $MOODLE_PATH/config.php

  echo "Prevent executable paths to be set via Admin GUI ..."
  sed -i '/require_once/i $CFG->preventexecpath = true;' $MOODLE_PATH/config.php

  sudo -u www cp $MOODLE_PATH/config.php $MOODLE_PATH/config.php.bak
  envsubst '$REDIS_LOCK_HOST_AND_PORT $REDIS_LOCK_AUTH_STRING $REDIS_SESSION_ID_HOST $REDIS_SESSION_ID_PORT $REDIS_SESSION_ID_AUTH_STRING' < "/root/.templates/config.php.template" \
    | { head -n 23 $MOODLE_PATH/config.php.bak; cat /dev/stdin; tail -n +24 $MOODLE_PATH/config.php.bak; } > $MOODLE_PATH/config.php
}

echo  "Syncing NGINX config files into place ..."
sudo -u root cp -R /root/etc/nginx/* /etc/nginx/
echo  "Done syncing NGINX config files ..."

echo  "Syncing PHP8.2 config files into place ..."
sudo -u root cp -R /root/etc/php82/* /etc/php82/
echo  "Done syncing PHP8.2 config files ..."

echo  "Cleaning root's temp files ..."
rm -rvf /root/etc

# run envsubst
envsubst \$MOODLE_ROOT_PATH < /etc/nginx/nginx.conf-template > /etc/nginx/nginx.conf
rm -rvf /etc/nginx/nginx.conf-template

envsubst \$MOODLE_ROOT_PATH < /etc/php82/php.ini-template > /etc/php82/php.ini
rm -rvf /etc/php82/php.ini-template

if [ ! -f "$MOODLE_PATH/version.php" ] ; then
  echo "Bundled Moodle core files are missing from $MOODLE_PATH."
  echo "Rebuild the image so Moodle is included at build time."
  exit 1
fi

echo  "Checking if Moodle is already setup ..."
if [ ! -f "$MOODLE_DATAROOT_PATH/.moodle-installed" ] ; then

  if [ ! -f "$MOODLE_PATH/config.php" ] ; then
    echo "Nope, not installed yet, using the Moodle core bundled in the image ..."

    echo "Setting proper ownership on the Moodle data directory ..."
    chown -R www:www $MOODLE_DATAROOT_PATH

    echo "Setting permissions on moodledata directories ..."
    find $MOODLE_DATAROOT_PATH -type d -exec chmod 0775 {} \;
    echo "Setting permissions on files in moodledata ..."
    find $MOODLE_DATAROOT_PATH -type f -exec chmod 0664 {} \;

    generate_config_php

    echo "Installing database ..."
    sudo -u www php82 -d max_input_vars=10000 \
      $MOODLE_PATH/admin/cli/install_database.php \
      --lang="$MOODLE_LANGUAGE" \
      --fullname="$MOODLE_SITENAME" \
      --shortname="$MOODLE_SITENAME" \
      --summary="$MOODLE_SITESUMMARY" \
      --adminuser="$MOODLE_USERNAME" \
      --adminpass="$MOODLE_PASSWORD" \
      --adminemail="$MOODLE_EMAIL" \
      --agree-license
    echo "Done installing database ..."

    apply_config_php_customizations

    echo "Configuring other specific settings ..."

    # moodle binaries
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=pathtophp --set=/usr/bin/php82
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=pathtodu --set=/usr/bin/du
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=aspellpath --set=/usr/bin/aspell
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=pathtodot --set=/usr/bin/dot
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=pathtogs --set=/usr/bin/gs
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=pathtopython --set=/usr/bin/python3
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=enableblogs --set=0

    # moodle smtp settings
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=smtphosts --set="$SMTP_HOST:$SMTP_PORT"
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=smtpuser --set="$SMTP_USER"
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=smtppass --set="$SMTP_PASSWORD"
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=smtpsecure --set="$SMTP_PROTOCOL"
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=noreplyaddress --set="$MOODLE_MAIL_NOREPLY_ADDRESS"
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name=emailsubjectprefix --set="$MOODLE_MAIL_PREFIX"
    # redis session cookies
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_handler_class" --set='\core\session\redis'
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_database" --set=0
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_host" --set=$REDIS_SESSION_ID_HOST
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_port" --set=$REDIS_SESSION_ID_PORT
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_auth" --set=$REDIS_SESSION_ID_AUTH_STRING
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_prefix" --set='mdl_sessid_'
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_acquire_lock_timeout" --set=120
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_acquire_lock_warn" --set=0
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_lock_expire" --set=7200
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_lock_retry" --set=100
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_serializer_use_igbinary" --set=true
    # sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/cfg.php --name="session_redis_compressor" --set='gzip'
  fi

  # Avoid writing the config file
  echo "Protecting config.php file ..."
  chmod 0444 $MOODLE_PATH/config.php

  if [ "${ENABLE_FRESHCLAM:-no}" = "yes" ]; then
    echo "Obtaining latest and initial clamav virus databases ..."
    /usr/bin/freshclam
    echo "Done obtaining latest and initial clamav virus databases ..."
  else
    echo "Skipping freshclam bootstrap in local Docker setup ..."
  fi

  # Fix publicpaths check to point to the internal container on port 8080
  if [ ! -f "$MOODLE_PATH/lib/classes/check/environment/publicpaths.modified" ] ; then
    echo "Modifying publicpaths.php for port :8080 ..."
    # sudo -u www sed -i 's/wwwroot/wwwroot\ \. \"\:8080\"/g' "$MOODLE_PATH/lib/classes/check/environment/publicpaths.php"
    sudo -u www touch $MOODLE_PATH/lib/classes/check/environment/publicpaths.modified
  fi

  # precomplie css cache for this pod
  echo "Precompiling boost's css theme ..."
  sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/build_theme_css.php --themes=boost

  # persist setup
  sudo -u www touch $MOODLE_DATAROOT_PATH/.moodle-installed
  echo -e "Done with setting up Moodle ...\n"

else

  if [ ! -f "$MOODLE_PATH/config.php" ] ; then
    echo "Config.php is missing, regenerating it from the existing environment ..."
    generate_config_php
    apply_config_php_customizations
  fi

  echo "Yes! All set Moodle setup ..."

  if [ -f "$MOODLE_DATAROOT_PATH/.moodle-autoupgrade" ]; then
    echo "Upgrading moodle..."
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/maintenance.php --enable
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/upgrade.php --non-interactive --allow-unstable
    sudo -u www php82 -d max_input_vars=10000 $MOODLE_PATH/admin/cli/maintenance.php --disable
  else
    echo "Skipped auto update of Moodle"
  fi
fi

echo "Adding cron entries for Moodle's cron and AdHoc Tasks ..."
echo "*/1 * * * * /usr/bin/sudo -u www /usr/bin/php82 $MOODLE_PATH/admin/cli/cron.php >> $MOODLE_DATAROOT_PATH/log/moodle_cron.log 2>&1" > /var/spool/cron/crontabs/root
echo "*/1 * * * * /usr/bin/sudo -u www /usr/bin/php82 $MOODLE_PATH/admin/cli/adhoc_task.php --execute >> $MOODLE_DATAROOT_PATH/log/moodle_task.log 2>&1" >> /var/spool/cron/crontabs/root
echo "0 0 * * * /usr/bin/freshclam >> $MOODLE_DATAROOT_PATH/log/freshclam.log 2>&1" >> /var/spool/cron/crontabs/root

if [ "${ENABLE_MOOSH_BOOTSTRAP:-no}" != "yes" ]; then
  echo "Skipping optional Moosh/bootstrap extras in local Docker setup ..."
  echo "All steps are done properly now! Moodle is properly installed ..."
  return 0 2>/dev/null || exit 0
fi

## Testing for Moosh setup
filename="$MOODLE_DATAROOT_PATH/moosh/.installed"
echo "Checking if Moosh is already setup ..."
if [ ! -f $filename ] ; then
    echo "Downloading and extracting Moosh ..."
    download_and_extract_tarball "$MOOSH_URL" "$MOODLE_DATAROOT_PATH/moosh"
    chown -R www:www $MOODLE_DATAROOT_PATH/moosh
    cd $MOODLE_DATAROOT_PATH/moosh && composer --quiet --no-interaction --no-cache update && cd /
    sudo -u www sed -i 's/\$arg, \$v);/\$arg, \$v \? \$v \: \"\");/g' "$MOODLE_DATAROOT_PATH/moosh/Moosh/MooshCommand.php"
    touch $filename
    echo -e "Done downloading and setting up Moosh ...\n"
else
  echo "Moosh is already setup, skipping ..."
fi

# create symlink for moosh within the image itself
ln -s $MOODLE_DATAROOT_PATH/moosh/moosh.php /bin/moosh

## Check for plugins and update its availiable list
echo "Updating Moosh plugins list for www user ..."
sudo -u www moosh --moodle-path=$MOODLE_PATH plugin-list > /dev/null 2>&1
echo "Done updating Moosh plugins list for www user ..."

## Testing for report_benchmark plugin setup
filename="$MOODLE_DATAROOT_PATH/moosh/.plugin-report-benchmark-installed"
if [ ! -f $filename ]; then
  echo "Installing the plugin report_benchmark via Moosh ..."
  sudo -u www moosh --moodle-path=$MOODLE_PATH plugin-install -f report_benchmark
  touch $filename
  echo "Done installing the plugin report_benchmark via Moosh ..."
fi

## Testing for tool_opcache plugin setup
filename="$MOODLE_DATAROOT_PATH/moosh/.plugin-tool_opcache-installed"
if [ ! -f $filename ]; then
  echo "Installing the plugin tool_opcache via Moosh ..."
  sudo -u www moosh --moodle-path=$MOODLE_PATH plugin-install -f tool_opcache
  touch $filename
  echo "Done installing the plugin tool_opcache via Moosh ..."
fi

# Sets Redis Session cache mapping in Moodle via Moosh
cache_adjusted=""
filename="$MOODLE_DATAROOT_PATH/moosh/.redis-session-mapping-done"
if [ ! -f $filename ] && [ ! -z "$REDIS_SESSION_IP_AND_PORT" ]; then
  echo "Adding cache redis-store for Session Cache via Moosh ..."
  sudo -u www moosh --moodle-path=$MOODLE_PATH cache-add-redis-store \
    --password $REDIS_SESSION_AUTH_STRING \
    --key-prefix "store_session_1_" \
    "Session" \
    $REDIS_SESSION_IP_AND_PORT

  cache_adjusted="true"
  touch $filename
  echo "Done adding cache redis-store for Session Cache via Moosh ..."
fi

# Sets Redis Application cache mapping in Moodle via Moosh
filename="$MOODLE_DATAROOT_PATH/moosh/.redis-app-mapping-done"
if [ ! -f $filename ] && [ ! -z "$REDIS_APP_IP_AND_PORT" ]; then
  echo "Adding cache redis-store for Application Cache via Moosh ..."
  sudo -u www moosh --moodle-path $MOODLE_PATH cache-add-redis-store \
    --password $REDIS_APP_AUTH_STRING \
    --key-prefix "store_app_1_" \
    "Application" \
    $REDIS_APP_IP_AND_PORT

  cache_adjusted="true"
  touch $filename
  echo "Done adding cache redis-store for Application Cache via Moosh ..."
fi

if [ ! -z "$cache_adjusted" ]; then
  echo "Editting mappings in Cache plugin for both Application and Session "
  echo "and mapping them to the appropriate Redis's store ..."
  sudo -u www moosh --moodle-path $MOODLE_PATH cache-clear
  sudo -u www moosh --moodle-path $MOODLE_PATH cache-edit-mappings \
    --application "Application" \
    --session "Session"
fi

echo "All steps are done properly now! Moodle is poperly installed ..."
