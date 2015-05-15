<?php

/**
 * Class PrestaShop Exception
 */
class PrestaShopException extends PrestaShopExceptionCore
{
    /**
     * Log the error on the disk
     */
    protected function logError()
    {
        $logger = new FileLogger();
        $logger->setFilename(_PS_ROOT_DIR_ . '/log/' . date('Ymd') . '_exception.log');
        $logger->logException($this->getExtendedMessage(false), $this);
    }
}
