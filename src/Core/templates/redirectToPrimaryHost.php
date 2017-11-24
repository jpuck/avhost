<?php

$escaped_hostname = str_replace('.', '\\.', $hostname);

return <<<CONF
RewriteEngine On
RewriteCond %{HTTPS} =on
RewriteRule ^ - [env=proto:https]
RewriteCond %{HTTPS} !=on
RewriteRule ^ - [env=proto:http]

# redirect all aliases to primary host
RewriteCond %{HTTP_HOST} !^$escaped_hostname\$ [NC]
RewriteRule ^ %{ENV:PROTO}://%{SERVER_NAME}%{REQUEST_URI} [R=301,L]
CONF;
