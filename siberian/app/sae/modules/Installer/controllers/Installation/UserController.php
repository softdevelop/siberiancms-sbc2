<?php

/**
 * Class Installer_Installation_UserController
 */
class Installer_Installation_UserController extends Installer_Controller_Installation_Default
{

    public function createAction()
    {

        if ($data = $this->getRequest()->getPost()) {

            $users = array(new Backoffice_Model_User(), new Admin_Model_Admin());

            try {
                if (empty($data['email']) OR empty($data['password']) OR empty($data['confirm_password'])) {
                    throw new Exception(__('Please, fill out all fields'));
                }
                if (!Zend_Validate::is($data['email'], 'emailAddress')) {
                    throw new Exception(__('Please enter a valid email address'));
                }
                if ($data['password'] != $data['confirm_password']) {
                    throw new Exception(__("The entered password confirmation does not match the entered password."));
                }

                foreach ($users as $user) {
                    $user->setData($data)
                        ->setPassword($data['password'])
                        ->save();
                }

                $html = array('success' => 1);

            } catch (Exception $e) {
                $html = array('message' => $e->getMessage());
                $this->getResponse()->setHttpResponseCode(400);
            }

            $this->getLayout()->setHtml(Zend_Json::encode($html));
        }

    }

}