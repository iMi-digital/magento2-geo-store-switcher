<?php


namespace Tobai\GeoStoreSwitcher\Model\Observers;

use \Magento\Framework\Event\ObserverInterface;

class ResponseBefore implements ObserverInterface
{
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Tobai\GeoStoreSwitcher\Model\GeoStore\Switcher $geoStoreSwitcher,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\App\RequestInterface $requestHelper,
        \Tobai\GeoStoreSwitcher\Model\Config\General $configGeneral,
        \Magento\Store\ViewModel\SwitcherUrlProvider $switcherUrlProvider
    ) {
        $this->storeManager = $storeManager;
        $this->geoStoreSwitcher = $geoStoreSwitcher;
        $this->resultFactory = $resultFactory;
        $this->requestHelper = $requestHelper;
        $this->configGeneral = $configGeneral;
        $this->switcherUrlProvider = $switcherUrlProvider;
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

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->requestHelper->getModuleName() == 'stores') { // prevent endless loops
            return;
        }

        $storeLanguageCookie = $this->requestHelper->getCookie('storelanguage');
        if ( ! isset($storeLanguageCookie) && $this->configGeneral->isAvailable()) {
            $targetStoreId = $this->getStoreIdBasedOnIP();
            $currentStore  = $this->storeManager->getStore();
            if ($targetStoreId && ($currentStore->getId() != $targetStoreId)) {
                $targetStore = $this->storeManager->getStore($targetStoreId);
                $redirectUrl = $this->switcherUrlProvider->getTargetStoreRedirectUrl($targetStore);
                $redirect    = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
//                return $redirect->setUrl($redirectUrl);
                $observer->getResponse()->setRedirect($redirectUrl);
            }
        }
        return;

    }
}