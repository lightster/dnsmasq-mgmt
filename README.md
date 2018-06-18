# dnsmasq-mgmt

**Tool for pointing wildcarded domains to your local development machine**

Dnsmasq provides network infrastructure for small networks.  `dnsmasq-mgmt` is a tool that installs Dnsmasq and eases the configuration to locally point domains to IP addresses of your choosing.

dnsmasq-mgmt was built to avoid manually updating Dnsmasq or /etc/hosts configuration files.

*Currently dnsmasq-mgmt is only supported on macOS.*

## Installation

Use composer to install dnsmasq-mgmt via:
```bash
composer global require "lightster/dnsmasq-mgmt=~0.0.9"
```

Then allow dnsmasq-mgmt to restart Dnsmasq and clear the operating system's DNS cache without requesting the sudo password each time:
```bash
sudo ~/.composer/vendor/bin/dnsmasq-mgmt sudoers:install
```

The last step in installation is to actually install Dnsmasq and setup directories utilized by Dnsmasq:
```bash
~/.composer/vendor/bin/dnsmasq-mgmt dnsmasq:install
```

## Configuring domains

### Adding a domain

To have `b.com` point and subdomains of `b.com` point to localhost, run:
```bash
~/.composer/vendor/bin/dnsmasq-mgmt address:add b.com 127.0.0.1
```

### Removing a domain

To remove `b.com`, run:

```bash
~/.composer/vendor/bin/dnsmasq-mgmt address:add b.com 127.0.0.1
```

## Advanced usage

### Make dnsmasq-mgmt readily available

Rather than needing to call `dnsmasq-mgmt` with a path of `~/.composer/vendor/bin/dnsmasq-mgmt` every time, add `~/.composer/vendor/bin/` to the PATH environment variable in your `~/.bash_profile` configuration:
```bash
PATH="$PATH:${HOME}/.composer/vendor/bin"
```

### Other commands

dnsmasq-mgmt offers a few other commands.  You can get a full list of commands by running the `list` subcommand:
```
dnsmasq-mgmt list
```

### Install bash completion

If you are using bash, you can install bash completion for dnsmasq-mgmt by adding the following to your `~/.bashrc` (or `~/.bash_profile`):
```bash
source  ~/.composer/vendor/lightster/dnsmasq-mgmt/bin/dnsmasq-mgmt-completion.bash
```
