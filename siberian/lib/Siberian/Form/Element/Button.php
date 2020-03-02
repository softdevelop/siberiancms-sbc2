<?php
/**
 * Class Siberian_Form_Element_Button
 */
class Siberian_Form_Element_Button extends Zend_Form_Element_Button {

    /**
     * @var bool
     */
    public $is_form_horizontal = true;

    /**
     * @var string
     */
    public $color = "color-blue";

    /**
     * @var string
     */
    public $label_class = "col-sm-3";

    /**
     * @var string
     */
    public $element_class = "col-sm-7";

    /**
     * @var string
     */
    public $error_class = "col-sm-7 col-sm-offset-3";

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
     * @param $label
     * @param $input
     * @param $offset
     * @return $this
     */
    public function setCols($label, $input, $offset) {
        $this->label_cols = $label;
        $this->element_class = $input;
        $this->offset_cols = $offset;

        return $this;
    }

	/**
	 * @throws Zend_Form_Exception
	 */
	public function init(){
		$this
			->setAttrib('class', 'btn')
		;
		$this->addPrefixPath('Siberian_Form_Decorator_', 'Siberian/Form/Decorator/', 'decorator');
		$this->setDecorators([
  			'ViewHelper',
			['HtmlTag', [
				'class'=>'form-actions'
            ]]
        ]);
	}

	/**
	 * @return Siberian_Form_Element_Button
	 */
	public function setBackDesign(){
		$this->addClass($this->color);
		return $this->setDecorators([
			'ViewHelper',
			['HtmlTag', [
				'class' => 'sb-back-button'
            ]]
        ]);
	}

    /**
     * @return Zend_Form_Element
     * @throws Zend_Form_Exception
     */
	public function setMiniDeleteDesign(){
		$this->setAttrib("class", "");
		$this->setAttrib("type", "submit");
		$this->setAttrib("escape", false);

		return $this->setDecorators([
			'ViewHelper',
			['HtmlTag', [
				'class' => 'sb-mini-delete'
            ]]
        ]);
	}

    /**
     * @param string $class
     * @return Zend_Form_Element
     */
	public function setNewDesign($class = ""){
		$this->addClass($this->color);

        $label_class = $this->label_class;
        $element_class = $this->element_class;
        $error_class = $this->error_class;

		return $this->setDecorators([
			'ViewHelper',
			[['wrapper'=>'HtmlTag'], [
				'class' => ' '.$element_class
            ]],
			['Description', [
				'placement' => Zend_Form_Decorator_Abstract::APPEND,
				'class' => 'sb-form-line-complement sb-form-description '.$error_class,
				'escape' => false
            ]],
			['Label', [
				'class' => 'sb-form-line-title '.$label_class,
				'requiredSuffix' => ' *',
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
				'class' => 'form-group sb-form-line '.$class
            ]]
        ]);
	}

	/**
	 * Set required flag
	 *
	 * @param  bool $flag Default value is true
	 * @return Zend_Form_Element
	 */
	public function setRequired($flag = true)
	{
		$this->_required = (bool) $flag;

		if($this->_required) {
			$this->addClass("is-required");
		}

		return $this;
	}

	/**
	 * @param $new
	 * @return Siberian_Form_Element_Button
	 */
    public function addClass($new) {
	    return Siberian_Form_Abstract::addClass($new, $this);
	}

}