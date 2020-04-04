#!/bin/bash
pidof -x -o %PPID apache_reload.sh && exit
while test 1=1; do
	if [ -f /tmp/apache_reload ]; then
		rm /tmp/apache_reload
		echo reloading
		/etc/init.d/apache reload
		/etc/init.d/nginx reload
	fi
	sleep 1
done
