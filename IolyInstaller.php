<?php
/**
 * ioly composer installer file
 * Installs and optionally activates modules in the shop via ioly module manager
 * and is called via Composer.
 *
 * @version 1.6.0
 * @package ioly
 * @author Stefan Moises <moises@shoptimax.de>
 * @copyright shoptimax GmbH, 2016
 */
namespace ioly;

require_once dirname(__FILE__) . '/../../../bootstrap.php';
require_once dirname(__FILE__) . '/../../../IolyInstallerConfig.php';

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
     * Main function
     * Runs after all composer installs are finished
     * @param Event $event
     */
    public static function postAutoloadDump(Event $event)
    {
        echo "\nIolyInstallerComposer postAutoloadDump ... \n";
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');

        // run main installer class
        IolyInstallerCore::run($vendorDir);
    }
}
