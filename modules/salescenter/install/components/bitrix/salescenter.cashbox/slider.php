<?php

$siteId = '';
if(isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}

if($siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();

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
else
{
	$APPLICATION->IncludeComponent(
		'bitrix:ui.sidepanel.wrapper',
		'',
		[
			'POPUP_COMPONENT_NAME' => 'bitrix:salescenter.cashbox',
			'POPUP_COMPONENT_TEMPLATE_NAME' => '',
			'POPUP_COMPONENT_PARAMS' => [
				'id' => $request->get('id'),
				'handler' => $request->get('handler'),
				'page' => $request->get('page'),
				'isFrame' => $request->get('IFRAME') === 'Y',
				'preview' => $request->get('preview') === 'y',
			],
			'PLAIN_VIEW' => $request->get('preview') === 'y',
		]
	);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');