<?
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define('SKIP_TEMPLATE_AUTH_ERROR', true);
define('NOT_CHECK_PERMISSIONS', true);

$siteId = '';
if(isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}
if($siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

global $APPLICATION;
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

$APPLICATION->includeComponent(
	'bitrix:documentgenerator.view', '',
	[
		'ID' => $request->get('id'),
		'HASH' => $request->get('hash'),
	]
);

$hideSign = false;
if(\Bitrix\Main\Loader::includeModule('documentgenerator'))
{
	if(!\Bitrix\DocumentGenerator\Integration\Bitrix24Manager::isRestrictionsActive())
	{
		$hideSign = (\Bitrix\Main\Config\Option::get('documentgenerator', 'document_enable_public_b24_sign', 'Y') != 'Y');
	}
}
else
{
	$hideSign = (\Bitrix\Main\Config\Option::get('documentgenerator', 'document_enable_public_b24_sign', 'Y') != 'Y');
}
define('SKIP_TEMPLATE_B24_SIGN', $hideSign);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");