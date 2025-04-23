# hcpp-magiclink
A plugin for Hestia Control Panel (via hestiacp-pluginable) that makes an entire website domain inaccessible until a magic link URL is first visited.

## Installation
HCPP-MagicLink requires an Ubuntu or Debian based installation of [Hestia Control Panel](https://hestiacp.com) in addition to an installation of [HestiaCP-Pluginable](https://github.com/virtuosoft-dev/hestiacp-pluginable) (version 2.X or higher) to function; please ensure that you have first installed pluginable on your Hestia Control Panel before proceeding. Clone the latest release version (i.e. replace **v1.0.0** below with the latest release version) to the magiclink folder:


```
cd /usr/local/hestia/plugins
sudo git clone --branch v1.0.0 https://github.com/virtuosoft-dev/hcpp-magiclink magiclink
```

## Using MagicLink to Secure a Website Domain
