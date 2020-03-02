<?php

class Template_CropController extends Admin_Controller_Default
{

    public function uploadAction()
    {

        if ($datas = $this->getRequest()->getParams() AND !empty($_FILES)) {
            try {

                $folder = Core_Model_Directory::getTmpDirectory(true) . '/';

                $params = [];
                $params['validators'] = [
                    'Extension' => ['jpg', 'png', 'jpeg', 'gif', 'case' => false],
                    'Size' => ['min' => 100, 'max' => 200000000],
                    'ImageSize' => [
                        'minwidth' => 20,
                        'minheight' => 20,
                        'maxwidth' => 5000,
                        'maxheight' => 5000,
                    ]
                ];

                $params['destination_folder'] = $folder;
                $params['uniq'] = 1;
                $params['uniq_prefix'] = '';

                //param customs
                foreach ($params['validators']['ImageSize'] as $key => $value) {
                    if (isset($datas[$key])) {
                        $params['validators']['ImageSize'][$key] = $datas[$key];
                    }
                }
                if (isset($datas['uniq_prefix'])) {
                    $params['uniq_prefix'] = $datas['uniq_prefix'];
                }

                if (isset($datas['desired_name']) && $datas['desired_name'] != '') {
                    $params['desired_name'] = $datas['desired_name'];
                }

                $uploader = new Core_Model_Lib_Uploader();
                $file = $uploader->upload($params);

                $image_sizes = getimagesize(Core_Model_Directory::getTmpDirectory(true) . '/' . $file);

                $datas = [
                    "success" => true,
                    "message" => __("File uploaded."),
                    "files" => $file,
                    "source_width" => $image_sizes[0],
                    "source_height" => $image_sizes[1],
                ];
            } catch (Exception $e) {
                $datas = [
                    "error" => true,
                    "message" => $e->getMessage()
                ];
            }
            $this->_sendJson($datas);
        }
    }

    public function cropAction()
    {
        $picture = $this->getRequest()->getParam('picture');
        $image_sizes = getimagesize(Core_Model_Directory::getTmpDirectory(true) . '/' . $picture);
        $option_value_id = '';
        $is_colorizable = false;
        if ($this->getRequest()->getParam('option_value_id')) {
            $option_value_id = $this->getRequest()->getParam('option_value_id');
        }
        if ($this->getRequest()->getParam('is_colorizable')) {
            $is_colorizable = $this->getRequest()->getParam('is_colorizable');
        }
        $html = $this->getLayout()->addPartial('crop', 'core_view_default', 'page/layout/crop.phtml')
            ->setPicture($picture)
            ->setWidth($image_sizes[0])
            ->setHeight($image_sizes[1])
            ->setOutputWidth($this->getRequest()->getParam('outputWidth'))
            ->setOutputHeight($this->getRequest()->getParam('outputHeight'))
            ->setOutputUrl($this->getRequest()->getParam('outputUrl'))
            ->setQuality($this->getRequest()->getParam('quality'))
            ->setUploader($this->getRequest()->getParam('uploader'))
            ->setOptionId($option_value_id)
            ->setIsColorizable((boolean) filter_var($is_colorizable, FILTER_VALIDATE_BOOLEAN))
            ->setForceColor($this->getRequest()->getParam('force_color'))
            ->setImageColor($this->getRequest()->getParam('image_color'))
            ->toHtml();

        $this->getLayout()->setHtml($html);
    }

    public function cropv2Action()
    {
        $picture = $this->getRequest()->getParam('picture');
        $image_sizes = getimagesize(Core_Model_Directory::getTmpDirectory(true) . '/' . $picture);
        $option_value_id = '';
        $is_colorizable = false;
        if ($this->getRequest()->getParam('option_value_id')) {
            $option_value_id = $this->getRequest()->getParam('option_value_id');
        }
        if ($this->getRequest()->getParam('is_colorizable')) {
            $is_colorizable = $this->getRequest()->getParam('is_colorizable');
        }
        $html = $this->getLayout()->addPartial('crop', 'core_view_default', 'page/layout/crop_v2.phtml')
            ->setPicture($picture)
            ->setWidth($image_sizes[0])
            ->setHeight($image_sizes[1])
            ->setOutputWidth($this->getRequest()->getParam('outputWidth'))
            ->setOutputHeight($this->getRequest()->getParam('outputHeight'))
            ->setOutputUrl($this->getRequest()->getParam('outputUrl'))
            ->setQuality($this->getRequest()->getParam('quality'))
            ->setUploader($this->getRequest()->getParam('uploader'))
            ->setOptionId($option_value_id)
            ->setIsColorizable((boolean) filter_var($is_colorizable, FILTER_VALIDATE_BOOLEAN))
            ->setForceColor($this->getRequest()->getParam('force_color'))
            ->setImageColor($this->getRequest()->getParam('image_color'))
            ->toHtml();

        $this->getLayout()->setHtml($html);
    }


    public function validateAction()
    {
        if ($datas = $this->getRequest()->getPost()) {
            try {
                $uploader = new Core_Model_Lib_Uploader();
                $file = $uploader->savecrop($datas);
                $datas = [
                    'success' => true,
                    'file' => $file,
                    'message' => $this->_('Info successfully saved'),
                ];
            } catch (Exception $e) {
                $datas = [
                    'error' => true,
                    'message' => $e->getMessage()
                ];
            }

            $this->_sendJson($datas);
        }
    }

}
