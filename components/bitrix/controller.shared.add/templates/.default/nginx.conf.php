server {
	listen	127.0.0.1;
	server_name	#SITE_ID#.#URL_SUBDOMAIN#;

	access_log	/var/log/nginx/access.log;

	location / {
		root	#DOCUMENT_ROOT#;
		index	index.php index.html index.htm;
	}

	location ~ \.php$ {
		proxy_set_header X-Real-IP $remote_addr;
		proxy_set_header Host      $host;
		proxy_pass	http://#SITE_ID#.#URL_SUBDOMAIN#:81;
	}

	# deny access to .htaccess files, if Apache's document root
	# concurs with nginx's one
	#
	location ~ /\.ht {
		deny  all;
	}
}
