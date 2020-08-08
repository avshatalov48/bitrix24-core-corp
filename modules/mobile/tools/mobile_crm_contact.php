<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule('crm'))
{
	return;
}

$currentUserPermissions = CCrmPerms::GetCurrentUserPermissions();

if(!function_exists('__CrmShowEndJsonResonse'))
{
	function __CrmShowEndJsonResonse($result)
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

if($_SERVER["REQUEST_METHOD"]=="POST" && $_POST["action"] <> '' && check_bitrix_sessid())
{
	$action = $_POST["action"];

	switch ($action)
	{
		case "delete":
			$entityID = $_POST["itemId"];
			$entityID = intval($entityID);

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_CONTACT_ID_NOT_DEFINED')));
			}
			if(!CCrmContact::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_CONTACT_NOT_FOUND')));
			}
			if(!CCrmContact::CheckDeletePermission($entityID, $currentUserPermissions))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_CONTACT_ACCESS_DENIED')));
			}

			if (intval($entityID))
			{
				$arEntityAttr = $entityID > 0
					? $currentUserPermissions->GetEntityAttr('CONTACT', array($entityID))
					: array();

				$CCrmBizProc = new CCrmBizProc('CONTACT');
				if (!CCrmAuthorizationHelper::CheckDeletePermission(CCrmOwnerType::ContactName, $entityID, $currentUserPermissions, $arEntityAttr))
				{
					__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_CONTACT_ACCESS_DENIED')));
				}
				elseif (!$CCrmBizProc->Delete($entityID, $arEntityAttr))
				{
					__CrmShowEndJsonResonse(array('ERROR' => $CCrmBizProc->LAST_ERROR));
				}

				$obj = new CCrmContact();
				$res = $obj->Delete($entityID, array('PROCESS_BIZPROC' => false));

				if ($res)
					__CrmShowEndJsonResonse(array('SUCCESS' => "Y"));
				else
					__CrmShowEndJsonResonse(array('ERROR' => GetMessage("CRM_CONTACT_DELETE_ERROR")));
			}
			break;
	}
}