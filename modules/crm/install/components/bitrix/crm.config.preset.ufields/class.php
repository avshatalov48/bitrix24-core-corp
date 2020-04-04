<?php

namespace Bitrix\Crm;

use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);

class PresetEditComponent extends \CBitrixComponent
{
	protected $componentId;
	protected $errors;
	protected $gridId;
	protected $actionData;
	protected $ajaxMode;
	protected $ajaxId;
	protected $ajaxOptionJump;
	protected $ajaxOptionHistory;
	protected $ajaxOptionShadow;

	protected $entityTypeId;
	protected $entityTypeName;

	protected $fieldTypeList;
	protected $userFieldTypeList;

	protected $listHeaders;
	protected $listData;
	protected $rowsCount;
	protected $navObject;

	protected $sort;
	protected $sortVars;
	protected $filter;

	protected $presetListUrl;
	protected $presetEditUrl;
	protected $presetUfieldsUrl;

	protected $preset;
	protected $requisite;

	protected $presetFieldMap;
	protected $userFieldInfoList;


	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->componentId = $this->randString();
		$this->errors = array();
		$this->gridId = 'CRM_PRESET_UFIELDS_V18';
		$this->actionData = array(
			'ACTIVE' => false,
			'METHOD' => '',
			'NAME' => '',
			'ID' => 0,
			'FIELDS' => array(),
			'ALL_ROWS',
			'AJAX_CALL' => false
		);
		$this->ajaxMode = false;
		$this->ajaxId = '';
		$this->ajaxOptionJump = false;
		$this->ajaxOptionHistory = false;
		$this->ajaxOptionShadow = false;

		$this->entityTypeId = 0;
		$this->entityTypeName = '';

		$this->fieldTypeList = array(
			'ID' => array('data_type' => 'integer'),
			'FIELD_NAME' => array('data_type' => 'string'),
			'FIELD_TITLE' => array('data_type' => 'string'),
			'FIELD_TYPE' => array('data_type' => 'string')
		);
		$this->userFieldTypeList = EntityPreset::getUserFieldTypes();

		$this->listHeaders = array();
		$this->listData = array();
		$this->rowsCount = 0;
		$this->navObject = null;

		$this->sort = array('FIELD_TITLE' => 'asc');
		$this->sortVars = array('by' => 'by', 'order' => 'order');
		$this->filter = array();

		$this->presetListUrl = '';
		$this->presetEditUrl = '';
		$this->presetUfieldsUrl = '';

		$this->preset = EntityPreset::getSingleInstance();
		$this->requisite = EntityRequisite::getSingleInstance();

