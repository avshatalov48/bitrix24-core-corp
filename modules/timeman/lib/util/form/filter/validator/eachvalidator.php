<?php
namespace Bitrix\Timeman\Util\Form\Filter\Validator;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter\BaseFormFilter;

/**
 * Class EachValidator
 * if you have array field in form and want to run validation on every item in array
 * @package Bitrix\Timeman\Util\Form\Filter\Validators
 */
class EachValidator extends BaseFormFilter
{
	/** @var BaseFormFilter $validator */
	private $validator;

	public $stopOnFirstError = true;

	public function configureValidator(BaseFormFilter $validator)
	{
		$this->validator = $validator;
		$validator->setFieldNames($this->fieldNames);
		return $this;
	}

	public function validateAttribute(BaseForm $form, $fieldName)
	{
		$values = $form->$fieldName;
		$result = new Result();

		if (!is_array($values) && !$values instanceof \ArrayAccess)
		{
			$result->addError(new Error(Loc::getMessage($this->defaultErrorMessage), $this->defaultErrorMessage));
			$this->addError($form, $fieldName, $result);
			return;
		}
		if (!$this->validator)
		{
			return;
		}
		$detectedErrors = $form->getErrors($fieldName);
		$filteredValue = $form->$fieldName;
		foreach ($values as $key => $value)
		{
			$form->clearErrors($fieldName);
			$form->$fieldName = $value;
			if (!$this->validator->isSkipOnEmpty() || !$this->validator->isEmpty($value))
			{
				$this->validator->validateField($form, $fieldName);
			}
			$filteredValue[$key] = $form->$fieldName;
			if ($form->hasErrors($fieldName))
			{
				$validationErrors = $form->getErrors($fieldName);
				$detectedErrors = array_merge($detectedErrors, $validationErrors);

				$form->$fieldName = $values;
				if ($this->stopOnFirstError)
				{
					break;
				}
			}
		}

		$form->$fieldName = $filteredValue;
		$form->clearErrors($fieldName);
		$form->addErrors([$fieldName => $detectedErrors]);
	}

	protected function validateValue($values)
	{
		if ($values !== null && !is_array($values) && !$values instanceof \ArrayAccess)
		{
			return [$this->defaultErrorMessage, []];
		}
		if (!$this->validator)
		{
			return null;
		}
		foreach ($values as $value)
		{
			if ($this->validator->isSkipOnEmpty() && $this->validator->isEmpty($value))
			{
				continue;
			}
			$result = $this->validator->validateValue($value);
			if ($result !== null)
			{
				$result[1]['#VALUE#'] = $value;
				return $result;
			}
		}

		return null;
	}
}