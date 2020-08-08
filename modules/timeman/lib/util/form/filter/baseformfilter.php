<?php
namespace Bitrix\Timeman\Util\Form\Filter;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Timeman\Util\Form\BaseForm;

abstract class BaseFormFilter
{
	protected $skipOnError = false;
	protected $skipOnEmpty = true;
	protected $fieldNames = [];
	protected $fieldLabels = [];

	protected $defaultErrorMessage = '';

	/**
	 * @param array ...$fieldNames
	 */
	public function __construct()
	{
		$fields = func_get_args();
		if (!is_array($fields))
		{
			$fields = [$fields];
		}
		$this->fieldNames = $fields;
	}

	public function isSkipOnEmpty()
	{
		return $this->skipOnEmpty;
	}

	public function configureSkipOnEmpty($skip)
	{
		$this->skipOnEmpty = $skip;
		return $this;
	}

	public function configureSkipOnError($skip)
	{
		$this->skipOnError = $skip;
		return $this;
	}

	public function configureDefaultErrorMessage($errorMessage)
	{
		$this->defaultErrorMessage = $errorMessage;
		return $this;
	}

	public function getAttributeLabel($field)
	{
		$labels = $this->configureFieldLabels();
		return isset($labels[$field]) ? $labels[$field] : $this->generateFieldLabel($field);
	}

	public function generateFieldLabel($name)
	{
		return $name;
	}

	public function configureFieldLabels()
	{
		return [];
	}

	private function getValidationFieldNames($fieldNames = null)
	{
		if ($fieldNames === null)
		{
			return $fieldNames;
		}
		if (is_string($fieldNames))
		{
			$fieldNames = [$fieldNames];
		}
		$validatedFields = [];
		$selfField = $this->getFieldNames();
		foreach ($fieldNames as $fieldName)
		{
			if (in_array($fieldName, $selfField, true))
			{
				$validatedFields[] = $fieldName;
			}
		}
		return $validatedFields;
	}

	final public function validateFields(BaseForm $form, $fieldNames = null)
	{
		$fieldNames = $this->getValidationFieldNames($fieldNames);

		foreach ($fieldNames as $fieldName)
		{
			$skip = $this->skipOnError && $form->hasErrors($fieldName)
					|| $this->skipOnEmpty && $this->isEmpty($form->$fieldName);
			if (!$skip)
			{
				$this->validateField($form, $fieldName);
			}
		}
	}

	/** for validators, it has only the value to be validated
	 * @param $value
	 * @return array|null the error message and the array of replacements for the error message.
	 */
	abstract protected function validateValue($value);

	/** for modifiers, it has an access to the form and can rewrite form fields
	 * errors are added to the form directly
	 * @param BaseForm $form
	 * @param $fieldName
	 */
	public function validateField(BaseForm $form, $fieldName)
	{
		$params['#FIELD_NAME#'] = $form->getFieldLabel($fieldName);
		$errors = $this->validateValue($form->$fieldName);
		if ($errors)
		{
			$result = new Result();
			$result->addError(new Error(Loc::getMessage($errors[0], array_merge($params, $errors[1])), $errors[0]));
			$this->addError($form, $fieldName, $result);
		}
	}

	/** using validator for stand-alone validation
	 * @param $value
	 * @param null $error
	 * @return Result
	 */
	public function validate($value)
	{
		$result = new Result();
		$errors = $this->validateValue($value);
		if (empty($errors))
		{
			return $result;
		}

		list($locCode, $params) = $errors;
		if (is_array($value))
		{
			$params['#VALUE#'] = 'array()';
		}
		elseif (is_object($value))
		{
			$params['#VALUE#'] = 'object';
		}
		else
		{
			$params['#VALUE#'] = $value;
		}
		$result->addError(new Error(Loc::getMessage($locCode, $params), $locCode));
		return $result;
	}

	public function setFieldNames(array $fields)
	{
		$this->fieldNames = $fields;
	}

	protected function addError(BaseForm $form, $fieldName, Result $result)
	{
		foreach ($result->getErrors() as $error)
		{
			$form->addError($fieldName, $error->getMessage());
		}
	}

	protected function isEmpty($value)
	{
		return $value === null || $value === [] || $value === '';
	}

	public function getFieldNames()
	{
		return $this->fieldNames;
	}
}