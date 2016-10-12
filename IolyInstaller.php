<?php
/**
 * ioly installation file
 * Installs and optionally activates modules in the shop via ioly module manager.
 * @version 1.5.0
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
 * @package Ioly
 */
class IolyInstaller
{
    /**
     * Ioly Core
     * @var Ioly
     */
    protected static $ioly;
    /**
     * Ioly Config
     * @var \Ioly\IolyInstallerConfig
     */
    protected static $config;
    /**
     * @var array
     */
    protected static $domainsLocal;
    /**
     * The http port to use
     * @var int
     */
    protected static $portLocal;
    /**
     * @var array
     */
    protected static $shoptifindDataLocal;
    /**
     * @var oxmodulelist $oModuleList
     */
    protected static $oModuleList;
    /**
     * All available modules
     * @var array $aModules
     */
    protected static $aModules;
    /**
     * @var int
     */
    private static $_startTime = 0;

    /**
     * IolyInstaller constructor.
     */
    public static function constructStatic()
    {
        self::$_startTime = microtime(true);
        self::$config = new IolyInstallerConfig();
        // problem with output buffer since oxUtilsObject::oxNew() - first called in generateViews() -
        // triggers oxbase::getViewName() somehow calls
        // oxUtilsServer::setOxCookie() which dies with an Exception if anything has been echo'd before ...
        ob_start();
        echo "\nIoly constructStatic ... \n";
        self::$ioly = new \ioly\ioly();
        self::$ioly->setSystemBasePath(dirname(__FILE__));
        self::$ioly->setSystemVersion(self::$config->getMainShopVersion());
        // add custom smx cookbook!
        self::$ioly->addCookbook('smx', self::$config->getCookbookPath());

        echo "\nIoly init ... \n";
        self::init();

        $oConfig = \oxRegistry::getConfig();
        echo "\nIoly setting base vars ... \n";
        // all domains for local
        self::$domainsLocal = $oConfig->getConfigParam('domainsLocal') != null ? $oConfig->getConfigParam('domainsLocal') : array();
        // local port
        self::$portLocal = $oConfig->getConfigParam('portLocal') != null ? $oConfig->getConfigParam('portLocal') : "";
        self::$shoptifindDataLocal = $oConfig->getConfigParam('shoptifindDataLocal') != null ? $oConfig->getConfigParam('shoptifindDataLocal') : array();
        // set status marker
        self::handleStatusFile(true);
        echo "\nIoly constructStatic done ... \n";
    }

    /**
     * Write a status file when ioly installation is running
     * and delete if afterwards
     *
     * @param boolean $create
     */
    public static function handleStatusFile($create = true)
    {
        $filePath = dirname(__FILE__) . "/.ioly_install_running";
        if ($create) {
            $data = date("Y-m-d H:i:s");
            file_put_contents($filePath, $data, LOCK_EX);
            echo "\nIoly status file written!";
        } else {
            if (file_exists($filePath)) {
                @unlink($filePath);
                echo "\nIoly status file deleted!";
            }
        }

    }
    /**
     * Main function
     * Runs after all composer installs are finished
     * @param Event $event
     */
    public static function postAutoloadDump(Event $event)
    {
        echo "\nIoly postAutoloadDump ... \n";

        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        include $vendorDir . '/autoload.php';

        echo "\nIoly installer running ... \n";

        try {
            // before we do anything, clean tmp and create views!
            self::cleanup();

            self::doInstall();

            self::initModuleSettings();

            if (self::$config->isActivateAllExceptBlacklist()) {
                self::activateModulesExceptBlacklisted();
            } else {
                self::activateModulesWhitelisted();
            }
            self::updateConfig();
            self::setupShoptifind();
            self::finish();
        } catch (Exception $ex) {
            echo "\nException occurred! " . $ex->getMessage() . "\n" . $ex->getTraceAsString();
        }
        ob_end_flush();
    }

