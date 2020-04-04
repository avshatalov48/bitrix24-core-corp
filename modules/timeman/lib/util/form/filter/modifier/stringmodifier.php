<?php
namespace Bitrix\Timeman\Util\Form\Filter\Modifier;

use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter\BaseFormFieldModifier;

class StringModifier extends BaseFormFieldModifier
{
	private $trimNeeded;

	public function configureTrim($value)
	{
		$this->trimNeeded = $value;
		return $this;
	}

	/** extend this in modifier, you'll have access to the form and you can rewrite the form field
	 * errors are added to the form directly
	 * @param BaseForm $form
	 * @param $fieldName
	 */
	public function validateField(BaseForm $form, $fieldName)
	{
		if ($this->trimNeeded && is_string($form->$fieldName) && !$this->isEmpty($form->$fieldName))
		{
			$form->$fieldName = trim($form->$fieldName);
		}
	}
}