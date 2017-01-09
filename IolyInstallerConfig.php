<?php
/**
 * ioly installer configuration file
 * @version 1.7.0
 * @package ioly
 * @author Stefan Moises <moises@shoptimax.de>
 * @copyright shoptimax GmbH, 2016-2017
 */
namespace ioly;

/**
 * Class IolyInstallerConfig
 * Use this as a template and put it into your shop root!
 *
 * @package ioly
 */
class IolyInstallerConfig
{
    /**
     * @var string
     */
    protected $mainShopVersion = '4.10';
    /**
     * @var bool
     * activate ALL modules in module dir, except blacklist?
     */
    protected $activateAllExceptBlacklist = true;
    /**
     * @var bool
     * set lang URLs?
     */
    protected $blSetLangUrls = true;
    /**
     * @var string
     * shop ids for module activation
     */
    protected $shopIds = 'all'; // "all" or comma separated, e.g. "1,2,4"
    /**
     * @var bool
     * disable varnish?
     */
    protected $blDisableVarnish = true;
    /**
     * Automatically patch .gitignore for auto-installed ioly modules?
     * @var bool
     */
    protected $patchGitIngore = true;

    /**********************************************
     * DEFINE MODULES BLACKLIST TO *NOT* ACTIVATE
     *********************************************/
    /**
     * @var array
     */
    protected $aModulesBlacklist = array(
        1 => array(
            'invoicepdf',
            'oxpspaymorrow',
            'oepaypal',
            'oethemeswitcher',
        ),
    );

    /**********************************************
     * OR DEFINE MODULES WHITELIST TO ACTIVATE
     *********************************************/
    /**
     * @var array
     */
    protected $aModuleWhiteList = array(
        /*
        1 => array(
        ),
        2 => array(
        ),
        */
    );

    /**********************************************
     * DEFINE IOLY MODULES TO DOWNLOAD/INSTALL
     *********************************************/
    /**
     * @var array
     */
    protected $aPackages = array(
        'ioly/ioly-oxid-connector' => array('version' => 'latest', 'preserveFiles' => false, 'uninstallfirst' => false, 'forcereinstall' => false),
        'acirtautas/oxidmoduleinternals' => array('version' => '0.3.1', 'preserveFiles' => false, 'uninstallfirst' => false, 'forcereinstall' => false),
        'vanillathunder/vtdevutils' => array('version' => 'legacy', 'preserveFiles' => false, 'uninstallfirst' => true, 'forcereinstall' => false),
        'jkrug/ocbcleartmp' => array('version' => '1.0.0-v47', 'preserveFiles' => false, 'uninstallfirst' => false, 'forcereinstall' => true),
    );

    /**
     * The path and token to the internal ioly cookbook to download the module packages
     * @var string
     */
    protected $cookbookPath = "http://your_own_cookbook.com/repository/archive.zip?ref=develop&private_token=abcdefg";



    /**********************************************
     * DO NOT CHANGE ANYTHING BELOW HERE
     *********************************************/
    /**
     * @return string
     */
    public function getMainShopVersion()
    {
        return $this->mainShopVersion;
    }

    /**
     * @param string $mainShopVersion
     */
    public function setMainShopVersion($mainShopVersion)
    {
        $this->mainShopVersion = $mainShopVersion;
    }

    /**
     * @return boolean
     */
    public function isActivateAllExceptBlacklist()
    {
        return $this->activateAllExceptBlacklist;
    }

    /**
     * @param boolean $activateAllExceptBlacklist
     */
    public function setActivateAllExceptBlacklist($activateAllExceptBlacklist)
    {
        $this->activateAllExceptBlacklist = $activateAllExceptBlacklist;
    }

    /**
     * @return boolean
     */
    public function isBlSetLangUrls()
    {
        return $this->blSetLangUrls;
    }

    /**
     * @param boolean $blSetLangUrls
     */
    public function setBlSetLangUrls($blSetLangUrls)
    {
        $this->blSetLangUrls = $blSetLangUrls;
    }

    /**
     * @return string
     */
    public function getShopIds()
    {
        return $this->shopIds;
    }

    /**
     * @param string $shopIds
     */
    public function setShopIds($shopIds)
    {
        $this->shopIds = $shopIds;
    }

    /**
     * @return boolean
     */
    public function isPatchGitIngore()
    {
        return $this->patchGitIngore;
    }

    /**
     * @param boolean $patchGitIngore
     */
    public function setPatchGitIngore($patchGitIngore)
    {
        $this->patchGitIngore = $patchGitIngore;
    }

    /**
     * @param string $sShopId
     * @return array
     */
    public function getAModulesBlacklist($sShopId)
    {
        if (!isset($this->aModulesBlacklist[$sShopId])) {
            return false;
        }
        return $this->aModulesBlacklist[$sShopId];
    }

    /**
     * @param array $aModulesBlacklist
     */
    public function setAModulesBlacklist($aModulesBlacklist)
    {
        $this->aModulesBlacklist = $aModulesBlacklist;
    }

    /**
     * @param string $sShopId
     * @return array
     */
    public function getAModuleWhiteList($sShopId)
    {
        if (!isset($this->aModuleWhiteList[$sShopId])) {
            return false;
        }
        return $this->aModuleWhiteList[$sShopId];
    }

    /**
     * @param array $aModuleWhiteList
     */
    public function setAModuleWhiteList($aModuleWhiteList)
    {
        $this->aModuleWhiteList = $aModuleWhiteList;
    }

    /**
     * @return array
     */
    public function getAPackages()
    {
        return $this->aPackages;
    }

    /**
     * @param array $aPackages
     */
    public function setAPackages($aPackages)
    {
        $this->aPackages = $aPackages;
    }

    /**
     * @return string
     */
    public function getCookbookPath()
    {
        return $this->cookbookPath;
    }

    /**
     * @param string $cookbookPath
     */
    public function setCookbookPath($cookbookPath)
    {
        $this->cookbookPath = $cookbookPath;
    }
    /**
     * @return boolean
     */
    public function isBlDisableVarnish()
    {
        return $this->blDisableVarnish;
    }

    /**
     * @param boolean $blDisableVarnish
     */
    public function setBlDisableVarnish($blDisableVarnish)
    {
        $this->blDisableVarnish = $blDisableVarnish;
    }
}
