<?php

class Padlock_ApplicationController extends Application_Controller_Default
{

    /**
     * @var array
     */
    public $cache_triggers = [
        "editpost" => [
            "tags" => [
                "app_#APP_ID#",
                "homepage_app_#APP_ID#"
            ],
        ]
    ];

    public function editpostAction()
    {

        if ($datas = $this->getRequest()->getPost()) {

            try {
                $application = $this->getApplication();

                // Test s'il y a un value_id
                if (empty($datas['value_id'])) {
                    throw new Siberian_Exception(__('An error occurred while saving. Please try again later.'));
                }

                // Récupère l'option_value en cours
                $option_value = new Application_Model_Option_Value();
                $option_value->find($datas['value_id']);

                // Test s'il y a embrouille entre la value_id en cours de modification et l'application en session
                if (!$option_value->getId() OR $option_value->getAppId() != $this->getApplication()->getId()) {
                    throw new Siberian_Exception(__('An error occurred while saving. Please try again later.'));
                }

                // Prépare le weblink
                $padlock = $option_value->getObject();
                if (!$padlock->getId()) {
                    $padlock->setValueId($datas['value_id']);
                }

                $value_ids = [];
                if (!empty($datas['app_is_locked'])) {
                    $application->setRequireToBeLoggedIn(1);
                } else {
                    $value_ids = !empty($datas['value_ids']) ? $datas['value_ids'] : [];
                    $application->setRequireToBeLoggedIn(0);
                }

                $unlock_by = null;
                if (!empty($datas['type_ids'])) {
                    sort($datas['type_ids']);
                    $unlock_by = join("|", $datas['type_ids']);
                }

                $unlock_code = null;
                if (isset($datas["qrcode_unlock_code"])) {

                    $unlock_code = $datas["qrcode_unlock_code"];

                    $dir_image = Core_Model_Directory::getBasePathTo("/images/application/" . $this->getApplication()->getId());

                    if (!is_dir($dir_image)) {
                        mkdir($dir_image, 0775, true);
                    }
                    if (!is_dir($dir_image . "/application")) {
                        mkdir($dir_image . "/application", 0775, true);
                    }
                    if (!is_dir($dir_image . "/application/padlock")) {
                        mkdir($dir_image . "/application/padlock", 0775, true);
                    }

                    $dir_image .= "/application/padlock/";
                    $image_name = "padlock_qrcode.png";

                    copy('https://api.qrserver.com/v1/create-qr-code/?color=000000&bgcolor=FFFFFF&data=sendback%3A' . $datas["qrcode_unlock_code"] . '&qzone=1&margin=0&size=200x200&ecc=L', $dir_image . $image_name);
                }

                $application
                    ->setUnlockBy($unlock_by)
                    ->setUnlockCode($unlock_code)
                    ->save();

                $allow_everyone = (int)!empty($datas['allow_all_customers_to_access_locked_features']);
                $application->setData('allow_all_customers_to_access_locked_features', $allow_everyone)->save();

                $padlock
                    ->setAppId($application->getId())
                    ->setDescription($datas["description"])
                    ->setValueIds($value_ids)
                    ->save();

                // Get (ALL) locked valueIds for the App!
                // We can have multiple padlocks ... not working really ok but ...
                $padlockValues = (new Padlock_Model_Value())->findAll(["app_id = ?" => $application->getId()]);
                $lockedFeatures = [];
                foreach ($padlockValues as $padlockValue) {
                    $lockedFeatures[] = $padlockValue->getValueId();
                }

                $this
                    ->getCurrentOptionValue()
                    ->touch()
                    ->expires(-1);

                $data = [
                    "success" => true,
                    "message" => __("Padlock saved"),
                    "valueIds" => $lockedFeatures,
                ];


            } catch (Exception $e) {
                $data = [
                    "error" => true,
                    "message" => $e->getMessage(),
                ];
            }

            $this->_sendJson($data);

        }

    }

}