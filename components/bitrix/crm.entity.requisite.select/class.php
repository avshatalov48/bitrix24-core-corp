<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmEntityRequisiteSelectComponent extends CBitrixComponent
{
	/** @var int */
	protected $userID = 0;
	/** @var int */
	protected $entityTypeID = 0;
	/** @var int */
	protected $entityID = 0;
	/** @var string */
	protected $guid = '';
	/** @var array */
	protected $errors = array();

	public function executeComponent()
	{
		$this->initialize();
		$this->includeComponentTemplate();
	}

	protected function initialize()
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return;
		}

		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID']) ? $this->arParams['GUID'] : 'entity_requisite_select';

		$this->entityTypeID = isset($this->arParams['ENTITY_TYPE_ID'])
			? (int)$this->arParams['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		$this->entityID = isset($this->arParams['ENTITY_ID'])
			? (int)$this->arParams['ENTITY_ID'] : 0;

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['ENTITY_ID'] = $this->entityID;

		$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context_id');
		if($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
		{
			$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context');
			if($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
			{
				$this->arResult['EXTERNAL_CONTEXT_ID'] = '';
			}
		}

		$this->arResult['REQUISITE_ID'] = $this->request->get('requisite_id');
		$this->arResult['REQUISITE_ID'] = isset($this->arResult['REQUISITE_ID']) ? (int)$this->arResult['REQUISITE_ID'] : 0;

		$this->arResult['BANK_DETAIL_ID'] = $this->request->get('bank_detail_id');
		$this->arResult['BANK_DETAIL_ID'] = isset($this->arResult['BANK_DETAIL_ID']) ? (int)$this->arResult['BANK_DETAIL_ID'] : 0;

		$this->arResult['IFRAME'] = isset($this->request['IFRAME']) && $this->request['IFRAME'] === 'Y';
		$this->arResult['IFRAME_USE_SCROLL'] = $this->request['IFRAME_USE_SCROLL'] == 'Y';

		//todo: check entity_type_id, entity_id & permissions
		$this->arResult['REQUISITE_DATA'] = \CCrmEntitySelectorHelper::PrepareRequisiteData(
			$this->entityTypeID,
			$this->entityID,
			array('VIEW_DATA_ONLY' => true)
		);

		$this->arResult['ENTITY_DATA'] = array(
			'REQUISITE_ID' => $this->arResult['REQUISITE_ID'],
			'BANK_DETAIL_ID' => $this->arResult['BANK_DETAIL_ID']
		);

		$this->arResult['ENTITY_FIELDS'] = array(
			array(
				'name' => 'SELECTOR',
				'title' => Loc::getMessage('CRM_ENTITY_RQ_SEL_TITLE'),
				'type' => 'requisite_selector',
				'editable' => true,
				'required' => true,
				'data' => array('data' => $this->arResult['REQUISITE_DATA'])
			)
		);

		//region Config
		$this->arResult['ENTITY_CONFIG'] = array(
			array(
				'name' => 'main',
				'title' => Loc::getMessage('CRM_ENTITY_RQ_SEL_TITLE'),
				'type' => 'section',
				'elements' => array(array('name' => 'SELECTOR'))
			)
		);
		//endregion
	}
}