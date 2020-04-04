<?php
namespace Bitrix\Timeman\Form\Security;

use Bitrix\Main\Localization\Loc;
use Bitrix\Timeman\Util\Form\CompositeForm;
use Bitrix\Timeman\Util\Form\Filter;

Loc::loadMessages(__FILE__);

/**
 * @property OperationForm[] $operationForms
 */
class TaskForm extends CompositeForm
{
	public $name;
	public $id;
	public $isSystem;

	public function __construct($task = null, $operations = [])
	{
		$forms = [];
		if ($task !== null)
		{
			$this->name = $task['NAME'];
			$this->id = $task['ID'];
			$this->isSystem = $task['SYS'];
		}
		foreach ($operations as $operation)
		{
			$form = new OperationForm($operation);
			$forms[] = $form;
		}
		$this->operationForms = $forms;
	}

	public function configureFilterRules()
	{
		return [
			(new Filter\Modifier\StringModifier('name'))
				->configureTrim(true)
			,
			(new Filter\Validator\StringValidator('name'))
			,
			(new Filter\Validator\RequiredValidator('name'))
				->configureDefaultErrorMessage('TM_TASK_FORM_NAME_REQUIRED')
			,
			(new Filter\Validator\NumberValidator('id'))
				->configureMin(1)
				->configureIntegerOnly(true)
			,
			(new Filter\Validator\RangeValidator('isSystem'))
				->configureRange(['Y', 'N'])
				->configureStrict(true)
			,
		];
	}

	public function hasOperation($operationName)
	{
		foreach ($this->operationForms as $operationForm)
		{
			if ($operationForm->name === $operationName)
			{
				return true;
			}
		}
		return false;
	}

	public function getOperationsNames()
	{
		$names = [];
		foreach ($this->operationForms as $form)
		{
			if ($form->name)
			{
				$names[$form->name] = true;
			}
		}
		return array_keys($names);
	}

	/**
	 * @return array 'name' => class
	 */
	protected function getInternalForms()
	{
		return [
			'operationForms' => OperationForm::class,
		];
	}
}