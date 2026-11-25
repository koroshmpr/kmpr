#!/bin/sh
# Run a PHP snippet inside WordPress (Local by Flywheel). Usage: ./wp.sh 'echo get_bloginfo("name");'
PHP="$HOME/Library/Application Support/Local/lightning-services/php-8.2.30+1/bin/darwin-arm64/bin/php"
SOCK="$HOME/Library/Application Support/Local/run/sooecj4yr/mysql/mysqld.sock"
TMP=$(mktemp /tmp/kmpr-acct-XXXXXX.php)
printf '<?php\n$_SERVER["HTTP_HOST"]="localhost:10039";\nrequire "/Users/mac/Local Sites/kmpr/app/public/wp-load.php";\n%s\n' "$1" > "$TMP"
"$PHP" -d mysqli.default_socket="$SOCK" "$TMP"; RC=$?
rm -f "$TMP"; exit $RC
