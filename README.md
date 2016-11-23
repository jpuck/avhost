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

[1]:http://symfony.com/doc/current/components/console.html
[4]:https://github.com/jpuck/avhost/issues
[5]:https://getcomposer.org/
[6]:https://packagist.org/packages/jpuck/avhost
[7]:https://poser.pugx.org/jpuck/avhost/v/stable
[8]:https://poser.pugx.org/jpuck/avhost/downloads
[9]:https://poser.pugx.org/jpuck/avhost/license
[11]:https://travis-ci.org/jpuck/avhost
[12]:https://travis-ci.org/jpuck/avhost.svg?branch=master
[14]:https://codecov.io/gh/jpuck/avhost/branch/master
[16]:https://img.shields.io/codecov/c/github/jpuck/avhost/master.svg
