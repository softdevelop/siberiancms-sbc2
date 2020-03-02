<?php
/**
 * Class Siberian_Form_Element_MultiCheckbox
 */
class Siberian_Form_Element_MultiCheckbox extends Zend_Form_Element_MultiCheckbox {

    /**
     * @var bool
     */
    public $is_form_horizontal = true;

    /**
     * @var string
     */
    public $color = "color-blue";

    /**
     * @param $boolean
     */
    public function setIsFormHorizontal($boolean) {
        $this->is_form_horizontal = $boolean;
    }

    /**
     * @param $color
     */
    public function setColor($color) {
        $this->color = $color;
    }

	/**
	 * @throws Zend_Form_Exception
	 */
	public function init(){
		$this->addPrefixPath('Siberian_Form_Decorator_', 'Siberian/Form/Decorator/', 'decorator');
		$this->addFilters(['StringTrim','StripTags']);
		$this
			->setSeparator('')
			->setDecorators([
		  		'ViewHelper',
	           	['Errors', [
	           		'placement'=>Zend_Form_Decorator_Abstract::PREPEND,
	           		'class'=>'alert alert-error form-error']
                ],
	            ['Description', [
	                'placement' => Zend_Form_Decorator_Abstract::APPEND,
	                'class' => 'help-block',
                    'escape' => false,
                ]],
	            [['controls' => 'HtmlTag'], [
	                'tag'   => 'div',
	                'class' => 'controls',
                ]],
	            ['Label', [
	                'class' => 'control-label',
	                'requiredSuffix' => ' *',
                    'escape' => false,
	                'placement' => Zend_Form_Decorator_Abstract::PREPEND
                ]],
	            ['ControlGroup']
            ]);
	  	
	}

	/**
	 * @return Zend_Form_Element
	 * @throws Zend_Form_Exception
	 */
	public function setNewDesign(){
	  	$this->addClass('sb-form-checkbox ' . $this->color);
		$this->setSeparator("");
		$this->setAttrib("escape", false);

        if($this->is_form_horizontal) {
            $label_class = "col-sm-3";
            $element_class = "col-sm-7";
            $error_class = "col-sm-7 col-sm-offset-3";
        } else {
            $label_class = "";
            $element_class = "";
            $error_class = "";
        }

		return $this->setDecorators([
	  		'ViewHelper',
			[['container'=>'HtmlTag'], [
				'class' => 'sb-check-container '.$element_class
            ]],
            ['Description', [
                'placement' => Zend_Form_Decorator_Abstract::APPEND,
                'class' => 'sb-form-line-complement sb-form-description '.$error_class,
                'escape' => false
            ]],
			 ['Label', [
                'class' => 'sb-form-line-title '.$label_class,
                'requiredSuffix' => ' *',
                 'escape' => false,
                'placement' => Zend_Form_Decorator_Abstract::PREPEND,
             ]],
           	['Errors', [
           		'placement'=>Zend_Form_Decorator_Abstract::PREPEND,
           		'class'=>'alert alert-error'
            ]],
          	[['cb' => 'HtmlTag'], [
            	'class' => 'sb-cb',
            	'placement' => Zend_Form_Decorator_Abstract::APPEND,
            ]],
            ['ControlGroup', [
            	'class' => 'form-group sb-form-line'
            ]],
        ]);
	}

	/**
	 * Remove separator
	 */
	public function setIsInline() {
		return $this->setSeparator("");
	}

	/**
	 * @param array $options
	 * @return Zend_Form_Element_Multi
	 */
	public function addMultiOptions(array $options) {
		$new_options = [];
		foreach($options as $value => $label) {
			$new_options[$value] = '<span class="sb-checkbox-label">'.$label.'</span>';
		}

		return parent::addMultiOptions($new_options);
	}

	/**
	 * @param $new
	 * @return Siberian_Form_Element_MultiCheckbox
	 */
    public function addClass($new) {
	    return Siberian_Form_Abstract::addClass($new, $this);
	}

}