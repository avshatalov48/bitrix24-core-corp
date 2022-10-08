<?php
namespace Bitrix\Crm\Automation\Trigger;

Use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CallBackTrigger extends BaseTrigger
{
	protected static function areDynamicTypesSupported(): bool
	{
		return false;
	}

	public static function getCode()
	{
		return 'CALLBACK';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_CALLBACK_NAME');
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

	public static function toArray()
	{
		$result = parent::toArray();
		if (static::isEnabled())
		{
			$forms = \Bitrix\Crm\WebForm\Internals\FormTable::getDefaultTypeList(array(
				'select' => ['ID', 'NAME'],
				'order' => ['NAME' => 'ASC', 'ID' => 'ASC'],
				'filter' => ['=IS_CALLBACK_FORM' => 'Y']
			))->fetchAll();
			$result['WEBFORM_LIST'] = $forms;
		}
		return $result;
	}
}