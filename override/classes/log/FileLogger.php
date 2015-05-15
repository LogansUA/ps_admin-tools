<?php

include_once(_PS_MODULE_DIR_ . '/admintools/libs/rollbar-php/src/rollbar.php');

/**
 * Class FileLogger
 */
class FileLogger extends FileLoggerCore
{

    const DEBUG = 0;

    const INFO = 1;

    const WARNING = 2;

    const ERROR = 3;

    /**
     * Constructor
     *
     * @param int $level
     */
    public function __construct($level = self::INFO)
    {
        if (Configuration::get('PS_ROLLBAR_ENABLER_VALUE')) {
            Rollbar::init(array(
                'access_token' => Configuration::get('PS_ROLLBAR_ACCESS_TOKEN')
            ));
        }

        parent::__construct($level);
    }

    /**
     * Write the message in the log file
     *
     * @param string $message
     * @param int    $level
     *
     * @return bool
     */
    protected function logMessage($message, $level)
    {
        if (Configuration::get('PS_ROLLBAR_ENABLER_VALUE')) {
            switch ($level) {
                case self::DEBUG:
                case self::INFO:
                    Rollbar::report_message($message, 'info');
                    break;

                case self::ERROR:
                    Rollbar::report_message($message, 'error');
                    break;
                case self::WARNING:
                    Rollbar::report_message($message, 'warning');
                    break;
                default:
                    Rollbar::report_message($message);
                    break;
            }
        }

        parent::logMessage($message, $level);
    }

    /**
     * Log exception
     *
     * @param string               $message
     * @param \PrestaShopException $exception
     */
    public function logException($message, $exception)
    {
        parent::logMessage($message, self::ERROR);

        if (Configuration::get('PS_ROLLBAR_ENABLER_VALUE')) {
            Rollbar::report_exception($exception);
        }
    }
}

