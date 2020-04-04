<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
global $DB, $APPLICATION, $USER;
if(!function_exists('__CrmCheckListEndResponse'))
{
	function __CrmCheckListEndResponse($result)
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
if (!CModule::IncludeModule('sale'))
{
	return;
}
$userPerms = CCrmPerms::GetCurrentUserPermissions();
if(!CCrmPerms::IsAuthorized())
{
	return;
}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if ($action === 'GET_ROW_COUNT')
{
	$result = '';

	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

	if(!CCrmPerms::IsAccessEnabled($userPerms))
	{
		__CrmCheckListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();
	$gridID = isset($params['GRID_ID']) ? $params['GRID_ID'] : '';

	if(!($gridID !== ''
		&& isset($_SESSION['CRM_GRID_DATA'])
		&& isset($_SESSION['CRM_GRID_DATA'][$gridID])
		&& is_array($_SESSION['CRM_GRID_DATA'][$gridID])))
	{
		__CrmCheckListEndResponse(array('DATA' => array('TEXT' => '')));
	}

	$gridData = $_SESSION['CRM_GRID_DATA'][$gridID];

	$filter = isset($gridData['FILTER']) && is_array($gridData['FILTER']) ? $gridData['FILTER'] : array();

	CBitrixComponent::includeComponentClass("bitrix:crm.order.check.list");
	$checkObj = new CCrmOrderCheckListComponent();
	$result = $checkObj->getCount($filter);
	$text = '';
	if(is_numeric($result))
	{
		$text = GetMessage('CRM_CHECK_LIST_ROW_COUNT', array('#ROW_COUNT#' => $result));
		if($text === '')
		{
			$text = $result;
		}
	}
	__CrmCheckListEndResponse(array('DATA' => array('TEXT' => $text)));
}
elseif ($action === 'REFRESH_CHECK')
{
	$result = '';

	\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
	if(!CCrmPerms::IsAccessEnabled($userPerms))
	{
		__CrmCheckListEndResponse(array('ERROR' => 'Access denied.'));
	}

	$id = isset($_REQUEST['ID']) && !is_array($_REQUEST['ID']) ? intval($_REQUEST['ID']) : false;

	// ---------------------------------------------------
	$check = \Bitrix\Sale\Cashbox\CheckManager::getObjectById($id);
	if ($check->getField('STATUS') === 'P')
	{
		$cashbox = \Bitrix\Sale\Cashbox\Manager::getObjectById($check->getField('CASHBOX_ID'));
		if ($cashbox && $cashbox->isCheckable())
		{
			$r = $cashbox->check($check);
			if (!$r->isSuccess())
			{
				$err = implode("\n", $r->getErrorMessages());
				__CrmCheckListEndResponse(array('ERROR'=>$err));
			}
		}
	}

	__CrmCheckListEndResponse(array('SUCCESS' => true));
}