<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Crm\Integration\DocumentGeneratorManager;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class DocumentCreateTrigger extends BaseTrigger
{
	public static function isEnabled()
	{
		return DocumentGeneratorManager::getInstance()->isEnabled();
	}

	public static function getCode()
	{
		return 'DOCUMENT_CREATE';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_DOCUMENT_CREATE_NAME');
	}

	public function checkApplyRules(array $trigger)
	{
		if (!parent::checkApplyRules($trigger))
		{
			return false;
		}

		if (
			is_array($trigger['APPLY_RULES'])
			&& isset($trigger['APPLY_RULES']['TEMPLATE_ID'])
			&& $trigger['APPLY_RULES']['TEMPLATE_ID'] > 0
		)
		{
			return (int)$trigger['APPLY_RULES']['TEMPLATE_ID'] === (int)$this->getInputData('TEMPLATE_ID');
		}

		return true;
	}

	public static function toArray()
	{
		$result = parent::toArray();

		//get entity type id
		$entityTypeId = func_get_arg(0);

		if (static::isEnabled())
		{
			$result['TEMPLATE_LIST'] = [];
			$result['TEMPLATE_LABEL'] = Loc::getMessage('CRM_AUTOMATION_TRIGGER_DOCUMENT_CREATE_TEMPLATE_LABEL');
			$provider = DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvidersMap()[$entityTypeId];
			if ($provider)
			{
				foreach (\Bitrix\DocumentGenerator\Model\TemplateTable::getListByClassName($provider) as $tpl)
				{
					$result['TEMPLATE_LIST'][] = ['ID' => $tpl['ID'], 'NAME' => $tpl['NAME']];
				}
			}
		}
		return $result;
	}
}