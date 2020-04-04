<?
define("IM_AJAX_INIT", true);
define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck", true);
define("NO_AGENT_CHECK", true);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/component.php');

class CVNSetupAjax
{

	public static function execute()
	{
		global $USER;

		$result = array();
		$error  = false;

		if (!CModule::IncludeModule('voximplant'))
			$error = 'Module voximplant is not installed.';
		else if (!is_object($USER) || !$USER->IsAuthorized())
			$error = GetMessage('ACCESS_DENIED');
		else if (!check_bitrix_sessid())
			$error = GetMessage('ACCESS_DENIED');

		$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_SETTINGS,\Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY))
			$error = GetMessage('ACCESS_DENIED');

		if (!$error && $_REQUEST["act"] == "option")
			$result = self::executeSaveOption($error);

		self::returnJson(array_merge(array(
			'result' => $error === false ? 'ok' : 'error',
			'error'  => CharsetConverter::ConvertCharset($error, SITE_CHARSET, 'UTF-8')
		), $result));
	}

	private static function executeSaveOption(&$error)
	{
		$error = !CVoxImplantConfig::SetPortalNumber($_REQUEST["portalNumber"]);
		return array();
	}

	private static function returnJson($data)
	{
		global $APPLICATION;

		$APPLICATION->RestartBuffer();

		header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
		echo json_encode($data);
		CMain::FinalActions();
		die();
	}
}

CVNSetupAjax::execute();

CMain::FinalActions();
die();