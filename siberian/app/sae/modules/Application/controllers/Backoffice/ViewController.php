<?php

/**
 * Class Application_Backoffice_ViewController
 */
class Application_Backoffice_ViewController extends Backoffice_Controller_Default
{
    /**
     * @var array
     */
    public $cache_triggers = [
        "save" => [
            "tags" => ["app_#APP_ID#"],
        ],
        "switchtoionic" => [
            "tags" => ["app_#APP_ID#"],
        ],
        "saveadvertising" => [
            "tags" => ["app_#APP_ID#"],
        ],
    ];

    /**
     *
     */
    public function loadAction()
    {
        $payload = [
            'title' => sprintf('%s > %s',
                __('Manage'),
                __('Application')
            ),
            "icon" => "fa-mobile",
            "ionic_message" => __("If your app is already published on the stores, be sure you have sent an update with the Ionic version, and that this update has already been accepted, otherwise your app may be broken.")
        ];

        $this->_sendJson($payload);
    }

    public function findAction()
    {
        $application = Application_Model_Application::getInstance();

        $admin = new Admin_Model_Admin();

        if (Siberian_Version::is("sae")) {
            $admins = $admin->findAll()->toArray();
            $admin_owner = $admin;
            $admin_owner->setData(current($admins));
        } else {
            $admins = $admin->getAllApplicationAdmins($this->getRequest()->getParam("app_id"));
            $admin_owner = $application->getOwner();
        }

        $admin_list = [];
        foreach ($admins as $admin) {
            $_dataAdmin = $admin;
            $_dataAdmin["key"] = sha1($admin["firstname"] . $admin["admin_id"]);

            $admin_list[] = $_dataAdmin;
        }

        $admin = [
            "name" => $admin_owner->getFirstname() . " " . $admin_owner->getLastname(),
            "email" => $admin_owner->getEmail(),
            "company" => $admin_owner->getCompany(),
            "phone" => $admin_owner->getPhone()
        ];


        $store_categories = Application_Model_Device_Ionic_Ios::getStoreCategeories();
        $devices = [];
        foreach ($application->getDevices() as $device) {
            $device->setName($device->getName());
            $device->setBrandName($device->getBrandName());
            $device->setStoreName($device->getStoreName());
            $device->setHasMissingInformation(
                !$device->getUseOurDeveloperAccount() &&
                (!$device->getDeveloperAccountUsername() || !$device->getDeveloperAccountPassword())
            );
            $data = $device->getData();

            $data["owner_admob_weight"] = (integer)$data["owner_admob_weight"];

            $devices[] = $data;
        }

        $data = [
            'admin' => $admin,
            'admin_list' => $admin_list,
            'app_store_icon' => $application->getAppStoreIcon(),
            'google_play_icon' => $application->getGooglePlayIcon(),
            'devices' => $devices,
            'url' => $application->getUrl(),
            'has_ios_certificate' => Push_Model_Certificate::getiOSCertificat(),
            'pem_infos' => Push_Model_Certificate::getInfos(),
        ];

        foreach ($store_categories as $name => $store_category) {
            if ($store_category->getId() == $application->getMainCategoryId()) {
                $data['main_category_name'] = $name;
            } else if ($store_category->getId() == $application->getSecondaryCategoryId()) {
                $data['secondary_category_name'] = $name;
            }
        }

        $data["bundle_id"] = $application->getBundleId();
        $data["package_name"] = $application->getPackageName();
        $data["is_active"] = $application->isActive();
        $data["is_locked"] = $application->isLocked();
        $data["can_be_published"] = $application->canBePublished();
        $data["owner_use_ads"] = !!$application->getOwnerUseAds();

        if ($application->getFreeUntil()) {
            $data["free_until"] = datetime_to_format($application->getFreeUntil(), Zend_Date::DATE_SHORT);
        }
        $data["android_sdk"] = Application_Model_Tools::isAndroidSDKInstalled();
        $data["apk"] = Application_Model_ApkQueue::getPackages($application->getId());
        $data["apk_service"] = Application_Model_SourceQueue::getApkServiceStatus($application->getId());
        $data["zip"] = Application_Model_SourceQueue::getPackages($application->getId());
        $data["queued"] = Application_Model_Queue::getPosition($application->getId());
        $data["confirm_message_domain"] = __("If your app is already published, changing the URL key or domain will break it. You will have to republish it. Change it anyway?");

        $application->addData($data);

        $data = [
            "application" => $application->getData(),
            'statuses' => Application_Model_Device::getStatuses(),
            'design_codes' => Application_Model_Application::getDesignCodes()
        ];

        $data["application"]["disable_battery_optimization"] = (boolean) $data["application"]["disable_battery_optimization"];

        //Set ios Autopublish informations
        $appIosAutopublish = new Application_Model_IosAutopublish();
        $appIosAutopublish->find(1);

        $languages = 'en';
        if ($lang = Siberian_Json::decode($appIosAutopublish->getLanguages())) {
            foreach ($lang as $code => $value) {
                if ($value) {
                    $languages = $code;
                    break;
                }
            }
        }

        // Sanitize vars
        if (is_null($data['infos']["want_to_autopublish"])) {
            $data['infos']["want_to_autopublish"] = false;
        }
        if (is_null($data['infos']["itunes_login"])) {
            $data['infos']["itunes_login"] = "";
        }
        if (is_null($data['infos']["itunes_password"])) {
            $data['infos']["itunes_password"] = "";
        }

        $accountType = "non2fa";
        $itunesLogin = $appIosAutopublish->getItunesLogin();

        $isFilled = mb_strlen($appIosAutopublish->getCypheredCredentials()) > 0;

        $data["ios_publish_informations"] = [
            "id" => $appIosAutopublish->getId(),
            "want_to_autopublish" => $appIosAutopublish->getWantToAutopublish(),
            "account_type" => $accountType,
            "itunes_login" => $itunesLogin,
            "itunes_original_login" => $appIosAutopublish->getItunesOriginalLogin(),
            "itunes_password" => $isFilled ? Application_Model_IosAutopublish::$fakePassword : "",
            "has_ads" => (bool)$appIosAutopublish->getHasAds(),
            "has_bg_locate" => (bool)$appIosAutopublish->getHasBgLocate(),
            "has_audio" => (bool)$appIosAutopublish->getHasAudio(),
            "languages" => $languages,
            "last_start" => $appIosAutopublish->getLastStart(),
            "last_success" => $appIosAutopublish->getLastSuccess(),
            "last_finish" => $appIosAutopublish->getLastFinish(),
            "last_build_status" => $appIosAutopublish->getLastBuildStatus(),
            "last_builded_version" => $appIosAutopublish->getLastBuildedVersion(),
            "last_builded_ipa_link" => $appIosAutopublish->getLastBuildedIpaLink(),
            "error_message" => $appIosAutopublish->getErrorMessage(),
            "teams" => $appIosAutopublish->getTeamsArray(),
            "itcProviders" => $appIosAutopublish->getItcProvidersArray(),
            "selected_team" => $appIosAutopublish->getTeamId(),
            "selected_team_name" => $appIosAutopublish->getTeamName(),
            "selected_provider" => $appIosAutopublish->getItcProvider(),
            "password_filled" => $isFilled,
            "stats" => $appIosAutopublish->getStats(),
        ];

        $this->_sendJson($data);

    }

