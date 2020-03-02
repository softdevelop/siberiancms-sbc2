<?php

class Job_Api_PlaceController extends Api_Controller_Default {

    public function createAction() {

        if($data = $this->getRequest()->getPost()) {

            try {

                $place = new Admin_Model_Admin();
                $email_checker = new Admin_Model_Admin();

                if(!empty($data['user_id'])) {
                    throw new Exception($this->_("Unable to update a user from here."));
                }
                if(empty($data['email'])) {
                    throw new Exception($this->_("The email is required"));
                }

                $email_checker->find($data['email'], 'email');
                if($email_checker->getId()) {
                    throw new Exception($this->_("This email address is already used"));
                }

                if(!isset($data['password'])) {
                    throw new Exception($this->_('The password is required'));
                }

                $admin->addData($data)
                    ->setPassword($data["password"])
                    ->save()
                ;

                $data = [
                    "success" => 1,
                    "user_id" => $admin->getId(),
                    "token" => $admin->getLoginToken()
                ];

            } catch(Exception $e) {
                $data = [
                    'error' => 1,
                    'message' => $e->getMessage()
                ];
            }

            $this->_sendJson($data);

        }

    }

    public function updateAction() {

        if($data = $this->getRequest()->getPost()) {

            try {

                if(isset($data["id"])) unset($data["id"]);

                $admin = new Admin_Model_Admin();

                if(!empty($data["user_id"])) {

                    $admin->find($data["user_id"]);
                    if(!$admin->getId()) {
                        throw new Exception($this->_("This admin does not exist"));
                    }

                }

                if(!empty($data["email"])) {

                    $email_checker = new Admin_Model_Admin();
                    $email_checker->find($data['email'], 'email');

                    if($email_checker->getId() AND $email_checker->getId() != $admin->getId()) {
                        throw new Exception($this->_("This email address is already used"));
                    }

                }

                $admin->addData($data);

                if(isset($data['password'])) {
                    $admin->setPassword($data["password"]);
                }

                $admin->save();

                $data = [
                    "success" => 1,
                    "user_id" => $admin->getId(),
                    "token" => $admin->getLoginToken()
                ];

            } catch(Exception $e) {
                $data = [
                    'error' => 1,
                    'message' => $e->getMessage()
                ];
            }

            $this->_sendJson($data);

        }

    }

    public function forgotpasswordAction() {

        if($data = $this->getRequest()->getPost()) {

            try {

                if(empty($data['email'])) {
                    throw new Exception($this->_('Please enter your email address'));
                }

                $admin = new Admin_Model_Admin();
                $admin->findByEmail($data['email']);

                if(!$admin->getId()) {
                    throw new Exception($this->_("This email address does not exist"));
                }

                $password = Core_Model_Lib_String::generate(8);

                $admin->setPassword($password)->save();

                $layout = $this->getLayout()->loadEmail('admin', 'forgot_password');
                $subject = $this->_('%s - Your new password');
                $layout->getPartial('content_email')->setPassword($password);

                $content = $layout->render();

                # @version 4.8.7 - SMTP
                $mail = new Siberian_Mail();
                $mail->setBodyHtml($content);
                $mail->addTo($admin->getEmail(), $admin->getName());
                $mail->setSubject($subject, ["_sender_name"]);
                $mail->send();

                $data = ["success" => 1];

            }
            catch(Exception $e) {
                $data = [
                    'error' => 1,
                    'message' => $e->getMessage()
                ];
            }

            $this->_sendJson($data);

        }
    }

    public function isloggedinAction() {

        if($data = $this->getRequest()->getPost()) {

            try {
                $data = ["is_logged_in" => $this->getSession()->isLoggedIn()];
            } catch(Exception $e) {
                $data = [
                    "error" => 1,
                    "message" => $e->getMessage()
                ];
            }

            $this->_sendJson($data);

        }

    }

    public function autologinAction() {

        if($email = $this->getRequest()->getParam("email") AND $token = $this->getRequest()->getParam("token")) {

            try {

                $admin = new Admin_Model_Admin();
                $admin->find($email, "email");

                if(!$admin->getId()) {
                    throw new Exception($this->_("The user doesn't exist."));
                }

                if($admin->getLoginToken() != $token) {
                    throw new Exception($this->_("Authentication failed"));
                }

                $this->getSession()
                    ->setAdmin($admin)
                ;

                $this->_redirect("admin/application/list");

            } catch(Exception $e) {

            }
        }

    }

}
