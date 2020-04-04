<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

if(!CAllCrmInvoice::installExternalEntities())
	return;
if (!CCrmQuote::LocalComponentCausedUpdater())
	return;

if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
	return;
}
if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}


$arResult['ACTIVE_TAB'] = isset($_GET['ACTIVE_TAB']) ? trim($_GET['ACTIVE_TAB']) : '';
if($arResult['ACTIVE_TAB'] !== '')
{
	if(preg_match("/^status_tab_([a-z_0-9]+)$/i", $arResult['ACTIVE_TAB'], $m) !== 1)
	{
		$arResult['ACTIVE_TAB'] = '';
	}
	else
	{
		$arResult['ACTIVE_ENTITY_ID'] = $m[1];
	}
}

if($arResult['ACTIVE_TAB'] === '')
{
	$arResult['ACTIVE_TAB'] = 'status_tab_STATUS';
	$arResult['ACTIVE_ENTITY_ID'] = 'STATUS';
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid() &&
	isset($_POST['ACTION']) && $_POST['ACTION'] == 'save')
{
	$arAdd = array();
	$arUpdate = array();
	$arDelete = array();

	foreach($_POST['LIST'] as $entityId => $arFields)
	{
		$iPrevSort = 0;
		$CCrmStatus = false;

		/* Here we can define our own class to process own status type (if we need).
		 * It may be inherited from CCrmStatus.
		 * For example see CCrmStatusInvoice.
		 */
		$events = GetModuleEvents("crm", "OnBeforeCrmStatusCreate");

		while($arEvent = $events->Fetch())
		{
			$CCrmStatus = ExecuteModuleEventEx($arEvent, array($entityId));

			if($CCrmStatus)
				break;
		}

		if(!$CCrmStatus)
			$CCrmStatus = new CCrmStatus($entityId);

		$error = '';
		if(array_key_exists('REMOVE', $arFields) && is_array($arFields['REMOVE']))
		{
			$listField = array();
			foreach($arFields['REMOVE'] as $fieldId => $field)
			{
				$arCurrentData = $CCrmStatus->GetStatusById($fieldId);
				if ($arCurrentData['SYSTEM'] == 'N')
				{
					$result = $CCrmStatus->Delete($fieldId);
					if(!$result)
					{
						$listField[] = '"'.$arCurrentData['NAME'].'"';
					}
				}
				else
				{
					$arUpdate['NAME'] = trim($arCurrentData['NAME_INIT']);
					$CCrmStatus->Update($fieldId, $arUpdate);
				}
			}
			if(!empty($listField))
			{
				$langString = '';
				if(count($listField) > 1)
					$langString = '_MANY';

				$stringListField = implode(', ', $listField);
				$error = GetMessage('CRM_MODULE_ERROR_REMOVE_FIELD'.$langString,
					array('#field#' => $stringListField));
			}
		}

		if(!empty($error))
		{
			LocalRedirect($APPLICATION->GetCurPage().'?ACTIVE_TAB='.$_POST['ACTIVE_TAB'].'&ERROR='.$error);
		}

		$settings = array();
		foreach($arFields as $id => $arField)
		{
			$arField['SORT'] = (int)$arField['SORT'];
			if ($arField['SORT'] <= $iPrevSort)
				$arField['SORT'] = $iPrevSort + 10;
			$iPrevSort = $arField['SORT'];

			if (substr($id, 0, 1) == 'n')
			{
				if (trim($arField['VALUE']) == "")
					continue;

				$arAdd['NAME'] = trim($arField['VALUE']);
				$arAdd['SORT'] = $arField['SORT'];

				$id = $CCrmStatus->Add($arAdd);
				$arCurrentData = $CCrmStatus->GetStatusById($id);
				if(is_array($arCurrentData) && isset($arCurrentData['STATUS_ID']))
				{
					$arField['STATUS_ID'] = $arCurrentData['STATUS_ID'];
				}
				else
				{
					$field["STATUS_ID"] = $id;
				}
			}
			else
			{
				$arCurrentData = $CCrmStatus->GetStatusById($id);
				if(trim($arField['VALUE']) != $arCurrentData['NAME'] ||
					intval($arField['SORT']) != $arCurrentData['SORT'])
				{
					$arUpdate['NAME'] = trim($arField['VALUE']);
					$arUpdate['SORT'] = $arField['SORT'];
					$CCrmStatus->Update($id, $arUpdate);
				}
			}

			if(isset($arField['COLOR']) && $arField['COLOR'])
			{
				$settings[$arField['STATUS_ID']]['COLOR'] = $arField['COLOR'];
			}
		}

		if(!empty($settings))
		{
			COption::SetOptionString('crm', 'CONFIG_STATUS_'.$entityId, serialize($settings));
		}

		if($entityId === 'STATUS')
		{
			$agent = \Bitrix\Crm\Agent\Semantics\LeadSemanticsRebuildAgent::getInstance();
			if(!$agent->isRegistered())
			{
				$agent->enable(true);
				$agent->register();
			}
		}

		if($entityId === 'DEAL_STAGE' || preg_match("/DEAL_STAGE_\d+/", $entityId) == 1)
		{
			$agent = \Bitrix\Crm\Agent\Semantics\DealSemanticsRebuildAgent::getInstance();
			if(!$agent->isRegistered())
			{
				$agent->enable(true);
				$agent->register();
			}
		}
	}

	LocalRedirect($APPLICATION->GetCurPage().'?ACTIVE_TAB='.$_POST['ACTIVE_TAB']);
}

