<?php

use Bitrix\HumanResources\Service\Container;

$siteId = '';
if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}

if ($siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

\Bitrix\Main\Loader::includeModule('crm');
\Bitrix\Main\Loader::includeModule('humanresources');
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$company = Container::getHcmLinkCompanyRepository()->getById((int)$request->get('entity_id'));

if ($company === null)
{
	ShowError('No company found');
	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_after.php');
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog.php");
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_after.php');


global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:humanresources.hcmlink.mapped.users',
		'POPUP_COMPONENT_PARAMS' => [
			'COMPANY' => $company,
		],
		'USE_PADDING' => true,
		'USE_UI_TOOLBAR' => 'Y',
		'PAGE_MODE' => true,
		'PAGE_MODE_OFF_BACK_URL' => '/stream/',//todo change path
	],
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');