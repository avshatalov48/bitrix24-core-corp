<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Automation\Factory;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Rest;
use Bitrix\Crm\Automation\Trigger\WebHookTrigger;

class CrmAutomationComponent extends \CBitrixComponent implements Main\Engine\Contract\Controllerable
{
	protected $entity;

	public function configureActions()
	{
		return [];
	}

	protected function getEntityTypeId()
	{
		return isset($this->arParams['ENTITY_TYPE_ID']) ? (int)$this->arParams['ENTITY_TYPE_ID'] : 0;
	}

	protected function getEntityCategoryId()
	{
		return isset($this->arParams['ENTITY_CATEGORY_ID']) ? (int)$this->arParams['ENTITY_CATEGORY_ID'] : null;
	}

	protected function getEntityId()
	{
		return isset($this->arParams['ENTITY_ID']) ? (int)$this->arParams['ENTITY_ID'] : 0;
	}

	public function executeComponent()
	{
		if (!Main\Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
			return;
		}

		if (!Main\Loader::includeModule('bizproc'))
		{
			ShowError(Loc::getMessage('BIZPROC_MODULE_NOT_INSTALLED'));
			return;
		}

		$entityTypeId = $this->getEntityTypeId();
		$entityCategoryId = $this->getEntityCategoryId();

		if (!Factory::isSupported($entityTypeId))
		{
			ShowError(Loc::getMessage('CRM_AUTOMATION_NOT_SUPPORTED'));
			return;
		}

		if (!Factory::isAutomationAvailable($entityTypeId))
		{
			ShowError(Loc::getMessage('CRM_AUTOMATION_NOT_AVAILABLE'));
			return;
		}

		$entityId = $this->getEntityId();
		$entityCaption = '';

		if ($entityId > 0)
		{
			$entityCaption = \CCrmOwnerType::GetCaption($entityTypeId, $entityId);
		}

		if ($entityId > 0 && !$entityCaption)
		{
			$entityCaption = (string) $entityId;
		}

		$this->arResult = array(
			'ENTITY_CAPTION' => $entityCaption,
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_TYPE_NAME' => \CCrmOwnerType::ResolveName($entityTypeId),
			'ENTITY_ID' => $entityId,
			'ENTITY_CATEGORY_ID' => $entityCategoryId,
			'BIZPROC_EDITOR_URL' => $this->getBpDesignerEditUrl($entityTypeId),
			'STATUSES_EDIT_URL' => $this->getStatusesEditUrl($entityTypeId, $entityCategoryId),
			'ROBOTS_LIMIT' => Factory::getRobotsLimit($entityTypeId),
		);

		$this->includeComponentTemplate();
	}

	public function generateWebhookPasswordAction(array $documentType)
	{
		if (
			!Main\Loader::includeModule('crm')
			|| !Main\Loader::includeModule('rest')
			|| !Main\Loader::includeModule('bizproc')
			|| !WebHookTrigger::isEnabled()
			|| !Rest\Engine\Access::isAvailableCount(Rest\Engine\Access::ENTITY_TYPE_WEBHOOK)
		)
		{
			return ['error' => Loc::getMessage('CRM_AUTOMATION_WEBHOOK_NOT_AVAILABLE')];
		}

		$userId = Main\Engine\CurrentUser::get()->getId();

		$entityCategoryId = 0;
		if (isset($documentType[3]))
		{
			$entityCategoryId = $documentType[3];
		}
		$documentType = \CBPHelper::ParseDocumentId($documentType);
		$canCreate = CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::CreateAutomation,
			$userId,
			$documentType,
			['DocumentCategoryId' => $entityCategoryId]
		);

		if (!$canCreate)
		{
			return ['error' => Loc::getMessage('CRM_AUTOMATION_NOT_AVAILABLE')];
		}

		$pwd = WebHookTrigger::touchPassword($userId);

		if (!$pwd)
		{
			return ['error' => Loc::getMessage('CRM_AUTOMATION_WEBHOOK_CREATE_FAILURE')];
		}

		return ['password' => $pwd];
	}

	public function getCallLinesAction(array $documentType, array $property)
	{
		if (
			!Main\Loader::includeModule('crm')
			|| !Main\Loader::includeModule('bizproc')
			|| !Main\Loader::includeModule('voximplant')
		)
		{
			return ['error' => Loc::getMessage('CRM_AUTOMATION_NOT_AVAILABLE')];
		}

		$userId = Main\Engine\CurrentUser::get()->getId();
		$entityCategoryId = 0;

		if (isset($documentType[3]))
		{
			$entityCategoryId = $documentType[3];
		}

		$documentType = \CBPHelper::ParseDocumentId($documentType);
		$canCreate = CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::CreateAutomation,
			$userId,
			$documentType,
			['DocumentCategoryId' => $entityCategoryId]
		);

		if (!$canCreate)
		{
			return ['error' => Loc::getMessage('CRM_AUTOMATION_NOT_AVAILABLE')];
		}

		return [
			'options' => array_values(array_map(
				function($line)
				{
					return ['value' => $line['LINE_NUMBER'], 'name' => $line['SHORT_NAME']];
				},
				\CVoxImplantConfig::GetLines(false, true)
			)),
		];
	}

	private function getBpDesignerEditUrl($entityTypeId)
	{
		if (!Factory::canUseBizprocDesigner())
			return '';

		// full designer is not available for now
		if (!Factory::isBizprocDesignerSupported($entityTypeId))
		{
			return null;
		}

		$siteDir = isset($this->arParams['SITE_DIR']) ? (string)$this->arParams['SITE_DIR'] : SITE_DIR;
		$siteDir = rtrim($siteDir, '/');
		$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);

		$url = "{$siteDir}/crm/configs/bp/CRM_{$entityTypeName}/edit/#ID#/";

		if (!empty($this->arParams['back_url']))
		{
			$url .= '?back_url='.urlencode($this->arParams['back_url']);
		}

		return $url;
	}

	private function getStatusesEditUrl($entityTypeId, $categoryId)
	{
		if ($entityTypeId === \CCrmOwnerType::Order)
		{
			return SITE_DIR.'crm/configs/sale/?type=order';
		}

		$statusId = '';

		switch ($entityTypeId)
		{
			case CCrmOwnerType::Deal:
				$statusId = DealCategory::getStatusEntityID($categoryId);
				break;
			case CCrmOwnerType::Lead:
				$statusId = 'STATUS';
				break;
		}

		if ($statusId === '')
		{
			$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory((int)$entityTypeId);
			if ($factory)
			{
				$statusId = $factory->getStagesEntityId((int) $categoryId);
			}
		}

		return CComponentEngine::MakePathFromTemplate(
			SITE_DIR.'crm/configs/status/?ACTIVE_TAB=status_tab_#status_id#',
			array('status_id' => $statusId)
		);
	}
}