$arResult['HEADERS'] = array();
$arResult['ROWS'] = array();
$arResult['ENTITY'] = array();
$settings = array();
$colorSchemes = array();

foreach(CCrmStatus::GetEntityTypes() as $entityId => $arEntityType)
{
	$arResult['HEADERS'][$entityId] = $arEntityType['NAME'];
	$arResult['ROWS'][$entityId] = Array();

	if(isset($arEntityType['SEMANTIC_INFO']) && is_array($arEntityType['SEMANTIC_INFO']))
	{
		$arResult['ENTITY'][$entityId] = $arEntityType['SEMANTIC_INFO'];

		$parentEntityID = isset($arEntityType['PARENT_ID']) ? $arEntityType['PARENT_ID'] : '';
		$addCaption = GetMessage("CRM_STATUS_ADD_{$entityId}");
		if($addCaption == '' && $parentEntityID !== '')
		{
			$addCaption = GetMessage("CRM_STATUS_ADD_{$parentEntityID}");
		}
		$arResult['ENTITY'][$entityId]['ADD_CAPTION'] = $addCaption;

		$defaultName = GetMessage("CRM_STATUS_DEFAULT_NAME_{$entityId}");
		if($defaultName == '' && $parentEntityID !== '')
		{
			$defaultName = GetMessage("CRM_STATUS_DEFAULT_NAME_{$parentEntityID}");
		}
		$arResult['ENTITY'][$entityId]['DEFAULT_NAME'] = $defaultName;

		$deletionConfirmation = GetMessage("CRM_STATUS_DELETION_CONFIRMATION_{$entityId}");
		if($deletionConfirmation == '' && $parentEntityID !== '')
		{
			$deletionConfirmation = GetMessage("CRM_STATUS_DELETION_CONFIRMATION_{$parentEntityID}");
		}
		$arResult['ENTITY'][$entityId]['DELETION_CONFIRMATION'] = $deletionConfirmation;
	}

	$colorScheme = \Bitrix\Crm\Color\PhaseColorSchemeManager::resolveSchemeByName('CONFIG_STATUS_'.$entityId);
	if($colorScheme)
	{
		$colorSchemes[$entityId] = $colorScheme;
	}
	else
	{
		$settings[$entityId] = unserialize(COption::GetOptionString('crm', 'CONFIG_STATUS_'.$entityId));
	}
}

