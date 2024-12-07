<?php
namespace Bitrix\Crm\Controller\Form\Fields;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Crm\WebForm\EntityFieldProvider;
use Bitrix\Main;
use Bitrix\Crm\WebForm;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sign\Access\AccessController;
use Bitrix\Sign\Access\ActionDictionary;

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
		if (!$this->checkPermissionForFieldList())
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
		if ((int)($options['hideSmartB2eDocument'] ?? 0))
		{
			$hiddenTypes[] = \CCrmOwnerType::SmartB2eDocument;
		}
		if (isset($options['presetId']) && is_numeric($options['presetId']))
		{
			$presetId = (int)$options['presetId'];
		}
		else
		{
			$presetId = null;
		}

		$fields = EntityFieldProvider::getFieldsTree($hiddenTypes, $presetId);
		foreach ($fields as $key => $item)
		{
			if (str_starts_with($key, 'DYNAMIC_'))
			{
				$dynamicId = str_replace('DYNAMIC_', '', $key);
				$fields[$key]["DYNAMIC_ID"] = \CCrmOwnerType::ResolveUserFieldEntityID($dynamicId);
			}
		}

		return $fields;
	}

	public function checkPermissionForFieldList(): bool
	{
		$result = WebForm\Manager::checkReadPermission();
		if (!$result && Main\Loader::includeModule('sign'))
		{
			$canAddDocumentB2e = AccessController::can(
				Main\Engine\CurrentUser::get()->getId(),
				ActionDictionary::ACTION_B2E_DOCUMENT_ADD
			);
			$canAddDocumentB2b = AccessController::can(
				Main\Engine\CurrentUser::get()->getId(),
				ActionDictionary::ACTION_DOCUMENT_ADD
			);
			$result = $canAddDocumentB2b || $canAddDocumentB2e;
		}

		return $result;
	}
}
