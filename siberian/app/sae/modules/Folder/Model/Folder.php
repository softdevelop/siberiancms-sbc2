<?php

class Folder_Model_Folder extends Core_Model_Default
{

    /**
     * @var array
     */
    public $cache_tags = [
        "feature_folder",
    ];

    /**
     * @var bool
     */
    protected $_is_cacheable = true;

    /**
     * @var
     */
    protected $_root_category;

    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Folder_Model_Db_Table_Folder';
        return $this;
    }

    /**
     * @return array
     */
    public function getInappStates($value_id)
    {

        $in_app_states = [
            [
                "state" => "folder-category-list",
                "offline" => true,
                "params" => [
                    "value_id" => $value_id,
                ],
            ],
        ];

        return $in_app_states;
    }

    /**
     * @param $option_value
     * @return array
     */
    public function getFeaturePaths($option_value)
    {
        if (!$this->isCacheable()) {
            return [];
        }

        $value_id = $option_value->getId();
        $cache_id = "feature_paths_valueid_{$value_id}";
        if (!$result = $this->cache->load($cache_id)) {

            $paths = [];
            $paths[] = $option_value->getPath("findall", ['value_id' => $option_value->getId()], 'mobile');

            $paths = array_merge($paths, $this->_get_subcategories_feature_paths($this->getRootCategory(), $option_value));

            $this->cache->save($paths, $cache_id,
                $this->cache_tags + [
                    "feature_paths",
                    "feature_paths_valueid_{$value_id}"
                ]);
        } else {
            $paths = $result;
        }

        return $paths;
    }

    /**
     * @param $option_value
     * @return array
     */
    public function getAssetsPaths($option_value)
    {
        if (!$this->isCacheable()) {
            return [];
        }

        $paths = [];

        $value_id = $option_value->getId();
        $cache_id = "assets_paths_valueid_{$value_id}";
        if (!$result = $this->cache->load($cache_id)) {

            $folder = $option_value->getObject();

            if ($folder->getId()) {
                $category = new Folder_Model_Category();
                $category->find($folder->getRootCategoryId(), "category_id");
                if ($category->getId()) {
                    $paths[] = $category->getPictureUrl();
                    $paths = array_merge($paths, $this->_get_subcategories_assets_paths($category));
                }
            }

            $this->cache->save($paths, $cache_id,
                $this->cache_tags + [
                    "assets_paths",
                    "assets_paths_valueid_{$value_id}"
                ]);
        } else {
            $paths = $result;
        }

        return $paths;
    }

    /**
     * @param $option_value
     * @return bool|array
     */
    public function getEmbedPayload($option_value)
    {

        $payload = [
            "sections" => [],
            "page_title" => $option_value->getTabbarName()
        ];

        if ($this->getId()) {

            $request = $option_value->getRequest();

            $category_id = $request->getParam("category_id", null);
            $current_category = new Folder_Model_Category();

            if ($category_id) {
                $current_category->find($category_id, "category_id");
            }

            $object = $option_value->getObject();

            if (!$object->getId() OR ($current_category->getId() AND $current_category->getRootCategoryId() != $object->getRootCategoryId())) {
                throw new Siberian_Exception(__("An error occurred during process. Please try again later."));
            }

            $color_code = "background";
            if ($this->getApplication()->useIonicDesign()) {
                $color_code = "tabbar";
            }
            $color = $this->getApplication()->getBlock($color_code)->getImageColor();

            //Here we get the list used for the search in folder feature
            $current_option = $option_value;
            $folder = new Folder_Model_Folder();
            $category = new Folder_Model_Category();
            $folder->find($current_option->getId(), "value_id");

            $show_search = $folder->getShowSearch();

            $category->find($folder->getRootCategoryId(), "category_id");

            $result = [];
            array_push($result, $category);
            $this->_getAllChildren($category, $result);

            $search_list = [];

            $option_pictureb64 = null;

            foreach ($result as $folder) {

                $picture_b64 = null;
                if ($current_option->getIconId()) {
                    $picture_file = Core_Controller_Default_Abstract::sGetColorizedImage($current_option->getIconId(), $color);
                    $picture_b64 = $request->getBaseUrl() . $picture_file;
                    $option_pictureb64 = $request->getBaseUrl() . $picture_file;
                }

                $url = $this->getPath("folder/mobile_list", [
                    "value_id" => (integer)$current_option->getId(),
                    "category_id" => (integer)$folder->getId()]
                );

                $search_list[] = [
                    "name" => $folder->getTitle(),
                    "father_name" => $folder->getFatherName(),
                    "url" => $url,
                    "path" => $url,
                    "picture" => $picture_b64,
                    "offline_mode" => (boolean)$folder->isCacheable(),
                    "type" => "folder"
                ];
                $category_option = new Application_Model_Option_Value();
                $category_options = $category_option->findAll([
                    "app_id" => (integer)$this->getApplication()->getId(),
                    "folder_category_id" => (integer)$folder->getCategoryId(),
                    "is_visible" => true,
                    "is_active" => true
                ], ["folder_category_position ASC"]);

                foreach ($category_options as $feature) {
                    /**
                     * START Link special code
                     * We get informations about link at homepage level
                     */
                    $hide_navbar = false;
                    $use_external_app = false;
                    if ($object_link = $feature->getObject()->getLink() AND is_object($object_link)) {
                        $hide_navbar = $object_link->getHideNavbar();
                        $use_external_app = $object_link->getHideNavbar();
                    }
                    /**
                     * END Link special code
                     */

                    $picture_b64 = null;
                    if ($feature->getIconId()) {
                        $picture_file = Core_Controller_Default_Abstract::sGetColorizedImage($feature->getIconId(), $color);
                        $picture_b64 = $request->getBaseUrl() . $picture_file;
                    }

                    $url = $feature->getPath(null, ["value_id" => $feature->getId()], 'mobile');

                    $search_list[] = [
                        "name" => $feature->getTabbarName(),
                        "father_name" => $folder->getTitle(),
                        "url" => $url,
                        "path" => $url,
                        "is_link" => (boolean)!$feature->getIsAjax(),
                        "hide_navbar" => (boolean)$hide_navbar,
                        "use_external_app" => (boolean)$use_external_app,
                        "picture" => $picture_b64,
                        "offline_mode" => (boolean)$feature->getObject()->isCacheable(),
                        "code" => $feature->getCode(),
                        "type" => "feature",
                        "is_visible" => (boolean) $feature->getIsVisible(),
                        "is_locked" => (boolean) $feature->isLocked()
                    ];
                }
            }

            if (!$current_category->getId()) {
                $current_category = $object->getRootCategory();
            }

            $payload = [
                "folders" => [],
                "show_search" => (boolean)$show_search,
                "category_id" => (integer)$category_id
            ];

            $subcategories = $current_category->getChildren();

            foreach ($subcategories as $subcategory) {

                $picture_b64 = null;
                if ($subcategory->getPictureUrl()) {
                    $picture_b64 = $request->getBaseUrl() . $subcategory->getPictureUrl();
                }

                $url = __path("folder/mobile_list", [
                    "value_id" => $current_option->getId(),
                    "category_id" => $subcategory->getId()
                ]);

                $payload["folders"][] = [
                    "title" => $subcategory->getTitle(),
                    "subtitle" => $subcategory->getSubtitle(),
                    "picture" => $subcategory->getPictureUrl() ? $picture_b64 : $option_pictureb64,
                    "url" => $url,
                    "path" => $url,
                    "offline_mode" => (boolean)$current_option->getObject()->isCacheable(),
                    "category_id" => (integer)$subcategory->getId(),
                    "is_visible" => true,
                    "is_locked" => false,
                    "is_subfolder" => true
                ];
            }

            $pages = $current_category->getPages();

            foreach ($pages as $page) {
                /**
                 * START Link special code
                 * We get informations about link at homepage level
                 */
                $hide_navbar = false;
                $use_external_app = false;
                if ($object_link = $page->getObject()->getLink() AND is_object($object_link)) {
                    $hide_navbar = $object_link->getHideNavbar();
                    $use_external_app = $object_link->getUseExternalApp();
                }

                $picture_b64 = null;
                if ($page->getIconId()) {
                    $icon = Core_Controller_Default::sGetColorizedImage($page->getIconId(), $color);
                    $picture_b64 = $request->getBaseUrl() . $icon;

                }

                /**
                 * END Link special code
                 */
                $url = $page->getPath(null, ["value_id" => $page->getId()], 'mobile');

                $payload["folders"][] = [
                    "title" => $page->getTabbarName(),
                    "subtitle" => "",
                    "picture" => $picture_b64,
                    'icon_is_colorable' => (boolean) $page->getImage()->getCanBeColorized(),
                    "hide_navbar" => (boolean)$hide_navbar,
                    "use_external_app" => (boolean)$use_external_app,
                    "is_link" => (boolean)!$page->getIsAjax(),
                    "url" => $url,
                    "path" => $url,
                    "code" => $page->getCode(),
                    "offline_mode" => (boolean)$page->getObject()->isCacheable(),
                    "embed_payload" => $page->getEmbedPayload($request),
                    "is_visible" => (boolean)$page->getIsVisible(),
                    "is_locked" => (boolean)$page->isLocked(),
                    "touched_at" => (integer)$page->getTouchedAt(),
                    "expires_at" => (integer)$page->getExpiresAt()
                ];
            }

            $cover_b64 = null;
            if ($current_category->getPictureUrl()) {
                $cover_b64 = $request->getBaseUrl() . $current_category->getPictureUrl();
            }

            $payload["cover"] = [
                "title" => $current_category->getTitle(),
                "subtitle" => $current_category->getSubtitle(),
                "picture" => $cover_b64
            ];

            $payload["search_list"] = $search_list;
            $payload["page_title"] = $current_category->getTitle();

            $payload["success"] = true;

        }

        return $payload;
    }

    private function _getAllChildren($category, &$tab_children)
    {
        $children = $category->getChildren();
        foreach ($children as $child) {
            if ($category->getCategoryId() === $category->getParentId()) {
                continue;
            }
            $child->setFatherName($category->getTitle());
            array_push($tab_children, $child);
            $this->_getAllChildren($child, $tab_children);
        }
    }

    public function deleteFeature()
    {

        if (!$this->getId()) {
            return $this;
        }

        $this->getRootCategory()->delete();

        return $this->delete();
    }

    public function getRootCategory()
    {

        if (!$this->_root_category) {
            $this->_root_category = new Folder_Model_Category();
            $this->_root_category->find($this->getRootCategoryId());
        }

        return $this->_root_category;

    }

    /**
     * @param $category
     * @param Application_Model_Option_Value $option_value
     * @return array
     */
    private function _get_subcategories_feature_paths($category, $option_value)
    {
        $paths = [];
        $subcategories = $category->getChildren();

        foreach ($subcategories as $subcategory) {
            $params = [
                "value_id" => $option_value->getId(),
                "category_id" => $subcategory->getId()
            ];
            $paths[] = $option_value->getPath("findall", $params, 'mobile');
            $paths = array_merge($paths, $this->_get_subcategories_feature_paths($subcategory, $option_value));
        }

        return $paths;
    }

    private function _get_subcategories_assets_paths($category)
    {
        $paths = [];

        if (is_object($category) && $category->getId()) {
            $subs = $category->getChildren();
            foreach ($subs as $subcat) {
                $paths[] = $subcat->getPictureUrl();
                $paths = array_merge($paths, $this->_get_subcategories_assets_paths($subcat));
            }
        }

        return $paths;
    }

    public function createDummyContents($option_value, $design, $category)
    {

        $dummy_content_xml = $this->_getDummyXml($design, $category);

        // Continue if dummy is empty!
        if (!$dummy_content_xml) {
            return;
        }

        foreach ($dummy_content_xml->folders->folder as $folder) {
            $root_category = new Folder_Model_Category();
            $root_category->addData((array)$folder->category->main->content)
                ->save();

            if ($folder->category->main->features) {
                $i = 1;
                foreach ($folder->category->main->features->feature as $feature) {
                    $option = new Application_Model_Option();
                    $option->find((string)$feature->code, "code")->getObject();

                    $option_value_obj = new Application_Model_Option_Value();

                    $icon_id = NULL;
                    if ((string)$feature->icon) {
                        $icon = new Media_Model_Library_Image();
                        $icon->find((string)$feature->icon, "link");

                        if (!$icon->getData()) {
                            $icon->setLibraryId($option->getLibraryId())
                                ->setLink((string)$feature->icon)
                                ->setOptionId($option->getId())
                                ->setCanBeColorized($feature->colorizable ? (string)$feature->colorizable : 1)
                                ->setPosition(0)
                                ->save();
                        }

                        $icon_id = $icon->getId();
                    }

                    $datas = [
                        "tabbar_name" => (string)$feature->name ? (string)$feature->name : NULL,
                        "icon_id" => $icon_id,
                        "app_id" => $this->getApplication()->getId(),
                        "option_id" => $option->getId(),
                        "layout_id" => $this->getApplication()->getLayout()->getId(),
                        "folder_id" => $option_value->getId(),
                        "folder_category_id" => $root_category->getId(),
                        "folder_category_position" => $i++
                    ];

                    $option_value_obj->addData($datas)
                        ->save();

                }
            }

            $this->unsData();
            $this->setValueId($option_value->getId())
                ->setRootCategoryId($root_category->getId())
                ->save();

            foreach ($folder->category->subcategory as $subcategory) {
                $sub_root_category = new Folder_Model_Category();
                $sub_root_category->addData((array)$subcategory->content)
                    ->setParentId($root_category->getId())
                    ->save();

                if ($folder->category->subcategory->features) {
                    $i = 1;
                    foreach ($folder->category->subcategory->features->children() as $feature) {

                        $option = new Application_Model_Option();
                        $option->find((string)$feature->code, "code")->getObject();

                        $option_value_obj = new Application_Model_Option_Value();

                        $icon_id = NULL;
                        if ((string)$feature->icon) {
                            $icon = new Media_Model_Library_Image();
                            $icon->find((string)$feature->icon, "link");

                            if (!$icon->getData()) {
                                $icon->setLibraryId($option->getLibraryId())
                                    ->setLink((string)$feature->icon)
                                    ->setOptionId($option->getId())
                                    ->setCanBeColorized(1)
                                    ->setPosition(0)
                                    ->save();
                            }

                            $icon_id = $icon->getId();
                        }

                        $datas = [
                            "tabbar_name" => (string)$feature->name ? (string)$feature->name : NULL,
                            "icon_id" => $icon_id,
                            "app_id" => $this->getApplication()->getId(),
                            "option_id" => $option->getId(),
                            "layout_id" => $this->getApplication()->getLayout()->getId(),
                            "folder_id" => $option_value->getId(),
                            "folder_category_id" => $sub_root_category->getId(),
                            "folder_category_position" => $i++
                        ];

                        $option_value_obj->addData($datas)
                            ->save();

                    }
                }
            }
        }
    }

    /**
     * @deprecated folders are not duplicated anymore due to a wide range of issues.
     *
     * @param $option
     * @return $this
     */
    public function copyTo($option)
    {
        return $this;
    }

    public function copyCategoryTo($option, $category, $parent_id = null)
    {
        $children = $category->getChildren();
        $category_option = new Application_Model_Option_Value();
        $option_values = $category_option->findAll(['app_id' => $option->getAppId(), 'folder_category_id' => $category->getId()], ['folder_category_position ASC']);
        $category->setId(null)->setParentId($parent_id)->save();

        foreach ($option_values as $option_value) {
            $option_value->setFolderCategoryId($category->getId())->save();
        }

        foreach ($children as $child) {
            $this->copyCategoryTo($option, $child, $category->getId());
        }

        return $this;
    }

}