<?php

/**
 * Class Api_Model_User
 *
 * @method $this setUsername(string $username)
 * @method $this setIsVisible(boolean $isVisible)
 * @method $this setAcl(string $jsonEncodedAcl)
 * @method $this setBearerToken(string $bearerToken)
 * @method integer getId()
 * @method string getBearerToken()
 */
class Api_Model_User extends Core_Model_Default {

    /**
     * @var string
     */
    protected $_db_table = Api_Model_Db_Table_User::class;

    /**
     * @param $access_key
     * @return bool
     */
    public function hasAccess($access_key) {
        $parts = explode('.', $access_key);
        $acl = Siberian_Json::decode($this->getAcl());
        foreach($parts as $level) {
            if(isset($acl[$level])) {
                if(is_array($acl[$level])) {
                    $acl = $acl[$level];
                } elseif($acl[$level] === true) {
                    return true;
                }
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * @param $password
     * @return $this
     * @throws Exception
     */
    public function setPassword($password) {
        if(strlen($password) < 6) {
            throw new Exception(__('The password must be at least 6 characters'));
        }
        $this->setData('password', $this->_encrypt($password));
        return $this;
    }

    /**
     * @param $password
     * @return bool
     */
    public function isSamePassword($password) {
        return $this->getPassword() == $this->_encrypt($password);
    }

    /**
     * @param $password
     * @return bool
     */
    public function authenticate($password) {
        return $this->_checkPassword($password);
    }

    /**
     * @param $password
     * @return string
     */
    private function _encrypt($password) {
        return sha1($password);
    }

    /**
     * @return string
     */
    public function _generateBearerToken () {
        $digest = sprintf("%s:%s:%s", $this->getUsername(), uniqid(), $this->getPassword());
        $bearer = sha1(base64_encode($digest));

        return $bearer;
    }

    /**
     * @param $password
     * @return bool
     */
    private function _checkPassword($password) {
        return ($this->getPassword() == $this->_encrypt($password));
    }

}
