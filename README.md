ioly installer
===

This is a repository for OXID deployment with ioly. It allows you to install modules into the shop, clear tmp directory,
create database views, set configuration values etc.

Please copy the provided _"IolyInstallerConfig.php"_ file into your shop root and adjust the settings, e.g. which modules to
install and/or activate etc.

You can install the IolyInstaller via Composer afterwards, e.g.

```javascript
  "config": {
    "gitlab-token": {
      "glass.shoptimax.de": "abcdegf"
    }
  },
  "repositories": {
    "ioly/installer": {
      "type": "vcs",
      "url": "git@glass.shoptimax.de:internal/ioly_installer.git"
    }
  },
  "require": {
    "ioly/installer": "*"
  },
  "scripts": {
    "post-autoload-dump": [
      "ioly\\IolyInstaller::postAutoloadDump"
    ]
  },    
```


Read more about ioly in [master/README.md](https://github.com/ioly/ioly/blob/master/README.md).
