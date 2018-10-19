#!/bin/sh

perl -pi -e "s/#shared_preload_libraries = ''/shared_preload_libraries = 'pg_stat_statements'/g" /var/lib/postgresql/data/postgresql.conf

echo "Enabled pg_stat_statements"

perl -pi -e "s/#track_activities/track_activities/g" /var/lib/postgresql/data/postgresql.conf
perl -pi -e "s/#track_counts/track_counts/g" /var/lib/postgresql/data/postgresql.conf

echo "Enabled The Statistics Collector"