<?php

class Cms_Form_Block_Slider extends Cms_Form_Block_Image_Abstract {

    /**
     * @var string
     */
    public $blockType = 'slider';

    /**
     * @var string
     */
    public static $image_template = '
<div class="cms-image" style="background-image: url(#THUMBNAIL_PATH#);">
    <div class="cms-image-handle">
        <i class="fa fa-arrows"></i>
    </div>
    <div class="cms-image-delete">
        <i class="fa fa-times"></i>
    </div>
    <img src="/images/application/placeholder/blank-512.png" class="cms-image-unit" />
    <input type="hidden" name="block[%UNIQID%][slider][images][]" value="%IMAGE_PATH%" />
</div>';

    public function init() {
        parent::init();

        $this
            ->setAction(__path("/template/crop/upload"))
            ->setAttrib("id", "form-cms-block-slider-".$this->uniqid)
        ;

        $description = $this->addSimpleText("description", __("Description"));
        $description->setBelongsTo("block[".$this->uniqid."][slider]");

        $lineReturn = $this->addSimpleCheckbox("allow_line_return", __("Allow line return?"));
        $lineReturn->setBelongsTo("block[".$this->uniqid."][slider]");

        $pictures_uploader = $this->addSimpleFile("image_uploader", __("Add pictures"), ["multiple" => true]);
        $pictures_uploader->setBelongsTo("block[".$this->uniqid."][slider]");

        $cms_images_container = '
<div class="cms-images-container"></div>';

        $pictures_container = $this->addSimpleHtml("cms-images-container", $cms_images_container);

        $value_id = $this->addSimpleHidden("value_id");
        $value_id
            ->setRequired(true)
        ;
    }

    /**
     * @param $block
     * @return $this
     */
    public function loadBlock($block) {
        $this->getElement("allow_line_return")->setValue($block->getAllowLineReturn());

        parent::loadBlock($block);

        return $this;
    }

}