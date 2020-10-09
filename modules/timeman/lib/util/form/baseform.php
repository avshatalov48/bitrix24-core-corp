<?php
namespace Bitrix\Timeman\Util\Form;

use Bitrix\Timeman\Util\Form\Filter\BaseFormFilter;

class BaseForm
{
	/** @var FormError[] */
	private $errors = [];
	private $validators;

	/**
	 * @return string
	 */
	public function getFormName()
	{
		if (mb_stripos(static::class, '\\') === false)
		{
			return static::class;
		}
		return mb_substr(strrchr(static::class, '\\'), 1);
	}

	/**
	 * @param \Bitrix\Main\HttpRequest|array $data
	 * @param null $formName
	 * @return bool
	 */
	public function load($data, $formName = null)
	{
		$this->runBeforeLoad($data, $formName);

		if ($formName === null)
		{
			$formName = $this->getFormName();
		}
		if ($formName === '' && !empty($data))
		{
			$this->fillFields($data);

			return true;
		}
		elseif (isset($data[$formName]) && is_array($data[$formName]))
		{
			$this->fillFields($data[$formName]);

			return true;
		}
		elseif ($data instanceof \Bitrix\Main\HttpRequest)
		{
			$this->fillFields($data->getPost($formName));

			return true;
		}
		return false;
	}

	protected function runBeforeLoad($data, $formName)
	{
	}

	protected function runBeforeValidate()
	{
		return true;
	}

	protected function runAfterValidate()
	{
	}

	public function validate($fieldsNames = null, $clearErrors = true)
	{
		if ($clearErrors)
		{
			$this->clearErrors();
		}

		if (!$this->runBeforeValidate())
		{
			return false;
		}

		if ($fieldsNames === null)
		{
			$fieldsNames = $this->buildFormFields();
		}

		foreach ($this->getValidators() as $validator)
		{
			/** @var BaseFormFilter $validator */
			$validator->validateFields($this, $fieldsNames);
		}
		$this->runAfterValidate();

		return !$this->hasErrors();
	}

	private function getValidators()
	{
		if ($this->validators === null)
		{
			$this->validators = $this->buildValidators();
		}
		return $this->validators;
	}

	private function buildValidators()
	{
		$validators = [];
		foreach ($this->configureFilterRules() as $rule)
		{
			if ($rule instanceof BaseFormFilter)
			{
				$validators[] = $rule;
			}
		}
		return $validators;
	}

	public function configureFilterRules()
	{
		return [];
	}

	private function fillFields($values, $loadableOnly = true)
	{
		if (!is_array($values))
		{
			return;
		}

		$fields = array_flip($loadableOnly ? $this->getLoadableFields() : $this->buildFormFields());
		foreach ($values as $name => $value)
		{
			if (array_key_exists($name, $fields))
			{
				$this->$name = $value;
			}
		}
	}

	private function getLoadableFields()
	{
		$validators = $this->getValidators();
		$fields = [];
		foreach ($validators as $validator)
		{
			$fields = array_merge($fields, $validator->getFieldNames());
		}
		return array_unique($fields);
	}

	public function getFieldLabels()
	{
		return [];
	}

	protected function buildFormFields()
	{
		$class = new \ReflectionClass($this);
		$names = [];
		foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property)
		{
			if (!$property->isStatic())
			{
				$names[] = $property->getName();
			}
		}

		return $names;
	}

	public function getFieldLabel($fieldName)
	{
		$labels = $this->getFieldLabels();
		return isset($labels[$fieldName]) ? $labels[$fieldName] : $fieldName;
	}

	# errors

	/**
	 * @param null $fieldName
	 * @return array|FormError[]
	 */
	public function getErrors($fieldName = null)
	{
		if ($fieldName === null)
		{
			return $this->errors;
		}
		$res = [];
		foreach ($this->errors as $error)
		{
			if ($error->getCode() === $fieldName)
			{
				$res[] = $error;
			}
		}
		return $res;
	}

	/**
	 * @return array|FormError[]
	 */
	public function getFirstErrors()
	{
		if (empty($this->errors))
		{
			return [];
		}

		$results = [];
		foreach ($this->errors as $error)
		{
			if (empty($results[$error->getCode()]))
			{
				$results[$error->getCode()] = $error;
			}
		}
		return array_values($results);
	}

	/**
	 * @param string $fieldName
	 * @return FormError|null
	 */
	public function getFirstError($fieldName = null)
	{
		if (!$this->hasErrors())
		{
			return null;
		}
		if ($fieldName === null)
		{
			return $this->reset($this->getFirstErrors());
		}
		return $this->reset($this->getErrors($fieldName));
	}

	private function reset($resetData)
	{
		return reset($resetData) === false ? null : reset($resetData);
	}

	public function hasErrors($fieldName = null)
	{
		if ($fieldName === null)
		{
			return !empty($this->errors);
		}
		return !empty($this->getErrors($fieldName));
	}

	public function addError($field, $error = '')
	{
		$this->errors[] = new FormError($error, $field);
	}

	public function addErrors(array $items)/*-*///not tested
	{
		foreach ($items as $field => $errors)
		{
			if (is_array($errors))
			{
				foreach ($errors as $error)
				{
					$this->addError($field, $error);
				}
			}
			else
			{
				$this->addError($field, $errors);
			}
		}
	}

	public function clearErrors($attribute = null)
	{
		if ($attribute === null)
		{
			$this->errors = [];
		}
		else
		{
			foreach ($this->errors as $index => $item)
			{
				if ($item->getCode() === $attribute)
				{
					unset($this->errors[$index]);
				}
			}
		}
	}

	public static function validateMultiple(array $forms, $attributeNames = null)
	{
		$valid = true;
		/* @var $form static */
		foreach ($forms as $form)
		{
			$valid = $form->validate($attributeNames) && $valid;
		}

		return $valid;
	}

	public static function loadMultiple($forms, $data, $formName = null)
	{
		if ($formName === null)
		{
			/** @var BaseForm $firstForm */
			$firstForm = reset($forms);
			if ($firstForm === false)
			{
				return false;
			}
			$formName = $firstForm->getFormName();
		}

		$success = false;
		foreach ($forms as $key => $form)
		{
			/** @var BaseForm $form */
			if ($formName == '')
			{
				if (!empty($data[$key]))
				{
					$form->load($data[$key], '');
					$success = true;
				}
			}
			elseif (!empty($data[$formName][$key]))
			{
				$form->load($data[$formName][$key], '');
				$success = true;
			}
		}

		return $success;
	}
}