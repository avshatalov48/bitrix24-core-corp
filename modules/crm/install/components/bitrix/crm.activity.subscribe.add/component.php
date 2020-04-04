<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

if (!CModule::IncludeModule('subscribe'))
	return;

CModule::IncludeModule('fileman');


global $USER, $APPLICATION;

$CCrmPerms = new CCrmPerms($USER->GetID());
if ($CCrmPerms->HavePerm($arParams['ENTITY_TYPE'], BX_CRM_PERM_NONE, 'READ'))
	return;

CUtil::InitJSCore();

if (is_array($arParams['ENTITY_ID']))
	array_walk($arParams['ENTITY_ID'], create_function('&$val', '$val = (int)$val;'));
else if ($arParams['ENTITY_ID'] != 'all')
	$arParams['ENTITY_ID'] = (int)$arParams['ENTITY_ID'];

$arResult['FORM_TYPE'] = strtoupper($arParams['FORM_TYPE']);
$arResult['ENTITY_TYPE'] = strtoupper($arParams['ENTITY_TYPE']);
$arResult['ENTITY_ID'] = $arParams['ENTITY_ID'];
// FORM_ENTITY_TYPE and FORM_ENTITY_ID are identification of entity's context (if ENTITY_TYPE == 'CONTACT' and FORM_ENTITY_TYPE == 'DEAL', then we are working with a conpany in context of some deal)
$arResult['FORM_ENTITY_TYPE'] = isset($arParams['FORM_ENTITY_TYPE']) ? $arParams['FORM_ENTITY_TYPE'] : $arResult['ENTITY_TYPE'];
$arResult['FORM_ENTITY_ID'] = isset($arParams['FORM_ENTITY_ID']) ? $arParams['FORM_ENTITY_ID'] : $arResult['ENTITY_ID'];

$sEmailFrom = COption::GetOptionString('crm', 'email_from');
if(strlen($sEmailFrom) === 0)
{
	// Using current user email
	$userName = $USER->GetFullName();
	$sEmailFrom = strlen($userName) > 0 ? $USER->GetFullName().' <'.$USER->GetEmail().'>' : $USER->GetEmail();
}
$arResult['EMAIL_FROM'] = $sEmailFrom;

$arResult['COMMENTS'] = COption::GetOptionString('crm', 'email_template');

