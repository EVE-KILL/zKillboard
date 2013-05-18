# zKillboard
zKillboard is a killboard created for EVE-Online, for use on EVE-KILL.net, but can also be used for single entities.

## Credits
zKillboard is released under the GNU Affero General Public License, version 3. The full license is available in the `AGPL.md` file.
zKillboard also uses data and images from EVE-Online, which is covered by a seperate license from [CCP](http://www.ccpgames.com/en/home). You can see the full license in the `CCP.md` file.
It also uses various 3rd party libraries, which all carry their own licensing. Please refer to them for more info.

## WARNING
This is BETA, which means it is a work in progress.  It lacks documentation and is currently
not meant for use in production.

## Contact
`#esc` on `irc.coldfront.net`
Mibbit link incase you're lazy: http://chat.mibbit.com/?channel=%23esc&server=irc.coldfront.net

## LICENSE
see `LICENSE.md` file

# Running zKillboard

## Dependencies
- PHP 5.3+
- Apache + mod_rewrite or Lighttpd
- Linux, Mac OS X or Windows
- MariaDB 5.5+ (MySQL 5.5+ might work, but isn't strictly supported, since some tables are in the Aria format)
- Composer
- APC or Memcached isn't strictly required, but APC or Memcached is highly recommended

## Lighttpd rewrite
```
url.redirect = (
	"/?a=kill_detail&kll_id=([0-9]+)" => "/evekilldetailintercept/$1/",
	"/?a=kill_related&kll_id=([0-9]+)" => "/evekillrelatedintercept/$1/"
)

url.rewrite-if-not-file = (
	"(.*)" => "/index.php/$0"
)
```

## Apache rewrite
Apache rewrite is handled by the .htaccess, located in the /public directory.

## Recommended
- PHP 5.3+
- Linux
- MariaDB 5.5+
- Composer
- APC
- Twig PHP Plugin (Available for compiling after vendor stuff is downloaded. under vendor/twig/twig/ext/twig/)

## Installation
Installation is currently command line only on linux consoles. Other methods are currently not supported.

1. `cd` to a dir where you want zKillboard to reside.
2. Do `git clone git@github.com:EVE-KILL/zKillboard.git`.
3. `cd` into `zKillboard` dir.
4. Get composer. `curl -s https://getcomposer.org/installer | php`
5. Install vendor files with composer. `php composer.phar install`
6. `cd` into `install` dir.
7. Execute the installation script. `php5 install.php`
8. Follow the instructions and fill in the prompts
9. Setup stomp (Follow guide further down)
10. Setup cronjobs
11. Setup the CLI system.

## CLI System
First thing you do, is `ln -s /path/to/zkb/cli.php /usr/bin/zkillboard`
And then you install `bash-completion` which can be done with `apt-get install bash-completion`.
Then you move `bash_complete_zkillboard` to `/etc/bash_completion.d/zkillboard`
And restart your shell session, at which point you can do `zkillboard list` with tab completion, and use this new interface for CLI commands.

## Cronjobs (To be deprecated)
- * * * * * flock -w 63 /tmp/lock.parseKills php5 /path/to/zKillboard/util/doJob.php parseKills
- * * * * * flock -w 63 /tmp/lock.doPopulateCharactersTable php5 /path/to/zKillboard/util/doJob.php doPopulateCharactersTable
There are more cronjobs to setup, however these are the bare minimums.

If you install stomp, you may also want add the following to crontab.
- * * * * * flock -w 63 /tmp/lock.stomp php5 /path/to/zKillboard/util/stomp.php

## Stomp
The stomp service is read only. If you need to send data via it, come by IRC and have a chat with us.

- Stomp server: tcp://stomp.zkillboard.com:61613
- Stomp user: guest
- Stomp pass: guest