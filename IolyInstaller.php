<?php
/**
 * ioly composer installer file
 * Installs and optionally activates modules in the shop via ioly module manager
 * and is called via Composer.
 *
 * @version 1.8.0
 * @package ioly
 * @author Stefan Moises <moises@shoptimax.de>
 * @copyright shoptimax GmbH, 2016-2017
 */
namespace ioly;

if (file_exists(dirname(__FILE__) . '/../../../bootstrap.php')) {
    require_once dirname(__FILE__) . '/../../../bootstrap.php';    
}
else if (file_exists(dirname(__FILE__) . '/../../../source/bootstrap.php')) {
    // OXID 6
    require_once dirname(__FILE__) . '/../../../source/bootstrap.php';    
}
if (file_exists(dirname(__FILE__) . '/../../../IolyInstallerConfig.php')) {
    require_once dirname(__FILE__) . '/../../../IolyInstallerConfig.php';
}
else if (file_exists(dirname(__FILE__) . '/../../../source/IolyInstallerConfig.php')) {
    // OXID 6
    require_once dirname(__FILE__) . '/../../../source/IolyInstallerConfig.php';
}

use Composer\Script\Event;

/**
 * Class IolyInstaller
 * No need to change anything here, all settings are in IolyInstallerConfig.php!
 *
 * @package ioly
 */
class IolyInstaller
{

    /**
     * Only run once during composer lifecycle
     * @var bool
     */
    protected static $_firstRun = true;

    /**
     * Main function
     * Runs after all composer installs are finished
     * @param Event $event The composer event which is injected.
     */
    public static function postAutoloadDump(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $skipInstall = false;
        $skipActivation = false;
        $skipClean = false;
        // skip activation and cleanup?
        if (getenv('IOLY_ONLY_INSTALL') == "true") {
            $skipActivation = true;
            $skipClean = true;
        }
        // run main installer class
        if (self::$_firstRun) {
            IolyInstallerCore::run($vendorDir, $skipInstall, $skipActivation, $skipClean);
            self::$_firstRun = false;
        }
    }
}
