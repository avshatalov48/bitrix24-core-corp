<?php

use Bitrix\Sign\Service\Container;
use Bitrix\Sign\Type\Document\EntityType;

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

\Bitrix\Main\Loader::includeModule('sign');
\Bitrix\Main\Loader::includeModule('crm');
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$document = Container::instance()->getDocumentRepository()->getByEntityIdAndType(
	(int)$request->get('entity_id'),
	EntityType::SMART_B2E
);

if (Bitrix\Main\Loader::includeModule('pull'))
{
	\CPullWatch::Add(
		\Bitrix\Main\Engine\CurrentUser::get()->getId(),
		\Bitrix\Sign\Callback\Handler::FILTER_COUNTER_TAG);
}

if ($document === null)
{
	ShowError('No document found');
	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_after.php');
	require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog.php");
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_after.php');

$APPLICATION->IncludeComponent(
	'bitrix:ui.sidepanel.wrapper',
	'',
	[
		'POPUP_COMPONENT_NAME' => 'bitrix:sign.document.list',
		'POPUP_COMPONENT_PARAMS' => [
			'COMPONENT_TYPE' => $request->get('type'),
			'ENTITY_ID' => $document->id,
		],
		'USE_PADDING' => false,
		'USE_UI_TOOLBAR' => 'Y',
		'PAGE_MODE' => false,
		'PAGE_MODE_OFF_BACK_URL' => '/sign/b2e/',
	],
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');