<?php

namespace PaymentCash\Model;

use Core\Model\Base;
use PaymentCash\Model\Application as CashApplication;
use Siberian\Exception;

/**
 * Class Application
 * @package PaymentStripe\Model
 *
 * @method integer getIsEnabled()
 */
class Application extends Base
{
    /**
     * Application constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = "PaymentCash\Model\Db\Table\Application";
        return $this;
    }

    /**
     * @param null $appId
     * @return Application|null
     * @throws Exception
     * @throws \Zend_Exception
     */
    public static function getSettings($appId = null)
    {
        // Checking $appId, and/or fallback on context application!
        if ($appId === null) {
            $application = self::getApplication();
            if (!$application &&
                !$application->getId()) {
                throw new Exception(p__("payment_cash", "An app id is required."));
            }

            $appId = $application->getId();
        }

        // Fetching current Stripe settings!
        $settings = (new self())->find($appId, "app_id");
        if (!$settings->getId()) {
            $settings = new CashApplication();
            $settings
                ->setAppId($appId)
                ->save();
        }

        return $settings;
    }

    /**
     * @param null $appId
     * @return bool
     */
    public static function isAvailable($appId = null)
    {
        return true;
    }

    /**
     * @param null $appId
     * @return bool
     */
    public static function isEnabled($appId = null)
    {
        try {
            $settings = self::getSettings($appId);
            return (boolean) filter_var($settings->getIsEnabled(), FILTER_VALIDATE_BOOLEAN);
        } catch (\Exception $e) {
            return false;
        }
    }
}