    /**
     * Initial cleanup and preparation
     */
    protected static function init()
    {
        echo "\nIoly rm tmp files ... \n";
        // TODO - keep .htaccess and .gitignore files in tmp/**
        exec('rm -Rf ' . dirname(__FILE__) . "/tmp/*");
        // remove module entries from DB
        echo "\nIoly cleaning DB ... \n";
        try {
            \oxDb::getDb()->execute("DELETE FROM oxconfig WHERE  oxvarname LIKE '%Module%'");
            // delete template block settings, too
            \oxDb::getDb()->execute("DELETE FROM oxtplblocks");
        } catch (\Exception $ex) {
            echo "\nException cleaning DB: {$ex->getMessage()} \n";

        }
        echo "\nIoly DB clean ... \n";
    }

    /**
     * Empty tmp, create views
     */
    protected static function cleanup()
    {
        self::emptyTmp();
        self::generateViews(self::$config->getShopIds());
    }

    /**
     * If the oxconfig table is cleaned from module settings,
     * we need to set the module paths for subshops here ...
     */
    protected static function initModuleSettings()
    {
        echo "\n\n/**********************************************";
        echo "\ninitModuleSettings, setting aModulePaths ...";
        echo "\n/**********************************************\n";
        $oConfig = \oxRegistry::getConfig();
        self::$oModuleList = oxNew('oxModuleList');
        $sModulesDir = $oConfig->getModulesDir();
        // call this, in case of the oxconfig table doesn't have any module info yet!
        self::$aModules = self::$oModuleList->getModulesFromDir($sModulesDir);
        $aShopIds = self::getShopIdsFromString(self::$config->getShopIds());
        $oConfig = \oxRegistry::getConfig();
        $aModulePaths = $oConfig->getShopConfVar('aModulePaths', $aShopIds[0]);
        foreach ($aShopIds as $shopId) {
            $oConfig->setShopId($shopId);
            // OXID seems to have a bug in oxmodulelist.php and only saves the module paths for shop id 1, so
            // we save it for every shop id manually here!
            $oConfig->saveShopConfVar('aarr', 'aModulePaths', $aModulePaths, $shopId);
        }
        ob_flush();
    }

    /**
     * Un-/install modules
     */
    protected static function doInstall()
    {
        echo "\n\n/**********************************************";
        echo "\nINSTALLING IOLY MODULES ...";
        echo "\n/**********************************************\n";
        ob_flush();
        foreach (self::$config->getAPackages() as $package => $aData) {
            $version = $aData['version'];
            if ($aData['uninstallfirst']) {
                try {
                    if (self::$ioly->isInstalledInVersion($package, $version)) {
                        self::$ioly->uninstall($package, $version);
                    }
                } catch (Exception $ex) {
                    echo "\nError un-installing package '$package': " . $ex->getMessage();
                }
            }
            if (!self::$ioly->isInstalledInVersion($package, $version)) {
                try {
                    self::$ioly->install($package, $version, $aData['preserveFiles']);
                    echo "\nPackage: $package installed in version: $version";
                    // patch .gitignore?
                    if (self::$config->isPatchGitIngore()) {
                        self::patchGitIgnore($package, $version, $aData['preserveFiles']);
                    }
                } catch (Exception $ex) {
                    echo "\nError installing package '$package': " . $ex->getMessage();
                }
            } else {
                echo "\nPackage $package already installed in version: $version";
            }
            ob_flush();
        }
    }

