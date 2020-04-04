<?php

//define("NOT_CHECK_PERMISSIONS", true);
//define("STOP_STATISTICS", true);
//define("NO_KEEP_STATISTIC", "Y");
//define("NO_AGENT_STATISTIC","Y");
//define("DisableEventsCheck", true);

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

$templateName = '';
if($request['UPLOAD'] == 'Y')
{
	$templateName = 'upload';
}

$uploadUri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
$uploadUri->addParams(['UPLOAD' => 'Y']);

$APPLICATION->includeComponent(
	'bitrix:documentgenerator.templates', $templateName,
	[
		'UPLOAD_URI' => $uploadUri,
		'ID' => $request->get('ID'),
		'MODULE' => $request->get('MODULE'),
		'PROVIDER' => $request->get('PROVIDER'),
	]
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');