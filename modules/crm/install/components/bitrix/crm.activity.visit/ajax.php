<?php
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);

$siteId = '';
if (isset($_REQUEST['site_id']) && is_string($_REQUEST['site_id']))
	$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site_id']), 0, 2);

if (!$siteId)
	define('SITE_ID', $siteId);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/**
 * @global CUser $USER
 */

if(!CModule::IncludeModule('crm'))
	die();

global $DB, $APPLICATION;

$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	die();
}

CUtil::JSPostUnescape();

$action = !empty($_REQUEST['ajax_action']) ? $_REQUEST['ajax_action'] : null;

if (empty($action))
	die('Unknown action!');

$APPLICATION->ShowAjaxHead();
$action = strtoupper($action);

switch ($action)
{
	case 'EDIT':
		$APPLICATION->IncludeComponent('bitrix:crm.activity.visit',
			'.default',
			array(
				'ACTION' => 'EDIT',
				'ELEMENT_ID' => isset($_REQUEST['ID'])? (int)$_REQUEST['ID'] : 0,
				'ENTITY_TYPE' => $_REQUEST['entityType'],
				'ENTITY_ID' => (int)$_REQUEST['entityId'],
				'HAS_RECOGNIZE_CONSENT' => ($_REQUEST['HAS_RECOGNIZE_CONSENT'] === 'Y')
			)
		);
		break;
	case 'SAVE':
		CBitrixComponent::includeComponentClass('bitrix:crm.activity.visit');
		$result = CrmActivityVisitComponent::saveActivity($_POST, $curUser->getID(), SITE_ID);
		sendResponse($result);
		break;
	case 'GET_CARD':
		$APPLICATION->IncludeComponent('bitrix:crm.card.show',
			'',
			array(
				'ENTITY_TYPE' => $_REQUEST['ENTITY_TYPE'],
				'ENTITY_ID' => (int)$_REQUEST['ENTITY_ID'],
			)
		);
		break;
	case 'RECOGNIZE':
		CBitrixComponent::includeComponentClass('bitrix:crm.activity.visit');
		$result = CrmActivityVisitComponent::recognizeFace($_POST, $curUser->getID());
		sendResponse($result);
		break;
	case 'SEARCH_SOCIAL':
		$APPLICATION->IncludeComponent('bitrix:crm.activity.visit',
			'.default',
			array(
				'ACTION' => 'SOCIAL',
			)
		);
		break;
	case 'LOAD_SELECTOR':
		$APPLICATION->IncludeComponent(
			'bitrix:crm.entity.selector.ajax',
			'.default',
			array(
				'MULTIPLE' => 'N',
				'ENTITY_TYPE' => array('CONTACT', 'COMPANY', 'DEAL'),
				'NAME' => 'visitCrmSelector',
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
		break;
	case 'SAVE_RECOGNIZE_CONSENT':
		CBitrixComponent::includeComponentClass('bitrix:crm.activity.visit');
		$result = CrmActivityVisitComponent::saveRecognizeConsent($_POST);
		sendResponse($result);
		break;
	default:
		die('Unknown action!');
		break;
}

function sendResponse($data)
{
	if ($data instanceof Bitrix\Main\Result)
	{
		$errors = $data->getErrorMessages();
		$data = $data->getData();
	}

	$result = array('DATA' => $data, 'ERRORS' => $errors);
	$result['SUCCESS'] = count($errors) === 0;
	if(!defined('PUBLIC_AJAX_MODE'))
	{
		define('PUBLIC_AJAX_MODE', true);
	}

	$GLOBALS['APPLICATION']->RestartBuffer();
	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

	echo \Bitrix\Main\Web\Json::encode($result);
	CMain::FinalActions();
	die();
}