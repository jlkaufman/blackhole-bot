# Blackhole
This bot will connect to an IRC channel and a Jabber MUC, and relay everything back and forth, serving as a gateway.

## Prerequisites
It is assumed that you have [composer](http://getcomposer.org) already installed. In order to run this as a service, systemd is required. This was built with Debian 8 in mind. I'm a Debian user, and rarely get onto other distos. If you'd like to further develop the installer, please create a PR and I'll be happy to include it =)

## Quick install
The best way to install this project is with composer. In fact, it's the only supported method. IF you chose to grab the deps and install everything by hand, more power to you. I will not help you with this if you run into problems.

1. Clone this repo
2. run `composer install`
3. run `make`
4. run `sudo make install`
5. edit `/etc/blackhole-bot/blackhole.yml` to suit your needs. It is recommended that you create a user for the bot to run as.
    You can grab the user's uid with `id -u <username>` and the gid with `id -g <username>`
6. `service blackhole-bot <start|stop|status>` to control the bot after configuration.

## Make Targets
1. `make` - Builds the bot
2. `make install` - Installs the bot and installs the systemd service file
3. `make uninstall` - Uninstalls the bot
4. `make clean` - Cleans the working directory

## Development
Development can be done inside the vagrant vm...
1. run `vagrant up`
2. run `vagrant ssh`


For testing purposes you can run the bot with `./bin/bot`. Make sure to copy `config/blackhole.yml.sample` to `config/blackhole.yml` and you're good to go. 

* For CLI options, run `./bin/bot -h`

## Contributing

Anyone is welcome to contribute to the project. Make a fork, make some changes, and create a pull-request to contribute. Please let it be known, I wont accept
*any* pull requests with code that doesn't follow [PSR/2](http://www.php-fig.org/psr/psr-2/), or anything that looks to be a mess.


## Considerations

This bot is a side project. I will try to maintain it, but I make no promises. If you like it, contribute to it =)


## Known issues

* The XMPP library is bullshit. It *desperately* needs a replacement. 