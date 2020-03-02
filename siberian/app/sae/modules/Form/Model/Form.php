<?php

class Form_Model_Form extends Core_Model_Default
{

    protected $_sections;

    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Form_Model_Db_Table_Form';
        return $this;
    }

    /**
     * @return array
     */
    public function getInappStates($value_id)
    {

        $in_app_states = [
            [
                "state" => "form-view",
                "offline" => false,
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
    public function getEmbedPayload($option_value)
    {

        $payload = [
            "sections" => [],
            "page_title" => $option_value->getTabbarName(),
            "dateFormat" => $this->getDateFormat(),
            "design" => $this->getDesign(),
        ];

        if ($this->getId()) {
            $sections = $this->getSections();

            foreach ($sections as $section) {

                $section_data = [
                    "name" => $section->getName(),
                    "fields" => []
                ];

                $fields = $section->getFields();

                foreach ($fields as $field) {

                    $field_data = [
                        "id" => $field->getId(),
                        "type" => $field->getType(),
                        "name" => $field->getName(),
                        "options" => $field->hasOptions() ? $field->getOptions() : []
                    ];

                    if ($field->isRequired()) {
                        $field_data["name"] .= " *";
                    }

                    $section_data["fields"][] = $field_data;
                }

                $payload["sections"][] = $section_data;
            }

        }

        return $payload;

    }

    public function getSections()
    {

        if (!$this->_sections) {
            $section = new Form_Model_Section();
            $this->_sections = $section->findAll(['value_id' => $this->getValueId()]);
        }

        return $this->_sections;

    }

    /**
     * Recherche par value_id
     *
     * @param int $value_id
     * @return object
     */
    public function findByValueId($value_id)
    {
        return $this->getTable()->findByValueId($value_id);
    }

    public function createDummyContents($option_value, $design, $category)
    {

        $dummy_content_xml = $this->_getDummyXml($design, $category);

        // Continue if dummy is empty!
        if (!$dummy_content_xml) {
            return;
        }

        foreach ($dummy_content_xml->children() as $content) {
            $this->unsData();
            $this->setEmail((string)$content->form->email)
                ->setValueId($option_value->getId())
                ->save();

            foreach ($content->form_sections->section as $section) {
                $section_obj = new Form_Model_Section();
                $section_obj->setName((string)$section->name)
                    ->setValueId($option_value->getId())
                    ->save();

                foreach ($section->fields->field as $field) {
                    $field_obj = new Form_Model_Field();
                    $field_obj->setSectionId($section_obj->getId())
                        ->addData((array)$field)
                        ->save();
                }
            }
        }
    }

    public function copyTo($option)
    {

        $old_value_id = $this->getValueId();

        $this->setId(null)->setValueId($option->getId())->save();

        $section = new Form_Model_Section();
        $sections = $section->findAll(['value_id' => $old_value_id]);

        foreach ($sections as $section) {

            $old_section_id = $section->getId();
            $section->setId(null)->setValueId($option->getId())->save();

            $field = new Form_Model_Field();
            $fields = $field->findAll(['section_id' => $old_section_id]);

            foreach ($fields as $field) {
                $field->setId(null)
                    ->setSectionId($section->getId())
                    ->save();
            }

        }

        return $this;

    }

}
