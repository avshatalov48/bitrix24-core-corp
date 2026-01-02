<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\UI\Toolbar\Facade\Toolbar;

$siteId = '';
if(isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}

global $APPLICATION;

if($siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$request = Application::getInstance()->getContext()->getRequest();

if (($showChecks = $request->get('show_checks')) && $showChecks == 'y')
{
	$pageParams = [
		'lang' => LANGUAGE_ID,
		'publicSidePanel' => 'Y'
	];
	if($request->get('current_date') == 'y')
	{
		$pageParams['apply_filter'] = 'y';
		$pageParams['DATE_CREATE_datesel'] = 'CURRENT_DAY';
		$pageParams = http_build_query($pageParams).'&DATE_CREATE_from';
	}
	else
	{
		$pageParams = http_build_query($pageParams);
	}

	$APPLICATION->IncludeComponent(
		'bitrix:salescenter.page.include',
		'',
		array(
			'PAGE_PATH' => '/shop/settings/sale_cashbox_check.php',
			'PAGE_PARAMS' => $pageParams,
			'SEF_FOLDER' => '/shop/settings/',
			'INTERNAL_PAGE' => 'Y',
		),
		false
	);
}
elseif (($showChecksCorrection = $request->get('show_checks_correction')) && $showChecksCorrection == 'y')
{
	$pageParams = [
		'lang' => LANGUAGE_ID,
		'publicSidePanel' => 'Y'
	];

	$APPLICATION->IncludeComponent(
		'bitrix:salescenter.page.include',
		'',
		array(
			'PAGE_PATH' => '/shop/settings/sale_cashbox_correction.php',
			'PAGE_PARAMS' => http_build_query($pageParams),
			'SEF_FOLDER' => '/shop/settings/',
			'INTERNAL_PAGE' => 'Y',
		),
		false
	);
}
else
{
	if (Loader::includeModule('ui'))
	{
		Toolbar::deleteFavoriteStar();
	}

	if ($request->get('handler') === 'offline')
	{
		$isPlainView = false;
		$isUsePadding = false;
		$useUiToolbar = 'Y';
	}
	else
	{
		$isPlainView = true;
		$isUsePadding = true;
		$useUiToolbar = 'N';
	}

	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:salescenter.cashbox',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'id' => $request->get('id'),
				'handler' => $request->get('handler'),
				'kkm-id' => $request->get('kkm-id'),
				'page' => $request->get('page'),
				'isFrame' => $request->get('IFRAME') === 'Y',
				'preview' => $request->get('preview') === 'y',
				'restHandler' => $request->get('restHandler'),
			],
			'PLAIN_VIEW' => $isPlainView,
			'USE_PADDING' => $isUsePadding,
			'USE_UI_TOOLBAR' => $useUiToolbar,
		],
	);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
