<?php

class Layouts_Form_SwipeOptions extends Siberian_Form_Options_Abstract {


    public function init() {
        parent::init();

        /** Bind as a create form */
        self::addClass("create", $this);
        self::addClass("form-layout-options", $this);


        $icons = $this->addSimpleSelect("icons", __("Icons & Images"), [
            "default" => __("Square icons"),
            "cover" => __("Cover background images"),
        ]);

        $loop_features = $this->addSimpleCheckbox("loop", __("Loop features"));

        $angle = $this->addSimpleSlider("angle", __("Angle"), [
            "min" => -30,
            "max" => 30,
            "step" => 1,
            "unit" => "°",
        ]);
        $angle->setValue(-10);

        $stretch = $this->addSimpleSlider("stretch", __("Stretch"), [
            "min" => 0,
            "max" => 200,
            "step" => 5,
        ]);
        $stretch->setValue(50);

        $depth = $this->addSimpleSlider("depth", __("Depth"), [
            "min" => 0,
            "max" => 1000,
            "step" => 10,
        ]);
        $depth->setValue(200);

        $this->groupElements("sliders", ["angle", "stretch", "depth"]);

        $this->addNav("submit", __("Save"), false, false);

        self::addClass("btn-sm", $this->getDisplayGroup("submit")->getElement(__("Save")));

    }

    public function getPresets() {
        $preset_1 = [
            "preview" => "/app/sae/modules/Layouts/resources/design/desktop/flat/images/customization/layout/preset-swipe/preset1.png",
            "values" => [
                "loop" => 1,
                "angle" => -10,
                "stretch" => 50,
                "depth" => 200,
            ]
        ];

        $preset_2 = [
            "preview" => "/app/sae/modules/Layouts/resources/design/desktop/flat/images/customization/layout/preset-swipe/preset2.png",
            "values" => [
                "loop" => 1,
                "angle" => 0,
                "stretch" => 75,
                "depth" => 500,
            ]
        ];

        $preset_3 = [
            "preview" => "/app/sae/modules/Layouts/resources/design/desktop/flat/images/customization/layout/preset-swipe/preset3.png",
            "values" => [
                "loop" => 1,
                "angle" => 15,
                "stretch" => 15,
                "depth" => 190,
            ]
        ];

        $preset_4 = [
            "preview" => "/app/sae/modules/Layouts/resources/design/desktop/flat/images/customization/layout/preset-swipe/preset4.png",
            "values" => [
                "loop" => 1,
                "angle" => 0,
                "stretch" => 15,
                "depth" => 0,
            ]
        ];

        $preset_5 = [
            "preview" => "/app/sae/modules/Layouts/resources/design/desktop/flat/images/customization/layout/preset-swipe/preset5.png",
            "values" => [
                "loop" => 0,
                "angle" => 0,
                "stretch" => 35,
                "depth" => 300,
            ]
        ];

        $preset_6 = [
            "preview" => "/app/sae/modules/Layouts/resources/design/desktop/flat/images/customization/layout/preset-swipe/preset6.png",
            "values" => [
                "loop" => 1,
                "angle" => 20,
                "stretch" => 0,
                "depth" => 300,
            ]
        ];

        return [
            $preset_1,
            $preset_2,
            $preset_3,
            $preset_4,
            $preset_5,
            $preset_6,
        ];
    }

}