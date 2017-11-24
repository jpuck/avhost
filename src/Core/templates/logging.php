<?php

return <<<CONF
ErrorLog \${APACHE_LOG_DIR}/{$hostname}.error.log
ErrorLogFormat "%A [%{cu}t] [%-m:%l] %7F: %E: %M% ,\\ referer\\ %{Referer}i"
CustomLog \${APACHE_LOG_DIR}/{$hostname}.access.log "%p %h %l %u %t \\"%r\\" %>s %O \\"%{Referer}i\\" \\"%{User-Agent}i\\""
CONF;
