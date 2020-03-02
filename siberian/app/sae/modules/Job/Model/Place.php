<?php

/**
 * Class Job_Model_Place
 *
 * @method Job_Model_Db_Table_Place getTable()
 */
class Job_Model_Place extends Core_Model_Default
{

    /**
     * Job_Model_Place constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Job_Model_Db_Table_Place';
        return $this;
    }

    /**
     * @param $values
     * @param $order
     * @param $params
     * @return mixed
     */
    public function findActive($values, $order, $params)
    {
        return $this->getTable()->findActive($values, $order, $params);
    }

    /**
     * @param $valueId
     * @param $values
     * @param array $params
     * @return mixed
     */
    public function findAllWithFilters($valueId, $values, $params = [])
    {
        return $this->getTable()->findAllWithFilters($valueId, $values, $params);
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return mixed
     */
    public function toggle()
    {
        $this->setIsActive(!$this->getIsActive())->save();

        return $this->getIsActive();
    }

    /**
     *
     */
    public function enable()
    {
        $this->is_active = true;
    }

    /**
     *
     */
    public function disable()
    {
        $this->is_active = false;
    }

    /**
     *
     */
    public function save()
    {
        parent::save();
    }

    /**
     * @param bool $relative
     * @return string
     */
    public function _getIcon()
    {
        return $this->__getBase64Image($this->getIcon());
    }

    /**
     * @param $base64
     * @param $option
     * @return $this
     */
    public function _setIcon($base64, $option)
    {
        $icon_path = $this->__setImageFromBase64($base64, $option, 300, 300);
        $this->setData("icon", $icon_path);

        return $this;
    }

    /**
     * @param bool $base64
     * @return string
     */
    public function _getBanner()
    {
        return $this->__getBase64Image($this->getBanner());
    }

    /**
     * @param $base64
     * @param $option
     * @return $this
     */
    public function _setBanner($base64, $option)
    {
        $banner_path = $this->__setImageFromBase64($base64, $option, 1200, 400);
        $this->setBanner($banner_path);

        return $this;
    }
}