		$this->presetFieldMap = null;
		$this->userFieldInfoList = null;
	}

	protected function getUnusedUserFieldInfoList()
	{
		if ($this->presetFieldMap === null)
		{
			$presetFieldList = $this->preset->getSettingsFieldsOfPresets(\Bitrix\Crm\EntityPreset::Requisite, 'all');
			$this->presetFieldMap = array_fill_keys($presetFieldList, true);
			unset($presetFieldList);
		}

		if ($this->userFieldInfoList === null)
		{
			$this->userFieldInfoList = $this->requisite->getFormUserFieldsInfo();
		}

		$unusedUserFieldInfoList = array();
		foreach ($this->userFieldInfoList as $fieldName => $fieldInfo)
		{
			if (!isset($this->presetFieldMap[$fieldName]))
			{
				$fieldId = (int)$fieldInfo['id'];
				$unusedUserFieldInfoList[$fieldId] = array(
					'ID' => $fieldId,
					'FIELD_NAME' => $fieldName,
					'FIELD_TITLE' => $fieldInfo['title'],
					'FIELD_TYPE' => isset($this->userFieldTypeList[$fieldInfo['type']]['NAME']) ?
						$this->userFieldTypeList[$fieldInfo['type']]['NAME'] : ''
				);
			}
		}

		return $unusedUserFieldInfoList;
	}

	protected function dropUnusedUserFieldInfoCache()
	{
		$this->userFieldInfoList = null;
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

		$this->setPageTitle();

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

		return $this->getComponentResult();
	}

	protected function parseParams()
	{
		$this->entityTypeId = isset($_REQUEST['entity_type']) ? intval($_REQUEST['entity_type']) : 0;
		if (isset($this->arParams['ENTITY_TYPE_ID']))
			$this->entityTypeId = intval($this->arParams['ENTITY_TYPE_ID']);
		if (!EntityPreset::checkEntityType($this->entityTypeId))
		{
			$this->errors[] = Loc::getMessage('CRM_PRESET_ENTITY_TYPE_INVALID');
			return false;
		}
		$entityTypes = EntityPreset::getEntityTypes();
		$this->entityTypeName = $entityTypes[$this->entityTypeId]['NAME'];

		$this->presetListUrl = isset($this->arParams['PRESET_LIST_URL']) ? $this->arParams['PRESET_LIST_URL'] : '';
		$this->presetEditUrl = isset($this->arParams['PRESET_EDIT_URL']) ? $this->arParams['PRESET_EDIT_URL'] : '';
		$this->presetUfieldsUrl = isset($this->arParams['PRESET_UFIELDS_URL']) ?
			$this->arParams['PRESET_UFIELDS_URL'] : '';

		$this->ajaxMode = isset($this->arParams['AJAX_MODE']) ? $this->arParams['AJAX_MODE'] === 'Y' : true;
		$this->ajaxId = isset($this->arParams['AJAX_ID']) ? $this->arParams['AJAX_ID'] : '';
		$this->ajaxOptionJump = isset($this->arParams['AJAX_OPTION_JUMP']) ?
			$this->arParams['AJAX_OPTION_JUMP'] === 'Y' : false;
		$this->ajaxOptionHistory = isset($this->arParams['AJAX_OPTION_HISTORY']) ?
			$this->arParams['AJAX_OPTION_HISTORY'] === 'Y' : false;
		$this->ajaxOptionShadow = isset($this->arParams['AJAX_OPTION_SHADOW']) ?
			$this->arParams['AJAX_OPTION_SHADOW'] === 'Y' : false;

		return true;
	}

	protected function setPageTitle()
	{
		$this->getApp()->SetTitle(Loc::getMessage('CRM_PRESET_UFIELDS_TITLE'));
	}

	protected function parseFilter()
	{
		// filter
		$this->filter = array();

		return true;
	}

	protected function prepareListHeaders()
	{
		$this->listHeaders = array(
			array('id' => 'ID', 'name' => Loc::getMessage('CRM_PRESET_UFIELD_ID'), 'sort' => 'ID', 'default' => false, 'editable' => false, 'type' => 'int'),
			array('id' => 'FIELD_NAME', 'name' => Loc::getMessage('CRM_PRESET_UFIELD_FIELD_NAME'), 'sort' => 'FIELD_NAME', 'default' => false, 'editable' => false, 'type' => 'text'),
			array('id' => 'FIELD_TITLE', 'name' => Loc::getMessage('CRM_PRESET_UFIELD_FIELD_TITLE'), 'sort' => 'FIELD_TITLE', 'default' => true, 'editable' => true, 'type' => 'text'),
			array('id' => 'FIELD_TYPE', 'name' => Loc::getMessage('CRM_PRESET_UFIELD_FIELD_TYPE'), 'sort' => 'FIELD_TYPE', 'default' => true, 'editable' => false, 'type' => 'text')
		);

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
		$this->sort = $gridSorting['sort'];
		$this->sortVars = $gridSorting['vars'];

		// select
		$select = $gridOptions->GetVisibleColumns();
		if (empty($select))
		{
			foreach ($this->listHeaders as $header)
			{
				if ($header['default'])
					$select[] = $header['id'];
			}
		}
		if(!in_array('ID', $select))
			$select[] = 'ID';
		$select = array_unique($select, SORT_STRING);

		$arNavParams = $gridOptions->GetNavParams();
		$pageSize = $arNavParams['nPageSize'];
		unset($arNavParams);

		$fieldList = $this->getUnusedUserFieldInfoList();

		// sort
		if (count($this->sort) > 0 && count($fieldList) > 0)
		{
			$arSortKeys = array_keys($this->sort);
			$arSortBy = $arSortDir = $arSortType = array();
			$origFieldsNames = array_keys($this->fieldTypeList);
			$numSorts = 0;
			foreach ($arSortKeys as $sortKey)
			{
				if (in_array($sortKey, $origFieldsNames, true))
				{
					$arSortBy[] = ToUpper($sortKey);
					$arSortDir[] = (ToUpper($this->sort[$sortKey]) === 'DESC') ? SORT_DESC : SORT_ASC;
					$sortType = SORT_REGULAR;
					switch ($this->fieldTypeList[$sortKey]['data_type'])
					{
						case 'integer':
							$sortType = SORT_NUMERIC;
							break;

						case 'string':
						case 'boolean':
							$sortType = SORT_STRING;
							break;
					}
					$arSortType[] = $sortType;
					$numSorts++;
				}
			}
			if ($numSorts > 0)
			{
				$fieldsNames = array();
				foreach ($origFieldsNames as $fieldName)
				{
					if (!in_array($fieldName, $arSortBy, true))
						$fieldsNames[] = $fieldName;
				}
				$fieldsNames = array_merge($arSortBy, $fieldsNames);
				$fieldsIndex = $columns = array();
				$index = 0;
				foreach ($fieldsNames as $fieldName)
				{
					$columns[$index] = array();
					$fieldsIndex[$fieldName] = $index++;
				}
				foreach ($fieldList as $row)
				{
					foreach ($row as $fieldName => $fieldValue)
					{
						if (isset($fieldsIndex[$fieldName]))
							$columns[$fieldsIndex[$fieldName]][] = $fieldValue;
					}
				}
				$args = array();
				$index = 0;
				foreach ($columns as &$column)
				{
					$args[] = &$column;
					if ($index < $numSorts)
					{
						$args[] = &$arSortDir[$index];
						$args[] = &$arSortType[$index];
					}
					$index++;
				}
				unset($column);
				call_user_func_array('array_multisort', $args);
				$numRows = count($fieldList);
				$fieldList = array();
				for ($index = 0; $index < $numRows; $index++)
				{
					$row = array();
					foreach ($origFieldsNames as $fieldName)
						$row[$fieldName] = $columns[$fieldsIndex[$fieldName]][$index];
					$fieldList[] = $row;
				}
			}
		}

		// select
		if (count($fieldList) > 0 && is_array($select) && count($select) > 0)
		{
			$selectedFields = array_intersect($select, array_keys($this->fieldTypeList));
			if (count($selectedFields) > 0)
			{
				$listData = &$fieldList;
				unset($fieldList);
				$fieldList = array();
				foreach ($listData as $row)
				{
					$newRow = array();
					foreach ($selectedFields as $fieldName)
						$newRow[$fieldName] = $row[$fieldName];
					$fieldList[] = $newRow;
				}
			}

		}

		$res = new \CDBResult;
		$res->InitFromArray($fieldList);
		$res->NavStart($pageSize);
		$res->bShowAll = false;
		while ($row = $res->Fetch())
			$this->listData[$row['ID']] = $row;
		$this->rowsCount = $res->SelectedRowsCount();
		$this->navObject = $res;

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
			else if (isset($_REQUEST['action']))
			{
				$this->actionData['METHOD'] = $_SERVER['REQUEST_METHOD'];

				$this->actionData['NAME'] = $_REQUEST['action'];
				unset($_GET['action'], $_POST['action'], $_REQUEST['action']);

				$fields = array(
					'FIELD_NAME' => isset($_REQUEST['FIELD_NAME']) ? strval($_REQUEST['FIELD_NAME']) : '',
					'FIELD_TITLE' => isset($_REQUEST['FIELD_TITLE']) ? strval($_REQUEST['FIELD_TITLE']) : '',
					'FIELD_TYPE' => isset($_REQUEST['FIELD_TYPE']) ? strval($_REQUEST['FIELD_TYPE']) : ''
				);

				$fieldId = isset($_REQUEST['ID']) ? (int)$_REQUEST['ID'] : 0;
				if ($this->actionData['NAME'] === 'edit' && $fieldId > 0)
					$this->actionData['FIELDS'][$fieldId] = $fields;
				else
					$this->actionData['FIELDS'] = $fields;
				unset($fieldId, $fields);

				unset($_GET['ID'], $_POST['ID'], $_REQUEST['ID']);
				unset($_GET['FIELD_NAME'], $_POST['FIELD_NAME'], $_REQUEST['FIELD_NAME']);
				unset($_GET['FIELD_TITLE'], $_POST['FIELD_TITLE'], $_REQUEST['FIELD_TITLE']);
				unset($_GET['FIELD_TYPE'], $_POST['FIELD_TYPE'], $_REQUEST['FIELD_TYPE']);

				$this->actionData['ACTIVE'] = true;
			}
		}

		return $this->actionData['ACTIVE'];
	}

	protected function processListAction()
	{
		if($this->actionData['ACTIVE'])
		{
			$modified = 0;
			if($this->actionData['METHOD'] == 'POST' && $this->actionData['NAME'] == 'delete')
			{
				$arId = array();
				$bAllRows = $this->actionData['ALL_ROWS'];
				$checkExist = !$bAllRows;
				if (!$bAllRows && is_array($this->actionData['ID']))
					$arId = $this->actionData['ID'];
				if ($bAllRows)
				{
					$userFieldInfoList = $this->getUnusedUserFieldInfoList();
					$arId = array_keys($userFieldInfoList);
				}

				foreach ($arId as $ufId)
				{
					$this->requisite->deleteUserField($ufId, $checkExist);
					$modified++;
				}
			}
			elseif($this->actionData['METHOD'] == 'POST' && $this->actionData['NAME'] == 'edit')
			{
				if(isset($this->actionData['FIELDS']) && is_array($this->actionData['FIELDS']))
				{
					$arId = array();
					foreach (array_keys($this->actionData['FIELDS']) as $id)
						$arId[(int)$id] = true;
					$arId = array_keys($arId);

					$fieldList = $this->getUnusedUserFieldInfoList();

					foreach ($fieldList as $index => $row)
					{
						if (in_array(intval($row['ID']), $arId, true))
						{
							if (is_array($this->actionData['FIELDS'][$row['ID']]))
							{
								$fields = array();
								$data = &$this->actionData['FIELDS'][$row['ID']];
								foreach ($this->listHeaders as $header)
								{
									if (isset($header['editable']) && $header['editable'] == true
										&& $header['id'] !== 'FIELD_NAME' && isset($data[$header['id']])
										&& $data[$header['id']] !== $row[$header['id']])
									{
										$fields[$header['id']] = $data[$header['id']];
									}
								}
								unset($data);
								if (!empty($fields) && isset($fields['FIELD_TITLE'])
									&& is_string($fields['FIELD_TITLE']) && strlen($fields['FIELD_TITLE']) > 0)
								{
									$this->requisite->updateUserFieldTitle($index, $fields['FIELD_TITLE']);
									$modified++;
								}
							}
						}
					}
					unset($fieldList, $title);
				}
			}
			if ($modified)
			{
				$this->dropUnusedUserFieldInfoCache();
			}
		}

		return true;
	}

	protected function processRedirect()
	{
		if($this->actionData['ACTIVE'] && !$this->actionData['AJAX_CALL'])
		{
			LocalRedirect(
				str_replace(
					array('#entity_type#'),
					array($this->entityTypeId),
					$this->presetUfieldsUrl
				)
			);
		}

		return true;
	}

	protected function prepareResult()
	{
		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeId;
		$this->arResult['ENTITY_TYPE_NAME'] = $this->entityTypeName;
		$this->arResult['GRID_ID'] = $this->gridId;
		$this->arResult['PRESET_LIST_URL'] = $this->presetListUrl;
		$this->arResult['PRESET_EDIT_URL'] = $this->presetEditUrl;
		$this->arResult['PRESET_UFIELDS_URL'] = $this->presetUfieldsUrl;
		$this->arResult['HEADERS'] = $this->listHeaders;
		$this->arResult['COMPONENT_ID'] = $this->componentId;
		$this->arResult['AJAX_MODE'] = $this->ajaxMode ? 'Y' : 'N';
		$this->arResult['AJAX_ID'] = $this->ajaxId;
		$this->arResult['AJAX_OPTION_JUMP'] = $this->ajaxOptionJump ? 'Y' : 'N';
		$this->arResult['AJAX_OPTION_HISTORY'] = $this->ajaxOptionHistory ? 'Y' : 'N';
		$this->arResult['AJAX_OPTION_SHADOW'] = $this->ajaxOptionShadow ? 'Y' : 'N';
		$this->arResult['FILTER'] = $this->filter;
		$this->arResult['LIST_DATA'] = $this->listData;
		$this->arResult['SORT'] = $this->sort;
		$this->arResult['SORT_VARS'] = $this->sortVars;
		$this->arResult['ROWS_COUNT'] = $this->rowsCount;
		$this->arResult['NAV_OBJECT'] = $this->navObject;
		$this->arResult['USER_FIELD_ENTITY_ID'] = $this->requisite->getUfId();

		return true;
	}

	protected function checkModules()
	{
		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		return true;
	}

	protected function checkRights()
	{
		$permissions = new \CCrmPerms(\CCrmSecurityHelper::GetCurrentUserID());
		if (!$permissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$this->errors[] = Loc::getMessage('CRM_PERMISSION_DENIED');
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
		return !$this->hasErrors();
	}
}
