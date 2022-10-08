<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Timeline;

Loc::loadMessages(__FILE__);

class CCrmNewEntityCounterPanelComponent extends CBitrixComponent
{
	/** @var int */
	protected $userID = 0;
	/** @var string */
	protected $guid = '';
	/** @var string */
	protected $gridID = '';
	/** @var string */
	protected $entityTypeName = '';
	/** @var int */
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	/** @var int */
	protected $entityLastID = 0;
	/** @var string */
	protected $pullTagName = "";
	/** @var array */
	protected $pullCommands = array();
	/** @var array */
	protected $errors = array();

	public function executeComponent()
	{
		$this->initialize();
		$this->includeComponentTemplate();
	}
	protected function initialize()
	{
		$this->userID = $this->arResult['USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();

		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : 'new_entity_counter_panel';
		if(isset($this->arParams['ENTITY_TYPE_NAME']))
		{
			$this->entityTypeName = $this->arParams['ENTITY_TYPE_NAME'];
		}

		$this->gridID = $this->arResult['GRID_ID'] = isset($this->arParams['GRID_ID'])
			? $this->arParams['GRID_ID'] : '';

		$this->entityTypeID = CCrmOwnerType::ResolveID($this->entityTypeName);
		if(!CCrmOwnerType::IsDefined($this->entityTypeID))
		{
			$this->errors[] = GetMessage('CRM_NEW_ENT_COUNTER_ENTITY_TYPE_NOT_DEFINED');
			return;
		}

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['ENTITY_TYPE_NAME'] = $this->entityTypeName;
		$this->arResult['CATEGORY_ID'] = isset($this->arParams['CATEGORY_ID']) ? (int)$this->arParams['CATEGORY_ID'] : null;

		$entity = \Bitrix\Crm\Entity\EntityManager::resolveByTypeID($this->entityTypeID);
		$this->arResult['ENTITY_LAST_ID'] = $this->entityLastID = $entity->getLastID($this->userID, true);

		if(Bitrix\Main\Loader::includeModule('pull'))
		{
			$controller = \Bitrix\Crm\Timeline\TimelineManager::resolveController(
				array('ASSOCIATED_ENTITY_TYPE_ID' => $this->entityTypeID)
			);

			if($controller)
			{
				$this->pullCommands = $this->arResult['PULL_COMMANDS'] = $controller->getSupportedPullCommands();
				$this->pullTagName = $this->arResult['PULL_TAG_NAME'] = $controller->prepareEntityPushTag(0);

				\CPullWatch::Add($this->userID, $this->pullTagName);
			}
		}

		/*
		 * Messages are used:
		 * CRM_NEW_ENT_COUNTER_DEAL_CAPTION
		 * CRM_NEW_ENT_COUNTER_LEAD_CAPTION
		 * CRM_NEW_ENT_COUNTER_CONTACT_CAPTION
		 * CRM_NEW_ENT_COUNTER_COMPANY_CAPTION
		 */
		$this->arResult['ENTITY_CAPTION'] = Loc::getMessage("CRM_NEW_ENT_COUNTER_{$this->entityTypeName}_CAPTION");
	}
}