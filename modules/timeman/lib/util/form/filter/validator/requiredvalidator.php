<?php
namespace Bitrix\Timeman\Util\Form\Filter\Validator;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Util\Form\Filter\BaseFormFilter;
Loc::loadMessages(__FILE__);
class RequiredValidator extends BaseFormFilter
{
	protected $defaultErrorMessage = 'TM_FORM_REQUIRED_FIELD_ERROR';
	protected $skipOnEmpty = false;

	/** extend this in validator, you'll have only the value to be validated
	 * and field name
	 * @param $value
	 * @return array|null the error message and the array of replacements for the error message.
	 */
	protected function validateValue($value)
	{
		if (!$this->isEmpty(is_string($value) ? trim($value) : $value))
		{
			return null;
		}

		return [$this->defaultErrorMessage, []];
	}
}