    public function saveAction()
    {
        $request = $this->getRequest();
        if ($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                /** Unable to save angular application */
                if (isset($data["design_code"])) {
                    unset($data["design_code"]);
                }

                if (empty($data["app_id"])) {
                    throw new Exception(__("An error occurred while saving. Please try again later."));
                }

                $application = new Application_Model_Application();
                $application->find($data["app_id"]);

                if (!$application->getId()) {
                    throw new Exception(__("An error occurred while saving. Please try again later."));
                }

                if (isset($data["design_code"]) AND $application->getDesignCode() == Application_Model_Application::DESIGN_CODE_IONIC AND $data["design_code"] != Application_Model_Application::DESIGN_CODE_IONIC) {
                    throw new Exception(__("You can't go back to Angular."));
                }

                if (!empty($data["key"])) {

                    $module_names = array_map('strtolower', Zend_Controller_Front::getInstance()->getDispatcher()->getSortedModuleDirectories());
                    if (in_array($data["key"], $module_names)) {
                        throw new Exception(__("Your domain key \"%s\" is not valid.", $data["key"]));
                    }

                    $dummy = new Application_Model_Application();
                    $dummy->find($data["key"], "key");
                    if ($dummy->getId() AND $dummy->getId() != $application->getId()) {
                        throw new Exception(__("The key is already used by another application."));
                    }
                } else {
                    throw new Exception(__("The key cannot be empty."));
                }

                if (!empty($data["domain"])) {

                    $data["domain"] = str_replace(["http://", "https://"], "", $data["domain"]);

                    $tmp_url = str_replace(["http://", "https://"], "", $this->getRequest()->getBaseUrl());
                    $tmp_url = current(explode("/", $tmp_url));

                    $tmp_domain = explode("/", $data["domain"]);
                    $domain = current($tmp_domain);
                    if (preg_match('/^(www.)?(' . $domain . ')/', $tmp_url)) {
                        throw new Exception(__("You can't use this domain."));
                    } else {
                        $domain_folder = next($tmp_domain);
                        $module_names = array_map('strtolower', Zend_Controller_Front::getInstance()->getDispatcher()->getSortedModuleDirectories());
                        if (in_array($domain_folder, $module_names)) {
                            throw new Exception(__("Your domain key \"%s\" is not valid.", $domain_folder));
                        }
                    }

                    if (!Zend_Uri::check("http://" . $data["domain"])) {
                        throw new Exception(__("Please enter a valid URL"));
                    }

                    $dummy = new Application_Model_Application();
                    $dummy->find($data["domain"], "domain");
                    if ($dummy->getId() AND $dummy->getId() != $application->getId()) {
                        throw new Exception("The domain is already used by another application.");
                    }

                }

                if (!empty($data["package_name"])) {
                    $application->setPackageName($data["package_name"]);
                }

                if (!empty($data["bundle_id"])) {
                    $application->setBundleId($data["bundle_id"]);
                }

                if (empty($data["free_until"])) {
                    $data["free_until"] = null;
                } else {
                    $data["free_until"] = new Zend_Date($data["free_until"], "MM/dd/yyyy");
                    $data["free_until"] = $data["free_until"]->toString('yyyy-MM-dd HH:mm:ss');
                }

                if (array_key_exists("disable_battery_optimization", $data)) {
                    $val = filter_var($data["disable_battery_optimization"], FILTER_VALIDATE_BOOLEAN);
                    $application->setDisableBatteryOptimization($val ? 1 : 0);

                    unset($data["disable_battery_optimization"]);
                }

                $application->addData($data)->save();

                $data = [
                    "success" => 1,
                    "message" => __("Info successfully saved"),
                    "bundle_id" => $application->getBundleId(),
                    "package_name" => $application->getPackageName(),
                    "url" => $application->getUrl(),
                ];

            } catch (Exception $e) {
                $data = [
                    "error" => 1,
                    "message" => $e->getMessage()
                ];
            }

            $this->_sendHtml($data);
        }

    }

    public function switchionicAction()
    {
        if ($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                if (empty($data["app_id"])) {
                    throw new Exception(__("An error occurred while saving. Please try again later."));
                }

                if (isset($data["design_code"]) && $data["design_code"] != Application_Model_Application::DESIGN_CODE_IONIC) {
                    throw new Exception(__("You can't go back with Angular."));
                }

                $application = new Application_Model_Application();
                $application->find($data["app_id"]);

                if (!$application->getId()) {
                    throw new Siberian_Exception(__("Application id %s not found.", $data["app_id"]));
                }

                $application->setDesignCode(Application_Model_Application::DESIGN_CODE_IONIC);

                if ($design_id = $application->getDesignId()) {

                    $design = new Template_Model_Design();
                    $design->find($design_id);

                    if ($design->getId()) {
                        $application->setDesign($design);
                        Template_Model_Design::generateCss($application, false, false, true);
                    }

                }

                $application->save();

                $data = [
                    "success" => 1,
                    "message" => __("Your application is now switched to Ionic"),
                    "design_code" => "ionic",
                ];

            } catch (Exception $e) {
                $data = [
                    "error" => 1,
                    "message" => $e->getMessage()
                ];
            }

            $this->_sendHtml($data);
        }
    }


    public function savedeviceAction()
    {

        if ($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                if (empty($data["app_id"]) OR !is_array($data["devices"]) OR empty($data["devices"])) {
                    throw new Exception('#783-01: ' . __("An error occurred while saving. Please try again later."));
                }

                $application = new Application_Model_Application();
                $application->find($data["app_id"]);

                if (!$application->getId()) {
                    throw new Exception('#783-02: ' . __("An error occurred while saving. Please try again later."));
                }

                foreach ($data["devices"] as $device_data) {
                    if (!empty($device_data["store_url"])) {
                        if (stripos($device_data["store_url"], "http") === false) {
                            $device_data["store_url"] = "http://" . $device_data["store_url"];
                        }
                        if (!Zend_Uri::check($device_data["store_url"])) {
                            throw new Exception(__("Please enter a correct URL for the %s store", $device_data["name"]));
                        }
                    } else {
                        $device_data["store_url"] = null;
                    }

                    if (!preg_match("/^([0-9]+)(\.([0-9]{0,5})){0,4}$/", $device_data["version"])) {
                        throw new Exception(__("Please enter a correct version for the %s app", $device_data["name"]));
                    }

                    $device = $application->getDevice($device_data["type_id"]);
                    $device->addData($device_data)->save();
                }

                $data = [
                    "success" => 1,
                    "message" => __("Info successfully saved")
                ];

            } catch (Exception $e) {
                $data = [
                    "error" => 1,
                    "message" => $e->getMessage()
                ];
            }

            $this->_sendHtml($data);
        }

    }

    public function saveadvertisingAction()
    {

        if ($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                if (empty($data["app_id"]) OR !is_array($data["devices"]) OR empty($data["devices"])) {
                    throw new Exception(__("An error occurred while saving. Please try again later."));
                }

                $application = new Application_Model_Application();
                $application->find($data["app_id"]);

                if (!$application->getId()) {
                    throw new Exception(__("An error occurred while saving. Please try again later."));
                }

                $data_app_to_save = [
                    "owner_use_ads" => $data["owner_use_ads"]
                ];

                $application->addData($data_app_to_save)->save();

                foreach ($data["devices"] as $device_data) {
                    $device = $application->getDevice($device_data["type_id"]);
                    $data_device_to_save = [
                        "owner_admob_id" => $device_data["owner_admob_id"],
                        "owner_admob_interstitial_id" => $device_data["owner_admob_interstitial_id"],
                        "owner_admob_type" => $device_data["owner_admob_type"],
                        "owner_admob_weight" => $device_data["owner_admob_weight"]
                    ];
                    $device->addData($data_device_to_save)->save();
                }

                $data = [
                    "success" => 1,
                    "message" => __("Info successfully saved")
                ];

            } catch (Exception $e) {
                $data = [
                    "error" => 1,
                    "message" => $e->getMessage()
                ];
            }

            $this->_sendHtml($data);
        }
    }

    public function savebannerAction()
    {

        if ($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                if (empty($data["app_id"]) OR !is_array($data["devices"]) OR empty($data["devices"])) {
                    throw new Exception(__("An error occurred while saving. Please try again later."));
                }

                $application = new Application_Model_Application();
                $application->find($data["app_id"]);

                if (!$application->getId()) {
                    throw new Exception(__("An error occurred while saving. Please try again later."));
                }

                $data_app_to_save = [
                    "banner_title" => $data["banner_title"],
                    "banner_author" => $data["banner_author"],
                    "banner_button_label" => $data["banner_button_label"]
                ];

                $application->addData($data_app_to_save)->save();

                foreach ($data["devices"] as $device_data) {
                    $device = $application->getDevice($device_data["type_id"]);
                    $data_device_to_save = [
                        "banner_store_label" => $device_data["banner_store_label"],
                        "banner_store_price" => $device_data["banner_store_price"]
                    ];
                    $device->addData($data_device_to_save)->save();
                }

                $data = [
                    "success" => 1,
                    "message" => __("Info successfully saved")
                ];

            } catch (Exception $e) {
                $data = [
                    "error" => 1,
                    "message" => $e->getMessage()
                ];
            }

            $this->_sendHtml($data);
        }
    }

    /**
     * @throws Siberian_Exception
     * @throws Zend_Exception
     * @throws \Siberian\Exception
     */
    public function downloadsourceAction()
    {
        $request = $this->getRequest();
        try {
            $params = $request->getParams();
            if (empty($params)) {
                throw new \Siberian\Exception(__('Missing parameters for generation.'));
            }

            $application = new Application_Model_Application();

            if (empty($params['app_id']) OR empty($params['device_id'])) {
                throw new \Siberian\Exception('#908-00: ' . __('This application does not exist'));
            }

            $application->find($params['app_id']);
            if (!$application->getId()) {
                throw new \Siberian\Exception('#908-01: ' . __('This application does not exist'));
            }

            $mainDomain = __get('main_domain');
            if (empty($mainDomain)) {
                throw new \Siberian\Exception('#908-02: ' .
                    __('Main domain is required, you can set it in <b>Settings > General</b>'));
            }

            $application->setDesignCode('ionic');

            $application_id = $params['app_id'];
            $type = ($request->getParam('type') == 'apk') ? 'apk' : 'zip';
            $device = ($request->getParam('device_id') == 1) ? 'ios' : 'android';
            $noads = ($request->getParam('no_ads') == 1) ? 'noads' : '';
            $isApkService = $request->getParam('apk', false) === 'apk';
            $design_code = $request->getParam('design_code');
            $adminIdCredentials = $request->getParam('admin_id', 0);

            // Firebase Validation!
            if ($device === 'android') {
                $credentials = (new Push_Model_Firebase())
                    ->find($adminIdCredentials, 'admin_id');

                $credentials->checkFirebase();
            }

            if ($type == 'apk') {
                $queue = new Application_Model_ApkQueue();

                $queue->setAppId($application_id);
                $queue->setName($application->getName());
            } else {
                $queue = new Application_Model_SourceQueue();

                $queue->setAppId($application_id);
                $queue->setName($application->getName());
                $queue->setType($device . $noads);
                $queue->setDesignCode($design_code);
            }

            // New case for source to apk generator!
            if ($isApkService) {
                $queue->setIsApkService(1);
                $queue->setApkStatus('pending');
            }

            $queue->setHost($mainDomain);
            $queue->setUserId($this->getSession()->getBackofficeUserId());
            $queue->setUserType('backoffice');
            $queue->save();

            /** Fallback for SAE, or disabled cron */
            $reload = false;
            if (!Cron_Model_Cron::is_active()) {
                $cron = new Cron_Model_Cron();
                $value = ($type == 'apk') ? 'apkgenerator' : 'sources';
                $task = $cron->find($value, 'command');
                Siberian_Cache::__clearLocks();
                $siberian_cron = new Siberian_Cron();
                $siberian_cron->execute($task);
                $reload = true;
            }

            $more['apk'] = Application_Model_ApkQueue::getPackages($application->getId());
            $more['zip'] = Application_Model_SourceQueue::getPackages($application_id);
            $more['queued'] = Application_Model_Queue::getPosition($application_id);
            $more['apk_service'] = Application_Model_SourceQueue::getApkServiceStatus($application_id);

            $payload = [
                'success' => true,
                'message' => __('Application successfully queued for generation.'),
                'more' => $more,
                'reload' => $reload,
                'isApkService' => $isApkService,
            ];


        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    public function cancelqueueAction()
    {
        try {
            if ($data = $this->getRequest()->getParams()) {

                $application_id = $data['app_id'];
                $type = ($this->getRequest()->getParam("type") == "apk") ? "apk" : "zip";
                $device = ($this->getRequest()->getParam("device_id") == 1) ? "ios" : "android";
                $noads = ($this->getRequest()->getParam("no_ads") == 1) ? "noads" : "";

                Application_Model_Queue::cancel($application_id, $type, $device . $noads);

                $more["zip"] = Application_Model_SourceQueue::getPackages($application_id);
                $more["queued"] = Application_Model_Queue::getPosition($application_id);

                $data = [
                    "success" => 1,
                    "message" => __("Generation cancelled."),
                    "more" => $more,
                ];

            } else {
                $data = [
                    "error" => 1,
                    "message" => __("Missing parameters for cancellation."),
                ];
            }
        } catch (Exception $e) {
            $data = [
                "error" => 1,
                "message" => $e->getMessage(),
            ];
        }


        $this->_sendHtml($data);
    }

    public function uploadcertificateAction()
    {

        if ($app_id = $this->getRequest()->getParam("app_id")) {

            try {

                if (empty($_FILES) || empty($_FILES['file']['name'])) {
                    throw new Exception("No file has been sent");
                }

                $application = Application_Model_Application::getInstance();
                $app_id = $application->getId();

                $base_path = Core_Model_Directory::getBasePathTo("var/apps/iphone/");
                if (!is_dir($base_path)) mkdir($base_path, 0775, true);
                $path = Core_Model_Directory::getPathTo("var/apps/iphone/");
                $adapter = new Zend_File_Transfer_Adapter_Http();
                $adapter->setDestination(Core_Model_Directory::getTmpDirectory(true));

                if ($adapter->receive()) {

                    $file = $adapter->getFileInfo();

                    $certificat = new Push_Model_Certificate();
                    $certificat->find(['type' => 'ios', 'app_id' => $app_id]);

                    if (!$certificat->getId()) {
                        $certificat->setType("ios")
                            ->setAppId($app_id);
                    }

                    $new_name = uniqid("cert_") . ".pem";
                    if (!rename($file["file"]["tmp_name"], $base_path . $new_name)) {
                        throw new Exception(__("An error occurred while saving. Please try again later."));
                    }

                    $certificat->setPath($path . $new_name)
                        ->save();

                    $data = [
                        "success" => 1,
                        "pem_infos" => Push_Model_Certificate::getInfos($app_id),
                        "message" => __("The file has been successfully uploaded")
                    ];

                } else {
                    $messages = $adapter->getMessages();
                    if (!empty($messages)) {
                        $message = implode("\n", $messages);
                    } else {
                        $message = __("An error occurred during the process. Please try again later.");
                    }

                    throw new Exception($message);
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

}
