<?php

namespace Bitrix\Crm;

use \Bitrix\Main\Application;
use \Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);

class RequisiteListComponent extends \CBitrixComponent
{
	protected $site;
	protected $requisite;
	protected $preset;
	/*protected $userType;*/
	protected $componentId;
	protected $errors;
	protected $bInternal;
	protected $bExport;
	protected $bEnableToolbar;
	protected $formId;
	protected $tabId;
	protected $gridId;
	protected $actionData;
	protected $bAjax;
	protected $ajaxMode;
	protected $ajaxId;
	protected $ajaxOptionJump;
	protected $ajaxOptionHistory;
	protected $requisiteFieldTitles;

	protected $entityTypeId;
	protected $entityId;
	protected $entityInfo;

	protected $listHeaders;
	protected $fixedHeaderFields;
	/*protected $fieldsOfActivePresets;
	protected $addressFields;*/
	protected $listData;
	/*protected $listUfData;*/

	protected $activePresetList;

	protected $sort;
	protected $sortVars;
	protected $filter;

	protected $pageNum;
	protected $enableNextPage;

	public function __construct($component = null)
	{
		global $USER_FIELD_MANAGER;

		parent::__construct($component);

		$this->site = new \CSite();
		$this->requisite = new EntityRequisite();
		$this->preset = new EntityPreset();
		/*$this->userType = new \CCrmUserType($USER_FIELD_MANAGER, $this->requisite->getUfId());*/

		$this->componentId = $this->randString();
		$this->errors = array();
		$this->bInternal = false;
		$this->bExport = false;
		$this->bEnableToolbar = false;
		$this->formId = '';
		$this->tabId = '';
		$this->gridId = '';
		$this->actionData = array(
			'ACTIVE' => false,
			'METHOD' => '',
			'NAME' => '',
			'ID' => 0,
			'FIELDS' => array(),
			'ALL_ROWS',
			'AJAX_CALL' => false
		);
		$this->bAjax = false;
		$this->ajaxMode = false;
		$this->ajaxId = '';
		$this->ajaxOptionJump = false;
		$this->ajaxOptionHistory = false;
		$this->requisiteFieldTitles = array();

		$this->entityTypeId = 0;
		$this->entityId = 0;
		$this->entityInfo = null;

		$this->listHeaders = array();
		$this->fixedHeaderFields = array();
		/*$this->fieldsOfActivePresets = array();
		$this->addressFields = array();*/
		$this->listData = array();
		/*$this->listUfData = array();*/

		$this->activePresetList = array();

		$this->sort = array('SORT' => 'ASC', 'ID' => 'ASC');
		$this->sortVars = array('by' => 'by', 'order' => 'order');
		$this->filter = array();

		$this->pageNum = 1;
		$this->enableNextPage = false;
	}

	public function executeComponent()
	{
		if (!$this->checkModules())
		{
			$this->showErrors();
			return $this->getComponentResult();
		}

		if (!$this->parseParams())
		{
			$this->showErrors();
			return $this->getComponentResult();
		}

		if (!$this->checkEntityReference())
		{
			$this->showErrors();
			return $this->getComponentResult();
		}

		$this->parseFilter();

		$this->prepareListHeaders();

		if ($this->parseListAction())
		{
			$this->processListAction();

			if (!empty($this->errors))
				$this->showErrors();
			else
				$this->processRedirect();
		}

		if (!$this->prepareListData())
		{
			$this->showErrors();
			return $this->getComponentResult();
		}

		$this->prepareResult();

		$this->includeComponentTemplate();

		include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.requisite/include/nav.php');

		return $this->getComponentResult();
	}

