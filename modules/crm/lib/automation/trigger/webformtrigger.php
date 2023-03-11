<?php

namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;

class WebFormTrigger extends BaseTrigger
{
	public static function getCode()
	{
		return 'WEBFORM';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_WEBFORM_NAME_1');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (
			is_array($trigger['APPLY_RULES'])
			&& isset($trigger['APPLY_RULES']['form_id'])
			&& $trigger['APPLY_RULES']['form_id'] > 0
		)
		{
			return (int)$trigger['APPLY_RULES']['form_id'] === (int)$this->getInputData('WEBFORM_ID');
		}
		return true;
	}

	protected static function getPropertiesMap(): array
	{
		return [
			[
				'Id' => 'form_id',
				'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_WEBFORM_PROPERTY_FORM'),
				'Type' => 'select',
				'EmptyValueText' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_WEBFORM_DEFAULT_FORM'),
				'Options' => static::getFormList(),
			],
		];
	}

	public static function getGroup(): array
	{
		return ['other'];
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_WEBFORM_DESCRIPTION') ?? '';
	}

	protected static function getFormList(array $filter = []): array
	{
		$forms = \Bitrix\Crm\WebForm\Internals\FormTable::getDefaultTypeList([
			'select' => ['ID', 'NAME'],
			'order' => ['NAME' => 'ASC', 'ID' => 'ASC'],
			'filter' => $filter,
		])->fetchAll();

		return array_map(
			fn ($form) => ['value' => $form['ID'], 'name' => $form['NAME']],
			$forms
		);
	}
}
