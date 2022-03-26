<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Activity;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CrmActivityCallListComponent extends \CBitrixComponent
{
	const ACTION_VIEW = 'VIEW';
	const ACTION_EDIT = 'EDIT';
	const ACTION_RELOAD = 'RELOAD';
	const ACTION_GRID_PAGE = 'GET_GRID_PAGE';
	const GRID_ID = 'CRM_CALL_LIST_ACTIVITY';

	public function executeComponent()
	{
		if(!Main\Loader::includeModule('voximplant'))
		{
			echo Loc::getMessage('VOXIMPLANT_MODULE_NOT_INSTALLED');
			return;
		}
			
		$action = $this->getAction();
		switch ($action)
		{
			case self::ACTION_EDIT:
				return $this->executeEditAction();
				break;
			case self::ACTION_RELOAD:
				return $this->executeReloadAction();
				break;
			case self::ACTION_GRID_PAGE:
				return $this->executeGetGridPage();
				break;
			default:
				return $this->executeViewAction();
				break;
		}
	}

	public static function createCallList($request)
	{
		$entityType = (string)$request['ENTITY_TYPE'];
		$entityIds = (array)$request['ENTITY_IDS'];
		$gridId = (string)$request['GRID_ID'];
		$createActivity = $request['CREATE_ACTIVITY'] == 'Y';
		$result = new Main\Result();
		
		if(!\Bitrix\Crm\CallList\CallList::isAvailable())
		{
			$result->setData([
				'RESTRICTION' => \Bitrix\Crm\Restriction\RestrictionManager::getCallListRestriction()->prepareInfoHelperScript()
			]);
			return $result;
		}

		try{
			if(count($entityIds) == 0)
			{
				$callList = \Bitrix\Crm\CallList\CallList::createWithGridId($entityType, $gridId);
			}
			else
			{
				$filterParams = '';
				if($entityType === CCrmOwnerType::LeadName)
					$filterParams = Loc::getMessage('CRM_CALL_LIST_ITEM_LEADS_HAND_SELECTED');
				else if($entityType === CCrmOwnerType::ContactName)
					$filterParams = Loc::getMessage('CRM_CALL_LIST_ITEM_CONTACTS_HAND_SELECTED');
				else if($entityType === CCrmOwnerType::CompanyName)
					$filterParams = Loc::getMessage('CRM_CALL_LIST_ITEM_COMPANIES_HAND_SELECTED');

				$callList = \Bitrix\Crm\CallList\CallList::createWithEntities($entityType, $entityIds);
			}

			$callList->persist();
			if($createActivity)
			{
				$createActivityResult = $callList->createActivity();
				if(!$createActivityResult->isSuccess())
				{
					$result->addErrors($createActivityResult->getErrors());
					return $result;
				}
			}

			$result->setData($callList->toArray());
		}
		catch (Main\SystemException $e)
		{
			$result->addError(new Main\Error($e->getMessage()));
		}

		return $result;
	}

	public static function addToCallList($request)
	{
		$callListId = (int)$request['CALL_LIST_ID'];
		$entityType = (string)$request['ENTITY_TYPE'];
		$entityTypeId = CCrmOwnerType::ResolveID($entityType);
		$entityIds = (array)$request['ENTITY_IDS'];
		$gridId = (string)$request['GRID_ID'];
		$result = new Main\Result();
		
		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, true);

			if($callList->getEntityTypeId() != CCrmOwnerType::Undefined && $callList->getEntityTypeId() != $entityTypeId)
			{
				$result->addError(new Main\Error(Loc::getMessage('CRM_CALL_LIST_ERROR_WRONG_ITEM_TYPE')));
				return $result;
			}

			$callList->setEntityTypeId($entityTypeId);
			$callList->setFilterParameters(null);

			if(is_array($entityIds) && count($entityIds) > 0)
			{
				$callList->addEntities($entityIds);
			}
			else if($gridId != '')
			{
				$callList->addEntitiesFromGrid($gridId);
			}

			$callList->persist();
		}
		catch (Main\SystemException $e)
		{
			$result->addError(new Main\Error($e->getMessage()));
			return $result;
		}

		$message = Loc::getMessage('CRM_CALL_LIST_ENTITIES_ADDED', array('#ENTITIES#' => static::getEntityCaption($entityTypeId, true)));
		$result->setData(array(
			'MESSAGE' => $message
		));
		return $result;
	}

	public function prepareItems(\Bitrix\Crm\CallList\CallList $callList)
	{
		$result = array();
		$result['GRID_ID'] = self::GRID_ID;
		$result['COLUMNS'] = $this->getColumns();
		$gridOptions = new CGridOptions(self::GRID_ID);
		$pageSize = 10;

		$nav = new \Bitrix\Main\UI\PageNavigation("page");
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$row = \Bitrix\Crm\CallList\Internals\CallListItemTable::getList(array(
			'select' => array('CNT'),
			'filter' => array(
				'=LIST_ID' => $callList->getId(),
				'=ENTITY_TYPE_ID' => $callList->getEntityTypeId()
			)
		))->fetch();

		if($row)
		{
			$nav->setRecordCount($row['CNT']);
		}
		$result['ROWS'] = $this->getRows($callList, $nav->getOffset(), $nav->getLimit());
		
		$result['NAV_OBJECT'] = $nav;

		return $result;
	}

	public static function getAvatar($entityType, $entityId, $userId)
	{
		$findParams = array('USER_ID'=> $userId);

		$entityTypeId = CCrmOwnerType::ResolveID($entityType);
		$entityId = (int)$entityId;
		$entityFields = CCrmSipHelper::getEntityFields($entityTypeId, $entityId, $findParams);
		if(!isset($entityFields['PHOTO']))
			return '';

		$photoInfo = CFile::ResizeImageGet($entityFields['PHOTO'], array('width' => 300, 'height' => 300), BX_RESIZE_IMAGE_EXACT);
		if(is_array($photoInfo) && isset($photoInfo['src']))
			return $photoInfo['src'];
		else
			return '';
	}
	
	protected function getColumns()
	{
		return array(
			array("id" => "ENTITY_NAME", "name" => GetMessage("CRM_CALL_LIST_ENTITY_NAME"), "default" => true, "width" => 200),
			array("id" => "STATUS", "name" => GetMessage("CRM_CALL_LIST_ITEM_STATUS"), "default" => true, "width" => 200),
			array("id" => "RECORD", "name" => GetMessage("CRM_CALL_LIST_ITEM_CALL_RECORD"), "default" => true, "width" => 200),
			array("id" => "WEBFORM_ACTIVITY", "name" => GetMessage("CRM_CALL_LIST_ITEM_WEBFORM_ACTIVITY"), "default" => true, "width" => 200),
			array("id" => "CREATED", "name" => GetMessage("CRM_CALL_LIST_ITEM_CREATED"), "default" => true, "width" => 200),
		);
	}

	protected function getRows(\Bitrix\Crm\CallList\CallList $callList, $offset, $limit)
	{
		$result = array();
		$entityIds = array();
		$cursor = \Bitrix\Crm\CallList\Internals\CallListItemTable::getList(array(
			'select' => array('*', 'WEBFORM_ACTIVITY_ID' => 'WEBFORM_ACTIVITY.ID', 'CALL_RECORD_ID' => 'CALL.CALL_RECORD_ID'),
			'filter' => array(
				'=LIST_ID' => $callList->getId(),
				'=ENTITY_TYPE_ID' => $callList->getEntityTypeId()
			),
			'offset' => $offset,
			'limit' => $limit,
			'count_total' => true,
			'order' => array(
				'RANK' => 'ASC'
			)
		));
		
		$records = array();
		$userPermissions = \Bitrix\Crm\Service\Container::getInstance()->getUserPermissions();
		while ($row = $cursor->fetch())
		{
			$entityId = $row['ELEMENT_ID'];
			if (!$userPermissions->checkReadPermissions($callList->getEntityTypeId(), (int)$entityId))
			{
				continue;
			}

			$records[$entityId] = array(
				'ENTITY_ID' => $entityId,
				'STATUS' => \Bitrix\Crm\CallList\Item::getStatusName($row['STATUS_ID']),
				'RECORD' => $this->getRecordHtml($row['CALL_ID'], $row['CALL_RECORD_ID']),
				'WEBFORM_ACTIVITY' => $this->getWebformActivityHtml($row['WEBFORM_ACTIVITY_ID'], $row['WEBFORM_RESULT_ID']),
				'CREATED_ENTITIES' => array(),
				'CREATED' => ''
			);
			$entityIds[] = $entityId;
		}
		unset($row);
		unset($cursor);

		if(count($entityIds) == 0)
			return array();

		$filter = array(
			'=ID' => $entityIds,
			'CHECK_PERMISSIONS' => 'Y'
		);

		switch ($callList->getEntityTypeId())
		{
			case CCrmOwnerType::Lead:
				$cursor = CCrmLead::getListEx(array(), $filter, false, false, array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'TITLE'));
				break;
			case CCrmOwnerType::Contact:
				$cursor = CCrmContact::getListEx(array(), $filter, false, false, array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME'));
				break;
			case CCrmOwnerType::Company:
				$cursor = CCrmCompany::getListEx(array(), $filter, false, false, array('ID', 'TITLE', 'LOGO', 'ASSIGNED_BY_ID'));
				break;
			case CCrmOwnerType::Deal:
				$cursor = CCrmDeal::getListEx(array(), $filter, false, false, array('ID', 'TITLE', 'CONTACT_ID', 'COMPANY_ID', 'ASSIGNED_BY_ID'));
				break;
			case CCrmOwnerType::Quote:
				$cursor = CCrmQuote::getList(array(), $filter, false, false, array('ID', 'TITLE', 'CONTACT_ID', 'COMPANY_ID', 'ASSIGNED_BY_ID'));
				break;
			case CCrmOwnerType::Invoice:
				$cursor = CCrmInvoice::getList(array(), $filter, false, false, array('ID', 'ORDER_TOPIC', 'UF_CONTACT_ID', 'UF_COMPANY_ID'));
				break;
			default:
				return array();
		}

		while($row = $cursor->Fetch())
		{
			switch ($callList->getEntityTypeId())
			{
				case CCrmOwnerType::Lead:
					if($row['NAME'] != '' || $row['SECOND_NAME'] != '' || $row['LAST_NAME'] != '')
						$formattedName = CCrmLead::PrepareFormattedName(
							array(
								'HONORIFIC' => isset($row['HONORIFIC']) ? $row['HONORIFIC'] : '',
								'NAME' => isset($row['NAME']) ? $row['NAME'] : '',
								'SECOND_NAME' => isset($row['SECOND_NAME']) ? $row['SECOND_NAME'] : '',
								'LAST_NAME' => isset($row['LAST_NAME']) ? $row['LAST_NAME'] : ''
							)
						);
					else
						$formattedName = $row['TITLE'];
					break;
				case CCrmOwnerType::Contact:
					$formattedName = CCrmContact::PrepareFormattedName(array(
						'HONORIFIC' => isset($row['HONORIFIC']) ? $row['HONORIFIC'] : '',
						'NAME' => isset($row['NAME']) ? $row['NAME'] : '',
						'SECOND_NAME' => isset($row['SECOND_NAME']) ? $row['SECOND_NAME'] : '',
						'LAST_NAME' => isset($row['LAST_NAME']) ? $row['LAST_NAME'] : ''
					));
					break;
				case CCrmOwnerType::Company:
					$formattedName = $row['TITLE'];
					break;
				case CCrmOwnerType::Deal:
					$formattedName = $row['TITLE'];
					break;
				case CCrmOwnerType::Quote:
					$formattedName = $row['TITLE'];
					break;
				case CCrmOwnerType::Invoice:
					$formattedName = $row['ORDER_TOPIC'];
					break;
			}
			$entityUrl = CCrmOwnerType::GetEntityShowPath($callList->getEntityTypeId(), $row['ID']);
			$records[$row['ID']]['ENTITY_NAME'] = '<a href="'.$entityUrl.'" target="_blank">'.$formattedName.'</a>';
		}
		unset($row);

		// populating created entities
		$cursor = \Bitrix\Crm\CallList\Internals\CallListCreatedTable::getList(array(
			'filter' => array(
				'=LIST_ID' => $callList->getId(),
				'=ELEMENT_ID' => $entityIds
			)
		));
		while($row = $cursor->fetch())
		{
			$records[$row['ELEMENT_ID']]['CREATED_ENTITIES'][] = array(
				'TYPE' => $row['ENTITY_TYPE'],
				'ID' => $row['ENTITY_ID'],
			);
		}

		foreach ($records as $id => $record)
		{
			$record['CREATED'] = $this->getCreatedHtml(
				is_array($record['CREATED_ENTITIES']) ? $record['CREATED_ENTITIES'] : array()
			);
			unset($record['CREATED_ENTITIES']);
			$row = array(
				"columns" => $record,
				"id" => (string)$id,
				"data" => array(),
				"editable" => true,
				"actions" => array(
					array(
						"TITLE" => Loc::getMessage("CRM_CALL_LIST_DELETE"),
						"TEXT" => Loc::getMessage("CRM_CALL_LIST_DELETE"),
						"ONCLICK" => "BX.CallListActivity.getLast().deleteItems([".(int)$id."])"
					)
				),
			);
			$result[] = $row;
		}

		return $result;
	}

	protected function getAction()
	{
		return isset($this->arParams['ACTION']) ? $this->arParams['ACTION'] : self::ACTION_VIEW;
	}

	protected function getActivity()
	{
		return isset($this->arParams['ACTIVITY']) ? $this->arParams['ACTIVITY'] : array();
	}

	protected function executeEditAction()
	{
		$activity = $this->getActivity();
		$params = array(
			'CALL_LIST_ID' => isset($activity['ASSOCIATED_ENTITY_ID']) ? $activity['ASSOCIATED_ENTITY_ID'] : null,
			'SUBJECT' => $activity['SUBJECT'],
			'DESCRIPTION' => $activity['DESCRIPTION']
		);
		$this->arResult = $this->prepareDataForEdit($params);
		$this->arResult['INITIALIZE_EDITOR'] = true;
		$this->arResult['GRID_ID'] = self::GRID_ID;
		$this->includeComponentTemplate('edit');
		return $this->arResult;
	}

	protected function executeGetGridPage()
	{
		$callList = \Bitrix\Crm\CallList\CallList::createWithId((int)$this->arParams['CALL_LIST_ID']);
		$this->arResult = array(
			'CALL_LIST' => $callList->toArray(),
			'ALLOW_EDIT' => $this->arParams['ALLOW_EDIT']
		);

		$this->arResult['CALL_LIST']['NEW'] = false;
		$this->arResult['CALL_LIST']['ITEMS'] = $this->prepareItems($callList);
		$this->includeComponentTemplate('grid');
		return $this->arResult;
	}

	protected function executeViewAction()
	{
		$this->arResult = $this->prepareDataForView();
		$this->arResult['GRID_ID'] = self::GRID_ID;
		$this->includeComponentTemplate('view');
		return $this->arResult;
	}
	
	protected function executeReloadAction()
	{
		$params = array(
			'CALL_LIST_ID' => $this->arParams['CALL_LIST_ID'],
			'SUBJECT' => $this->arParams['SUBJECT'],
			'DESCRIPTION' => $this->arParams['DESCRIPTION']
		);
		$this->arResult = $this->prepareDataForEdit($params);
		$this->arResult['INITIALIZE_EDITOR'] = false;
		$this->includeComponentTemplate('edit');
		return $this->arResult;
	}

	protected function prepareDataForEdit($params)
	{
		$result = array();

		$callListId = $params['CALL_LIST_ID'];

		if($callListId)
		{
			try
			{
				$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId);
				$result['CALL_LIST'] = $callList->toArray();
				$result['CALL_LIST']['NEW'] = false;
				$result['CALL_LIST']['ENTITY_CAPTION'] = self::getEntityCaption($callList->getEntityTypeId(), true);
				$result['CALL_LIST']['ITEMS'] = $this->prepareItems($callList);
			}
			catch (Main\SystemException $exception)
			{
				$result['ERRORS'] =
					$exception->getCode() === 403
						? Loc::getMessage("CRM_CALL_LIST_ACCESS_DENIED")
						: $exception->getMessage();

				return $result;
			}
		}
		else
		{
			$callList = \Bitrix\Crm\CallList\CallList::createEmpty();
			$callList->persist();

			$result['CALL_LIST'] = array(
				'NEW' => true,
				'ID' => $callList->getId()
			);
		}

		$result['CALL_LIST']['SUBJECT'] = $params['SUBJECT'];
		$result['CALL_LIST']['DESCRIPTION'] = $params['DESCRIPTION'];
		$result['CALL_LIST']['FILTER_TEXT'] = Loc::getMessage(
			($callList->isFiltered() ? 'CRM_CALL_LIST_FILTERED' : 'CRM_CALL_LIST_HAND_PICKED'),
			array('#ENTITIES#' => static::getEntityCaption($callList->getEntityTypeId(), true))
		);

		$result['URLS'] = array(
			'LEAD_LIST' => CCrmOwnerType::GetListUrl(CCrmOwnerType::Lead),
			'CONTACT_LIST' => CCrmOwnerType::GetListUrl(CCrmOwnerType::Contact),
			'COMPANY_LIST' => CCrmOwnerType::GetListUrl(CCrmOwnerType::Company),
			'DEAL_LIST' => CCrmOwnerType::GetListUrl(CCrmOwnerType::Deal),
			'QUOTE_LIST' => CCrmOwnerType::GetListUrl(CCrmOwnerType::Quote),
			'INVOICE_LIST' => CCrmOwnerType::GetListUrl(CCrmOwnerType::Invoice)
		);

		if(!$result['CALL_LIST']['NEW'])
		{
			switch ($result['CALL_LIST']['ENTITY_TYPE_ID'])
			{
				case CCrmOwnerType::Lead:
					$result['URLS']['ADD_MORE_URL'] = $result['URLS']['LEAD_LIST'];
					break;
				case CCrmOwnerType::Contact:
					$result['URLS']['ADD_MORE_URL'] = $result['URLS']['CONTACT_LIST'];
					break;
				case CCrmOwnerType::Company:
					$result['URLS']['ADD_MORE_URL'] = $result['URLS']['COMPANY_LIST'];
					break;
				case CCrmOwnerType::Deal:
					$result['URLS']['ADD_MORE_URL'] = $result['URLS']['DEAL_LIST'];
					break;
				case CCrmOwnerType::Quote:
					$result['URLS']['ADD_MORE_URL'] = $result['URLS']['QUOTE_LIST'];
					break;
				case CCrmOwnerType::Invoice:
					$result['URLS']['ADD_MORE_URL'] = $result['URLS']['INVOICE_LIST'];
					break;
			}
		}

		return $result;
	}

	/**
	 * @param int $entityTypeId
	 * @param bool $plural
	 */
	protected static function getEntityCaption($entityTypeId, $plural)
	{
		if ($plural)
		{
			$allCaptions = CCrmOwnerType::GetAllCategoryCaptions();
		}
		else
		{
			$allCaptions = CCrmOwnerType::GetAllDescriptions();
		}
		return $allCaptions[$entityTypeId];
	}

	protected function getRecordHtml($callId, $recordId)
	{
		$callId = (int)$callId;
		$recordId = (int)$recordId;
		if(!$callId || !$recordId)
			return '-';

		$recordFile = CFile::GetFileArray($recordId);
		if (!$recordFile)
			return '-';

		$recordHref = $recordFile['SRC'];

		global $APPLICATION;
		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:player",
			"audio",
			Array(
				"PLAYER_TYPE" => "",
				"CHECK_FILE" => "N",
				"USE_PLAYLIST" => "N",
				"PATH" => $recordHref,
				"WIDTH" => 250,
				"HEIGHT" => 30,
				"PREVIEW" => false,
				"LOGO" => false,
				"FULLSCREEN" => "N",
				"SKIN" => "timeline_player.css",
				"SKIN_PATH" => "/bitrix/js/crm/",
				"CONTROLBAR" => "bottom",
				"WMODE" => "transparent",
				"WMODE_WMV" => "windowless",
				"HIDE_MENU" => "N",
				"SHOW_CONTROLS" => "Y",
				"SHOW_STOP" => "Y",
				"SHOW_DIGITS" => "Y",
				"CONTROLS_BGCOLOR" => "FFFFFF",
				"CONTROLS_COLOR" => "000000",
				"CONTROLS_OVER_COLOR" => "000000",
				"SCREEN_COLOR" => "000000",
				"AUTOSTART" => "N",
				"REPEAT" => "N",
				"VOLUME" => "90",
				"DISPLAY_CLICK" => "play",
				"MUTE" => "N",
				"HIGH_QUALITY" => "N",
				"ADVANCED_MODE_SETTINGS" => "Y",
				"BUFFER_LENGTH" => "10",
				"DOWNLOAD_LINK" => false,
				"DOWNLOAD_LINK_TARGET" => "_self",
				"ALLOW_SWF" => "N",
				"ADDITIONAL_PARAMS" => array(
					'LOGO' => false,
					'NUM' => false,
					'HEIGHT_CORRECT' => false,
				),
				"PLAYER_ID" => "bitrix_vi_record_".$callId,
				"TYPE" => "audio/mp3",
			),
			false,
			Array("HIDE_ICONS" => "Y")
		);
		return '<div class="crm-activity-call-list-tel-player">'.ob_get_clean().'</div>';
	}

	protected function getWebformActivityHtml($activityId, $webformResultId)
	{
		if($activityId > 0)
		{
			$viewUrl = CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::Activity, $activityId);
			return '<a href="'.htmlspecialcharsbx($viewUrl).'" target="_blank">'.Loc::getMessage('CRM_CALL_LIST_ITEM_FORM', array('#ID#' => (int)$webformResultId)).'</a>';
		}
		else
		{
			return '-';
		}
	}

	protected function getCreatedHtml(array $entities)
	{
		if(count($entities) == 0)
			return '-';

		$result = '';
		foreach ($entities as $entity)
		{
			$typeId = CCrmOwnerType::ResolveID($entity['TYPE']);
			$entityName = CCrmOwnerType::GetDescription($typeId);
			$entityCaption = CCrmOwnerType::GetCaption($typeId, $entity['ID']);
			$url = CCrmOwnerType::GetEntityShowPath($typeId, $entity['ID']);
			$result .= '<div><a href="'.htmlspecialcharsbx($url).'" target="_blank">' . htmlspecialcharsbx($entityName) . ' &laquo;' . htmlspecialcharsbx($entityCaption) . '&raquo;</a></div>';
		}
		return $result;
	}

	protected function prepareDataForView()
	{
		$result = array();
		$callListId = $this->arParams['ACTIVITY']['ASSOCIATED_ENTITY_ID'];
		try
		{
			$callList = \Bitrix\Crm\CallList\CallList::createWithId($callListId, true);
			$result['ACTIVITY'] = $this->arParams['ACTIVITY'];
			$result['CALL_LIST'] = $callList->toArray();
			if($result['CALL_LIST']['WEBFORM_ID'] > 0)
			{
				$webform = \Bitrix\Crm\WebForm\Internals\FormTable::getById($result['CALL_LIST']['WEBFORM_ID'])->fetch();
				$result['CALL_LIST']['WEBFORM_SECURITY_CODE'] = $webform['SECURITY_CODE'];
				$result['CALL_LIST']['WEBFORM_NAME'] = $webform['NAME'];
				$result['CALL_LIST']['WEBFORM_URL'] = Bitrix\Crm\WebForm\Manager::getEditUrl($result['CALL_LIST']['WEBFORM_ID']);
			}
			$started = false;
			foreach ($callList->getItems() as $item)
			{
				if($item->getStatusId() !== 'NEW')
				{
					$started = true;
					break;
				}
			}
			$result['CALL_LIST']['STARTED'] = $started;
			$result['CALL_LIST']['ENTITY_CAPTION'] = $this->getEntityCaption($callList->getEntityTypeId(), true);
			$result['CALL_LIST']['ITEMS'] = $this->prepareItems($callList);
			$result['CALL_LIST']['TOTAL_COUNT'] = $callList->getItemsCount();
			$result['CALL_LIST']['FILTER_TEXT'] = Loc::getMessage(
				($callList->isFiltered() ? 'CRM_CALL_LIST_FILTERED' : 'CRM_CALL_LIST_HAND_PICKED'),
				array('#ENTITIES#' => static::getEntityCaption($callList->getEntityTypeId(), true))
			);

			$statusList = CCrmStatus::GetStatusList('CALL_LIST', true);
			$stats = array();
			foreach ($statusList as $status => $statusName)
			{
				$stats[$status] = array(
					'NAME' => $statusName,
					'COUNT' => 0
				);
			}

			$completeCount = 0;
			foreach ($callList->getItems() as $item)
			{
				if ($item->getStatusId() != 'IN_WORK')
					$completeCount++;

				$stats[$item->getStatusId()]['COUNT']++;
			}
			$result['CALL_LIST']['COMPLETE_COUNT'] = $completeCount;
			$result['CALL_LIST']['STATUS_STATS'] = $stats;
		}
		catch (\Bitrix\Main\SystemException $exception)
		{
			$result['ERRORS'] =
				$exception->getCode() === 403
					? Loc::getMessage("CRM_CALL_LIST_ACCESS_DENIED")
					: $exception->getMessage();
		}
		return $result;
	}

	protected static function getWebformActivityIds(array $webformResultIdList, $webformId)
	{
		$result = array();
		$cursor = \Bitrix\Crm\ActivityTable::getList(array(
			'select' => array('ID', 'ASSOCIATED_ENTITY_ID'),
			'filter' => array(
				'TYPE_ID' =>  \CCrmActivityType::Provider,
				'PROVIDER_ID' => Activity\Provider\WebForm::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => $webformId,
				'ASSOCIATED_ENTITY_ID' => $webformResultIdList
			)
		));
		while ($row = $cursor->fetch())
		{
			$result[$row['ASSOCIATED_ENTITY_ID']] = $row['ID'];
		}
		return $result;
	}
}
