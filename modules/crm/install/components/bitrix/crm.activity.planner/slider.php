<?php

//define("NOT_CHECK_PERMISSIONS", true);
//define("STOP_STATISTICS", true);
//define("NO_KEEP_STATISTIC", "Y");
//define("NO_AGENT_STATISTIC","Y");
//define("DisableEventsCheck", true);

$siteId = '';
if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
{
	$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);
}

if ($siteId)
{
	define('SITE_ID', $siteId);
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$action = isset($_REQUEST['ajax_action']) && $_REQUEST['ajax_action'] == 'ACTIVITY_VIEW' ? 'VIEW' : 'EDIT';

$APPLICATION->includeComponent(
	'bitrix:crm.activity.planner', '',
	array(
		'ACTION'           => $action,
		'SUBJECT'          => $_REQUEST['SUBJECT'] ?? '',
		'BODY'             => $_REQUEST['BODY'] ?? '',
		'ELEMENT_ID'       => $action && isset($_REQUEST['activity_id']) ? (int) $_REQUEST['activity_id'] : 0,
		'FROM_ACTIVITY_ID' => isset($_REQUEST['FROM_ACTIVITY_ID']) ? (int) $_REQUEST['FROM_ACTIVITY_ID'] : 0,
		'MESSAGE_TYPE'     => isset($_REQUEST['MESSAGE_TYPE']) ? (string) $_REQUEST['MESSAGE_TYPE'] : 0,
		'TYPE_ID'          => isset($_REQUEST['TYPE_ID']) ? (int) $_REQUEST['TYPE_ID'] : 0,
		'PROVIDER_ID'      => isset($_REQUEST['PROVIDER_ID']) ? (string) $_REQUEST['PROVIDER_ID'] : 0,
		'PROVIDER_TYPE_ID' => isset($_REQUEST['PROVIDER_TYPE_ID']) ? (string) $_REQUEST['PROVIDER_TYPE_ID'] : 0,
		'OWNER_ID'         => isset($_REQUEST['OWNER_ID']) ? (int) $_REQUEST['OWNER_ID'] : 0,
		'OWNER_TYPE_ID'    => isset($_REQUEST['OWNER_TYPE_ID']) ? (int) $_REQUEST['OWNER_TYPE_ID'] : 0,
		'OWNER_TYPE'       => isset($_REQUEST['OWNER_TYPE']) ? (string) $_REQUEST['OWNER_TYPE'] : 0,
		'OWNER_PSID'       => isset($_REQUEST['OWNER_PSID']) ? (int) $_REQUEST['OWNER_PSID'] : 0,
		'COMMUNICATIONS'   => isset($_REQUEST['COMMUNICATIONS']) ? (array) $_REQUEST['COMMUNICATIONS'] : 0,
		'PLANNER_ID' 	   => isset($_REQUEST['PLANNER_ID']) ? (string) $_REQUEST['PLANNER_ID'] : 0,
		'ASSOCIATED_ENTITY_ID' => isset($_REQUEST['ASSOCIATED_ENTITY_ID']) ? (string) $_REQUEST['ASSOCIATED_ENTITY_ID'] : 0,
		'STORAGE_TYPE_ID' => isset($_REQUEST['STORAGE_TYPE_ID']) ? (int) $_REQUEST['STORAGE_TYPE_ID'] : 0,
		'STORAGE_ELEMENT_IDS' => isset($_REQUEST['STORAGE_ELEMENT_IDS']) && is_array($_REQUEST['STORAGE_ELEMENT_IDS']) ? $_REQUEST['STORAGE_ELEMENT_IDS'] : [],
	)
);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
