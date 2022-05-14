<?php

namespace Bitrix\Crm;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

class PresetListComponent extends \CBitrixComponent
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

	/** @var EntityPreset */
	protected $preset;

	protected $currentCountryId;
	protected $countryList;

	protected $fixedPresetList;
	protected $fixedPresetSelectItems;



	public function __construct($component = null)
	{
		parent::__construct($component);

		$this->componentId = $this->randString();
		$this->errors = array();
		$this->gridId = 'CRM_PRESET_LIST_V15';
		$this->actionData = array(
			'ACTIVE' => false,
			'METHOD' => '',
			'NAME' => '',
			'ID' => 0,
			'FIELDS' => array(),
			'ALL_ROWS' => false,
			'AJAX_CALL' => false
		);
		$this->ajaxMode = false;
		$this->ajaxId = '';
		$this->ajaxOptionJump = false;
		$this->ajaxOptionHistory = false;
		$this->ajaxOptionShadow = false;


		$this->entityTypeId = 0;
		$this->entityTypeName = '';

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

		$this->currentCountryId = 0;
		$this->countryList = array();

		$this->fixedPresetList = array();
		$this->fixedPresetSelectItems = array();
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
		$this->currentCountryId = EntityPreset::getCurrentCountryId();


		$this->entityTypeId = isset($_REQUEST['entity_type']) ? intval($_REQUEST['entity_type']) : 0;
		if (isset($this->arParams['ENTITY_TYPE_ID']))
			$this->entityTypeId = intval($this->arParams['ENTITY_TYPE_ID']);
		if (!\Bitrix\Crm\EntityPreset::checkEntityType($this->entityTypeId))
		{
			$this->errors[] = GetMessage('CRM_PRESET_ENTITY_TYPE_INVALID');
			return false;
		}
		if ($this->entityTypeId !== EntityPreset::Requisite)
			$this->gridId .= '_E'.$this->entityTypeId;
		$entityTypes = \Bitrix\Crm\EntityPreset::getEntityTypes();
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

		$countryList = array(0 => GetMessage('CRM_PRESET_COUNTRY_EMPTY'));
		foreach (EntityPreset::getCountryList() as $k => $v)
			$countryList[$k] = $v;
		$this->countryList = &$countryList;

		$fixedPresetItemsByCountry = array();
		foreach (EntityRequisite::getFixedPresetList() as $fixedPresetInfo)
		{
			$countryId = (int)$fixedPresetInfo['COUNTRY_ID'];
			if (!is_array($fixedPresetItemsByCountry[$countryId]))
				$fixedPresetItemsByCountry[$countryId] = array();
			$fixedPresetItemsByCountry[$countryId][] = array(
				'id' => $fixedPresetInfo['ID'],
				'title' => $fixedPresetInfo['NAME']
			);
			$this->fixedPresetList[$fixedPresetInfo['ID']] = $fixedPresetInfo;
		}
		$fixedPresetSelectItems[] = array('id' => 0, 'title' => GetMessage('CRM_PRESET_NOT_SELECTED'));
		if (is_array($fixedPresetItemsByCountry[$this->currentCountryId]))
		{
			$i = 0;
			foreach ($fixedPresetItemsByCountry[$this->currentCountryId] as $item)
			{
				if ($i === 0)
				{
					$fixedPresetSelectItems[] =
						array('type' => 'group', 'title' => $countryList[$this->currentCountryId]);
				}
				$fixedPresetSelectItems[] = $item;
				$i++;
			}
			unset($fixedPresetItemsByCountry[$this->currentCountryId]);
		}
		if (EntityPreset::isUTFMode())
		{
			foreach ($fixedPresetItemsByCountry as $countryId => $fixedPresetItems)
			{
				$i = 0;
				foreach ($fixedPresetItems as $item)
				{
					if ($i === 0)
					{
						$fixedPresetSelectItems[] =
							array('type' => 'group', 'title' => $countryList[$countryId]);
					}
					$fixedPresetSelectItems[] = $item;
					$i++;
				}
			}
		}
		$this->fixedPresetSelectItems = &$fixedPresetSelectItems;

		return true;
	}

	protected function setPageTitle()
	{
		$this->getApp()->SetTitle(GetMessage('CRM_PRESET_LIST_TITLE_EDIT', array('#NAME#' => $this->entityTypeName)));
	}

	protected function parseFilter()
	{
		// filter
		$this->filter = array('=ENTITY_TYPE_ID' => $this->entityTypeId);

		return true;
	}

	protected function prepareListHeaders()
	{
		Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/crm/lib/preset.php');

		$this->listHeaders = array(
			array('id' => 'ID', 'name' => GetMessage('CRM_REQUISITE_PRESET_ENTITY_ID_FIELD'), 'sort' => 'ID', 'default' => false, 'editable' => false, 'type' => 'int'),
			array('id' => 'NAME', 'name' => GetMessage('CRM_REQUISITE_PRESET_ENTITY_NAME_FIELD'), 'sort' => 'NAME', 'default' => true, 'editable' => true, 'type' => 'text'),
			array('id' => 'ACTIVE', 'name' => GetMessage('CRM_REQUISITE_PRESET_ENTITY_ACTIVE_FIELD'), 'sort' => 'ACTIVE', 'default' => true, 'editable' => array('items' => array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))), 'type' => 'list'),
			array('id' => 'SORT', 'name' => GetMessage('CRM_REQUISITE_PRESET_ENTITY_SORT_FIELD'), 'sort' => 'SORT', 'default' => true, 'editable' => true, 'type' => 'int'),
			array('id' => 'COUNTRY_ID', 'name' => GetMessage('CRM_REQUISITE_PRESET_ENTITY_COUNTRY_ID_FIELD'), 'sort' => 'COUNTRY_ID', 'default' => false, 'editable' => false/*array('items' => $this->countryList)*/, 'type' => 'text'/*'list'*/)
		);

		if ($this->entityTypeId === EntityPreset::Requisite)
		{
			$this->listHeaders[] = array(
				'id' => 'REQUISITE_DEF_FOR_COMPANY',
				'name' => GetMessage('CRM_PRESET_DEF_FOR_REQUISITE_OF_COMPANY'),
				'sort' => false,
				'default' => true,
				'editable' => false,
				'align' => 'left',
				'type' => 'checkbox'
			);
			$this->listHeaders[] = array(
				'id' => 'REQUISITE_DEF_FOR_CONTACT',
				'name' => GetMessage('CRM_PRESET_DEF_FOR_REQUISITE_OF_CONTACT'),
				'sort' => false,
				'default' => true,
				'editable' => false,
				'align' => 'left',
				'type' => 'checkbox'
			);
		}

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
		if (!empty($select))
		{
			$headerFields = array();
			foreach ($this->listHeaders as $header)
			{
				if (isset($header['id']) && !empty($header['id']))
					$headerFields[$header['id']] = true;
			}
			$newSelect = array();
			foreach ($select as $fieldName)
			{
				if (isset($headerFields[$fieldName]))
					$newSelect[] = $fieldName;
			}
			$select = $newSelect;
			unset($newSelect, $headerFields);
		}
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
		$selectDefCompanyPreset = $selectDefContactPreset = false;
		if ($this->entityTypeId === EntityPreset::Requisite)
		{
			$key = array_search('REQUISITE_DEF_FOR_COMPANY', $select, true);
			if ($key !== false)
			{
				$selectDefCompanyPreset = true;
				unset($select[$key]);
			}
			$key = array_search('REQUISITE_DEF_FOR_CONTACT', $select, true);
			if ($key !== false)
			{
				$selectDefContactPreset = true;
				unset($select[$key]);
			}
		}
		$select = array_unique($select, SORT_STRING);

		$arNavParams = $gridOptions->GetNavParams();
		$pageSize = $arNavParams['nPageSize'];
		unset($arNavParams);

		$companyRequisiteDefPresetId = EntityRequisite::getDefaultPresetId(\CCrmOwnerType::Company);
		$contactRequisiteDefPresetId = EntityRequisite::getDefaultPresetId(\CCrmOwnerType::Contact);

		$res = new \CDBResult(
			$this->preset->getList(
				array(
					'order' => $this->sort,
					'filter' => $this->filter,
					'select' => $select
				)
			)
		);
		$res->NavStart($pageSize);
		$res->bShowAll = false;
		while ($row = $res->Fetch())
		{
			if ($this->entityTypeId === EntityPreset::Requisite)
			{
				if ($selectDefCompanyPreset)
					$row['REQUISITE_DEF_FOR_COMPANY'] = ($companyRequisiteDefPresetId === (int)$row['ID']) ? 'Y' : 'N';
				if ($selectDefContactPreset)
					$row['REQUISITE_DEF_FOR_CONTACT'] = ($contactRequisiteDefPresetId === (int)$row['ID']) ? 'Y' : 'N';
			}
			$this->listData[$row['ID']] = $row;
		}
		$this->rowsCount = $res->SelectedRowsCount();
		$this->navObject = $res;

		return true;
	}

	protected function parseListAction()
	{
		if(check_bitrix_sessid())
		{
			$getAction = 'action_'.$this->gridId;
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

				$this->actionData['AJAX_CALL'] = isset($_POST['AJAX_CALL']) ? $_POST['AJAX_CALL'] === 'Y' : false;

				$this->actionData['ACTIVE'] = true;
			}
			else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET[$getAction]))
			{
				$this->actionData['METHOD'] = 'GET';

				$this->actionData['NAME'] = $_GET[$getAction];
				unset($_GET[$getAction], $_REQUEST[$getAction]);

				$this->actionData['ALL_ROWS'] = false;

				if(isset($_GET['ID']))
				{
					$this->actionData['ID'] = $_GET['ID'];
					unset($_GET['ID'], $_REQUEST['ID']);
				}

				$this->actionData['AJAX_CALL'] = isset($_GET['AJAX_CALL']) ? $_GET['AJAX_CALL'] === 'Y' : false;

				$this->actionData['ACTIVE'] = true;
			}
			else if (isset($_REQUEST['action']))
			{
				$this->actionData['METHOD'] = $_SERVER['REQUEST_METHOD'];

				$this->actionData['NAME'] = $_REQUEST['action'];
				unset($_GET['action'], $_POST['action'], $_REQUEST['action']);

				$fields = array();
				$fixedPresetId = (int)$_REQUEST['FIXED_PRESET_ID'];
				$bFromFixedPreset = false;
				if ($this->actionData['NAME'] === 'ADD_PRESET' && $_REQUEST['CREATE_NEW'] === 'N'
					&& $fixedPresetId > 0 && is_array($this->fixedPresetList[$fixedPresetId]))
				{
					$fields = $this->fixedPresetList[$fixedPresetId];
					unset($fields['ID']);
					$bFromFixedPreset = true;
				}
				$fields['NAME'] = isset($_REQUEST['NAME']) ? strval($_REQUEST['NAME']) : '';
				$fields['ACTIVE'] = isset($_REQUEST['ACTIVE']) ? (strval($_REQUEST['ACTIVE']) === 'Y' ? 'Y' : 'N') : 'N';
				if (isset($fields['XML_ID']))
					unset($fields['XML_ID']);
				$fields['SORT'] = isset($_REQUEST['SORT']) ? intval($_REQUEST['SORT']) : 500;
				if (!$bFromFixedPreset)
				{
					$countryId = isset($_REQUEST['COUNTRY_ID']) ? intval($_REQUEST['COUNTRY_ID']) : 0;
					if (!isset($this->countryList[$countryId]))
						$countryId = $this->currentCountryId;
					$fields['COUNTRY_ID'] = $countryId;
				}

				$presetId = isset($_REQUEST['ID']) ? (int)$_REQUEST['ID'] : 0;
				if ($this->actionData['NAME'] === 'edit' && $presetId > 0)
					$this->actionData['FIELDS'][$presetId] = $fields;
				else
					$this->actionData['FIELDS'] = $fields;
				unset($presetId, $fields);

				unset($_GET['ID'], $_POST['ID'], $_REQUEST['ID']);
				unset($_GET['CREATE_NEW'], $_POST['CREATE_NEW'], $_REQUEST['CREATE_NEW']);
				unset($_GET['FIXED_PRESET_ID'], $_POST['FIXED_PRESET_ID'], $_REQUEST['FIXED_PRESET_ID']);
				unset($_GET['NAME'], $_POST['NAME'], $_REQUEST['NAME']);
				unset($_GET['ACTIVE'], $_POST['ACTIVE'], $_REQUEST['ACTIVE']);
				unset($_GET['SORT'], $_POST['SORT'], $_REQUEST['SORT']);
				unset($_GET['COUNTRY_ID'], $_POST['COUNTRY_ID'], $_REQUEST['COUNTRY_ID']);

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

					$res = $this->preset->getList(
						array(
							'filter' => $arFilterDel,
							'select' => array('ID')
						)
					);

					$dbConnection = Application::getConnection();
					while($row = $res->fetch())
					{
						$dbConnection->startTransaction();
						$result = $this->preset->delete($row['ID']);
						if ($result->isSuccess())
						{
							$dbConnection->commitTransaction();
						}
						else
						{
							$dbConnection->rollbackTransaction();
							$errMsg = GetMessage('CRM_PRESET_ERR_DELETE', array('#ID#' => $row['ID']));
							$errorMessages = $result->getErrorMessages();
							if (is_array($errorMessages) && !empty($errorMessages))
								$errMsg .= ': '.$errorMessages[0];
							$this->errors[] = $errMsg;
							unset($errMsg, $errorMessages);
						}
					}
				}
			}
			elseif($this->actionData['METHOD'] == 'POST' && $this->actionData['NAME'] === 'edit')
			{
				if(isset($this->actionData['FIELDS']) && is_array($this->actionData['FIELDS']))
				{
					$arId = array();
					foreach (array_keys($this->actionData['FIELDS']) as $id)
						$arId[(int)$id] = true;
					$arId = array_keys($arId);
					$res = $this->preset->getList(
						array(
							'filter' => array('=ID' => $arId),
							'select' => array('ID')
						)
					);
					unset($arId);
					$dbConnection = Application::getConnection();
					while ($row = $res->fetch())
					{
						$updateFields = array();
						foreach ($this->listHeaders as $header)
						{
							$data = &$this->actionData['FIELDS'][$row['ID']];
							if (isset($header['editable']) && $header['editable'] == true && isset($data[$header['id']]))
							{
								$updateFields[$header['id']] = $data[$header['id']];
							}
							unset($data);
						}
						if (!empty($updateFields))
						{
							$dbConnection->startTransaction();
							if ($this->preset->update((int)$row['ID'], $updateFields)->isSuccess())
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
			elseif ($this->actionData['METHOD'] == 'GET'
				&& ($this->actionData['NAME'] === 'set_def_for_company'
					|| $this->actionData['NAME'] === 'set_def_for_contact'))
			{
				$presetId = isset($this->actionData['ID']) ? (int)$this->actionData['ID'] : 0;
				if ($presetId > 0)
				{
					$entityTypeId = ($this->actionData['NAME'] === 'set_def_for_company') ?
						\CCrmOwnerType::Company : \CCrmOwnerType::Contact;
					EntityRequisite::setDefaultPresetId($entityTypeId, $presetId);
				}
			}
			elseif ($this->actionData['METHOD'] == 'GET' && $this->actionData['NAME'] === 'error')
			{
				if(!empty($this->actionData['ERROR']))
					ShowError($this->actionData['ERROR']);
			}
			elseif($this->actionData['NAME'] == 'ADD_PRESET' && is_array($this->actionData['FIELDS']))
			{
				$fields = array(
					'NAME' => mb_substr($this->actionData['FIELDS']['NAME'], 0, 255),
					'ACTIVE' => ($this->actionData['FIELDS']['ACTIVE'] === 'Y') ? 'Y' : 'N',
					'SORT' => $this->actionData['FIELDS']['SORT'],
					'COUNTRY_ID' => $this->actionData['FIELDS']['COUNTRY_ID'],
					'ENTITY_TYPE_ID' => $this->entityTypeId
				);
				if (is_array($this->actionData['FIELDS']['SETTINGS']))
					$fields['SETTINGS'] = $this->actionData['FIELDS']['SETTINGS'];
				$this->preset->add($fields);
			}
		}

		return true;
	}

	protected function processRedirect()
	{
		if($this->actionData['ACTIVE'] && !$this->actionData['AJAX_CALL'])
			LocalRedirect(str_replace(array('#entity_type#'), array($this->entityTypeId), $this->presetListUrl));

		return true;
	}

	protected function checkNeedChangeCurrentCountry(): bool
	{
		$countryId = (int)Option::get('crm', '~crm_requisite_current_country_can_change', 0);
		return (
			$countryId > 0
			&& in_array($countryId, EntityRequisite::getAllowedRqFieldCountries(), true)
			&& $countryId !== $this->currentCountryId
		);
	}

	protected function getCountryName(int $countryId): string
	{
		return
			($countryId > 0 && isset($this->countryList[$countryId]))
			? $this->countryList[$countryId]
			: ''
		;
	}

	protected function getSrcCountryName(): string
	{
		return $this->getCountryName($this->currentCountryId);
	}

	protected function getDstCountryName(): string
	{
		$countryId = (int)Option::get('crm', '~crm_requisite_current_country_can_change', 0);

		return $this->getCountryName($countryId);
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
		$this->arResult['ERRORS'] = $this->errors;
		$this->arResult['FILTER'] = $this->filter;
		$this->arResult['LIST_DATA'] = $this->listData;
		$this->arResult['SORT'] = $this->sort;
		$this->arResult['SORT_VARS'] = $this->sortVars;
		$this->arResult['ROWS_COUNT'] = $this->rowsCount;
		$this->arResult['NAV_OBJECT'] = $this->navObject;
		$this->arResult['CURRENT_COUNTRY_ID'] = $this->currentCountryId;
		$this->arResult['COUNTRY_LIST'] = $this->countryList;
		$this->arResult['FIXED_PRESET_SELECT_ITEMS'] = $this->fixedPresetSelectItems;

		$needChangeCurrentCountry = $this->preset->checkNeedChangeCurrentCountry();
		$this->arResult['NEED_FOR_CHANGE_CURRENT_COUNTRY'] = $needChangeCurrentCountry;
		$this->arResult['SRC_COUNTRY_NAME'] = $needChangeCurrentCountry ? $this->getSrcCountryName() : '';
		$this->arResult['DST_COUNTRY_NAME'] = $needChangeCurrentCountry ? $this->getDstCountryName() : '';

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
