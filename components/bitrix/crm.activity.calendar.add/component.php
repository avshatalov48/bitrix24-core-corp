<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $USER, $APPLICATION;

if (!CModule::IncludeModule('crm'))
	return;

if (!CModule::IncludeModule(CRM_MODULE_CALENDAR_ID))
	return;

CModule::IncludeModule('fileman');

global $USER, $APPLICATION, $DB;

$CCrmPerms = new CCrmPerms($USER->GetID());
if ($CCrmPerms->HavePerm($arParams['ENTITY_TYPE'], BX_CRM_PERM_NONE, 'READ'))
	return;

CUtil::InitJSCore();

if (is_array($arParams['ENTITY_ID']))
{
	array_walk(
		$arParams['ENTITY_ID'],
		function (&$val) {
			$val = (int)$val;
		}
	);
}
elseif ($arParams['ENTITY_ID'] != 'all')
{
	$arParams['ENTITY_ID'] = (int)$arParams['ENTITY_ID'];
}
elseif ($arParams['ENTITY_ID'] == 'all')
{
	return;
}

$arParams['RESULT_TAB'] = trim($arParams['RESULT_TAB']);
$arResult['FORM_TYPE'] = mb_strtoupper($arParams['FORM_TYPE']);
if (!in_array($arResult['FORM_TYPE'],array('LIST','SHOW','EDIT')))
	$arResult['FORM_TYPE'] = 'LIST';
$arResult['ENTITY_TYPE'] = mb_strtoupper($arParams['ENTITY_TYPE']);
$arResult['ENTITY_ID'] = $arParams['ENTITY_ID'];

$arResult['ENTITY_ID'] = (is_array($arResult['ENTITY_ID']) ? $arResult['ENTITY_ID'] : array($arResult['ENTITY_ID']));
$arFilter = array('ID' => $arResult['ENTITY_ID']);

switch ($arResult['ENTITY_TYPE'])
{
	case 'CONTACT':
		$obRes = CCrmContact::GetListEx(array('ID' => 'ASC'), $arFilter, false, false, array('ID', 'ASSIGNED_BY_ID'));
		break;
	case 'DEAL':
		$obRes = CCrmDeal::GetListEx(array('ID' => 'ASC'), $arFilter, false, false, array('ID', 'ASSIGNED_BY_ID'));
		break;
	case 'COMPANY':
		$obRes = CCrmCompany::GetListEx(array('ID' => 'ASC'), $arFilter, false, false, array('ID', 'ASSIGNED_BY_ID'));
		break;
	default:
	case 'LEAD':
		$obRes = CCrmLead::GetListEx(array('ID' => 'ASC'), $arFilter, false, false, array('ID', 'ASSIGNED_BY_ID'));
		$arResult['ENTITY_TYPE'] = 'LEAD';
		break;
}

$arID = array();
while ($arRow = $obRes->Fetch())
	$arID[$arRow['ID']] = $arRow['ASSIGNED_BY_ID'];

if (empty($arID))
	return;

$arResult['ERROR_MESSAGE'] = array();
$strCalendarDate = ConvertTimeStamp(time()+CTimeZone::GetOffset(),'FULL',SITE_ID);
$arDefResult['VALUES'] = array(
	'CALENDAR_TOPIC' => '',
	'CALENDAR_FROM' => $strCalendarDate,
	'CALENDAR_TO' => $strCalendarDate,
	'CALENDAR_DESC' => '',
	'REMIND_FLAG' => 'N',
	'REMIND_LEN' => 15,
	'REMIND_TYPE' => 'min',
	'IMPORTANCE' => 'normal'
);

