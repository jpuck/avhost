<?php

return <<<CONF
ServerName $hostname
ServerAlias www.$hostname
ServerAdmin webmaster@$hostname
DocumentRoot $documentRoot
UseCanonicalName On
ServerSignature Off
CONF;
