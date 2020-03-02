<?php
/**
 * Class Siberian_Form_Element_Email
 */
class Siberian_Form_Element_Email extends Zend_Form_Element_Email {

    /**
     * @var bool
     */
    public $is_form_horizontal = true;

    /**
     * @param $boolean
     */
    public function setIsFormHorizontal($boolean) {
        $this->is_form_horizontal = $boolean;
    }

    /**
     * @var string
     */
    public $color = "color-blue";

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
		$this->setDecorators([
	  		'ViewHelper',
           	['Errors', [
           		'placement'=>Zend_Form_Decorator_Abstract::PREPEND,
           		'class'=>'alert alert-error form-error']
            ],
            ['Description', [
                'placement' => Zend_Form_Decorator_Abstract::APPEND,
                'class' => 'help-inline',
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
	 * @return Siberian_Form_Element_Text
	 */
	public function setNewDesign(){
		$this->addClass('sb-input-'.$this->getName());
		$this->addClass("input-flat");

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
			[['wrapper'=>'HtmlTag'], [
				'class' => ''.$element_class
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
            ]]
        ]);
	  	
	}

	/**
	 * @param string $description
	 * @return Siberian_Form_Element_Text
	 */
	public function setDescription($description){
		$this->addClass('sb-form-float');
		return parent::setDescription($description);
	}

	/**
	 * @param $new
	 * @return Siberian_Form_Element_Text
	 */
	public function addClass($new) {
	    return Siberian_Form_Abstract::addClass($new, $this);
	}

	/**
	 * @param $title
	 * @return Siberian_Form_Element_Text
	 * @throws Zend_Form_Exception
	 */
	public function setTooltip($title){
		$this->addClass('sb-tooltip');
		$this->setAttrib('title', str_replace('"', "'", $title));
		return $this;
	}

	/**
	 * @param $regexPhp
	 * @param $regexJs
	 * @param $error
	 * @param bool $empty
	 * @return Siberian_Form_Element_Text
	 * @throws Zend_Form_Exception
	 */
	public function setRegex($regexPhp, $regexJs, $error, $empty = false){
		$regexValidator = new Zend_Validate_Regex(['pattern' => $regexPhp]);
		
		$this
			->addvalidator($regexValidator)
			->addClass('data-validate-regex')
			->setAttrib('data-regex-pattern', $regexJs)
			->setAttrib('data-regex-error', $error)
		;
		
		if($empty) {
			$this
				->setAttrib('data-regex-empty', 'true')
			;
		}
		
		return $this;
	}
}