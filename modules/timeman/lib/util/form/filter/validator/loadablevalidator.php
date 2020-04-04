<?php
namespace Bitrix\Timeman\Util\Form\Filter\Validator;

use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter\BaseFormFilter;

/**
 * Class LoadableValidator - if you need just load data to a form and do not want any validation runs - use this validator
 * @package Bitrix\Timeman\Util\Form\Filter\Validators
 */
class LoadableValidator extends BaseFormFilter
{
	public function validateAttribute(BaseForm $form, $fieldName)
	{

	}

	protected function validateValue($values)
	{

	}
}