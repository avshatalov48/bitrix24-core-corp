<?php
namespace Bitrix\Timeman\Util\Form;

use Bitrix\Timeman\Util\ArrayHelper;
use CUtil;

abstract class CompositeForm extends BaseForm
{
	private $nestedForms = [];

	/**
	 * @return array 'name' => class
	 */
	abstract protected function getInternalForms();

	public function load($data, $formName = null)
	{
		$success = parent::load($data, $formName);

		$parentData = $this->getBaseFormData($data, $formName);
		foreach ($this->nestedForms as $name => $form)
		{
			if (is_array($form))
			{
				$success = parent::loadMultiple($form, $parentData, $formName === null ? null : $name) || $success;
			}
			else
			{
				/** @var BaseForm $form */
				$success = $form->load($parentData, $formName !== '' ? null : $name) || $success;
			}
		}
		return $success;
	}

	protected function runBeforeLoad($data, $formName)
	{
		parent::runBeforeLoad($data, $formName);
		$parentData = $this->getBaseFormData($data, $formName);

		$internalForms = $this->getInternalForms();
		foreach ($internalForms as $formAlias => $formClass)
		{
			/** @var BaseForm $form */
			$form = new $formClass;
			$formName = $formName === '' ? $formAlias : $form->getFormName();
			if (isset($parentData[$formName]))
			{
				if (is_array($parentData[$formName]))
				{
					if ($this->hasMultiForms($parentData[$formName]))
					{
						$forms = [];
						foreach ($parentData[$formName] as $key => $formData)
						{
							$forms[$key] = new $formClass;
						}
						$this->$formAlias = $forms;
					}
					else
					{
						if (!$this->$formAlias)
						{
							$this->$formAlias = new $formClass;
						}
					}
				}
			}
			else
			{
				if (is_array($this->nestedForms[$formAlias] ?? null))
				{
					$filledForm = false;
					foreach ($this->nestedForms[$formAlias] as $innerForm)
					{
						if ($filledForm)
						{
							break;
						}
						$reflect = new \ReflectionClass($innerForm);
						$props = array_diff($reflect->getProperties(\ReflectionProperty::IS_PUBLIC), $reflect->getParentClass()->getProperties(\ReflectionProperty::IS_PUBLIC));
						foreach ($props as $prop)
						{
							if (!$this->isEmpty($prop->getValue($innerForm)))
							{
								$filledForm = true;
								break;
							}
						}
					}
					if (!$filledForm)
					{
						unset($this->nestedForms[$formAlias]);
					}
				}
			}
		}
	}

	private function isEmpty($value)
	{
		return $value === '' || $value === [] || $value === null;
	}

	private function hasMultiForms($data)
	{
		foreach ($data as $item)
		{
			if (!is_array($item))
			{
				return false;
			}
		}
		return true;
	}

	public function validate($fieldsNames = null, $clearErrors = true)
	{
		if ($fieldsNames !== null)
		{
			$parentNames = array_filter($fieldsNames, 'is_string');
			$success = $parentNames ? parent::validate($parentNames, $clearErrors) : true;
		}
		else
		{
			$success = parent::validate(null, $clearErrors);
		}
		foreach ($this->nestedForms as $name => $form)
		{
			if ($fieldsNames === null || array_key_exists($name, $fieldsNames) || in_array($name, $fieldsNames, true))
			{
				$innerNames = ArrayHelper::getValue($fieldsNames, $name);
				if (is_array($form))
				{
					$success = parent::validateMultiple($form, $innerNames) && $success;
				}
				else
				{
					$success = $form->validate($innerNames, $clearErrors) && $success;
				}
			}
		}
		return $success;
	}

	public function hasErrors($fieldName = null)
	{
		if ($fieldName !== null && strpos($fieldName, '.') === false)
		{
			return parent::hasErrors($fieldName);
		}
		if (parent::hasErrors($fieldName))
		{
			return true;
		}
		foreach ($this->nestedForms as $name => $form)
		{
			if (is_array($form))
			{
				foreach ($form as $index => $item)
				{
					if ($fieldName === null)
					{
						if ($item->hasErrors())
						{
							return true;
						}
					}
					elseif (strpos($fieldName, $name . '.' . $index . '.') === 0)
					{
						if ($item->hasErrors(substr($fieldName, strlen($name . '.' . $index . '.'))))
						{
							return true;
						}
					}
				}
			}
			else
			{
				if ($fieldName === null)
				{
					if ($form->hasErrors())
					{
						return true;
					}
				}
				elseif (strpos($fieldName, $name . '.') === 0)
				{
					if ($form->hasErrors(substr($fieldName, strlen($name . '.'))))
					{
						return true;
					}
				}
			}
		}
		return false;
	}

	public function getFirstErrors()
	{
		$result = parent::getFirstErrors();
		foreach ($result as $index => $error)
		{
			$result[$error->getCode()] = $error;
			unset($result[$index]);
		}
		foreach ($this->nestedForms as $name => $form)
		{
			if (is_array($form))
			{
				foreach ($form as $index => $item)
				{
					foreach ($item->getFirstErrors() as $error)
					{
						$result[$name . '.' . $index . '.' . $error->getCode()] = $error;
					}
				}
			}
			else
			{
				foreach ($form->getFirstErrors() as $error)
				{
					$result[$name . '.' . $error->getCode()] = $error;
				}
			}
		}
		return $result;
	}

	public function getErrors($fieldName = null)
	{
		$result = parent::getErrors($fieldName);
		foreach ($result as $index => $error)
		{
			if ($fieldName === null)
			{
				$result[$error->getCode()][] = $error;
				unset($result[$index]);
			}
		}
		foreach ($this->nestedForms as $name => $form)
		{
			if (is_array($form))
			{
				foreach ($form as $index => $item)
				{
					foreach ($item->getErrors() as $error)
					{
						/** @var FormError $error */
						$errorField = $name . '.' . $index . '.' . $error->getCode();
						if ($fieldName === null)
						{
							$result[$errorField][] = $error;
						}
						elseif ($errorField === $fieldName)
						{
							$result[] = $error;
						}
					}
				}
			}
			else
			{
				foreach ($form->getErrors() as $error)
				{
					/** @var FormError $error */
					$errorField = $name . '.' . $error->getCode();
					if ($fieldName === null)
					{
						$result[$errorField][] = $error;
					}
					elseif ($errorField === $fieldName)
					{
						$result[] = $error;
					}
				}
			}
		}
		return $result;
	}

	private function getBaseFormData($data, $formName)
	{
		if ($formName === '')
		{
			return $data;
		}
		$formNameParent = $formName;
		if ($formNameParent === null)
		{
			$formNameParent = $this->getFormName();
		}

		return isset($data[$formNameParent]) ? $data[$formNameParent] : $data;
	}

	public function __get($name)
	{
		if (isset($this->nestedForms[$name]))
		{
			return $this->nestedForms[$name];
		}
		return null;
	}

	public function __set($name, $value)
	{
		if (in_array($name, array_keys($this->getInternalForms()), true))
		{
			$this->nestedForms[$name] = $value;
		}
	}

	public function __isset($name)
	{
		return isset($this->nestedForms[$name]);
	}

	public function __clone()
	{
		$forms = [];
		foreach ($this->nestedForms as $key => $form)
		{
			if (is_array($form))
			{
				foreach ($form as $formKey => $item)
				{
					$forms[$key][$formKey] = clone $item;
				}
			}
			else
			{
				$forms[$key] = clone $form;
			}
		}
		$this->nestedForms = $forms;
	}
}