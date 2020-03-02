<?php

use Siberian\File;

/**
 * Class Installer_Backoffice_ModuleController
 */
class Installer_Backoffice_ModuleController extends Backoffice_Controller_Default
{
    /**
     * @var array
     */
    static $MODULES = [];

    /**
     * @var bool
     */
    public $increase_timelimit = false;

    /**
     *
     */
    public function loadAction()
    {
        if (class_exists('Core_Model_Statistics')) {
            $stats = new Core_Model_Statistics();
            $stats->statistics();
        }

        $payload = [
            'title' => sprintf('%s > %s',
                __('Settings'),
                __('Updates & Modules')),
            'icon' => 'fa-cloud-download',
            'words' => [
                'titleMajor' => __('Major update disclaimer, confirmation required!'),
                'confirmDelete' => __('Yes, Proceed to update!'),
                'cancelDelete' => __('No, go back!'),
                'mismatch' => __('The entered text mismatch'),
                'confirmKey' => 'yes-proceed-to-update-#VERSION#',
                'majorMessage' =>
                    "<b class=\"delete-warning\">" .
                    __("You are about to update to version %s, this version introduces breaking changes in the platform,", "#VERSION#") .
                    "<br />" .
                    __("before updating we strongly advise you to read the release note and the following document.") .
                    "</b>" .
                    "<br />" .
                    "<br />" .
                    "<a class=\"btn btn-primary\" href=\"https://updates02.siberiancms.com/release-notes/major/#VERSION#.html\">Version #VERSION# technical notes.</a>" .
                    "<br />" .
                    "<br />" .
                    __("To prevent accidental actions we ask you to confirm your intention.") .
                    "<br />" .
                    "Please type <code style=\"user-select: none;\">yes-proceed-to-update-#VERSION#</code> to continue or close this modal to cancel."
            ],
        ];

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function downloadupdateAction()
    {
        try {
            $fatalErrors = false;
            $_errors = [];

            if (function_exists('exec')) {
                // Testing zip/unzip !
                $base = Core_Model_Directory::getBasePathTo('/var/tmp/');
                $zip_file = Core_Model_Directory::getBasePathTo('/var/tmp/test.zip');
                $test_file = Core_Model_Directory::getBasePathTo('/var/tmp/test.file');

                if (file_exists($test_file)) {
                    unlink($test_file);
                }
                if (file_exists($zip_file)) {
                    unlink($zip_file);
                }

                try {
                    File::putContents($test_file, 'test');
                    chdir($base);
                    exec('zip test.zip test.file');
                    if (!file_exists($zip_file)) {
                        //$_errors[] = 'Please enable/add binary: zip & unzip';
                        //$fatalErrors = true;
                    } else {
                        // now test unzip
                        if (file_exists($test_file)) {
                            unlink($test_file);
                        }
                        exec('unzip test.zip');
                        if (!file_exists($test_file)) {
                            //$_errors[] = 'Please enable/add binary: unzip';
                            //$fatalErrors = true;
                        }
                    }
                } catch (Exception $e) {
                    $_errors[] = 'Please enable/add binary: zip';
                    $fatalErrors = true;
                } finally {
                    // Unlink files
                    if (file_exists($test_file)) {
                        unlink($test_file);
                    }
                    if (file_exists($zip_file)) {
                        unlink($zip_file);
                    }
                }
            } else {
                $_errors[] = 'Please enable/add function: exec()';
                $fatalErrors = true;
            }

            if ($fatalErrors) {
                throw new Siberian_Exception(implode(', ', $_errors));
            }

            set_time_limit(6000);
            ini_set('max_execution_time', 6000);
            ini_set('memory_limit', '512M');

            $data = $this->_fetchUpdates();

            if (empty($data['success'])) {
                throw new Siberian_Exception(__('An error occurred while loading. Please, try again later.'));
            }

            if (!empty($data['url']) && !empty($data['filename'])) {

                $tmp_path = Core_Model_Directory::getTmpDirectory(true) . '/' . $data['filename'];

                $client = new Zend_Http_Client($data['url'], [
                    'adapter' => 'Zend_Http_Client_Adapter_Curl',
                    'curloptions' => [CURLOPT_SSL_VERIFYPEER => false],
                ]);

                $client->setMethod(Zend_Http_Client::POST);

                if (Siberian_Version::TYPE === 'SAE') {
                    $client->setParameterPost('sae', 1);
                } else {
                    $licenseKey = System_Model_Config::getValueFor('siberiancms_key');
                    if (!$licenseKey) {
                        throw new Siberian_Exception(__('There is no CMS license key set.'));
                    }
                    $client->setParameterPost('licenseKey', $licenseKey);
                    $client->setParameterPost('host', $_SERVER['HTTP_HOST']);
                }

                $response = $client->request();

                if ($response->getStatus() == 200) {
                    $content = $response->getBody();

                    if (empty($content)) {
                        throw new Siberian_Exception(__('#100: Unable to fetch the update. Please, try again later.'));
                    }

                    File::putContents($tmp_path, $content);
                } else {
                    throw new Siberian_Exception(__('#101: Unable to fetch the update. Please, try again later.'));
                }

                if (!file_exists($tmp_path)) {
                    throw new Siberian_Exception(__('#102: Unable to fetch the update. Please, try again later.'));
                }

                $payload = $this->_getPackageDetails($tmp_path);
            } else {
                $payload = [
                    'success' => true,
                    'message' => __($data['message'])
                ];
            }
        } catch (Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function uploadAction()
    {
        try {
            // Demo version
            if (__getConfig('is_demo')) {
                throw new Exception("This is a demo version, no modules can be uploaded");
            }

            if (empty($_FILES) || empty($_FILES['file']['name'])) {
                throw new \Siberian\Exception(__("No file has been sent"));
            }

            $adapter = new Zend_File_Transfer_Adapter_Http();
            $adapter->setDestination(Core_Model_Directory::getTmpDirectory(true));

            if ($adapter->receive()) {
                $file = $adapter->getFileInfo();
                $payload = $this->_getPackageDetails($file['file']['tmp_name']);
            } else {
                $messages = $adapter->getMessages();
                if (!empty($messages)) {
                    $message = implode("\n", $messages);
                } else {
                    $message = __("An error occurred during the process. Please try again later.");
                }

                throw new \Siberian\Exception($message);
            }
        } catch (Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function checkpermissionsAction()
    {
        if ($file = $this->getRequest()->getParam("file")) {
            try {
                $filename = base64_decode($file);
                $file = Core_Model_Directory::getTmpDirectory(true) . "/$filename";

                if (!file_exists($file)) {
                    throw new \Siberian\Exception(__("The file %s does not exist", $filename));
                }

                $parser = new Installer_Model_Installer_Module_Parser();
                $is_ok = $parser->setFile($file)->checkPermissions();

                if (!$is_ok) {
                    $ftp_host = System_Model_Config::getValueFor("ftp_host");
                    $ftp_user = System_Model_Config::getValueFor("ftp_username");
                    $ftp_password = System_Model_Config::getValueFor("ftp_password");
                    $ftp_port = System_Model_Config::getValueFor("ftp_port");
                    $ftp_path = System_Model_Config::getValueFor("ftp_path");
                    $ftp = new Siberian_Ftp($ftp_host, $ftp_user, $ftp_password, $ftp_port, $ftp_path);

                    if ($ftp->checkConnection() && $ftp->isSiberianDirectory()) {
                        $is_ok = true;
                    }
                }

                if ($is_ok) {
                    $payload = [
                        'success' => true
                    ];
                } else {

                    $messages = $parser->getErrors();
                    $message = implode("\n", $messages);
                    throw new \Siberian\Exception(__($message));
                }

            } catch (Exception $e) {
                $payload = [
                    'error' => true,
                    'message' => $e->getMessage()
                ];
            }

            $this->_sendJson($payload);
        }
    }

    public function saveftpAction()
    {

        if ($data = Zend_Json::decode($this->getRequest()->getRawBody())) {

            try {

                $error_code = 0;
                $ftp_host = !empty($data["host"]) ? $data["host"] : null;
                $ftp_user = !empty($data["username"]) ? $data["username"] : null;
                $ftp_password = !empty($data["password"]) ? $data["password"] : null;
                $ftp_port = !empty($data["port"]) ? $data["port"] : Siberian_Ftp::DEFAULT_PORT;
                $ftp_path = null;

                if (!empty($data["path"])) {
                    $ftp_path = rtrim($data["path"], "/");
                }
                if (!$ftp_path) {
                    $ftp_path = Siberian_Ftp::DEFAULT_PATH;
                }

                $ftp = new Siberian_Ftp($ftp_host, $ftp_user, $ftp_password, $ftp_port, $ftp_path);
                if (!$ftp->checkConnection()) {
                    $error_code = 1;
                    throw new \Siberian\Exception(__("Unable to connect to your FTP. Please check the connection information."));
                } else if (!$ftp->isSiberianDirectory()) {
                    $error_code = 2;
                    throw new \Siberian\Exception(__("Unable to detect your site. Please make sure the entered path is correct."));
                }

                $fields = [
                    "ftp_host" => $ftp_host,
                    "ftp_username" => $ftp_user,
                    "ftp_password" => $ftp_password,
                    "ftp_port" => $ftp_port,
                    "ftp_path" => $ftp_path,
                ];

                foreach ($fields as $key => $value) {
                    $config = new System_Model_Config();
                    $config->find($key, "code");

                    if (!$config->getId()) {
                        $config->setCode($key)
                            ->setLabel(ucfirst(implode(" ", explode("_", $key))));
                    }

                    $config->setCode($key)
                        ->setValue($value)
                        ->save();
                }

                $data = [
                    "success" => 1,
                    "message" => __("Info successfully saved")
                ];

            } catch (Exception $e) {
                $data = [
                    "error" => 1,
                    "code" => $error_code,
                    "message" => $e->getMessage()
                ];
            }

            $this->_sendJson($data);
        }
    }

    public function copyAction()
    {
        if ($file = $this->getRequest()->getParam("file")) {

            $data = [];

            try {

                $filename = base64_decode($file);
                $file = Core_Model_Directory::getTmpDirectory(true) . "/$filename";

                if (!file_exists($file)) {
                    throw new Siberian_Exception(__("The file %s does not exist", $filename));
                }

                $parser = new Installer_Model_Installer_Module_Parser();
                if ($parser->setFile($file)->copy()) {
                    $data = ["success" => 1];
                } else {

                    $messages = $parser->getErrors();
                    $message = implode("\n", $messages);

                    throw new Siberian_Exception(__($message));

                }

            } catch (Exception $e) {
                $data = [
                    "error" => 1,
                    "message" => $e->getMessage()
                ];
            }

            $this->_sendJson($data);
        }

    }

    public function installAction()
    {

        # Increase the timelimit to ensure update will finish
        //$this->increase_timelimit = set_time_limit(300);

        $data = [];
        try {

            $cache = Zend_Registry::isRegistered('cache') ? Zend_Registry::get('cache') : null;
            if ($cache) {
                $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
            }

            $cache_ids = ['js_mobile.js', 'js_desktop.js', 'css_mobile.css', 'css_desktop.css'];
            foreach ($cache_ids as $cache_id) {
                if (file_exists(Core_Model_Directory::getCacheDirectory(true) . "/{$cache_id}")) {
                    unlink(Core_Model_Directory::getCacheDirectory(true) . "/{$cache_id}");
                }
            }

            $module_names = Zend_Controller_Front::getInstance()->getDispatcher()->getSortedModuleDirectories();
            self::$MODULES = [];
            foreach ($module_names as $module_name) {
                $module = new Installer_Model_Installer_Module();
                $module->prepare($module_name);
                if ($module->canUpdate()) {
                    self::$MODULES[] = $module->getName();
                }
            }

            self::$MODULES = array_unique(self::$MODULES);

            $installers = [];
            foreach (self::$MODULES as $module) {
                $installer = new Installer_Model_Installer();
                $installer
                    ->setModuleName($module)
                    ->install();

                $installers[] = $installer;

                # Try to increase max execution time (if the set failed)
                $this->_signalRetry();
            }

            foreach ($installers as $installer) {
                $installer->insertData();

                # Try to increase max execution time (if the set failed)
                $this->_signalRetry();
            }

            /** Try installing fresh template. */
            $installer = new Installer_Model_Installer();
            $installer
                ->setModuleName('Template')
                ->install();

            /** Clear cache */
            Siberian_Cache_Design::clearCache();
            Siberian_Cache_Translation::clearCache();
            Siberian_Minify::clearCache();

            $host = $this->getRequest()->getHeader("host");
            if ($host AND $host == base64_decode("YXBwcy5tb2JpdXNjcy5jb20=")) {
                $email = base64_decode("Y29udGFjdEBzaWJlcmlhbmNtcy5jb20=");
                $object = "$host - Siberian Update";
                $message = "Siberian " . Siberian_Version::NAME . " " . Siberian_Version::VERSION;
                mail($email, $object, $message);
            }

            $data = [
                "success" => 1,
                "message" => __("Module successfully installed")
            ];

            # Try to increase max execution time (if the set failed)
            $this->_signalRetry();

            $protocol = isset($_SERVER['HTTPS']) ? 'https://' : 'http://';
            Siberian_Autoupdater::configure($protocol . $this->getRequest()->getHttpHost());

            $cron_model = new Cron_Model_Cron();
            $cachebuilder = $cron_model->find("cachebuilder", "command");

            if ($cachebuilder->getId()) {
                $options = [
                    "host" => $protocol . $this->getRequest()->getHttpHost(),
                ];
                $cachebuilder->setOptions(Siberian_Json::encode($options))->save();
                $cachebuilder->enable();
            }

        } catch (Siberian_Exec_Exception $e) {
            $data = [
                "success" => 1,
                "reached_timeout" => true,
                "message" => $e->getMessage()
            ];
        } catch (Exception $e) {
            $data = [
                "error" => 1,
                "message" => $e->getMessage()
            ];
        }

        $this->_sendHtml($data);

    }

    /**
     * Detect if we are close to the timeout and send a signal to continue the installation process.
     *
     * @throws Siberian_Exec_Exception
     */
    protected function _signalRetry()
    {
        if (!$this->increase_timelimit) {
            if (Siberian_Exec::willReachMaxExecutionTime(5)) {
                throw new Siberian_Exec_Exception("Installation will continue, please wait ...");
            }
        }
    }

    protected function _fetchUpdates()
    {

        /** Default updates url in case of missing configuration */
        $updates_url = "https://updates02.siberiancms.com";

        $update_channel = __get("update_channel");
        if (in_array($update_channel, ["stable", "beta", "preview"])) {
            switch ($update_channel) {
                case "stable":
                    $updates_url = "https://updates02.siberiancms.com";
                    break;
                case "beta":
                    $updates_url = "https://beta-updates02.siberiancms.com";
                    break;
                case "preview":
                    $updates_url = "https://preview-updates02.siberiancms.com";
                    break;
            }
        }

        $current_version = Siberian_Version::VERSION;

        $url = "{$updates_url}/check.php?";
        $url .= "version={$current_version}";

        $client = new Zend_Http_Client($url, [
            'adapter' => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => [CURLOPT_SSL_VERIFYPEER => false],
        ]);
        $client->setMethod(Zend_Http_Client::POST);

        if (Siberian_Version::TYPE === "SAE") {
            $client->setParameterPost("sae", "1");
        } else {
            $license_key = System_Model_Config::getValueFor("siberiancms_key");
            if (!$license_key) {
                throw new Siberian_Exception(__("There is no CMS license key set."));
            }
            $client->setParameterPost("licenseKey", $license_key);
            $client->setParameterPost("host", $_SERVER["HTTP_HOST"]);
        }

        $response = $client->request();

        $content = $response->getBody();

        if (empty($content)) {
            throw new Siberian_Exception(__("An error occurred while loading. Please, try again later."));
        }

        $content = Zend_Json::decode($content);
        if ($response->getStatus() != 200) {

            $message = __("Unable to check for updates now. Please, try again later.");
            if (!empty($content["error"]) AND !empty($content["message"])) {
                $message = __($content["message"]);
            }

            throw new Siberian_Exception($message);
        } else if (empty($content["url"])) {
            $content["message"] = __("Your system is up to date.");
        }

        return $content;

    }

    protected function _getPackageDetails($file)
    {
        $installer = new Installer_Model_Installer();
        $installer->parse($file);

        $package = $installer->getPackageDetails();

        $path = pathinfo($file);
        $filename = $path['filename'] . '.' . $path['extension'];

        $data = [
            'success' => 1,
            'filename' => base64_encode($filename),
            'package_details' => [
                'name' => __('%s Update', $package->getName()),
                'version' => $package->getVersion(),
                'description' => $package->getDescription()
            ]
        ];

        $data['release_note'] = [
            'url' => false,
            'show' => false,
        ];

        if (($release_note = $package->getReleaseNote())) {
            $data['release_note'] = $package->getReleaseNote();
        }

        $data['package_details']['restore_apps'] = false;
        if (($restore_apps = $package->getRestoreApps())) {
            $data['package_details']['restore_apps'] = $package->getRestoreApps();
        }

        // @version 4.18.5
        // cleanup_files is used to clear previous installed folder and remove all potentially unused files!
        $data['package_details']['cleanup_files'] = false;
        if (($restore_apps = $package->getCleanupFiles())) {
            $data['package_details']['cleanup_files'] = $package->getCleanupFiles();
        }

        return $data;

    }

}
