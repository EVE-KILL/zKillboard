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
- Memcached isn't strictly required, but is highly recommended
- cURL and it's php library, php5-curl

## Path
Always use the /public/ dir in your httpd, many things are located in said directory that are needed for the page to work.

## Apache rewrite
Apache rewrite is handled by the .htaccess, located in the /public directory.

## Apache Config
```
<VirtualHost *:80>
        ServerAlias yourdomain.tld

        DocumentRoot /path/to/zkb_install/public/
        <Directory /path/to/zkb_install/public/>
	        Options FollowSymLinks MultiViews
	        AllowOverride All
	        Order allow,deny
	        Allow from all
        </Directory>
</VirtualHost>
```

## Lighttpd config (Including redirect/rewrite rules)
```
url.redirect = (
	"/?a=kill_detail&kll_id=([0-9]+)" => "/evekilldetailintercept/$1/",
	"/?a=kill_related&kll_id=([0-9]+)" => "/evekillrelatedintercept/$1/"
)

url.rewrite-if-not-file = (
	"(.*)" => "/index.php/$0"
)
server.document-root = "/path/to/zkb_install/public/"
```

## Other webservers
Other webservers are supported, aslong as they can run PHP, they should work.
But other webservers have other ways to write rewrites, so from our side of things, they are unsupported.
Feel free to issue pull requests to amend this.

## Recommended
- PHP 5.3+
- Linux
- MariaDB 5.5+
- Composer
- Memcached
- Twig PHP Plugin (Available for compiling after vendor stuff is downloaded. under vendor/twig/twig/ext/twig/)
- cURL and it's php plugin, php5-curl

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
10. Setup the CLI system.
11. Setup cronjobs

## CLI System
1. Symlink cli.php to /usr/bin/zkillboard `ln -s /path/to/zkb/cli.php /usr/bin/zkillboard`
2. Install bash-completion. Under Debian this can be done like so `apt-get install bash-completion`
3. Move `bash_complete_zkillboard` to `/etc/bash_completion.d/zkillboard`
4. Restart your shell session
5. Issue `zkillboard list` and enjoy the zkillboard cli interface, with full tab completion

## Cronjobs
zKillboard comes with a script that automates the cron execution.
It keeps track of when each job has been run and how frequently it needs to be executed.
Just run it every minute via cron or a similar system:

```
* * * * * /var/killboard/zkillboard.com/cron.php >/whatever/log/you/like.txt 2>&1
```

If you're not happy with the default timeouts, or want to disable/enable some jobs entirely, you can use the cron.overrides file.
The cron.overrides file has to be placed into the zKB root dir, next to the cron.php script. It's a simple json file, with the following format:

```json
{
    "commandName":{
        "timeoutInSeconds":"arguments"
    }
}
```

For example the following would disable stompReceive entirely, and increase the timeout for apiFetch and parseKills to 5 minutes:
```json
{
    "stompReceive":{},
    "apiFetch":{
        "300":""
    },
    "parseKills":{
        "300":""
    }
}
```

If you don't want to use the automated cron script, you can run each command manualy in your crontab:
```
* * * * * /var/killboard/zkillboard.com/cliLock.sh minutely all
* * * * * /var/killboard/zkillboard.com/cliLock.sh apiFetch
* * * * * /var/killboard/zkillboard.com/cliLock.sh parseKills
* * * * * /var/killboard/zkillboard.com/cliLock.sh p120s
* * * * * /var/killboard/zkillboard.com/cliLock.sh stompReceive
* * * * * /var/killboard/zkillboard.com/cliLock.sh updateCharacters
* * * * * /var/killboard/zkillboard.com/cliLock.sh updateCorporations
* * * * * /var/killboard/zkillboard.com/cliLock.sh populateCharacters
1 * * * * /var/killboard/zkillboard.com/cliLock.sh summary
1 * * * * /var/killboard/zkillboard.com/cliLock.sh hourly
1 * * * * /var/killboard/zkillboard.com/cliLock.sh feed fetch
0 */6 * * * /var/killboard/zkillboard.com/cliLock.sh itemUpdate
9 */8 * * * /var/killboard/zkillboard.com/cliLock.sh populateAlliances
0 12 * * * /var/killboard/zkillboard.com/cliLock.sh priceUpdate
0 16 * * * /var/killboard/zkillboard.com/cliLock.sh calculateAllTimeStatsAndRanks ranks
0 20 * * * /var/killboard/zkillboard.com/cliLock.sh calculateRecentTimeStatsAndRanks stats
```

All cronjobs can be launched manually with the cli interface.

## Feed (Experimental)
The feed interface can be accessed by issuing `zkillboard feed`, all commands available can be found with help.

## Stomp
Stomp uses the STOMP PHP plugin which you can get via git.
Here is a quick one liner: `cd /tmp/ && git clone git://github.com/php/pecl-tools-stomp.git && cd pecl-tools-stomp && phpize && ./configure && make && make install`
After that you make a `20-stomp.ini` in `/etc/php5/conf.d/` and add `extension=stomp.so` to it, and save. And restart your php stuff.

The stomp service is read only. If you need to send data via it, come by IRC and have a chat with us.

- Stomp server: tcp://stomp.zkillboard.com:61613
- Stomp user: guest
- Stomp pass: guest
