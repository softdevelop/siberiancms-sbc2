<?php

class Radio_ApplicationController extends Application_Controller_Default
{

    /**
     * @var array
     */
    public $cache_triggers = array(
        "editpost" => array(
            "tags" => array(
                "homepage_app_#APP_ID#",
            ),
        ),
    );

    /**
     * Simple edit post, validator
     *
     * @throws Zend_Exception
     * @throws Zend_Form_Exception
     * @throws exception
     */
    public function editpostAction()
    {
        try {
            $values = $this->getRequest()->getPost();

            $form = new Radio_Form_Radio();
            if (!$form->isValid($values)) {
                $payload = [
                    'error' => true,
                    'message' => $form->getTextErrors(),
                    'errors' => $form->getTextErrors(true),
                ];
            } else {
                /** Do whatever you need when form is valid */
                $radio = new Radio_Model_Radio();
                $radio->find($values["radio_id"]);
                $radio->setData($values);

                // Fix for shoutcast, force stream! fix once for all!
                $contentType = Siberian_Request::testStream($radio->getData('link'));
                if (!in_array(explode('/', $contentType)[0], ['audio']) &&
                    !in_array($contentType, ['application/ogg'])) {
                    if (strrpos($radio->getData('link'), ';') === false) {
                        $radio->setData('link', $radio->getData('link') . '/;');
                    }
                }

                // Set version 2 on create/save, means it's been updated
                $radio->setVersion(2);

                \Siberian\Feature::formImageForOption(
                    $this->getCurrentOptionValue(),
                    $radio,
                    $values,
                    "background",
                    true
                );

                /** Alert ipv4 */
                $warning_message = Siberian_Network::testipv4($values['link']);

                $radio->save();

                /** Update touch date, then never expires (until next touch) */
                $this->getCurrentOptionValue()
                    ->touch()
                    ->expires(-1);

                $payload = array(
                    'success' => true,
                    'message' => __('Success.'),
                );
            }
        } catch (\Exception $e) {
            $payload = [
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * @return string|void
     * @throws Exception
     * @throws Zend_Exception
     */
    public function exportAction()
    {
        if ($this->getCurrentOptionValue()) {
            $radio = new Radio_Model_Radio();
            $result = $radio->exportAction($this->getCurrentOptionValue());

            $this->_download($result, "radio-" . date("Y-m-d_h-i-s") . ".yml", "text/x-yaml");
        }
    }

}