<?php

class Form_Model_Db_Table_Section extends Core_Model_Db_Table {

    protected $_name = "form_section";
    protected $_primary = "section_id";

    /**
     * Recherche par value_id
     * 
     * @param int $value_id
     * @return object
     */
    public function findByValueId($value_id) {

        $select = $this->select()
            ->from(array('cc' => $this->_name))
            ->where('cc.value_id = ?', $value_id)
        ;

        return $this->fetchAll($select);
    }
}