if ($arResult['ENTITY_ID'] == 'all')
{
	$eventPage = isset($_REQUEST['EVENT_PAGE']) ? $_REQUEST['EVENT_PAGE'] : '';
	if($eventPage !== '')
	{
		// HACK: for filter unique id
		$arUrl = parse_url($eventPage);
		$GLOBALS['APPLICATION']->SetCurPage($arUrl['path']);
	}

	$CGridOptions = new CCrmGridOptions('CRM_'.$arResult['ENTITY_TYPE'].'_LIST');
	$arFilter = $CGridOptions->GetFilter(is_array($arResult['FILTER']) ? $arResult['FILTER'] : array());

	// converts data from filter
	if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
	{
		$arFilter[strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
		unset($arFilter['FIND_list'], $arFilter['FIND']);
	}

	// Preparing to filter by entity multi fields
	CCrmEntityHelper::PrepareMultiFieldFilter($arFilter, array(), '=%', false);

	$arImmutableFilters = array('FM', 'ID', 'COMPANY_ID', 'CURRENCY_ID', 'CONTACT_ID', 'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID', 'PRODUCT_ROW_PRODUCT_ID');
	$arFilter2logic = array('TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'ADDRESS', 'COMMENTS', 'BANKING_DETAILS', 'COMPANY_TITLE');
	foreach ($arFilter as $k => $v)
	{
		if(in_array($k, $arImmutableFilters, true))
		{
			continue;
		}

		$arMatch = array();

		if($k === 'ORIGINATOR_ID')
		{
			// HACK: build filter by internal entities
			$arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
			unset($arFilter[$k]);
		}
		elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
		{
			if(strlen($v) > 0)
			{
				$arFilter['>='.$arMatch[1]] = $v;
			}
			unset($arFilter[$k]);
		}
		elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
		{
			if(strlen($v) > 0)
			{
				if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
				{
					$v = CCrmDateTimeHelper::SetMaxDayTime($v);
				}
				$arFilter['<='.$arMatch[1]] = $v;
			}
			unset($arFilter[$k]);
		}
		elseif (in_array($k, $arFilter2logic))
		{
			$v = trim($v);
			if($v !== '')
			{
				$arFilter['?'.$k] = $v;
			}
			unset($arFilter[$k]);
		}
		elseif ($k != 'LOGIC' && strpos($k, 'UF_') !== 0)
		{
			$arFilter['%'.$k] = $v;
			unset($arFilter[$k]);
		}
	}
}
else
{
	$arResult['ENTITY_ID'] = (is_array($arResult['ENTITY_ID']) ? $arResult['ENTITY_ID'] : array($arResult['ENTITY_ID']));
	$arFilter = array('ID' => $arResult['ENTITY_ID']);
}

switch ($arResult['ENTITY_TYPE'])
{
	case 'CONTACT':
	{
		$obRes = CCrmContact::GetListEx(array('ID' => 'ASC'), $arFilter, false, false, array('ID'));
		break;
	}
	case 'COMPANY':
	{
		$obRes = CCrmCompany::GetListEx(array('ID' => 'ASC'), $arFilter, false, false, array('ID'));
		break;
	}
	case 'LEAD':
	default:
	{
		$obRes = CCrmLead::GetListEx(array('ID' => 'ASC'), $arFilter, false, false, array('ID')); $arResult['ENTITY_TYPE'] = 'LEAD';
	}
}

$arID = array();
while ($arRow = $obRes->Fetch())
	$arID[] = $arRow['ID'];

$arFmList = array();
if (!empty($arID))
{
	$arFilter = array(
		'ENTITY_ID' => $arResult['ENTITY_TYPE'],
		'TYPE_ID' => 'EMAIL',
		'ELEMENT_ID' => $arID
	);

	$obRes = CCrmFieldMulti::GetList(array('ID' => 'asc'), $arFilter);
	while($arRow = $obRes->Fetch())
	{
		if ($arRow['VALUE_TYPE'] == 'WORK' || !isset($arFmList[$arRow['ELEMENT_ID']]))
			$arFmList[$arRow['ELEMENT_ID']] = $arRow['VALUE'];
	}
}
$arEntityID = array_keys($arFmList);
$arResult['EMAIL_LIST'] = implode(', ', array_unique(array_values($arFmList)));
if (empty($arResult['EMAIL_LIST']))
	$arResult['ERROR_MESSAGE'] = GetMessage('CRM_SUBSCRIBE_EMPTY_EMAIL');
$arFmUserList = array();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	if (is_int($arResult['ENTITY_ID']))
	{
		if (check_email($_POST['TO']) && in_array($_POST['TO'], $arFmList))
			$arFmUserList[] = $_POST['TO'];
	}
	else
	{
		$_arFmUserList = explode(',', $_POST['TO']);

		$arFmUserList = array();
		foreach ($_arFmUserList as $_sEmail)
		{
			$_sEmail = trim($_sEmail);
			if (check_email($_sEmail))
				$arFmUserList[] = $_sEmail;
		}
		$arFmUserList = array_unique($arFmUserList);
	}

	if (check_email($_POST['FROM']))
		$sEmailFrom = $_POST['FROM'];

	// Try to resolve posting charset -->
	$postingCharset = '';
	$siteCharset = defined('LANG_CHARSET') ? LANG_CHARSET : (defined('SITE_CHARSET') ? SITE_CHARSET : 'windows-1251');
	$arSupportedCharset = explode(',', COption::GetOptionString('subscribe', 'posting_charset'));
	if(count($arSupportedCharset) === 0)
	{
		$postingCharset = $siteCharset;
	}
	else
	{
		foreach($arSupportedCharset as $curCharset)
		{
			if(strcasecmp($curCharset, $siteCharset) === 0)
			{
				$postingCharset = $curCharset;
				break;
			}
		}

		if($postingCharset === '')
		{
			$postingCharset = $arSupportedCharset[0];
		}
	}
	//<-- Try to resolve posting charset

	if (!empty($arFmUserList) && !empty($sEmailFrom))
	{
		$CPosting = new CPosting();
		$arFields = Array(
			'STATUS'	=> 'D',
			'FROM_FIELD'	=> $sEmailFrom,
			'TO_FIELD'	=> $sEmailFrom,
			'BCC_FIELD'	=> implode(',', $arFmUserList),
			'SUBJECT'	=> $_POST['TITLE'],
			'BODY_TYPE'	=> 'html',
			'BODY'		=> $_POST['COMMENTS'],
			'DIRECT_SEND'	=> 'Y',
			'SUBSCR_FORMAT'	=> 'html',
			'CHARSET' => $postingCharset,
		);
		$SID = $CPosting->Add($arFields);

		if (!empty($_FILES['ATTACH']))
		{
			$arAttachs = array();
			foreach($_FILES['ATTACH'] as $type => $ar)
			{
				foreach($ar as $key => $value)
				{
					$arAttachs[$key][$type] = $value;
				}
			}
			foreach ($arAttachs as $k => $arAttach)
			{
				// Fix for issue #29769
				if($arAttach['error'] == 0 && isset($arAttach['tmp_name']) && strlen($arAttach['tmp_name']) > 0 && is_uploaded_file($arAttach['tmp_name']))
				{
					$CPosting->SaveFile($SID, $arAttach);
				}
			}
		}

		if($CPosting->ChangeStatus($SID, 'P'))
		{
			$rsAgents = CAgent::GetList(array('ID'=>'DESC'), array(
				'MODULE_ID' => 'subscribe',
				'NAME' => 'CPosting::AutoSend('.$SID.',%',
			));
			if(!$rsAgents->Fetch())
				CAgent::AddAgent('CPosting::AutoSend('.$SID.',true);', 'subscribe', 'N', 0);
		}

		$arFilter = array();
		$arEntity = array();

		foreach ($arFmUserList as $_sEmail)
			$arFilter[] = array('TYPE_ID' => 'EMAIL', 'VALUE' => $_sEmail);

		$obEntityR = CCrmFieldMulti::GetList(array(), array('ENTITY_ID' => 'LEAD|CONTACT|COMPANY', 'FILTER' => $arFilter), array('ID', 'ENTITY_ID'));
		while($arEntityR = $obEntityR->Fetch()) // key to disable dublicate
			$arEntity[$arEntityR['ELEMENT_ID']] = array('ENTITY_TYPE' => $arEntityR['ENTITY_ID'], 'ENTITY_ID' => (int) $arEntityR['ELEMENT_ID']);

		$CCrmEvent = new CCrmEvent();

		$sBodyEvent  = '';
		$sBodyEvent .= GetMessage('CRM_SUBSCRIBE_SUBJECT').': '.$arFields['SUBJECT']."\n\r";
		$sBodyEvent .= GetMessage('CRM_SUBSCRIBE_FROM').': '.$arFields['FROM_FIELD']."\n\r";
		$sBodyEvent .= GetMessage('CRM_SUBSCRIBE_TO').': '.$arFields['BCC_FIELD']."\n\r\n\r";
		$sBodyEvent .= $_POST['COMMENTS'];

		if (!empty($arEntity))
		{
			$CCrmEvent->Add(array(
				'ENTITY' => $arEntity,
				'EVENT_ID' => 'MESSAGE',
				'EVENT_TEXT_1' => $sBodyEvent,
				'FILES' => !empty($_FILES['ATTACH']) ? $_FILES['ATTACH'] : array()
				)
			);
		}

		// Try add event to entity of context
		if($arResult['FORM_ENTITY_TYPE'] !== $arResult['ENTITY_TYPE']
			&& $arResult['FORM_ENTITY_ID'] !== $arResult['ENTITY_ID'])
		{
			$CCrmEvent->Add(array(
				'ENTITY' => array(
				$arResult['FORM_ENTITY_ID'] => array(
					'ENTITY_TYPE' => $arResult['FORM_ENTITY_TYPE'],
					'ENTITY_ID' => $arResult['FORM_ENTITY_ID']
					)
				),
				'EVENT_ID' => 'MESSAGE',
				'EVENT_TEXT_1' => $sBodyEvent,
				'FILES' => !empty($_FILES['ATTACH']) ? $_FILES['ATTACH'] : array()
				)
			);
		}
	}

	$arResult['EVENT_PAGE'] = CHTTP::urlAddParams(
		$_POST['EVENT_PAGE'],
		array('CRM_'.trim($arResult['FORM_ENTITY_TYPE']).'_'.trim($arParams['FORM_TYPE']).'_active_tab' => (!empty($_REQUEST['TAB_ID']) ? $_REQUEST['TAB_ID'] : 'tab_event'))
	);
}

// check if only one is selected form the entity of a choice on which mailbox to send
$arResult['EMAIL'] = array();
if (count($arResult['ENTITY_ID']) == 1 && $arResult['ENTITY_ID'][0] > 0)
{
	$arFilter = array(
		'ENTITY_ID' => $arResult['ENTITY_TYPE'],
		'TYPE_ID' => 'EMAIL',
		'ELEMENT_ID' => $arResult['ENTITY_ID']
	);

	$obRes = CCrmFieldMulti::GetList(array('COMPLEX_ID' => 'desc', 'ID' => 'asc'), $arFilter);
	while($arRow = $obRes->Fetch())
	{
		$arResult['EMAIL']['REFERENCE'][] = CCrmFieldMulti::GetEntityNameByComplex($arRow['COMPLEX_ID']).': '.$arRow['VALUE'];
		$arResult['EMAIL']['REFERENCE_ID'][] = $arRow['VALUE'];
	}

	if (isset($arResult['EMAIL']['REFERENCE']))
	{
		if (count($arResult['EMAIL']['REFERENCE']) == 0)
			$arResult['ERROR_MESSAGE'] = GetMessage('CRM_SUBSCRIBE_EMPTY_EMAIL');
	}
	else
		$arResult['ERROR_MESSAGE'] = GetMessage('CRM_SUBSCRIBE_EMPTY_EMAIL');

}
$this->IncludeComponentTemplate();
?>