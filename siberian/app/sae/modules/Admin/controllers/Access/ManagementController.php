<?php

/**
 * Class Admin_Access_ManagementController
 */
class Admin_Access_ManagementController extends Admin_Controller_Default
{
    /**
     *
     */
    public function listAction()
    {
        $this->loadPartials();
    }

    /**
     *
     */
    public function newAction()
    {
        $this->_forward('edit');
    }

    /**
     *
     */
    public function editAction()
    {
        $request = $this->getRequest();
        $admin = new Admin_Model_Admin();
        $applications = [];
        $admin_id = $request->getParam('admin_id', false);

        /**
         * @var Admin_Model_Admin $currentAdmin
         */
        $currentAdmin = $this->getAdmin();
        $currentAdminId = $currentAdmin->getId();

        if ($admin_id) {
            $admin->find($admin_id);


            if (($admin->getId() || $admin->getParentId()) &&
                ($admin->getId() != $currentAdminId && $admin->getParentId() != $currentAdminId)
            ) {
                $this->getSession()->addError('This administrator does not exist');
                $this->redirect('admin/access_management/list');
            }
        }

        if ($admin->getParentId() &&
            $admin->getId() != $currentAdminId) {

            $application = new Application_Model_Application();
            $authorized_applications = $application->findAllByAdmin($request->getParam("admin_id"));
            $applications = $application->findAllByAdmin($currentAdminId);

            $data = ["app_ids" => [], "is_allowed_to_add_pages" => []];

            foreach ($applications as $application) {

                $application->setIsAllowedToEdit(false)
                    ->setIsAllowedToAddPages(false);

                foreach ($authorized_applications as $authorized_application) {
                    if ($application->getId() == $authorized_application->getId()) {
                        $application->setIsAllowedToEdit(true)
                            ->setIsAllowedToAddPages($authorized_application->getIsAllowedToAddPages());
                    }
                }
            }
        }

        $this->loadPartials();
        $this
            ->getLayout()
            ->getPartial('content')
            ->setEditAdmin($admin)
            ->setApplications($applications)
            ->setMode("management");
    }

    public function setapplicationtoadminAction()
    {

        if ($data = $this->getRequest()->getPost()) {

            try {

                if (empty($data["admin_id"]) OR empty($data["app_id"])) {
                    throw new Exception($this->_("1 An error occurred while saving. Please try again later."));
                }

                $admin = new Admin_Model_Admin();
                $admin->find($data["admin_id"]);

                if (($admin->getId() OR $admin->getParentId()) AND
                    ($admin->getId() != $this->getAdmin()->getId() AND
                        $admin->getParentId() != $this->getAdmin()->getId())
                ) {
                    $this->getSession()->addError("This administrator does not exist");
                }

                $application = new Application_Model_Application();
                $application->find($data["app_id"]);

                if (!$application->getId()) {
                    throw new Exception($this->_("2 An error occurred while saving. Please try again later."));
                }

                if (!$application->hasAsAdmin($this->getAdmin()->getId())) {
                    throw new Exception($this->_("3 An error occurred while saving. Please try again later."));
                }

                $is_selected = !empty($data["is_selected"]);
                $data = ["success" => 1];

                if ($is_selected) {
                    $data["is_allowed_to_add_pages"] = true;
                    $admin->setIsAllowedToAddPages(true);
                    $application->addAdmin($admin);
                } else {
                    $data["is_allowed_to_add_pages"] = false;
                    $application->removeAdmin($admin);
                }

            } catch (Exception $e) {
                $data = [
                    "error" => 1,
                    "message" => $e->getMessage()
                ];
            }

            $this->_sendHtml($data);
        }

    }

    public function setpermissionstoadminAction()
    {

        if ($data = $this->getRequest()->getPost()) {

            try {

                if (empty($data["admin_id"]) OR empty($data["app_id"])) {
                    throw new Exception($this->_("1An error occurred while saving. Please try again later."));
                }

                $admin = new Admin_Model_Admin();
                $admin->find($data["admin_id"]);

                if (($admin->getId() OR $admin->getParentId()) AND
                    ($admin->getId() != $this->getAdmin()->getId() AND
                        $admin->getParentId() != $this->getAdmin()->getId())
                ) {
                    $this->getSession()->addError("This administrator does not exist");
                }

                $application = new Application_Model_Application();
                $application->find($data["app_id"]);

                if (!$application->getId()) {
                    throw new Exception($this->_("2An error occurred while saving. Please try again later."));
                }

                if (!$application->hasAsAdmin($this->getAdmin()->getId())) {
                    throw new Exception($this->_("3An error occurred while saving. Please try again later."));
                }

                $admin->setIsAllowedToAddPages(!empty($data["is_selected"]));
                $application->addAdmin($admin);

                $data = ["success" => 1];

            } catch (Exception $e) {
                $data = [
                    "error" => 1,
                    "message" => $e->getMessage()
                ];
            }

            $this->_sendHtml($data);
        }

    }

    public function deleteAction()
    {

        if ($admin_id = $this->getRequest()->getParam('admin_id')) {

            try {

                $admin = new Admin_Model_Admin();
                $admin->find($admin_id);

                if (!$admin->getId()) {
                    throw new Exception($this->_("This administrator does not exist"));
                }

                $admin->delete();

                $html = [
                    'success' => 1,
                    'admin_id' => $admin_id
                ];

            } catch (Exception $e) {
                $html = [
                    'error' => 1,
                    'message' => $e->getMessage()
                ];
            }

            $this->_sendHtml($html);

        }

    }

}