    /**
     * Add ignore entries to .gitignore
     * @param string $package       The module package
     * @param string $version       The module version
     * @param bool   $preserveFiles Preserve available files in dir?
     */
    protected static function patchGitIgnore($package, $version, $preserveFiles)
    {
        // get all module files from ioly
        $aModuleFiles = self::$ioly->getFileList($package, $version);
        // do not preserve files, so add complete module dir to .gitignore
        if (!$preserveFiles) {
            $modulePath = '';
            foreach ($aModuleFiles as $file => $sha) {
                if (($pos = strpos($file, "/metadata.php")) !== false) {
                    $modulePath = substr($file, 0, $pos+1);
                }
            }
            if ($modulePath != '') {
                // remove leading slash
                if (substr($modulePath, 0, 1) == '/') {
                    $modulePath = substr($modulePath, 1);
                }
                echo "\nmodulePath: $modulePath";
                $data = "\n# added by IolyInstaller for package {$package}, version {$version}\n" . $modulePath . "\n#end package {$package}\n";
                $filePath = dirname(__FILE__) . '/.gitignore';
                $contents = "";
                if (file_exists($filePath)) {
                    $contents = file_get_contents($filePath);
                }
                if (strpos($contents, $modulePath) === false) {
                    file_put_contents($filePath, $data, FILE_APPEND | LOCK_EX);
                }
            }
        } else {
            // preserve existing files and
            // add every single file installed by ioly, so that only preserved files are visible in git!
            $data = "\n# added by IolyInstaller for package {$package}, version {$version}\n";
            $filePath = dirname(__FILE__) . '/.gitignore';
            $contents = "";
            if (file_exists($filePath)) {
                $contents = file_get_contents($filePath);
            }
            foreach ($aModuleFiles as $file => $sha) {
                if (substr($file, 0, 1) == '/') {
                    $file = substr($file, 1);
                }
                if (strpos($contents, $file) === false) {
                    $data .= $file . "\n";
                }
                echo "\nmoduleFile: $file";
            }
            $data .= "#end package {$package}\n";
            file_put_contents($filePath, $data, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Activate ALL modules, except those blacklisted
     */
    protected static function activateModulesExceptBlacklisted()
    {
        /**********************************************
         * ACTIVATE MODULES IN DIR EXCEPT BLACKLISTED?
         *********************************************/

        echo "\n\n/**********************************************";
        echo "\nACTIVATING MODULES ...";
        echo "\n/**********************************************\n";
        ob_flush();
        foreach (self::$aModules as $sModuleId => $oModule) {
            echo "\nChecking Module: $sModuleId";
            $sFilteredIds = self::getFilteredShopIdsForModule($sModuleId);
            if ($sFilteredIds != '') {
                self::activateModule($sModuleId, $sFilteredIds);
                echo "\nPackage: $sModuleId activated in subshops: $sFilteredIds ";
            }
            ob_flush();
        }
    }

    /**
     * Activate only whitelisted modules
     */
    protected static function activateModulesWhitelisted()
    {
        echo "\n\n/**********************************************";
        echo "\nACTIVATING MODULES ...";
        echo "\n/**********************************************\n";
        ob_flush();
        $aShopIds = self::getShopIdsFromString(self::$config->getShopIds());
        foreach ($aShopIds as $shopId) {
            if (!self::$config->getAModuleWhiteList($shopId)) {
                echo "\nNo whitelist for subshop: $shopId ";
                continue;
            }
            foreach (self::$config->getAModuleWhiteList($shopId) as $sModuleId) {
                self::activateModule($sModuleId, $shopId);
                echo "\nPackage: $sModuleId activated in subshop: $shopId ";
                ob_flush();
            }
        }
    }

    /**
     * Activate module for deployment
     * @param string $sModuleId
     * @param string $sFilteredIds
     * @param bool   $deactivateFirst
     * @return bool
     */
    protected static function activateModule($sModuleId, $sFilteredIds, $deactivateFirst = false)
    {
        $activated = false;
        $oConfig = \oxRegistry::getConfig();
        $aShopIds = self::getShopIdsFromString($sFilteredIds);
        foreach ($aShopIds as $shopId) {
            try {
                $oConfig->setShopId($shopId);

                $oModule = oxNew('oxModule');
                if ($oModule->load($sModuleId)) {
                    echo "\nLoaded $sModuleId module, trying to activate ...";
                    /** @var oxModuleCache $oModuleCache */
                    $oModuleCache = oxNew('oxModuleCache', $oModule);
                    /** @var \oxModuleInstaller $oModuleInstaller */
                    $oModuleInstaller = oxNew('oxModuleInstaller', $oModuleCache);
                    if ($deactivateFirst && $oModuleInstaller->deactivate($oModule)) {
                        echo "\nDeactivated $sModuleId in Shop $shopId";
                    }
                    if ($oModuleInstaller->activate($oModule)) {
                        echo "\nActivated $sModuleId in Shop $shopId";
                        $activated = true;
                    } else {
                        echo "\n***** PROBLEM ACTIVATING $sModuleId IN SHOP $shopId!!! *****";
                        $activated = false;
                    }
                } else {
                    echo "\n***** PROBLEM LOADING $sModuleId IN SHOP $shopId!!! *****";
                    $activated = false;
                }
                ob_flush();
            } catch (\Exception $ex) {
                echo "\nError activating module '$sModuleId': " . $ex->getMessage() . "\n" . $ex->getTraceAsString();
            }
        }
        return $activated;
    }

    /**
     * change shop mall URLs etc. in DB!
     */
    protected static function updateConfig()
    {
        if (self::$domainsLocal && is_array(self::$domainsLocal)) {
            foreach (self::$domainsLocal as $shopId => $mallUrl) {
                $oConfig = \oxRegistry::getConfig();
                $oConfig->setShopId($shopId);
                $sUrl = 'http://' . $mallUrl;
                if (self::$portLocal != '80') {
                    $sUrl .= ":" . self::$portLocal;
                }
                echo "\nSetting mall url for shopid: $shopId to $sUrl";
                $oConfig->saveShopConfVar('str', 'sMallShopURL', $sUrl, $shopId);
                $oConfig->saveShopConfVar('str', 'sMallSSLShopURL', $sUrl, $shopId);
                // no varnish!?
                if (self::$config->isBlDisableVarnish()) {
                    $oConfig->saveShopConfVar('bool', 'blReverseProxyActive', 'false', $shopId);
                }

                if (self::$config->isBlSetLangUrls()) {
                    // lang URLs! use the same URL for all languages for now ...
                    $aLangIds = \oxRegistry::getLang()->getAllShopLanguageIds();
                    $aUrls = array();
                    foreach ($aLangIds as $idx => $sLangId) {
                        echo "\nLangId: $sLangId";
                        $aUrls[] = $sUrl;
                    }
                    echo "\nAll lang URLs: " . print_r($aUrls, true);
                    $oConfig->saveShopConfVar('arr', 'aLanguageURLs', $aUrls);
                    $oConfig->saveShopConfVar('arr', 'aLanguageSSLURLs', $aUrls);
                }
                ob_flush();
            }
        }
    }

    /**
     * Set Shoptifind configuration
     */
    protected static function setupShoptifind()
    {
        $oConfig = \oxRegistry::getConfig();
        if (self::$shoptifindDataLocal && is_array(self::$shoptifindDataLocal)) {
            foreach (self::$shoptifindDataLocal as $shopId => $aShoptifindData) {
                $oConfig->setShopId($shopId);
                echo "\nSetting Shoptifind vars for Shop $shopId - Host: " . $aShoptifindData['shoptifindHost'] . " Port: " . $aShoptifindData['shoptifindPort'] . " AppPath: " . print_r($aShoptifindData['shoptifindAppPath'], true);
                $oConfig->saveShopConfVar('str', 'shoptifindHost', $aShoptifindData['shoptifindHost'], $shopId);
                $oConfig->saveShopConfVar('str', 'shoptifindPort', $aShoptifindData['shoptifindPort'], $shopId);
                $oConfig->saveShopConfVar('aarr', 'shoptifindAppPath', $aShoptifindData['shoptifindAppPath'], $shopId);
                ob_flush();
            }
        }
    }

    /**
     * Process LESS files
     * special - copy less files from "less/" to "less_mirror/" folders ...
     */
    protected static function updateLess()
    {
        if (file_exists(dirname(__FILE__) . "/updateLessMirror.php")) {
            echo "\nCopying less files ...";
            include dirname(__FILE__) . "/updateLessMirror.php";
            ob_flush();
        }
    }

    /**
     * Final cleanup and preparation
     */
    protected static function finish()
    {
        self::cleanup();
        self::updateLess();
        self::handleStatusFile(false);

        $endTime = microtime(true) - self::$_startTime;
        echo "\n\n/*************************************************************";
        echo "\nIOLY SHOP SETUP DONE! Time taken: $endTime seconds";
        echo "\n/*************************************************************\n";
    }

    /**
     * Get shop id array from string
     * @param string $sShopIds
     *
     * @return array
     */
    protected static function getShopIdsFromString($sShopIds)
    {
        $aShopIds = array();
        if ($sShopIds == "all") {
            $aShopIds = \oxRegistry::getConfig()->getShopIds();
        } elseif (strpos($sShopIds, ",") !== false) {
            $aShopIds = explode(",", $sShopIds);
        } else {
            // single shopid
            if (trim($sShopIds) != '') {
                $aShopIds[] = $sShopIds;
            }
        }
        return $aShopIds;
    }
    /**
     * Filter modules per shopid based on module blacklist
     * @param string $sModuleId
     *
     * @return string
     */
    protected static function getFilteredShopIdsForModule($sModuleId)
    {
        $aShopIds = self::getShopIdsFromString(self::$config->getShopIds());
        $sFilteredIds = array();
        foreach ($aShopIds as $sShopId) {
            // check if module is blacklisted for this shopid
            if (!self::$config->getAModulesBlacklist($sShopId) || !in_array($sModuleId, self::$config->getAModulesBlacklist($sShopId))) {
                $sFilteredIds[] = $sShopId;
            }
        }
        return implode(",", $sFilteredIds);
    }
    /**
     * Empty tmp dir
     */
    protected static function emptyTmp()
    {
        echo "\nClearing tmp ... ";
        $msg = "";
        $tmpdir = \oxRegistry::getConfig()->getConfigParam('sCompileDir');
        $d = opendir($tmpdir);
        while (($filename = readdir($d)) !== false) {
            // keep .htaccess and .gitignore files in tmp/**
            if (strpos($filename, ".htaccess") !== false || strpos($filename, ".gitignore") !== false) {
                continue;
            }
            $filepath = $tmpdir . $filename;
            if (is_file($filepath)) {
                $msg .= "\nDeleting $filepath ...";
                unlink($filepath);
            }
        }
        $msg .= "\nTmp clean!!";
        echo $msg;
    }

    /**
     * Create views
     * @param array $aShopIds
     */
    protected static function generateViews($aShopIds)
    {
        echo "\nGenerating views for shopids: " . $aShopIds;

        if (!is_array($aShopIds)) {
            $aShopIds = self::getShopIdsFromString($aShopIds);
        }
        echo "\nshopid array: " . print_r($aShopIds, true);
        $msg = "";
        $oShop = oxNew('oxShop');
        $oShop->generateViews();
        foreach ($aShopIds as $sShopId) {
            $oShop->load($sShopId);
            $msg .= "\nGenerating views for ShopID $sShopId ...";
            $oShop->generateViews();
        }

        // Admin (tools_list.php) still uses this:
        $oMetaData = oxNew('oxDbMetaDataHandler');
        $blViewSuccess = $oMetaData->updateViews();

        $msg .= "\nViews generated: $blViewSuccess";
        echo $msg;
        ob_flush();
    }

    /**
     * Testing version ...
     * @param array $aShopIds
     */
    protected static function generateViews2($aShopIds)
    {
        echo "\nGenerating views for shopids: " . $aShopIds;

        if (!is_array($aShopIds)) {
            $aShopIds = self::getShopIdsFromString($aShopIds);
        }
        echo "\nshopid array: " . print_r($aShopIds, true);
        $msg = "";
        $currShopId = \oxRegistry::getConfig()->getShopId();
        foreach ($aShopIds as $sShopId) {
            $msg .= "\nGenerating views for ShopID $sShopId ...";

            // set shop id
            \oxRegistry::getConfig()->setShopId($sShopId);
            // reset table description cache
            \oxDb::getInstance()->resetTblDescCache();

            // Admin (tools_list.php) still uses this:
            $oMetaData = \oxRegistry::get('oxDbMetaDataHandler');
            // we need to set this to really generate language specific views
            $aMultiTables = \oxRegistry::getConfig()->getConfigParam('aMultiLangTables');
            if (!in_array('oxarticles', $aMultiTables)) {
                $aMultiTables = array_merge(array('oxarticles','oxartextends','oxfield2shop', 'oxshops'), $aMultiTables);
            }
            $msg .= "\nMultilang tables: " . print_r($aMultiTables, true);
            $oMetaData->getConfig()->setConfigParam('aMultiLangTables', $aMultiTables);
            $blViewSuccess = $oMetaData->updateViews();
            $msg .= "\nViews generated: $blViewSuccess";
        }
        // set shop id back
        \oxRegistry::getConfig()->setShopId($currShopId);

        echo $msg;
        ob_flush();
    }
}
IolyInstaller::constructStatic();
