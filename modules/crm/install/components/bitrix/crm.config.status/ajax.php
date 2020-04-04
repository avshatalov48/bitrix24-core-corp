<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION;
if(!function_exists('__CrmConfigStatusEndResponse'))
{
	function __CrmConfigStatusEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
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
	__CrmConfigStatusEndResponse(array('ERROR' => 'Could not include crm module.'));
}

$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	__CrmConfigStatusEndResponse(array('ERROR' => 'Access denied.'));
}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if ($action === 'FIX_STATUSES')
{
	if(COption::GetOptionString('crm', '~CRM_FIX_STATUSES', 'N') !== 'Y')
	{
		__CrmConfigStatusEndResponse(array('COMPLETED' => 'Y'));
	}

	$dbRes = $DB->Query(
		"SELECT ENTITY_ID, STATUS_ID FROM b_crm_status WHERE SYSTEM = 'N' GROUP BY ENTITY_ID, STATUS_ID HAVING COUNT(*) > 1",
		false,
		'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
	);

	$items = array();
	while($arRes = $dbRes->Fetch())
	{
		$items[] = $arRes;
	}

	foreach($items as $item)
	{
		$entityID = isset($item['ENTITY_ID']) ? $item['ENTITY_ID'] : '';
		$statusID = isset($item['STATUS_ID']) ? (int)$item['STATUS_ID'] : 0;

		if($entityID === '' || $statusID <= 0)
		{
			continue;
		}

		$dbRes = $DB->Query(
			"SELECT ID, SORT, NAME, SYSTEM FROM b_crm_status WHERE ENTITY_ID = '{$entityID}' AND STATUS_ID = '{$statusID}'",
			false,
			'FILE: '.__FILE__.'<br /> LINE: '.__LINE__
		);

		$entity = new CCrmStatus($entityID);
		$isFirst = true;
		while($arRes = $dbRes->Fetch())
		{
			if($isFirst)
			{
				$isFirst = false;
				continue;
			}

			$itemID = (int)$arRes['ID'];
			$error = $entity->Update(
				$itemID,
				array(
					'STATUS_ID' => $entity->GetNextStatusId(),
					'SORT' => isset($arRes['SORT']) ? (int)$arRes['SORT'] : 10,
					'SYSTEM' => isset($arRes['SYSTEM']) ? $arRes['SYSTEM'] : 'N',
					'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : ''
				),
				array('ENABLE_STATUS_ID' => true)
			);
		}
	}
	COption::RemoveOption('crm', '~CRM_FIX_STATUSES');
	__CrmConfigStatusEndResponse(array('COMPLETED' => 'Y'));
}
elseif($action === 'GET_COLOR')
{
	$color = $_REQUEST['COLOR'];
	$r = ord(pack("H*", substr($color, 1, 2)));
	$g = ord(pack("H*", substr($color, 3, 2)));
	$b = ord(pack("H*", substr($color, 5, 2)));
	$y = 0.21 * $r + 0.72 * $g + 0.07 * $b;

	if ($y < 145)
	{
		$iconClass = 'light-icon';
		$blockClass = 'transaction-stage-phase-dark';
		$colorValue =  '#FFFFFF';
	}
	else
	{
		$iconClass = 'dark-icon';
		$blockClass = '';
		$colorValue = '#545C69';
	}

	__CrmConfigStatusEndResponse(
		array('COMPLETED' => 'Y', 'ICON_CLASS' => $iconClass, 'COLOR' => $colorValue, 'BLOCK_CLASS' => $blockClass)
	);
}
?>