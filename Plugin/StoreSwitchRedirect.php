<?php
/**
 * Copyright © 2016 ToBai. All rights reserved.
 */
namespace Tobai\GeoStoreSwitcher\Plugin;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\InventoryImportExport\Model\Import\Serializer\Json as Serializer;

class StoreSwitchRedirect
{
    const GEO_STORE_SWITCH_COOKIE = 'geoIpRedirected';

    const YEAR_IN_SECONDS = 365 * 24 * 60 * 60;

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

    /**
     * @var \Tobai\GeoStoreSwitcher\Model\Config\General
     */
    private $configGeneral;

    /**
     * @var \Magento\Store\Model\StoreSwitcherInterface
     */
    private $storeSwitcher;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var int|null
     */
    private $targetStoreId;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    private $currentStore;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Tobai\GeoStoreSwitcher\Model\GeoStore\Switcher $geoStoreSwitcher
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Magento\Framework\App\RequestInterface $requestHelper
     * @param \Tobai\GeoStoreSwitcher\Model\Config\General $configGeneral
     * @param \Magento\Store\Model\StoreSwitcherInterface $storeSwitcher
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Serializer $serializer
     *
     * @param SessionManagerInterface $sessionManager
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Tobai\GeoStoreSwitcher\Model\GeoStore\Switcher $geoStoreSwitcher,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\App\RequestInterface $requestHelper,
        \Tobai\GeoStoreSwitcher\Model\Config\General $configGeneral,
        \Magento\Store\Model\StoreSwitcherInterface $storeSwitcher,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    ) {
        $this->storeManager = $storeManager;
        $this->geoStoreSwitcher = $geoStoreSwitcher;
        $this->resultFactory = $resultFactory;
        $this->requestHelper = $requestHelper;
        $this->configGeneral = $configGeneral;
        $this->storeSwitcher = $storeSwitcher;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
    }

    /**
     * @param \Magento\Framework\App\FrontControllerInterface $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     *
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     * @throws \Magento\Store\Model\StoreSwitcher\CannotSwitchStoreException
     */
    public function aroundDispatch(
        \Magento\Framework\App\FrontControllerInterface $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $storeLanguageCookie = $this->requestHelper->getCookie(self::GEO_STORE_SWITCH_COOKIE, null);
        if ( ! isset($storeLanguageCookie) && $this->configGeneral->isAvailable()) {
            $this->setCookie();
            $this->setTargetStoreIdBasedOnIp();
            $this->setCurrentStore();

            if ($this->shouldRedirect()) {
                $redirectUrl = $this->getRedirectUrl();
                $redirect = $this->getRedirect();

                return $redirect->setUrl($redirectUrl);
            }
        }

        return $proceed($request);
    }

    /**
     * @return void
     */
    protected function setTargetStoreIdBasedOnIp()
    {
        $this->geoStoreSwitcher->initCurrentStore();
        $this->targetStoreId = $this->geoStoreSwitcher->getCurrentStoreId();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Store\Model\StoreSwitcher\CannotSwitchStoreException
     */
    private function getRedirectUrl()
    {
        $targetStore = $this->storeManager->getStore($this->targetStoreId);
        $redirectUrl = $this->storeSwitcher->switch($this->currentStore, $targetStore, $targetStore->getCurrentUrl());

        $queryParam = '?';
        if (strpos($redirectUrl, '?') !== false) {
            $queryParam = '&';
        }

        return $redirectUrl . $queryParam . 'redirected=1';
    }

    /**
     * @return Redirect
     */
    private function getRedirect()
    {
        /** @var Redirect $redirect */
        $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $redirect->setHeader('Cache-Control', 'no-cache'); // This prevents the page cache from failing hard

        return $redirect;
    }

    /**
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    private function setCookie()
    {
        $metaData = $this->getCookieMetaData();
        $this->cookieManager->setPublicCookie(self::GEO_STORE_SWITCH_COOKIE, 1, $metaData);
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function setCurrentStore()
    {
        $this->currentStore = $this->storeManager->getStore();
    }

    /**
     * @return bool
     */
    private function shouldRedirect()
    {
        return $this->targetStoreId && ($this->currentStore->getId() != $this->targetStoreId);
    }

    /**
     * @return \Magento\Framework\Stdlib\Cookie\PublicCookieMetadata
     */
    private function getCookieMetaData()
    {
        return $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(self::YEAR_IN_SECONDS)
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());
    }
}
