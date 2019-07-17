<?php
/**
 * Copyright © 2016 ToBai. All rights reserved.
 */
namespace Tobai\GeoStoreSwitcher\Plugin;

use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Tobai\GeoStoreSwitcher\Model\Config\Backend\ScopeConfig as BackendScopeConfig;

class StoreSwitchAction
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Tobai\GeoStoreSwitcher\Model\GeoStore\Switcher
     */
    private $geoStoreSwitcher;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    private $resultFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $requestHelper;

    private $configGeneral;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Tobai\GeoStoreSwitcher\Model\GeoStore\Switcher $geoStoreSwitcher
     * @param \Tobai\GeoStoreSwitcher\Model\Config\ScopeCodeResolver $scopeCodeResolver
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Tobai\GeoStoreSwitcher\Model\GeoStore\Switcher $geoStoreSwitcher,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\App\RequestInterface $requestHelper,
        \Tobai\GeoStoreSwitcher\Model\Config\General $configGeneral
    ) {
        $this->storeManager     = $storeManager;
        $this->geoStoreSwitcher = $geoStoreSwitcher;
        $this->resultFactory    = $resultFactory;
        $this->requestHelper    = $requestHelper;
        $this->configGeneral    = $configGeneral;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function afterExecute($subject, $result)
    {
        $storeLanguageCookie = $this->requestHelper->getCookie('storelanguage');
        if ( ! isset($storeLanguageCookie) && $this->configGeneral->isAvailable()) {
            $targetStoreId = $this->getStoreIdBasedOnIP();
            $currentStore  = $this->storeManager->getStore();
            if ($targetStoreId && ($currentStore->getId() != $targetStoreId)) {
                $redirectUrl = rtrim($this->storeManager->getStore($targetStoreId)->getUrl(),'/') . $this->requestHelper->getPathInfo();
                $redirect    = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
                $result      = $redirect->setUrl($redirectUrl);
            }
        }
        return $result;
    }

    /**
     * @return int|null
     */
    protected function getStoreIdBasedOnIp()
    {
        $this->geoStoreSwitcher->initCurrentStore();
        $storeId = $this->geoStoreSwitcher->getCurrentStoreId();
        return $storeId;
    }
}