	protected function parseParams()
	{
		$this->arParams['PATH_TO_REQUISITE_LIST'] = CrmCheckPath('PATH_TO_REQUISITE_LIST', $this->arParams['PATH_TO_REQUISITE_LIST'], $this->getApp()->GetCurPage());
		$this->arParams['PATH_TO_REQUISITE_EDIT'] = CrmCheckPath('PATH_TO_REQUISITE_EDIT', $this->arParams['PATH_TO_REQUISITE_EDIT'], $this->getApp()->GetCurPage() . '?id=#id#&edit');

		$this->arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $this->arParams['PATH_TO_COMPANY_SHOW'], $this->getApp()->GetCurPage().'?company_id=#company_id#&show');
		$this->arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $this->arParams['PATH_TO_CONTACT_SHOW'], $this->getApp()->GetCurPage().'?contact_id=#contact_id#&show');
		$this->arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $this->arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
		$this->arParams['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE']) ? $this->site->GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $this->arParams["NAME_TEMPLATE"]);

		// entity type id
		$this->entityTypeId = isset($_REQUEST['etype']) ? intval($_REQUEST['etype']) : 0;
		if (isset($this->arParams['ENTITY_TYPE_ID']))
			$this->entityTypeId = intval($this->arParams['ENTITY_TYPE_ID']);

		// entity id
		$this->entityId = isset($_REQUEST['eid']) ? intval($_REQUEST['eid']) : 0;
		if (isset($this->arParams['ENTITY_ID']))
			$this->entityTypeId = intval($this->arParams['ENTITY_ID']);

		$skipContext = false;
		if ($this->entityTypeId > 0 && $this->entityId > 0)
		{
			if (!is_array($this->arParams['INTERNAL_FILTER']))
			{
				$this->arParams['INTERNAL_FILTER'] = array(
					'=ENTITY_TYPE_ID' => $this->entityTypeId,
					'=ENTITY_ID' => $this->entityId
				);
			}
			else
			{
				$this->arParams['INTERNAL_FILTER']['=ENTITY_TYPE_ID'] = $this->entityTypeId;
				$this->arParams['INTERNAL_FILTER']['=ENTITY_ID'] = $this->entityId;
				unset(
					$this->arParams['INTERNAL_FILTER']['ENTITY_TYPE_ID'],
					$this->arParams['INTERNAL_FILTER']['ENTITY_ID']
				);
			}
			if ($this->entityTypeId === \CCrmOwnerType::Contact)
				$this->arParams['INTERNAL_CONTEXT'] = array('CONTACT_ID' => $this->entityId);
			else if ($this->entityTypeId === \CCrmOwnerType::Company)
				$this->arParams['INTERNAL_CONTEXT'] = array('COMPANY_ID' => $this->entityId);
			else
				$this->arParams['INTERNAL_CONTEXT'] = array();
			$skipContext = true;
		}

		if (is_array($this->arParams['INTERNAL_FILTER']) && !empty($this->arParams['INTERNAL_FILTER']))
			$this->bInternal = true;

		if(!$skipContext && $this->bInternal && is_array($this->arParams['INTERNAL_CONTEXT']))
		{
			$internalContext = $this->arParams['INTERNAL_CONTEXT'];
			if (isset($internalContext['CONTACT_ID']))
			{
				$this->entityTypeId = \CCrmOwnerType::Contact;
				$this->entityId = intval($internalContext['CONTACT_ID']);
			}
			else if (isset($internalContext['COMPANY_ID']))
			{
				$this->entityTypeId = \CCrmOwnerType::Company;
				$this->entityId = intval($internalContext['COMPANY_ID']);
			}
		}

		$this->bEnableToolbar = isset($this->arParams['ENABLE_TOOLBAR'])
			? (bool)$this->arParams['ENABLE_TOOLBAR'] : false;

		if(!is_array($this->arParams['INTERNAL_SORT']))
			$this->arParams['INTERNAL_SORT'] = array();

		$this->formId = isset($this->arParams['FORM_ID']) ? $this->arParams['FORM_ID'] : '';
		$this->tabId = isset($this->arParams['TAB_ID']) ? $this->arParams['TAB_ID'] : '';

		$gridIdSuffix =
			isset($this->arParams['GRID_ID_SUFFIX']) ? strval($this->arParams['GRID_ID_SUFFIX']) : '';
		if (empty($gridIdSuffix))
			$gridIdSuffix = $this->GetParent() !== null ? strtoupper($this->GetParent()->GetName()) : '';
		$this->gridId = preg_replace(
			'/[^a-z0-9_]/i', '',
			'CRM_REQUISITE_LIST_V15'.($this->bInternal && !empty($gridIdSuffix) ? '_'.$gridIdSuffix : '')
		);

		$this->arParams['REQUISITE_COUNT'] =
			isset($this->arParams['REQUISITE_COUNT']) ? intval($this->arParams['REQUISITE_COUNT']) : 20;

		$this->bAjax = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);

		$this->ajaxMode = isset($this->arParams['AJAX_MODE'])
			? $this->arParams['AJAX_MODE'] === 'Y' : !$this->bInternal;
		$this->ajaxId = isset($this->arParams['AJAX_ID']) ? $this->arParams['AJAX_ID'] : '';
		$this->ajaxOptionJump = isset($this->arParams['AJAX_OPTION_JUMP']) ?
			$this->arParams['AJAX_OPTION_JUMP'] === 'Y' : false;
		$this->ajaxOptionHistory = isset($this->arParams['AJAX_OPTION_HISTORY']) ?
			$this->arParams['AJAX_OPTION_HISTORY'] === 'Y' : false;

		$this->requisiteFieldTitles = $this->requisite->getFieldsTitles();

		// preset list
		$presetFilter = array(
			'=ENTITY_TYPE_ID' => EntityPreset::Requisite,
			'=ACTIVE' => 'Y'
		);
		if (!EntityPreset::isUTFMode())
		{
			$presetFilter['=COUNTRY_ID'] = EntityPreset::getCurrentCountryId();
		}
		$res = $this->preset->getList(array(
			'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
			'filter' => $presetFilter,
			'select' => array('ID', 'NAME')
		));
		while ($row = $res->fetch())
		{
			$presetTitle = trim(strval($row['NAME']));
			if (empty($presetTitle))
				$presetTitle = '['.$row['ID'].'] - '.GetMessage('CRM_REQUISITE_PRESET_NAME_EMPTY');
			$this->activePresetList[] = array('id' => $row['ID'], 'title' => $presetTitle);
		}
		unset($res, $row);

		return true;
	}

	protected function checkEntityReference()
	{
		if ($this->entityTypeId !== \CCrmOwnerType::Company && $this->entityTypeId !== \CCrmOwnerType::Contact)
		{
			$this->errors[] = GetMessage('CRM_REQUISITE_LIST_ERR_ENTITY_TYPE_ID');
			return false;
		}

		$entityInfo = null;
		if ($this->entityTypeId === \CCrmOwnerType::Company)
		{
			if ($this->entityId > 0)
			{
				$dbRes = \CCrmCompany::GetListEx(
					array(),
					array('=ID' => $this->entityId, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('TITLE')
				);
				if ($dbRes)
					$entityInfo = $dbRes->Fetch();
			}

			if (!is_array($entityInfo))
			{
				$this->errors[] = GetMessage('CRM_REQUISITE_LIST_ERR_COMPANY_NOT_FOUND');
				return false;
			}
			else
			{
				if (!\CCrmCompany::CheckReadPermission($this->entityId))
				{
					$this->errors[] = GetMessage('CRM_REQUISITE_LIST_ERR_COMPANY_READ_DENIED');
					return false;
				}
			}
		}
		else if ($this->entityTypeId === \CCrmOwnerType::Contact)
		{
			if ($this->entityId > 0)
			{
				$dbRes = \CCrmContact::GetListEx(
					array(),
					array('=ID' => $this->entityId,'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME')
				);
				if ($dbRes)
					$entityInfo = $dbRes->Fetch();
			}

			if (!is_array($entityInfo))
			{
				$this->errors[] = GetMessage('CRM_REQUISITE_LIST_ERR_CONTACT_NOT_FOUND');
				return false;
			}
			else
			{
				if (!\CCrmContact::CheckReadPermission($this->entityId))
				{
					$this->errors[] = GetMessage('CRM_REQUISITE_LIST_ERR_CONTACT_READ_DENIED');
					return false;
				}
			}
		}

		if (is_array($entityInfo))
			$this->entityInfo = $entityInfo;

		return true;
	}

	protected function parseFilter()
	{
		// filter
		$this->filter = $this->bInternal ? $this->arParams['INTERNAL_FILTER'] : array();

		return true;
	}

	protected function prepareListHeaders()
	{
		$this->listHeaders = array(
			// default fields
			array('id' => 'ID', 'name' => isset($this->requisiteFieldTitles['ID']) ? $this->requisiteFieldTitles['ID'] : 'ID', 'sort' => 'ID', 'default' => false, 'editable' => false, 'type' => 'int'),
			array('id' => 'NAME', 'name' => isset($this->requisiteFieldTitles['NAME']) ? $this->requisiteFieldTitles['NAME'] : 'NAME', 'sort' => 'NAME', 'default' => true, 'editable' => true, 'type' => 'text'),
			array('id' => 'PRESET.NAME', 'name' => GetMessage('CRM_REQUISITE_LIST_PRESET_NAME_FIELD'), 'sort' => 'PRESET.NAME', 'default' => true, 'editable' => false, 'type' => 'text'),
			array('id' => 'DATE_CREATE', 'name' => isset($this->requisiteFieldTitles['DATE_CREATE']) ? $this->requisiteFieldTitles['DATE_CREATE'] : 'DATE_CREATE', 'sort' => 'DATE_CREATE', 'default' => true, 'editable' => false, 'type' => 'date'),
			array('id' => 'CREATED_BY_ID',  'name' => isset($this->requisiteFieldTitles['CREATED_BY_ID']) ? $this->requisiteFieldTitles['CREATED_BY_ID'] : 'CREATED_BY_ID', 'sort' => 'CREATED_BY_ID', 'default' => true, 'enable_settings' => true, 'type' => 'user'),
			array('id' => 'DATE_MODIFY', 'name' => isset($this->requisiteFieldTitles['DATE_MODIFY']) ? $this->requisiteFieldTitles['DATE_MODIFY'] : 'DATE_MODIFY', 'sort' => 'DATE_MODIFY', 'default' => false, 'editable' => false, 'type' => 'date'),
			array('id' => 'MODIFY_BY_ID',  'name' => isset($this->requisiteFieldTitles['MODIFY_BY_ID']) ? $this->requisiteFieldTitles['MODIFY_BY_ID'] : 'MODIFY_BY_ID', 'sort' => 'MODIFY_BY_ID', 'default' => false, 'enable_settings' => true, 'type' => 'user'),
			array('id' => 'SORT', 'name' => isset($this->requisiteFieldTitles['SORT']) ? $this->requisiteFieldTitles['SORT'] : 'SORT', 'sort' => 'SORT', 'default' => false, 'editable' => true, 'type' => 'int')
		);

		foreach ($this->listHeaders as $header)
			$this->fixedHeaderFields[$header['id']] = true;

		/*foreach ($this->preset->getSettingsFieldsOfPresets(EntityPreset::Requisite, 'active') as $fieldName)
			$this->fieldsOfActivePresets[$fieldName] = true;*/

		/*$this->addressFields = $this->requisite->getAddressFields();
		foreach ($this->requisite->getRqFields() as $rqFieldName)
		{
			if (isset($this->fieldsOfActivePresets[$rqFieldName]))
			{
				if (in_array($rqFieldName, $this->addressFields, true))
				{
					// address field
					$this->listHeaders[] = array(
						'id' => $rqFieldName,
						'name' => isset($this->requisiteFieldTitles[$rqFieldName]) ?
							$this->requisiteFieldTitles[$rqFieldName] : '',
						'sort' => false,
						'default' => false,
						'editable' => false,
						'type' => 'text'
					);
				}
				else
				{
					$this->listHeaders[] = array(
						'id' => $rqFieldName,
						'name' => isset($this->requisiteFieldTitles[$rqFieldName]) ?
							$this->requisiteFieldTitles[$rqFieldName] : '',
						'sort' => $rqFieldName,
						'default' => false,
						'editable' => true,
						'type' => 'text'
					);
				}
			}
		}

		$ufListHeaders = array();
		$this->userType->ListAddHeaders($ufListHeaders);
		foreach ($ufListHeaders as $header)
		{
			if (isset($this->fieldsOfActivePresets[$header['id']]))
				$this->listHeaders[] = $header;
		}
		unset($ufListHeaders);*/

		return true;
	}

	protected function prepareListData()
	{
		// sort
		$gridOptions = new \CCrmGridOptions($this->gridId);
		$gridSorting = $gridOptions->GetSorting(array(
			'sort' => $this->sort,
			'vars' => $this->sortVars
		));
		$this->sort = (!empty($this->arParams['INTERNAL_SORT'])) ? $this->arParams['INTERNAL_SORT'] : $gridSorting['sort'];
		$this->sortVars = $gridSorting['vars'];

		// check sort
		if (is_array($this->sort) && !empty($this->sort))
		{
			$allowedSorts = array();
			foreach ($this->listHeaders as $header)
			{
				if (isset($header['sort']) && !empty($header['sort']))
					$allowedSorts[$header['sort']] = true;
			}
			$newSort = array();
			foreach ($this->sort as $fieldName => $sortType)
			{
				$upperSortType = strtoupper($sortType);
				if (!isset($allowedSorts[$fieldName]) || ($upperSortType !== 'ASC' && $upperSortType !== 'DESC'))
				{
					$newSort = array();
					break;
				}
				$newSort[$fieldName] = $sortType;
			}
			$this->sort = $newSort;
			unset($newSort, $allowedSorts, $sortType, $upperSortType);
		}
		if (!is_array($this->sort) || empty($this->sort))
		{
			$this->sort = array('SORT' => 'ASC', 'ID' => 'ASC');
		}

		// select
		$select = $gridOptions->GetVisibleColumns();
		$selectModified = false;
		foreach ($select as $index => $fieldName)
		{
			if (!(isset($this->fixedHeaderFields[$fieldName])/* || isset($this->fieldsOfActivePresets[$fieldName])*/))
			{
				unset($select[$index]);
				$selectModified = true;
			}
		}
		unset($index, $fieldName);
		if ($selectModified/* || $this->userType->NormalizeFields($select)*/)
			$gridOptions->SetVisibleColumns($select);
		unset($selectModified);
		if (empty($select))
		{
			foreach ($this->listHeaders as $header)
			{
				if ($header['default'])
					$select[] = $header['id'];
			}
		}
		if(!in_array('ID', $select, true))
			$select[] = 'ID';
		$select = array_unique($select, SORT_STRING);
		/*$addressFieldsSelected = array();
		$key = null;
		foreach ($this->addressFields as $fieldName)
		{
			$key = array_search($fieldName, $select, true);
			if ($key != '')
			{
				$addressFieldsSelected[] = $select[$key];
				unset($select[$key]);
				$select = array_merge(
					$select,
					array(
						$fieldName.'_ADDRESS_1',
						$fieldName.'_ADDRESS_2',
						$fieldName.'_CITY',
						$fieldName.'_POSTAL_CODE',
						$fieldName.'_REGION',
						$fieldName.'_PROVINCE',
						$fieldName.'_COUNTRY',
						$fieldName.'_COUNTRY_CODE'
					)
				);
			}
		}
		unset($fieldName, $key);*/

		//region Navigation data initialization
		$pageNum = 0;

		$arNavParams = array('nPageSize' => $this->arParams['REQUISITE_COUNT']);
		$arNavParams = $gridOptions->GetNavParams($arNavParams);
		$pageSize = $arNavParams['nPageSize'];
		unset($arNavParams);

		if(isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y')
		{
			$pageNum = 1;
		}
		elseif($pageSize > 0 && isset($_REQUEST['page']))
		{
			$pageNum = (int)$_REQUEST['page'];
			if($pageNum < 0)
			{
				//Backward mode
				$offset = -($pageNum + 1);
				$total = $this->requisite->getCountByFilter($this->filter);
				$pageNum = (int)(ceil($total / $pageSize)) - $offset;
				if($pageNum <= 0)
				{
					$pageNum = 1;
				}
			}
		}

		if($pageNum > 0)
		{
			if(!isset($_SESSION['CRM_PAGINATION_DATA']))
			{
				$_SESSION['CRM_PAGINATION_DATA'] = array();
			}
			$_SESSION['CRM_PAGINATION_DATA'][$this->gridId] = array('PAGE_NUM' => $pageNum);
		}
		else
		{
			if(!$this->bInternal
				&& !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
				&& isset($_SESSION['CRM_PAGINATION_DATA'])
				&& isset($_SESSION['CRM_PAGINATION_DATA'][$this->gridId])
				&& isset($_SESSION['CRM_PAGINATION_DATA'][$this->gridId]['PAGE_NUM']))
			{
				$pageNum = (int)$_SESSION['CRM_PAGINATION_DATA'][$this->gridId]['PAGE_NUM'];
			}

			if($pageNum <= 0)
			{
				$pageNum  = 1;
			}
		}
		//endregion Navigation data initialization

		$res = $this->requisite->getList(
			array(
				'order' => $this->sort,
				'filter' => $this->filter,
				'select' => $select,
				'limit' => $pageSize + 1,
				'offset' => $pageSize * ($pageNum - 1)
			)
		);
		$this->pageNum = $pageNum;

		$qty = 0;
		while ($row = $res->fetch())
		{
			if($pageSize > 0 && ++$qty > $pageSize)
			{
				$this->enableNextPage = true;
				break;
			}

			$listItem = array();
			foreach ($row as $fName => $fValue)
			{
				if ($fName === 'CRM_REQUISITE_PRESET_NAME')
					$listItem['PRESET.NAME'] = $fValue;
				else
					$listItem[$fName] = $fValue;
			}

			$this->listData[$row['ID']] = $listItem;
			/*$this->listUfData[$row['ID']] = array();*/
		}

		/*$params = array('GRID_ID' => $this->gridId);
		$this->userType->ListAddEnumFieldsValue(
			$params,
			$this->listData,
			$this->listUfData,
			'<br />',
			$this->bExport,
			array(
				'FILE_URL_TEMPLATE' =>
					'/bitrix/components/bitrix/crm.requisite/show_file.php?ownerId=#owner_id#&fieldName=#field_name#'.
					'&fileId=#file_id#'
			)
		);*/

		return true;
	}

	protected function parseListAction()
	{
		if(check_bitrix_sessid())
		{
			$postAction = 'action_button_'.$this->gridId;
			$allRows = 'action_all_rows_'.$this->gridId;

			if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$postAction]))
			{
				$this->actionData['METHOD'] = 'POST';

				$this->actionData['NAME'] = $_POST[$postAction];
				unset($_POST[$postAction], $_REQUEST[$postAction]);

				$this->actionData['ALL_ROWS'] = false;
				if(isset($_POST[$allRows]))
				{
					$this->actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
					unset($_POST[$allRows], $_REQUEST[$allRows]);
				}

				if(isset($_POST['ID']))
				{
					$this->actionData['ID'] = $_POST['ID'];
					unset($_POST['ID'], $_REQUEST['ID']);
				}

				if(isset($_POST['FIELDS']))
				{
					$this->actionData['FIELDS'] = $_POST['FIELDS'];
					unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
				}

				$this->actionData['AJAX_CALL'] = isset($_POST['AJAX_CALL']) ? ($_POST['AJAX_CALL'] === 'Y') : false;

				$this->actionData['ACTIVE'] = true;
			}
		}

		return $this->actionData['ACTIVE'];
	}

	protected function processListAction()
	{
		if($this->actionData['ACTIVE'])
		{
			if($this->actionData['METHOD'] == 'POST' && $this->actionData['NAME'] == 'delete')
			{
				$arId = array();
				if (is_array($this->actionData['ID']))
					$arId = $this->actionData['ID'];
				$bAllRows = $this->actionData['ALL_ROWS'];
				if (!empty($arId) || $bAllRows)
				{
					if ($bAllRows)
						$arFilterDel = $this->filter;
					else
						$arFilterDel = array('=ID' => $arId);

					$res = $this->requisite->getList(
						array(
							'filter' => $arFilterDel,
							'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID')
						)
					);

					$dbConnection = Application::getConnection();
					$rightsCache = array();
					while($row = $res->fetch())
					{
						$id = $row['ID'];

						// check entity
						$entityTypeId = (int)$row['ENTITY_TYPE_ID'];
						$entityId = (int)$row['ENTITY_ID'];
						if (!is_array($rightsCache[$entityTypeId]))
							$rightsCache[$entityTypeId] = array();
						if (!isset($rightsCache[$entityTypeId][$entityId]))
						{
							$rightsCache[$entityTypeId][$entityId] = (
								$this->requisite->validateEntityExists($entityTypeId, $entityId) &&
								$this->requisite->validateEntityUpdatePermission($entityTypeId, $entityId)
							);
						}
						if (!$rightsCache[$entityTypeId][$entityId])
							continue;

						$dbConnection->startTransaction();

						if ($this->requisite->delete($id))
						{
							$dbConnection->commitTransaction();
						}
						else
						{
							$dbConnection->rollbackTransaction();
						}
					}
				}
			}
			elseif($this->actionData['METHOD'] == 'POST' && $this->actionData['NAME'] == 'edit')
			{
				if(isset($this->actionData['FIELDS']) && is_array($this->actionData['FIELDS']))
				{
					$rightsCache = array();
					$arId = array();
					foreach (array_keys($this->actionData['FIELDS']) as $id)
						$arId[(int)$id] = true;
					$arId = array_keys($arId);
					$res = $this->requisite->getList(
						array(
							'filter' => array('=ID' => $arId),
							'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID')
						)
					);
					unset($arId);
					$arAllowedId = array();
					while ($row = $res->fetch())
					{
						// check entity
						$id = (int)$row['ID'];
						$entityTypeId = (int)$row['ENTITY_TYPE_ID'];
						$entityId = (int)$row['ENTITY_ID'];
						if (!is_array($rightsCache[$entityTypeId]))
							$rightsCache[$entityTypeId] = array();
						if (!isset($rightsCache[$entityTypeId][$entityId]))
						{
							$rightsCache[$entityTypeId][$entityId] = (
								$this->requisite->validateEntityExists($entityTypeId, $entityId) &&
								$this->requisite->validateEntityUpdatePermission($entityTypeId, $entityId)
							);
						}

						$arAllowedId[$id] = $rightsCache[$entityTypeId][$entityId];
					}
					$dbConnection = Application::getConnection();
					foreach($this->actionData['FIELDS'] as $id => $data)
					{
						if (!$arAllowedId[(int)$id])
							continue ;

						$updateFields = array();
						foreach ($this->listHeaders as $header)
						{
							if (isset($header['editable']) && $header['editable'] == true && isset($data[$header['id']]))
							{
								$updateFields[$header['id']] = $data[$header['id']];
							}
						}
						if (!empty($updateFields))
						{
							$dbConnection->startTransaction();

							if ($this->requisite->update((int)$id, $updateFields))
							{
								$dbConnection->commitTransaction();
							}
							else
							{
								$dbConnection->rollbackTransaction();
							}
						}
					}
				}
			}
		}

		return true;
	}

	protected function processRedirect()
	{
		return true;
	}

	protected function prepareResult()
	{
		$this->arResult['COMPONENT_ID'] = $this->componentId;
		$this->arResult['INTERNAL'] = $this->bInternal;
		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeId;
		$this->arResult['ENTITY_ID'] = $this->entityId;
		$this->arResult['GRID_ID'] = $this->gridId;
		$this->arResult['FORM_ID'] = $this->formId;
		$this->arResult['TAB_ID'] = $this->tabId;

		$this->arResult['AJAX_MODE'] = $this->ajaxMode ? 'Y' : 'N';
		$this->arResult['AJAX_ID'] = $this->ajaxId;
		$this->arResult['AJAX_OPTION_JUMP'] = $this->ajaxOptionJump ? 'Y' : 'N';
		$this->arResult['AJAX_OPTION_HISTORY'] = $this->ajaxOptionHistory ? 'Y' : 'N';

		$this->arResult['FILTER'] = $this->filter;
		$this->arResult['FILTER_PRESETS'] = array();

		$this->arResult['HEADERS'] = $this->listHeaders;
		$this->arResult['LIST_DATA'] = $this->listData;
		/*$this->arResult['LIST_UF_DATA'] = $this->listUfData;*/

		$this->arResult['SORT'] = $this->sort;
		$this->arResult['SORT_VARS'] = $this->sortVars;

		$this->arResult['DB_LIST'] = null;

		$bUpdatePermission = false;
		if ($this->entityTypeId === \CCrmOwnerType::Company)
			$bUpdatePermission = \CCrmCompany::CheckUpdatePermission($this->entityId);
		else if ($this->entityTypeId === \CCrmOwnerType::Contact)
			$bUpdatePermission = \CCrmContact::CheckUpdatePermission($this->entityId);
		$this->arResult['PERMS']['ADD']    = $bUpdatePermission;
		$this->arResult['PERMS']['WRITE']  = $bUpdatePermission;
		$this->arResult['PERMS']['DELETE'] = $bUpdatePermission;

		$this->arResult['ENABLE_TOOLBAR'] = $this->bEnableToolbar;

		$this->arResult['PRESET_LIST'] = $this->activePresetList;

		$presetLastSelectedId = 0;
		$optionData = \CUserOptions::GetOption('crm', 'crm_preset_last_selected', array());
		if (is_array($optionData) && !empty($optionData) && isset($optionData[$this->entityTypeId]))
		{
			$presetLastSelectedId = (int)$optionData[$this->entityTypeId];
			if ($presetLastSelectedId < 0)
				$presetLastSelectedId = 0;
		}
		$this->arResult['PRESET_LAST_SELECTED_ID'] = $presetLastSelectedId;
		unset($presetLastSelectedId, $optionData);

		/*$this->arResult['ADDRESS_FIELDS'] = $this->addressFields;*/

		$backUrl = '';
		if ($this->entityTypeId === \CCrmOwnerType::Contact)
		{
			$backUrl = \CHTTP::urlAddParams(
				\CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_CONTACT_SHOW'],
					array('contact_id' => $this->entityId)
				),
				array($this->formId.'_active_tab' => $this->tabId)
			);
		}
		else if ($this->entityTypeId === \CCrmOwnerType::Company)
		{
			$backUrl = \CHTTP::urlAddParams(
				\CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_COMPANY_SHOW'],
					array('company_id' => $this->entityId)
				),
				array($this->formId.'_active_tab' => $this->tabId)
			);
		}
		$this->arResult['BACK_URL'] = $backUrl;

		$this->arResult['PATH_TO_REQUISITE_ADD'] = \CHTTP::urlAddParams(
			\CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_REQUISITE_EDIT'], array('id' => 0)),
			array('etype' => $this->entityTypeId, 'eid' => $this->entityId, 'back_url' => urlencode($backUrl))
		);

		if ($this->entityTypeId === \CCrmOwnerType::Company)
			$this->arResult['ENTITY_TYPE_MNEMO'] = 'COMPANY';
		else
			$this->arResult['ENTITY_TYPE_MNEMO'] = 'CONTACT';

		//region Navigation data storing
		$this->arResult['PAGINATION'] = array(
			'PAGE_NUM' => $this->pageNum,
			'ENABLE_NEXT_PAGE' => $this->enableNextPage
		);
		if(!isset($_SESSION['CRM_GRID_DATA']))
		{
			$_SESSION['CRM_GRID_DATA'] = array();
		}
		$_SESSION['CRM_GRID_DATA'][$this->arResult['GRID_ID']] = array('FILTER' => $this->arResult['FILTER']);

		return true;
	}

	protected function checkModules()
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->errors[] = GetMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		return true;
	}

	protected function hasErrors()
	{
		return (count($this->errors) > 0);
	}

	protected function showErrors()
	{
		if (count($this->errors) > 0)
			foreach ($this->errors as $errMsg)
				ShowError($errMsg);
	}

	protected function getApp()
	{
		global $APPLICATION;

		return $APPLICATION;
	}

	public function getComponentId()
	{
		return $this->componentId;
	}

	public function getComponentResult()
	{
		return array('ENTITY_TYPE_ID' => $this->entityTypeId, 'ENTITY_ID' => $this->entityId);
	}
}
