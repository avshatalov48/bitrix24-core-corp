<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}
/*
 * ONLY 'POST' SUPPORTED
 * SUPPORTED MODES:
 * 'SAVE' - add/update category fields
 */
global $APPLICATION;

$user = CCrmSecurityHelper::GetCurrentUser();
if (!$user->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

$userPermissions = CCrmPerms::GetCurrentUserPermissions();

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
if(!function_exists('__CrmDealCategoryEndJsonResonse'))
{
	function __CrmDealCategoryEndJsonResonse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		if(!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		if(!defined('PUBLIC_AJAX_MODE'))
		{
			define('PUBLIC_AJAX_MODE', true);
		}
		require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
		die();
	}
}

CUtil::JSPostUnescape();
$mode = isset($_POST['MODE']) ? $_POST['MODE'] : '';
if($mode === '' && isset($_POST['ACTION']))
{
	$mode = $_POST['ACTION'];
}
if($mode === '')
{
	__CrmDealCategoryEndJsonResonse(array('ERROR' => 'MODE IS NOT DEFINED!'));
}

if($mode === 'SAVE')
{
	if(!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
	{
		__CrmDealCategoryEndJsonResonse(array('ERROR' => 'ACCESS DENIED!'));
	}

	$itemID = isset($_POST['ITEM_ID']) ? (int)$_POST['ITEM_ID'] : 0;
	$fields = isset($_POST['FIELDS']) && is_array($_POST['FIELDS']) ? $_POST['FIELDS'] : array();
	$isDeafult = isset($_POST['IS_DEFAULT']) && strtoupper($_POST['IS_DEFAULT']) === 'Y';
	if(empty($fields))
	{
		__CrmDealCategoryEndJsonResonse(array('ERROR' => 'FIELDS ARE NOT FOUND!'));
	}

	if($isDeafult)
	{
		Bitrix\Crm\Category\DealCategory::setDefaultCategoryName(
			isset($fields['NAME']) ? $fields['NAME'] : ''
		);
	}
	elseif($itemID > 0)
	{
		try
		{
			Bitrix\Crm\Category\DealCategory::update($itemID, $fields);
		}
		catch(Bitrix\Crm\Entry\UpdateException $ex)
		{
			__CrmDealCategoryEndJsonResonse(array('ERROR' => $ex->getLocalizedMessage()));
		}
	}
	else
	{
		try
		{
			$itemID = Bitrix\Crm\Category\DealCategory::add($fields);
		}
		catch(Bitrix\Crm\Entry\AddException $ex)
		{
			__CrmDealCategoryEndJsonResonse(array('ERROR' => $ex->getLocalizedMessage()));
		}
	}

	__CrmDealCategoryEndJsonResonse(array('DATA' => array('ITEM_ID' => $itemID)));
}
