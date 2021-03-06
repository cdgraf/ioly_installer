ioly installer
===

This is a repository for OXID deployment with ioly. It allows you to install modules into the shop, clear tmp directory,
create database views, set configuration values etc.

Please copy the provided _"IolyInstallerConfig.php"_ file into your shop root and adjust the settings, e.g. which modules to
install and/or activate etc.

You can install the IolyInstaller via Composer afterwards, e.g.

```javascript
  "config": {
    "github-oauth": {
        "github.com": "abcdefghijk"
    },
  },
  "repositories": {
    "ioly/installer": {
      "type": "vcs",
      "url": "https://github.com/shoptimax/ioly_installer.git"
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

You can also use the installer core without Composer, e.g. for the OXID eShop:

```php
<?php
require "vendor/autoload.php";
ioly\IolyInstallerCore::run(dirname(__FILE__) . "/vendor", false, false, false);
```

Read more about ioly in [master/README.md](https://github.com/ioly/ioly/blob/master/README.md).
