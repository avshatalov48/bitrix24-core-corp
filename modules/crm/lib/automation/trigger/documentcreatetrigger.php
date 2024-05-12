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
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_DOCUMENT_CREATE_NAME_1');
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
			$provider = DocumentGeneratorManager::getInstance()->getCrmOwnerTypeProvider($entityTypeId);
			if ($provider)
			{
				$result['SETTINGS']['Properties'] = [
					[
						'Id' => 'TEMPLATE_ID',
						'Name' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_DOCUMENT_CREATE_TEMPLATE_LABEL'),
						'Type' => 'select',
						'EmptyValueText' => Loc::getMessage('CRM_AUTOMATION_TRIGGER_DOCUMENT_CREATE_TEMPLATE_ALL_LABEL'),
						'Options' => array_map(
							function($tpl)
							{
								return ['value' => $tpl['ID'], 'name' => $tpl['NAME']];
							},
							\Bitrix\DocumentGenerator\Model\TemplateTable::getListByClassName($provider)
						),
					],
				];
			}
		}

		return $result;
	}

	public static function getDescription(): string
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_DOCUMENT_CREATE_DESCRIPTION') ?? '';
	}

	public static function getGroup(): array
	{
		return ['paperwork'];
	}
}