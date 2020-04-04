<?php
namespace Bitrix\Timeman\Form\Security;

use Bitrix\Timeman\Security\UserPermissionsManager;
use Bitrix\Timeman\Util\Form\BaseForm;
use Bitrix\Timeman\Util\Form\Filter;

class OperationForm extends BaseForm
{
	public $name;

	public function __construct($operation = null)
	{
		if ($operation !== null)
		{
			$this->name = $operation['NAME'];
		}
	}

	public function configureFilterRules()
	{
		return [
			(new Filter\Modifier\StringModifier('name'))
				->configureTrim(true)
			,
			(new Filter\Validator\StringValidator('name'))
			,
			(new Filter\Validator\RangeValidator('name'))
				->configureRange(UserPermissionsManager::getOperationsNames())
				->configureStrict(true)
			,
		];
	}
}