<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Settings\LeadSettings;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Category\DealCategory;

Loc::loadMessages(__FILE__);

class CrmAutomationComponent extends \CBitrixComponent
{
	protected $entity;

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
		);

		$this->includeComponentTemplate();
	}

	private function getBpDesignerEditUrl($entityTypeId)
	{
		if (!Factory::canUseBizprocDesigner())
			return '';

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

		return CComponentEngine::MakePathFromTemplate(
			SITE_DIR.'crm/configs/status/?ACTIVE_TAB=status_tab_#status_id#',
			array('status_id' => $statusId)
		);
	}
}