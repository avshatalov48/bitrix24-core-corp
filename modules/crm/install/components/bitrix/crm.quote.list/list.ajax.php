<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('DisableEventsCheck', true);

//AGENTS ARE REQUIRED FOR REBUILD SEARCH INDEX
define('NO_AGENT_CHECK', (!isset($_REQUEST['ACTION']) || $_REQUEST['ACTION'] !== 'REBUILD_SEARCH_CONTENT'));

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmQuoteListEndResponse'))
{
	function __CrmQuoteListEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

if (!CModule::IncludeModule('crm'))
{
	return;
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	return;
}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if (isset($_REQUEST['MODE']) && $_REQUEST['MODE'] === 'SEARCH')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmQuote::CheckReadPermission(0, $userPerms))
	{
		__CrmQuoteListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$APPLICATION->RestartBuffer();

	// Limit count of items to be found
	$nPageTop = 50;		// 50 items by default
	if (isset($_REQUEST['LIMIT_COUNT']) && ($_REQUEST['LIMIT_COUNT'] >= 0))
	{
		$rawNPageTop = (int) $_REQUEST['LIMIT_COUNT'];
		if ($rawNPageTop === 0)
			$nPageTop = false;		// don't limit
		elseif ($rawNPageTop > 0)
			$nPageTop = $rawNPageTop;
	}

	$search = trim($_REQUEST['VALUE']);
	$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y'? true: false;
	$arFilter = array();
	if (is_numeric($search))
	{
		$arFilter['ID'] = (int) $search;
		$arFilter['%QUOTE_NUMBER'] = $search;
		$arFilter['%TITLE'] = $search;
		$arFilter['LOGIC'] = 'OR';
	}
	else if (preg_match('/(.*)\[(\d+?)\]/iu', $search, $arMatches))
	{
		$arFilter['ID'] = (int) $arMatches[2];
		$searchString = trim($arMatches[1]);
		if (is_string($searchString) && $searchString !== '')
		{
			$arFilter['%TITLE'] = $searchString;
			$arFilter['LOGIC'] = 'OR';
		}
		unset($searchString);
	}
	else
	{
		$arFilter['%QUOTE_NUMBER'] = $search;
		$arFilter['%TITLE'] = $search;
		$arFilter['LOGIC'] = 'OR';
	}

	$arQuoteStatusList = CCrmStatus::GetStatusListEx('QUOTE_STATUS');
	$arSelect = array('ID', 'QUOTE_NUMBER', 'TITLE', 'STATUS_ID', 'COMPANY_TITLE', 'CONTACT_FULL_NAME');
	$arOrder = array('TITLE' => 'ASC');
	$arData = array();
	$obRes = CCrmQuote::GetList(
		$arOrder,
		$arFilter,
		false,
		($nPageTop === false) ? false : array('nTopCount' => intval($nPageTop)),
		$arSelect
	);
	$arFiles = array();
	while ($arRes = $obRes->Fetch())
	{
		$clientTitle = (!empty($arRes['COMPANY_TITLE'])) ? $arRes['COMPANY_TITLE'] : '';
		$clientTitle .= (($clientTitle !== '' && !empty($arRes['CONTACT_FULL_NAME'])) ? ', ' : '').$arRes['CONTACT_FULL_NAME'];

		$quoteTitle = empty($arRes['TITLE']) ? $arRes['QUOTE_NUMBER'] : $arRes['QUOTE_NUMBER'].' - '.$arRes['TITLE'];

		$arData[] =
			array(
				'id' => $multi? CCrmQuote::OWNER_TYPE.'_'.$arRes['ID']: $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_quote_show'),
					array(
						'quote_id' => $arRes['ID']
					)
				),
				'title' => empty($quoteTitle) ? '' : str_replace(array(';', ','), ' ', $quoteTitle),
				'desc' => $clientTitle,
				'type' => 'quote'
			);
	}

	__CrmQuoteListEndResponse($arData);
}
elseif ($action === 'REBUILD_SEARCH_CONTENT')
{
	$agent = \Bitrix\Crm\Agent\Search\QuoteSearchContentRebuildAgent::getInstance();
	if($agent->isEnabled() && !$agent->isActive())
	{
		$agent->enable(false);
	}
	if(!$agent->isEnabled())
	{
		__CrmQuoteListEndResponse(array('STATUS' => 'COMPLETED'));
	}

	$progressData = $agent->getProgressData();
	__CrmQuoteListEndResponse(
		array(
			'STATUS' => 'PROGRESS',
			'PROCESSED_ITEMS' => $progressData['PROCESSED_ITEMS'],
			'TOTAL_ITEMS' => $progressData['TOTAL_ITEMS'],
		)
	);
}
elseif ($action === 'SAVE_PROGRESS' && check_bitrix_sessid())
{
	$ID = isset($_REQUEST['ID']) ? intval($_REQUEST['ID']) : 0;
	$typeName = isset($_REQUEST['TYPE']) ? $_REQUEST['TYPE'] : '';
	$statusID = isset($_REQUEST['VALUE']) ? $_REQUEST['VALUE'] : '';

	$targetTypeName = CCrmOwnerType::ResolveName(CCrmOwnerType::Quote);
	if($statusID === '' || $ID <= 0  || $typeName !== $targetTypeName)
	{
		__CrmQuoteListEndResponse(array('ERROR' => 'Invalid data!'));
	}

	$entityAttrs = $userPerms->GetEntityAttr($targetTypeName, array($ID));
	if (!$userPerms->CheckEnityAccess($targetTypeName, 'WRITE', $entityAttrs[$ID]))
	{
		__CrmQuoteListEndResponse(array('ERROR' => 'Access denied!'));
	}

	if(!CCrmQuote::Exists($ID))
	{
		__CrmQuoteListEndResponse(array('ERROR' => 'Not found!'));
	}

	$arFields = array('STATUS_ID' => $statusID);
	$CCrmQuote = new CCrmQuote(false);
	$result = $CCrmQuote->Update($ID, $arFields, true, true);

	$response = [
		'TYPE' => $targetTypeName,
		'ID' => $ID,
		'VALUE' => $statusID
	];

	if (!$result)
	{

		$response['ERROR'] = $CCrmQuote->LAST_ERROR;

		$lastErrors = $CCrmQuote->getLastErrors();
		if ($lastErrors !== null)
		{
			foreach ($lastErrors as $error)
			{
				if (
					$error->getCode() === \Bitrix\Crm\Field::ERROR_CODE_REQUIRED_FIELD_ATTRIBUTE
					&& isset($error->getCustomData()['fieldName'])
				)
				{
					$response['CHECK_ERRORS'][$error->getCustomData()['fieldName']] = $error->getMessage();
				}
			}
		}
	}

	__CrmQuoteListEndResponse($response);
}
elseif ($action === 'GET_ROW_COUNT')
{
	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmQuote::CheckReadPermission(0, $userPerms))
	{
		__CrmQuoteListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';
	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmQuoteListEndResponse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];
	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();
	$result = CCrmQuote::GetList(array(), $filter, array(), false, array(), array());

	$text = '';
	if(is_numeric($result))
	{
		$text = GetMessage('CRM_QUOTE_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
		if($text === '')
		{
			$text = $result;
		}
	}
	__CrmQuoteListEndResponse(array('DATA' => array('TEXT' => $text)));
}
__CrmQuoteListEndResponse(array());
