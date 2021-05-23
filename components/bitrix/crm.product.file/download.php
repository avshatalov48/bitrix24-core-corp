<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

$authToken = isset($_REQUEST['auth']) ? $_REQUEST['auth'] : '';
if($authToken !== '')
{
	define('NOT_CHECK_PERMISSIONS', true);
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

$errors = array();
if(CModule::IncludeModule('crm'))
{
	$options = array();
	if($authToken !== '')
	{
		$options['oauth_token'] = $authToken;
	}

	$catalogID = CCrmCatalog::GetDefaultID();
	$productID = isset($_REQUEST['productId']) ? intval($_REQUEST['productId']) : 0;
	$fieldName = isset($_REQUEST['fieldName']) ? strval($_REQUEST['fieldName']) : '';
	$fileID = isset($_REQUEST['fileId']) ? intval($_REQUEST['fileId']) : 0;

	/** @global CMain $APPLICATION */
	global $APPLICATION;

	$result = $APPLICATION->IncludeComponent(
		'bitrix:crm.product.file',
		'.default',
		array(
			'CATALOG_ID' => $catalogID,
			'PRODUCT_ID' => $productID,
			'FIELD_ID' => $fieldName,
			'FILE_ID' => $fileID,
			'OPTIONS' => $options,
			'HIDE_ERRORS' => 'Y'
		),
		$component
	);
}
require($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/include/prolog_after.php');
if(is_array($result['ERRORS']) && !empty($result['ERRORS']))
{
	foreach($result['ERRORS'] as $error)
	{
		echo $error;
	}
}
require($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/include/epilog.php');
