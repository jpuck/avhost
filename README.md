# Apache Virtual Host Generator

[![Build Status][12]][11]
[![Codecov][16]][14]
[![Latest Stable Version][7]][6]
[![Total Downloads][8]][6]
[![License][9]][6]

PHP 7 command line [symfony console application][1] to create Apache 2.4 virtual
hosts for Ubuntu.

----------

## Getting Started

This is an Apache adminstrative tool whose commands mostly require sudo.
It can write configuration files to `/etc/apache2/sites-available/` invoke `a2ensite`
as well as writing SSL certificates to `/etc/ssl/certs/` and keys to `/etc/ssl/private/`
so the best way to install it would be somewhere in root's path.

[Download the latest release][6], set it executable, and move it to a good path. Here's a oneline command:

    curl -s -L https://github.com/jpuck/avhost/releases/latest | egrep -o '/jpuck/avhost/releases/download/[0-9\.]*/avhost.phar' | wget --base=http://github.com/ -i - -O avhost && chmod +x avhost && sudo mv avhost /usr/local/bin/

After installing, run without any arguments to see a list of commands:

    avhost

Use the `-h` flag with any command to get help with usage:

    avhost <command> -h

# Troubleshooting

> Job for apache2.service failed because the control process exited with error code.
> See "systemctl status apache2.service" and "journalctl -xe" for details.

Looking at those logs are certainly helpful, but here are a couple things that might not be obvious
the first time:

## No ssl

To run an encrypted virtual host over [TLS (SSL)][21], you must have enabled [Apache Module mod_ssl][23].

    sudo a2enmod ssl

## No rewrite

The default configuration with `avhost` is to redirect all traffic to an encrypted connection when available.
This is accomplished with [Apache Module mod_rewrite][19].

    sudo a2enmod rewrite

[This is recommended][18] for [many reasons][17].
If necessary, this can be overridden by passing the option `--no-require-ssl`
which makes sense in some cases, like when using a self-signed certificate that might cause trust issues.
However, since you can get a free trusted certificate from [Let's Encrypt][20], then there's no reason to be using
a self-signed certificate on a public site anyway.

## 403 Forbidden

If you create the document root in some random folder, then not only must that folder and files be readable to Apache,
but also every directory up to root must be executable by Apache in order for it to traverse the file system.

For example, if your site's files are in `/path/to/private/web/folder`

```bash
# up to directory, folders executable
chmod go+X /
chmod go+X /path
chmod go+X /path/to
chmod go+X /path/to/private
chmod go+X /path/to/private/web

# in directory, folders executable, files readable
chmod -R go+rX /path/to/private/web/folder
```

## No headers

In order to allow [Cross Origin Resource Sharing][25], you must enable
[Apache Module mod_headers][24]

    sudo a2enmod headers

[1]:http://symfony.com/doc/current/components/console.html
[4]:https://github.com/jpuck/avhost/issues
[5]:https://getcomposer.org/
[6]:https://github.com/jpuck/avhost/releases/latest
[7]:https://poser.pugx.org/jpuck/avhost/v/stable
[8]:https://img.shields.io/github/downloads/jpuck/avhost/total.svg
[9]:https://poser.pugx.org/jpuck/avhost/license
[11]:https://travis-ci.org/jpuck/avhost
[12]:https://travis-ci.org/jpuck/avhost.svg?branch=master
[13]:https://github.com/composer/composer/issues/4072
[14]:https://codecov.io/gh/jpuck/avhost/branch/master
[16]:https://img.shields.io/codecov/c/github/jpuck/avhost/master.svg
[17]:https://webmasters.googleblog.com/2014/08/https-as-ranking-signal.html
[18]:https://www.eff.org/encrypt-the-web
[19]:https://httpd.apache.org/docs/current/mod/mod_rewrite.html
[20]:https://letsencrypt.org/
[21]:https://en.wikipedia.org/wiki/Transport_Layer_Security
[22]:http://stackoverflow.com/a/29400598/4233593
[23]:https://httpd.apache.org/docs/2.4/mod/mod_ssl.html
[24]:https://httpd.apache.org/docs/2.4/mod/mod_headers.html
[25]:https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
