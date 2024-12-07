<?php

namespace Bitrix\Crm;

use Bitrix\Crm\Communication\Validator;
use \Bitrix\Main\Application;
use \Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

Loc::loadMessages(__FILE__);

class PresetEditComponent extends \CBitrixComponent
{
	protected $componentId;
	protected $errors;
	protected $bEdit;
	protected $bCopy;
	protected $gridId;
	protected $actionData;
	protected $isAjaxCall;
	protected $ajaxMode;
	protected $ajaxId;
	protected $ajaxOptionJump;
	protected $ajaxOptionHistory;
	protected $ajaxOptionShadow;

	protected $entityTypeId;
	protected $entityTypeName;
	protected $entityFieldsAllowed;
	protected $entityFieldsTitles;
	protected $entityFieldsForSelect;
	protected $presetId;
	protected $presetData;

	protected $listHeaders;
	protected $listData;
	protected $rowsCount;
	protected $navObject;

	protected $sort;
	protected $sortVars;
	protected $filter;

	protected $presetListUrl;
	protected $presetEditUrl;

	protected $preset;
	protected $requisite;

	protected $currentCountryId;
	protected $presetCountryId;
	protected $countryList;



	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->componentId = $this->randString();
		$this->errors = array();
		$this->bEdit = false;
		$this->bCopy = false;
		$this->gridId = 'CRM_PRESET_EDIT_V15';
		$this->actionData = array(
			'ACTIVE' => false,
			'METHOD' => '',
			'NAME' => '',
			'ID' => 0,
			'FIELDS' => array(),
			'ALL_ROWS',
			'AJAX_CALL' => false
		);
		$this->isAjaxCall = false;
		$this->ajaxMode = false;
		$this->ajaxId = '';
		$this->ajaxOptionJump = false;
		$this->ajaxOptionHistory = false;
		$this->ajaxOptionShadow = false;


		$this->entityTypeId = 0;
		$this->entityTypeName = '';
		$this->entityFieldsAllowed = array();
		$this->entityFieldsTitles = array();
		$this->entityFieldsForSelect = array();
		$this->presetId = 0;
		$this->presetData = null;

		$this->listHeaders = array();
		$this->listData = array();
		$this->rowsCount = 0;
		$this->navObject = null;

		$this->sort = array('SORT' => 'asc', 'ID' => 'asc');
		$this->sortVars = array('by' => 'by', 'order' => 'order');
		$this->filter = array();

		$this->presetListUrl = '';
		$this->presetEditUrl = '';

		/** @var EntityPreset preset */
		$this->preset = null;

		/** @var EntityRequisite requisite */
		$this->requisite = null;

