#!/usr/bin/env bash

#
#   Bulk update PHP on Kinsta WordPress environments.
#
#   `kinsta-bulk-update-php.sh <php-version-from> <php-version-to>`
#
#   Example: kinsta-bulk-update-php.sh 8.0 8.3
#

version_from="$1"
version_to="$2"

if [[ "$version_from" == "" || "$version_to" == "" ]]; then
    echo "Requires 2 arguments"
    exit
fi

environment_ids=$( php kinsta.php fetch-environments php $version_from )
log_file=response.log

attempts=3
if [[ $threads == "" ]]; then
    threads=5
fi

for attempt in $(seq 1 $attempts); do

    # Wait before retrying failures
    if [[ "$attempt" != "1" ]]; then
      sleep 2s
    fi

    # Run checks in parallel and collect the results in log file. 
    # This runs `wp eval-file kinsta-environment-php-update.php <environment-id> <php-version>`
    ( echo $environment_ids | xargs -P $threads -n 1 -I {} wp eval-file kinsta-environment-php-update.php {} $version_to ) 2>&1 | tee $log_file

    # Process log
    php kinsta.php process-response

done