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

Registered on [packagist][6] for easy [global installation][12]
using [composer][5].

    composer global require jpuck/avhost

Make sure your `$PATH` contains the global bin directory,
because [composer doesn't automatically modify your `$PATH` variable][13].
However, composer will tell you the [location of the global bin directory][12]:

    composer global config bin-dir --absolute

[Add that path to your shell profile or rc so that it's always available][14]:

    echo 'export PATH="$PATH:$HOME/.config/composer/vendor/bin"' >> ~/.bashrc

Then restart your shell, or source the file to take immediate effect:

    source ~/.bashrc

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

## No sudo

> sudo: avhost: command not found

If the command works, but not as sudo, then [it's probably not in your path][22].

    sudo -E env "PATH=$PATH" <command> [arguments]

[1]:http://symfony.com/doc/current/components/console.html
[4]:https://github.com/jpuck/avhost/issues
[5]:https://getcomposer.org/
[6]:https://packagist.org/packages/jpuck/avhost
[7]:https://poser.pugx.org/jpuck/avhost/v/stable
[8]:https://poser.pugx.org/jpuck/avhost/downloads
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
