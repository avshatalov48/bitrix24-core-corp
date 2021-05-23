<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (!CModule::IncludeModule('crm') || !CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	die();
}

$action = isset($_REQUEST['ACTION']) ? $_REQUEST['ACTION'] : '';
if($action === '')
{
	die();
}

global $APPLICATION;

if($action === 'INDEX')
{
	$entityTypeName = isset($_REQUEST['ENTITY_TYPE_NAME']) ? $_REQUEST['ENTITY_TYPE_NAME'] : '';
	if($entityTypeName !== CCrmOwnerType::CompanyName)
	{
		die();
	}

	$entityID = isset($_REQUEST['ENTITY_ID']) ? intval($_REQUEST['ENTITY_ID']) : 0;
	if($entityID <= 0)
	{
		die();
	}

	$params = isset($_REQUEST['PARAMS']) && is_array($_REQUEST['PARAMS']) ? $_REQUEST['PARAMS'] : array();

	$formID = isset($params['FORM_ID']) ? $params['FORM_ID'] : '';
	$tabKey = $formID !== '' ? "{$formID}_active_tab" : 'active_tab';

	$pathToShow = isset($params['PATH_TO_ENTITY_SHOW']) ? $params['PATH_TO_ENTITY_SHOW'] : '';
	if($pathToShow === '')
	{
		$pathToShow = COption::GetOptionString('crm', 'path_to_company_show');
	}
	$showUrl = CComponentEngine::MakePathFromTemplate(
		$pathToShow,
		array('company_id' => $entityID)
	);

	Header('Content-Type: text/html; charset='.LANG_CHARSET);
	$APPLICATION->ShowAjaxHead();
	$APPLICATION->IncludeComponent('bitrix:bizproc.document',
		'',
		array(
			'MODULE_ID' => 'crm',
			'ENTITY' => 'CCrmDocumentCompany',
			'DOCUMENT_TYPE' => 'COMPANY',
			'DOCUMENT_ID' => "COMPANY_{$entityID}",
			'TASK_EDIT_URL' => CHTTP::urlAddParams($showUrl, array('bizproc_task' => '#ID#', $tabKey => 'tab_bizproc')),
			'WORKFLOW_LOG_URL' => CHTTP::urlAddParams($showUrl, array('bizproc_log' => '#ID#', $tabKey => 'tab_bizproc')),
			'WORKFLOW_START_URL' => CHTTP::urlAddParams($showUrl, array('bizproc_start' => 1, $tabKey => 'tab_bizproc')),
			'POST_FORM_URI' => isset($_REQUEST['post_form_uri']) ? CHTTP::urlAddParams($_REQUEST['post_form_uri'], array($tabKey => 'tab_bizproc')) : '',
			'back_url' => CHTTP::urlAddParams($showUrl, array($tabKey => 'tab_bizproc')),
			'SET_TITLE' => 'Y'
		),
		'',
		array('HIDE_ICONS' => 'Y')
	);

	require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
	die();
}