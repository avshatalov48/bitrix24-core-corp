<?

use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;
use Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

if (
	!Loader::includeModule('crm')
	|| !Loader::includeModule('catalog')
)
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

			if($entityID <= 0)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_PRODUCT_ID_NOT_DEFINED')));
			}
			if(!CCrmProduct::Exists($entityID))
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_PRODUCT_NOT_FOUND')));
			}
			if (
				!(
					AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
					&& AccessController::getCurrent()->check(ActionDictionary::ACTION_PRODUCT_DELETE)
				)
			)
			{
				__CrmShowEndJsonResonse(array('ERROR' => GetMessage('CRM_PRODUCT_ACCESS_DENIED')));
			}

			if (intval($entityID))
			{
				$obj = new CCrmProduct();
				$res = $obj->Delete($entityID);

				if ($res)
					__CrmShowEndJsonResonse(array('SUCCESS' => "Y"));
				else
					__CrmShowEndJsonResonse(array('ERROR' => CCrmProduct::GetLastError()));
			}
			break;
	}
}
?>