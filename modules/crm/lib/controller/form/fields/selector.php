<?php
namespace Bitrix\Crm\Controller\Form\Fields;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\WebForm\EntityFieldProvider;
use Bitrix\Main;
use Bitrix\Crm\WebForm;
use Bitrix\Main\Localization\Loc;

class Selector extends Main\Engine\JsonController
{
	public function getDataAction($options = []): array
	{
		return [
			'fields' => $this->getFieldsList($options),
			'options' => [
				'isLeadEnabled' => LeadSettings::getCurrent()->isEnabled(),
				'permissions' => [
					'userField' => [
						'add' => Container::getInstance()->getUserPermissions()->canWriteConfig(),
					],
				],
			],
		];
	}

	protected function getFieldsList($options = []): array
	{
		if (!WebForm\Manager::checkReadPermission())
		{
			$this->addError(new Main\Error(Loc::getMessage('CRM_CONTROLLER_FORM_FIELD_ACCESS_DENIED'), 510));
			return [];
		}

		$hiddenTypes = [];
		if ((int)($options['hideVirtual'] ?? 0))
		{
			$hiddenTypes[] = EntityFieldProvider::TYPE_VIRTUAL;
		}
		if ((int)($options['hideRequisites'] ?? 1))
		{
			$hiddenTypes[] = \CCrmOwnerType::Requisite;
		}
		if ((int)($options['hideSmartDocument'] ?? 0))
		{
			$hiddenTypes[] = \CCrmOwnerType::SmartDocument;
		}

		$fields = EntityFieldProvider::getFieldsTree($hiddenTypes);
		foreach ($fields as $key => $item)
		{
			if (strpos($key, 'DYNAMIC_') === 0)
			{
				$dynamicId = str_replace('DYNAMIC_', '', $key);
				$fields[$key]["DYNAMIC_ID"] = \CCrmOwnerType::ResolveUserFieldEntityID($dynamicId);
			}
		}

		return $fields;
	}
}