$arRemType = array(
	'min',
	'hour',
	'day'
);
$arPriorityType = array(
	'low',
	'normal',
	'high'
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	$boolError = false;
	$arResult['ERROR_MESSAGE'] = array();
	if (!isset($_POST['CALENDAR_TOPIC']))
	{
		$boolError = true;
		$arResult['ERROR_MESSAGE'][] = GetMessage('BX_CRM_CACA_ERR_TOPIC_ABSENT');
	}
	else
	{
		$arResult['VALUES']['CALENDAR_TOPIC'] = $_POST['CALENDAR_TOPIC'];
	}
	$boolCompare = true;
	if (!isset($_POST['CALENDAR_FROM']) || empty($_POST['CALENDAR_FROM']))
	{
		$boolError = true;
		$boolCompare = false;
		$arResult['ERROR_MESSAGE'][] = GetMessage('BX_CRM_CACA_ERR_DATE_FROM_ABSENT');
	}
	else
	{
		$arResult['VALUES']['CALENDAR_FROM'] = $_POST['CALENDAR_FROM'];
		if (empty($arResult['VALUES']['CALENDAR_FROM']) || !$DB->IsDate($arResult['VALUES']['CALENDAR_FROM'],false, SITE_ID,'FULL'))
		{
			$boolError = true;
			$boolCompare = false;
			$arResult['ERROR_MESSAGE'][] = GetMessage('BX_CRM_CACA_ERR_DATE_FROM_FORMAT');
		}
	}
	if (!isset($_POST['CALENDAR_TO']) || empty($_POST['CALENDAR_TO']))
	{
		if (isset($arResult['VALUES']['CALENDAR_FROM']))
			$arResult['VALUES']['CALENDAR_TO'] = $arResult['VALUES']['CALENDAR_FROM'];
		$boolCompare = false;
	}
	else
	{
		$arResult['VALUES']['CALENDAR_TO'] = $_POST['CALENDAR_TO'];
		if (!$DB->IsDate($arResult['VALUES']['CALENDAR_TO'],false,SITE_ID,'FULL'))
		{
			$boolError = true;
			$boolCompare = false;
			$arResult['ERROR_MESSAGE'][] = GetMessage('BX_CRM_CACA_ERR_DATE_TO_FORMAT');
		}
	}
	if (true == $boolCompare && MakeTimeStamp($arResult['VALUES']['CALENDAR_FROM']) > MakeTimeStamp($arResult['VALUES']['CALENDAR_TO']))
	{
		$boolError = true;
		$arResult['ERROR_MESSAGE'][] = GetMessage('BX_CRM_CACA_ERR_DATE_COMPARE');
	}

	if (isset($_POST['CALENDAR_DESC']))
		$arResult['VALUES']['CALENDAR_DESC'] = $_POST['CALENDAR_DESC'];

	$arResult['VALUES']['REMIND_FLAG'] = (isset($_POST['REMIND_FLAG']) && ('Y' == $_POST['REMIND_FLAG']) ? 'Y' : 'N');
	$strRemType = '';
	$intRemLen = 0;
	if ('Y' == $arResult['VALUES']['REMIND_FLAG'])
	{
		if (isset($_POST['REMIND_TYPE']))
			$strRemType = mb_strtolower($_POST['REMIND_TYPE']);
		if (!in_array($strRemType,$arRemType))
			$strRemType = 'min';
		if (isset($_POST['REMIND_LEN']))
			$intRemLen = intval($_POST['REMIND_LEN']);
		if (0 >= $intRemLen)
			$intRemLen = 15;
	}
	$strPriority = '';
	if (isset($_POST['PRIORITY']))
		$strPriority = mb_strtolower($_POST['PRIORITY']);
	if (!in_array($strPriority,$arPriorityType))
		$strPriority = 'normal';
	$arResult['VALUES']['REMIND_LEN'] = $intRemLen;
	$arResult['VALUES']['REMIND_TYPE'] = $strRemType;
	$arResult['VALUES']['PRIORITY'] = $strPriority;

	if (false == $boolError)
	{
		$entityTypeId = \CCrmOwnerType::ResolveID($arFilter['ENTITY_TYPE'] ?? '');
		foreach ($arID as $intID => $iUserID)
		{
			if ($entityTypeId <= 0 || $intID <= 0)
			{
				continue;
			}
			$arCrmEvents = [\Bitrix\Crm\UserField\Types\ElementType::getValueByIdentifier(new \Bitrix\Crm\ItemIdentifier(
				$entityTypeId,
				$intID
			))];
			$arFields = array(
				'CAL_TYPE' => 'user',
				'OWNER_ID' => $iUserID,
				'NAME' => $arResult['VALUES']['CALENDAR_TOPIC'],
				'DATE_FROM' => $arResult['VALUES']['CALENDAR_FROM'],
				'DATE_TO' => $arResult['VALUES']['CALENDAR_TO'],
				'DESCRIPTION' => $arResult['VALUES']['CALENDAR_DESC'],
				'IMPORTANCE' => $strPriority,
			);
			if ('Y' == $arResult['VALUES']['REMIND_FLAG'])
			{
				$arFields['REMIND'] = array(
					array(
						'type' => $strRemType,
						'count' => $intRemLen
					),
				);
			}
			$intEventID = CCalendar::SaveEvent(array(
				'arFields' => $arFields,
				'userId' => $iUserID,
				'autoDetectSection' => true,
				'autoCreateSection' => true
			));
			if (0 < intval($intEventID))
			{
				CCalendarEvent::UpdateUserFields(
					$intEventID,
					array(
						'UF_CRM_CAL_EVENT' => $arCrmEvents,
					));
			}
			else
			{
				$boolError = true;
				$arResult['ERROR_MESSAGE'][] = GetMessage('BX_CRM_CACA_ERR_ADD_FAIL');
			}
		}
	}
	if (true == $boolError)
	{
		$arKeys = array_keys($arDefResult['VALUES']);
		foreach ($arKeys as $strKey)
		{
			if (!isset($arResult['VALUES'][$strKey]))
				$arResult['VALUES'][$strKey] = '';
		}
	}
}
else
	$arResult['VALUES'] = $arDefResult['VALUES'];

if (empty($_POST['EVENT_PAGE']))
	$_POST['EVENT_PAGE'] = $APPLICATION->GetCurPage();

$arResult['EVENT_PAGE'] = CHTTP::urlAddParams(
	$_POST['EVENT_PAGE'],
	('LIST' == $arResult['FORM_TYPE'] ? array() :
	array('CRM_'.trim($arParams['ENTITY_TYPE']).'_'.trim($arParams['FORM_TYPE']).'_active_tab' => (!empty($arResult['RESULT_TAB']) ? $arResult['RESULT_TAB'] : 'tab_activity')))
);

$this->IncludeComponentTemplate();

?>