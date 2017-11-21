<?php

return <<<CONF
ServerName {$this->hostname}
ServerAlias www.{$this->hostname}
ServerAdmin webmaster@{$this->hostname}
DocumentRoot {$this->documentRoot}
UseCanonicalName On
ServerSignature Off
CONF;
