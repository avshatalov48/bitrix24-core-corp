<?php
namespace Bitrix\Timeman\Util\Form\Filter\Modifier;

use Bitrix\Main\Result;
use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter\BaseFormFieldModifier;

class CallbackModifier extends BaseFormFieldModifier
{
	private $filterCallback;
	private $skipOnArray = false;

	public function configureSkipOnArray($skip)
	{
		$this->skipOnArray = $skip;
		return $this;
	}

	public function configureCallback($preFilter)
	{
		$this->filterCallback = $preFilter;
		return $this;
	}

	public function validateField(BaseForm $form, $fieldName)
	{
		$value = $form->$fieldName;
		if (!$this->skipOnArray || !is_array($value))
		{
			$form->$fieldName = call_user_func($this->filterCallback, $value);
		}
		return new Result();
	}
}