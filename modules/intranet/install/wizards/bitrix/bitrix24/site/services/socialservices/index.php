<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(\Bitrix\Main\ModuleManager::isModuleInstalled("bitrix24") || !\Bitrix\Main\Loader::includeModule("socialservices"))
	return;

if(\Bitrix\Main\Config\Option::get('socialservices', 'bitrix24net_id', '') === '')
{
	$request = \Bitrix\Main\Context::getCurrent()->getRequest();
	$host = ($request->isHttps() ? 'https://' : 'http://').$request->getHttpHost();

	$registerResult = \CSocServBitrix24Net::registerSite($host);

	if(is_array($registerResult) && isset($registerResult["client_id"]) && isset($registerResult["client_secret"]))
	{
		\Bitrix\Main\Config\Option::set('socialservices', 'bitrix24net_domain', $host);
		\Bitrix\Main\Config\Option::set('socialservices', 'bitrix24net_id', $registerResult["client_id"]);
		\Bitrix\Main\Config\Option::set('socialservices', 'bitrix24net_secret', $registerResult["client_secret"]);
	}
}

if (\Bitrix\Main\Config\Option::get('socialservices', 'bitrix24net_id', '') !== '')
{
	COption::SetOptionString("socialservices", "auth_services".$suffix, serialize(array('Bitrix24Net' => 'Y')));
}
?>