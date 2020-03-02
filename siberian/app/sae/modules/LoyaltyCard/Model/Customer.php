<?php

/**
 * Class LoyaltyCard_Model_Customer
 *
 * @method LoyaltyCard_Model_Db_Table_Customer getTable()
 */
class LoyaltyCard_Model_Customer extends Core_Model_Default
{

    /**
     *
     */
    const TYPE_VALIDATE_POINT = 1;
    /**
     *
     */
    const TYPE_CLOSE_CARD = 2;

    /**
     * LoyaltyCard_Model_Customer constructor.
     * @param array $datas
     * @throws Zend_Exception
     */
    public function __construct($datas = [])
    {
        parent::__construct($datas);
        $this->_db_table = 'LoyaltyCard_Model_Db_Table_Customer';
    }

    /**
     * @return mixed|string
     */
    public static function getMessageCardIsLocked()
    {
        return parent::_('We are sorry, your card is temporarily blocked');
    }

    /**
     * @param $value_id
     * @param $customer_id
     * @return array
     * @throws Zend_Date_Exception
     * @throws Zend_Exception
     */
    public function findAllByOptionValue($value_id, $customer_id)
    {
        $tmp_cards = $this->getTable()->findAllByOptionValue($value_id, $customer_id);
        $cards = [];
        $remove_cards = false;

        if (!empty($tmp_cards)) {
            foreach ($tmp_cards as $tmp_card) {
                $card = new self;
                $card->setData($tmp_card->getData());
                $is_locked = false;
                if (!is_null($card->getLastError())) {
                    $now = $this->formatDate(null, 'y-MM-dd HH:mm:ss');
                    $date = new Zend_Date();
                    $date->setDate($card->getLastError(), "y-MM-dd HH:mm:ss");

                    $last_error = $date->addDay(1)->toString('y-MM-dd HH:mm:ss');
                    $is_locked = ($last_error > $now && $card->getNumberOfError() >= 3);
                    if (!($last_error > $now)) {
                        $card->setNumberOfError(0);
                    }
                }

                $card->setIsLocked($is_locked)->setId($card->getCustomerCardId());

                // Si la carte est bloquée, on ne conserve que celle là, on supprime les autres et on stop le traitement
                if ($is_locked) {
                    $cards = [$card];
                    break;
                } else {
                    $cards[] = $card;
                }
            }
        }

        return $cards;
    }

    /**
     * @param $value_id
     * @param $customer_id
     * @return $this
     * @throws Zend_Date_Exception
     */
    public function findLast($value_id, $customer_id)
    {
        $row = $this->getTable()->findLast($value_id, $customer_id);

        if ($row) {
            $this->setData($row->getData())
                ->setId($row->getCustomerCardId());

            $is_locked = false;
            if (!is_null($this->getLastError())) {
                $now = $this->formatDate(null, 'y-MM-dd HH:mm:ss');
                $date = new Zend_Date($this->getLastError());
                $last_error = $date->addDay(1)->toString('y-MM-dd HH:mm:ss');
                $is_locked = ($last_error > $now && $this->getNumberOfError() >= 3);
                if (!($last_error > $now)) {
                    $this->setNumberOfError(0);
                }
            }

            $this->setIsLocked($is_locked);

        }

        return $this;

    }

    /**
     * @param $value_id
     * @param $customer_id
     * @return $this
     */
    public function createCard($value_id, $customer_id)
    {

        $row = $this->getTable()->createCard($value_id, $customer_id);

        if ($row) {

            $this->setData($row->getData())
                ->setIsLocked(false)
                ->setId($row->getCustomerCardId());

        }

        return $this;

    }

    /**
     * @param $value_id
     * @param $customer_id
     * @param array $params
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function findAll($value_id, $customer_id = null, $params = [])
    {
        return $this->getTable()->findAll($value_id, $customer_id, $params);
    }

    /**
     * @param $password_id
     * @param $nbr
     * @param null $created_at
     * @return $this
     */
    public function createLog($password_id, $nbr, $created_at = null)
    {
        $log = new LoyaltyCard_Model_Customer_Log();
        $log->setCardId($this->getCardId())
            ->setCustomerId($this->getCustomerId())
            ->setPasswordId($password_id)
            ->setNumberOfPoints($nbr);
        if ($created_at) {
            $log->setCreatedAt($created_at);
        }
        $log->save();

        return $this;
    }

    /**
     * @return $this
     * @throws Zend_Date_Exception
     */
    public function addError()
    {

        $now = $this->formatDate(null, 'y-MM-dd HH:mm:ss');
        $date = new Zend_Date($this->getLastError());
        $last_error = $date->addDay(1)->toString('y-MM-dd HH:mm:ss');
        if ($last_error < $now) $nbr = 1;
        else $nbr = (int)$this->getNumberOfError() + 1;

        $last_error = $this->formatDate(null, 'y-MM-dd HH:mm:ss');
        $this->setNumberOfError($nbr)->setLastError($last_error)->save();
        return $this;
    }

    /**
     *
     */
    public function save()
    {

        if ($this->getCustomerCardId() == 0) $this->setCustomerCardId(null)->setId(null);
        parent::save();
    }

}