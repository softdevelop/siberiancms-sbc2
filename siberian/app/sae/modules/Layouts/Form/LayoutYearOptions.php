<?php

class Layouts_Form_LayoutYearOptions extends Siberian_Form_Options_Abstract {


    public function init() {
        parent::init();

        /** Bind as a create form */
        self::addClass("create", $this);
        self::addClass("form-layout-options", $this);

        $positionMenu = $this->addSimpleSelect("positionMenu", __("Position Menu"), [
            "menu-top" => __("Top"),
            "menu-middle" => __("Middle"),
            "menu-bottom" => __("Bottom"),
        ]);

        $textTransform = $this->addSimpleSelect("textTransform", __("Title case"), [
            "title-lowcase" => __("Lower case"),
            "title-uppercase" => __("Upper case"),
        ]);

        $title = $this->addSimpleRadio("title", __("Display titles"), [
            "titlevisible" => __("Visible"),
            "titlehidden" => __("Hidden"),
        ]);

        $this->addNav("submit", __("Save"), false, false);

        self::addClass("btn-sm", $this->getDisplayGroup("submit")->getElement(__("Save")));

    }

}