		$this->currentCountryId = 0;
		$this->presetCountryId = 0;
		$this->countryList = array();
	}

	public function executeComponent()
	{
		if (!$this->checkRights())
		{
			$this->showErrors();
			return $this->getComponentResult();
		}

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
		if ($this->preset === null)
		{
			$this->preset = EntityPreset::getSingleInstance();
		}
		if ($this->requisite === null)
		{
			$this->requisite = EntityRequisite::getSingleInstance();
		}
		$this->currentCountryId = EntityPreset::getCurrentCountryId();

		$this->entityTypeId = isset($_REQUEST['entity_type']) ? intval($_REQUEST['entity_type']) : 0;
		if (isset($this->arParams['ENTITY_TYPE_ID']))
			$this->entityTypeId = intval($this->arParams['ENTITY_TYPE_ID']);
		if (!EntityPreset::checkEntityType($this->entityTypeId))
		{
			$this->errors[] = GetMessage('CRM_PRESET_ENTITY_TYPE_INVALID');
			return false;
		}
		$entityTypes = EntityPreset::getEntityTypes();
		$this->entityTypeName = $entityTypes[$this->entityTypeId]['NAME'];

		$this->presetId = isset($_REQUEST['preset_id']) ? intval($_REQUEST['preset_id']) : 0;
		if (isset($this->arParams['PRESET_ID']))
			$this->presetId = intval($this->arParams['PRESET_ID']);
		$presetData = $this->preset->getById($this->presetId);
		if (!is_array($presetData) || empty($presetData)
			|| intval($presetData['ENTITY_TYPE_ID']) !== $this->entityTypeId)
		{
			$this->errors[] = GetMessage('CRM_PRESET_NOT_FOUND');
			return false;
		}
		$this->presetData = $presetData;

		$this->countryList = array();
		foreach (EntityPreset::getCountryList() as $k => $v)
			$this->countryList[$k] = $v;

		if ($this->presetData && isset($this->presetData['COUNTRY_ID']))
			$this->presetCountryId = (int)$this->presetData['COUNTRY_ID'];
		else
			$this->presetCountryId = $this->currentCountryId;
		if (!isset($this->countryList[$this->presetCountryId]))
			$this->presetCountryId = 0;

		switch ($this->entityTypeId)
		{
			case EntityPreset::Requisite:
				$this->entityFieldsAllowed = array_merge($this->requisite->getRqFields(), $this->requisite->getUserFields());
				$this->entityFieldsTitles = $this->requisite->getFieldsTitles($this->presetCountryId);
				break;
		}

		$this->presetListUrl = isset($this->arParams['PRESET_LIST_URL']) ? $this->arParams['PRESET_LIST_URL'] : '';
		$this->presetEditUrl = isset($this->arParams['PRESET_EDIT_URL']) ? $this->arParams['PRESET_EDIT_URL'] : '';

		$this->isAjaxCall = (isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']));
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
		$presetName = isset($this->presetData['NAME']) ? $this->presetData['NAME'] : '';
		$this->getApp()->SetTitle(
			GetMessage(
				'CRM_PRESET_EDIT_TITLE',
				array(
					'#NAME#' => $presetName,
					'#ENTITY_TYPE_NAME#' => $this->entityTypeName
				)
			)
		);
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
			array('id' => 'ID', 'name' => GetMessage('CRM_PRESET_FIELD_ID'), 'sort' => 'ID', 'default' => false, 'editable' => false, 'type' => 'int'),
			array('id' => 'FIELD_NAME', 'name' => GetMessage('CRM_PRESET_FIELD_FIELD_NAME'), 'sort' => 'FIELD_NAME', 'default' => false, 'editable' => false, 'type' => 'text'),
			array('id' => 'FIELD_ETITLE', 'name' => GetMessage('CRM_PRESET_FIELD_FIELD_ETITLE'), 'sort' => 'FIELD_ETITLE', 'default' => true, 'editable' => false, 'type' => 'text'),
			array('id' => 'FIELD_TITLE', 'name' => GetMessage('CRM_PRESET_FIELD_FIELD_TITLE'), 'sort' => 'FIELD_TITLE', 'default' => true, 'editable' => true, 'type' => 'text'),
			array('id' => 'SORT', 'name' => GetMessage('CRM_PRESET_FIELD_SORT'), 'sort' => 'SORT', 'default' => true, 'editable' => true, 'type' => 'int'),
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

		$fieldList = array();
		if (is_array($this->presetData['SETTINGS']['FIELDS']))
			$fieldList = $this->presetData['SETTINGS']['FIELDS'];

		foreach ($fieldList as &$field)
		{
			$field['FIELD_ETITLE'] = '';
			if (isset($field['FIELD_NAME']) && !empty($field['FIELD_NAME']))
			{
				if (isset($this->entityFieldsTitles[$field['FIELD_NAME']]))
				{
					$title = $this->entityFieldsTitles[$field['FIELD_NAME']];
					$field['FIELD_ETITLE'] = empty($title) ? $field['FIELD_NAME'] : $title;
				}
			}
		}
		unset($field, $title);

		$this->entityFieldsForSelect = $this->prepareEntityFieldsSelectItems();

		$fieldsInfo = $this->preset->getSettingsFieldsInfo();
		$fieldsInfo['FIELD_ETITLE'] = array('data_type' => 'string');

		// sort
		if (count($this->sort) > 0 && count($fieldList) > 0)
		{
			$arSortKeys = array_keys($this->sort);
			$arSortBy = $arSortDir = $arSortType = array();
			$origFieldsNames = array_keys($fieldsInfo);
			$numSorts = 0;
			foreach ($arSortKeys as $sortKey)
			{
				if (in_array($sortKey, $origFieldsNames, true))
				{
					$arSortBy[] = mb_strtoupper($sortKey);
					$arSortDir[] = (mb_strtoupper($this->sort[$sortKey]) === 'DESC') ? SORT_DESC : SORT_ASC;
					$sortType = SORT_REGULAR;
					switch ($fieldsInfo[$sortKey]['data_type'])
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
			$selectedFields = array_intersect($select, array_keys($fieldsInfo));
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

		$booleanUserFields = [];
		global $USER_FIELD_MANAGER;
		foreach ($USER_FIELD_MANAGER->GetUserFields(RequisiteTable::getUfId(), 0, LANGUAGE_ID) as $userField)
		{
			if ($userField['USER_TYPE_ID'] === 'boolean')
			{
				$booleanUserFields[] = $userField['FIELD_NAME'];
			}
		}

		foreach ($fieldList as $index => $field)
		{
			$fieldList[$index]['OPTIONS'] = [];
			if (in_array($field['FIELD_NAME'], $booleanUserFields))
			{
				$fieldList[$index]['OPTIONS']['disableTitleEdit'] = true;
			}
		}

		$res = new \CDBResult;
		$res->InitFromArray($fieldList);
		$res->NavStart($pageSize);
		$res->bShowAll = false;
		$i = 1;
		while ($row = $res->Fetch())
		{
			$this->listData[$i++.'_'.$row['ID']] = $row;
		}
		unset($i);
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
					'IN_SHORT_LIST' => isset($_REQUEST['IN_SHORT_LIST']) ?
						(strval($_REQUEST['IN_SHORT_LIST']) === 'Y' ? 'Y' : 'N') : 'N',
					'SORT' => isset($_REQUEST['SORT']) ? intval($_REQUEST['SORT']) : 500
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
				unset($_GET['IN_SHORT_LIST'], $_POST['IN_SHORT_LIST'], $_REQUEST['IN_SHORT_LIST']);
				unset($_GET['SORT'], $_POST['SORT'], $_REQUEST['SORT']);

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
				{
					foreach ($this->actionData['ID'] as $value)
					{
						if (is_string($value) && $value !== '')
						{
							$parts = explode('_', $value, 2);
							if (count($parts) > 1)
							{
								$arId[(int)$parts[1]] = true;
							}
						}
					}
				}
				$bAllRows = $this->actionData['ALL_ROWS'];
				if (!empty($arId) || $bAllRows)
				{
					$modified = 0;
					if (is_array($this->presetData) && is_array($this->presetData['SETTINGS']['FIELDS']))
					{
						foreach ($this->presetData['SETTINGS']['FIELDS'] as $index => $row)
						{
							if ($bAllRows || isset($arId[(int)$row['ID']]))
							{
								if ($this->preset->settingsDeleteField($this->presetData['SETTINGS'],
									(int)($row['ID']), $index))
								{
									$modified++;
								}
							}
						}
					}
					if ($modified)
					{
						$this->preset->update($this->presetId, array('SETTINGS' => $this->presetData['SETTINGS']));
					}
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

					$fieldList = array();
					if (is_array($this->presetData) && is_array($this->presetData['SETTINGS']['FIELDS']))
						$fieldList = &$this->presetData['SETTINGS']['FIELDS'];

					$modified = 0;
					foreach ($fieldList as $index => $row)
					{
						if (in_array(intval($row['ID']), $arId, true))
						{
							if (is_array($this->actionData['FIELDS'][$row['ID']]))
							{
								$data = &$this->actionData['FIELDS'][$row['ID']];
								$field = array('ID' => $row['ID']);
								foreach ($this->listHeaders as $header)
								{
									if (isset($header['editable']) && $header['editable'] == true
										&& $header['id'] !== 'FIELD_NAME' && isset($data[$header['id']]))
									{
										$field[$header['id']] = $data[$header['id']];
									}
								}
								unset($data);
								if (isset($field['FIELD_TITLE']))
								{
									if (isset($this->entityFieldsTitles[$row['FIELD_NAME']]))
									{
										$title = $this->entityFieldsTitles[$row['FIELD_NAME']];
										$origFieldTitle = empty($title) ? $row['FIELD_NAME'] : $title;
										if ($field['FIELD_TITLE'] === $origFieldTitle)
											$field['FIELD_TITLE'] = '';
									}
								}
								if (!empty($field))
								{
									if ($this->preset->settingsUpdateField($this->presetData['SETTINGS'], $field, $index))
										$modified++;
								}
							}
						}
					}
					unset($fieldList, $title);
					if ($modified)
					{
						$this->preset->update($this->presetId, array('SETTINGS' => $this->presetData['SETTINGS']));
					}
				}
			}
			elseif($this->actionData['NAME'] == 'ADD_FIELD' && is_array($this->actionData['FIELDS']))
			{
				if (is_array($this->presetData))
				{
					$field = $this->actionData['FIELDS'];

					if (isset($field['FIELD_TITLE']) && isset($field['FIELD_NAME']))
					{
						if (isset($this->entityFieldsTitles[$field['FIELD_NAME']]))
						{
							$title = $this->entityFieldsTitles[$field['FIELD_NAME']];
							$origFieldTitle = empty($title) ? $field['FIELD_NAME'] : $title;
							if ($field['FIELD_TITLE'] === $origFieldTitle)
								$field['FIELD_TITLE'] = '';
						}
						unset($title);
					}
					if (!is_array($this->presetData['SETTINGS']))
						$this->presetData['SETTINGS'] = array();
					if ($this->preset->settingsAddField($this->presetData['SETTINGS'], $field))
						$this->preset->update($this->presetId, array('SETTINGS' => $this->presetData['SETTINGS']));
				}
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
					array('#entity_type#', '#preset_id#'),
					array($this->entityTypeId, $this->presetId),
					$this->presetEditUrl
				)
			);
		}

		return true;
	}

	protected function prepareEntityFieldsSelectItems()
	{
		$result = array();

		$entityTypeId = EntityPreset::Requisite;

		if ($this->preset->checkEntityType($entityTypeId))
		{
			$fieldsAllowed = array();
			foreach (array_merge($this->requisite->getRqFields(), $this->requisite->getUserFields()) as $fieldName)
			{
				$fieldsAllowed[$fieldName] = true;
			}
			$presetList = $this->requisite->getFixedPresetList();

			$res = $this->preset->getList(array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'filter' => array('=ENTITY_TYPE_ID' => $entityTypeId, '=ACTIVE' => 'Y', '!ID' => $this->presetId),
				'select' => array('ID', 'NAME', 'COUNTRY_ID', 'SETTINGS')
			));
			while ($row = $res->fetch())
			{
				$presetTitle = trim(strval($row['NAME']));
				if (empty($presetTitle))
					$row['NAME'] = '['.$row['ID'].'] - '.GetMessage('CRM_REQUISITE_PRESET_NAME_EMPTY');
				$presetList[] = $row;
			}

			$topItems = array(array('id' => '', 'title' => GetMessage('CRM_PRESET_EDIT_SELECT_FIELDS_NONE')));
			$items = array();
			$otherItems = array();
			$usedFields = array();
			foreach ($presetList as $row)
			{
				if ($this->presetCountryId === intval($row['COUNTRY_ID']) && is_array($row['SETTINGS']))
				{
					$fields = $this->preset->settingsGetFields($row['SETTINGS']);
					if (!empty($fields))
					{
						$countryId = isset($row['COUNTRY_ID']) ? (int)$row['COUNTRY_ID'] : 0;
						$countryPostfix = isset($this->countryList[$countryId]) ?
							" ({$this->countryList[$countryId]})" : '';
						$g = $i = 0;
						foreach ($fields as $fieldInfo)
						{
							if (isset($fieldsAllowed[$fieldInfo['FIELD_NAME']]))
							{
								if ($g === 0)
								{
									$groupItem = array('type' => 'group', 'title' => $row['NAME'].$countryPostfix);
									if ($countryId === $this->presetCountryId)
									{
										$topItems[] = $groupItem;
									}
									else
									{
										$items[] = $groupItem;
									}
									$g++;
								}
								$title = $this->entityFieldsTitles[$fieldInfo['FIELD_NAME']];
								if (!empty($title))
								{
									$item = array(
										'id' => $fieldInfo['FIELD_NAME'],
										'title' => $title
									);
									if ($countryId === $this->presetCountryId)
									{
										$topItems[] = $item;
									}
									else
									{
										$items[] = $item;
									}
									$usedFields[$fieldInfo['FIELD_NAME']] = true;
									$i++;
								}
							}
						}
						if ($i === 0 && $g === 1)
						{
							if ($countryId === $this->presetCountryId)
							{
								unset($topItems[key($topItems)]);
							}
							else
							{
								unset($items[key($items)]);
							}
						}
					}
				}
			}
			$g = $i = 0;
			foreach (array_keys($fieldsAllowed) as $fieldName)
			{
				if (!isset($usedFields[$fieldName]))
				{
					if ($g === 0)
					{
						$otherItems[] = array(
							'type' => 'group', 'title' => GetMessage('CRM_PRESET_EDIT_SELECT_OTHER_FIELDS')
						);
						$g++;
					}
					$title = $this->entityFieldsTitles[$fieldName];
					if (!empty($title))
					{
						$otherItems[] = $item = array(
							'id' => $fieldName,
							'title' => $this->entityFieldsTitles[$fieldName]
						);
						$i++;
					}
				}
			}
			if ($i === 0 && $g === 1)
			{
				unset($otherItems[key($otherItems)]);
			}
			$result = array_merge($topItems, $items, $otherItems);
		}

		return $result;
	}

	protected function prepareResult()
	{
		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeId;
		$this->arResult['ENTITY_TYPE_NAME'] = $this->entityTypeName;
		$this->arResult['GRID_ID'] = $this->gridId;
		$this->arResult['PRESET_LIST_URL'] = $this->presetListUrl;
		$this->arResult['PRESET_EDIT_URL'] = $this->presetEditUrl;
		$this->arResult['HEADERS'] = $this->listHeaders;
		$this->arResult['COMPONENT_ID'] = $this->componentId;
		$this->arResult['IS_AJAX_CALL'] = $this->isAjaxCall;
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
		$this->arResult['ENTITY_FIELDS_FOR_SELECT'] = $this->entityFieldsForSelect;

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

	protected function checkRights()
	{
		$permissions = new \CCrmPerms(\CCrmSecurityHelper::GetCurrentUserID());
		if (!$permissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$this->errors[] = GetMessage('CRM_PERMISSION_DENIED');
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
