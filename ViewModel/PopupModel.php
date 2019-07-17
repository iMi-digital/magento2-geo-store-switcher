<?php


namespace Tobai\GeoStoreSwitcher\ViewModel;


use Magento\Directory\Model\CountryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\TranslatedLists;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ResourceModel\Website\Collection as WebsiteCollection;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory as WebsiteCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Tobai\GeoIp2\Model\CountryInterface;

class PopupModel implements ArgumentInterface
{
    const DEFAULT_COUNTRY_CONFIG_PATH = 'general/country/default';

    /**
     * @var StoreManager
     */
    private $storeManager;

    /**
     * @var CountryInterface
     */
    private $countryHelper;

    /**
     * @var CountryFactory
     */
    private $countryFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var \Tobai\GeoStoreSwitcher\Model\Config\General
     */
    private $configGeneral;

    /**
     * LanguageSwitchModel constructor.
     *
     * @param WebsiteCollectionFactory $websiteCollectionFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param TranslatedLists $translatedLists
     * @param StoreManager $storeManager
     */
    public function __construct(
        StoreManager $storeManager,
        CountryInterface $countryHelper,
        CountryFactory $countryFactory,
        \Tobai\GeoStoreSwitcher\Model\Config\General $configGeneral,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager   = $storeManager;
        $this->countryHelper  = $countryHelper;
        $this->countryFactory = $countryFactory;
        $this->configGeneral  = $configGeneral;
        $this->scopeConfig    = $scopeConfig;
    }

    /**
     * @return StoreInterface|string|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCurrentStore(): StoreInterface
    {
        return $this->storeManager->getStore();
    }

    public function getStorename()
    {
        return $this->scopeConfig->getValue('general/store_information/name');
    }

    /**
     * @return false|string
     */
    public function getCountryCodeFromIpOrFromDefault()
    {
        $countryCode = $this->countryHelper->getCountryCode();
        if ( ! $countryCode) {
            $countryCode = $this->scopeConfig->getValue(self::DEFAULT_COUNTRY_CONFIG_PATH);
        }
        return $countryCode;
    }

    /**
     * @return \Magento\Directory\Model\Country
     */
    private function getCountryFromIp()
    {
        return $this->countryFactory->create()->loadByCode($this->getCountryCodeFromIpOrFromDefault());
    }

    /**
     * @return string
     */
    public function getCountryNameFromIp()
    {
        return $this->getCountryFromIp()->getName();
    }

    /**
     * @return bool
     */
    public function isModuleActive()
    {
        return $this->configGeneral->isAvailable();
    }
}