$res = CCrmStatus::GetList(array('SORT' => 'ASC'));
while($status = $res->Fetch())
{
	$arResult['ROWS'][$status['ENTITY_ID']][$status['ID']] = $status;

	$entityId = $status['ENTITY_ID'];
	if(isset($colorSchemes[$entityId]))
	{
		$colorSchemeElement = $colorSchemes[$entityId]->getElementByName($status['STATUS_ID']);
		if($colorSchemeElement)
		{
			$arResult['ROWS'][$entityId][$status['ID']]['COLOR'] = $colorSchemeElement->getColor();
		}
	}
	elseif(!empty($settings))
	{
		$arResult['ROWS'][$status['ENTITY_ID']][$status['ID']]['COLOR'] = $settings[$status['ENTITY_ID']][$status['STATUS_ID']]['COLOR'];
	}

	if($arResult['ENTITY'][$status['ENTITY_ID']]['FINAL_SUCCESS_FIELD'] == $status['STATUS_ID'])
	{
		$arResult['ENTITY'][$status['ENTITY_ID']]['FINAL_SORT'] = $status['SORT'];
	}
}

/* Preparation of data for different settings */
foreach($arResult['ENTITY'] as $entityId => $dataEntity)
{
	$arResult['INITIAL_FIELDS'][$entityId] = array();
	$arResult['EXTRA_FIELDS'][$entityId] = array();
	$arResult['FINAL_FIELDS'][$entityId] = array();
	$arResult['EXTRA_FINAL_FIELDS'][$entityId] = array();
	$arResult['SUCCESS_FIELDS'][$entityId] = array();
	$arResult['UNSUCCESS_FIELDS'][$entityId] = array();
	$number = 1;
	foreach($arResult['ROWS'][$entityId] as $status)
	{
		$status['NUMBER'] = $number;
		if (empty($arResult['INITIAL_FIELDS'][$entityId]))
		{
			$arResult['INITIAL_FIELDS'][$entityId] = $status;
			$arResult['SUCCESS_FIELDS'][$entityId][] = $status;
		}
		elseif($status['STATUS_ID'] == $dataEntity['FINAL_SUCCESS_FIELD'])
		{
			$arResult['FINAL_FIELDS'][$entityId]['SUCCESSFUL'] = $status;
			$arResult['SUCCESS_FIELDS'][$entityId][] = $status;
		}
		elseif($status['STATUS_ID'] == $dataEntity['FINAL_UNSUCCESS_FIELD'])
		{
			$arResult['FINAL_FIELDS'][$entityId]['UNSUCCESSFUL'] = $status;
			$arResult['UNSUCCESS_FIELDS'][$entityId][] = $status;
		}
		else
		{
			if($status['SORT'] < $arResult['ENTITY'][$status['ENTITY_ID']]['FINAL_SORT'])
			{
				$arResult['EXTRA_FIELDS'][$entityId][] = $status;
				$arResult['SUCCESS_FIELDS'][$entityId][] = $status;
			}
			else
			{
				$arResult['EXTRA_FINAL_FIELDS'][$entityId][] = $status;
				$arResult['UNSUCCESS_FIELDS'][$entityId][] = $status;
			}
		}
		$number++;
	}
}

$arResult['NEED_FOR_FIX_STATUSES'] = false;
if(CCrmPerms::IsAdmin() && COption::GetOptionString('crm', '~CRM_FIX_STATUSES', 'N') === 'Y')
{
	$arResult['NEED_FOR_FIX_STATUSES'] = true;
}

$arResult['RAND_STRING'] = $this->randString();

CUtil::InitJSCore();
$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;
$this->IncludeComponentTemplate();
$APPLICATION->AddChainItem(GetMessage('CRM_FIELDS_ENTITY_LIST'), $arResult['~ENTITY_LIST_URL']);

?>