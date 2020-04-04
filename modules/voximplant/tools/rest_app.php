<?php

define("PUBLIC_AJAX_MODE", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!\Bitrix\Main\Loader::includeModule('voximplant'))
	return false;

if(!check_bitrix_sessid())
	return false;

if(!\Bitrix\Main\Loader::includeModule('rest'))
	return false;

$appId = (int)$_REQUEST['REST_APP_ID'];
/*$callId = (string)$_REQUEST['CALL_ID'];
$callListId = (int)$_REQUEST['CALL_LIST_ID'];

$appOptions = array();
$call = \Bitrix\Voximplant\CallTable::getByCallId($callId);

if(is_array($call))
{
	$appOptions['CALL_ID'] = $call['CALL_ID'];
	$appOptions['PHONE_NUMBER'] = $call['CALLER_ID'];
	$appOptions['CRM_ENTITY_TYPE'] = $call['CRM_ENTITY_TYPE'];
	$appOptions['CRM_ENTITY_ID'] = $call['CRM_ENTITY_ID'];
	$appOptions['CRM_ACTIVITY_ID'] = $call['CRM_ACTIVITY_ID'];
}

$appOptions['CALL_LIST_MODE'] = $callListId > 0;*/


$APPLICATION->ShowAjaxHead();
$APPLICATION->IncludeComponent(
	'bitrix:app.placement',
	'',
	array(
		'PLACEMENT' => "CALL_CARD",
		"PLACEMENT_OPTIONS" => $_REQUEST['PLACEMENT_OPTIONS'],
		'PARAM' => array(
			'FRAME_HEIGHT' => '100%',
			/* placement layout parameters, optional. width=100%, height=600px by default
			'FRAME_HEIGHT' => '200px',
			'FRAME_WIDTH' => '100%',*/
		),

		/* optional - js event for placement js interface configuration */
		'INTERFACE_EVENT' => 'onPlacementMessageInterfaceInit',
		'SAVE_LAST_APP' => 'N',
		'PLACEMENT_APP' => $appId
	),
	null,
	array('HIDE_ICONS' => 'Y')
);