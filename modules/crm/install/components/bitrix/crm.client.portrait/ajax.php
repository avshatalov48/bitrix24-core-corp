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

$sendResponse = function($data, array $errors = array(), $plain = false)
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
	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

	if ($plain)
	{
		$result = $result['DATA'];
	}

	echo \Bitrix\Main\Web\Json::encode($result);
	CMain::FinalActions();
	die();
};
$sendError = function($error) use ($sendResponse)
{
	$sendResponse(array(), array($error));
};

switch ($action)
{
	case 'SET_LOAD_TARGET':
		$CrmPerms = new CCrmPerms($curUser->GetID());
		if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
			die('ACCESS DENIED');

		$entityContext = isset($_REQUEST['entity_context']) && is_array($_REQUEST['entity_context']) ? $_REQUEST['entity_context'] : array();
		$entityType = isset($entityContext['entityType']) ? (string)$entityContext['entityType'] : '';

		if ($entityType !== \CCrmOwnerType::CompanyName && $entityType !== \CCrmOwnerType::ContactName)
			die('Incorrect request');

		$optionName = 'portrait_'.strtolower($entityType);
		$optionId = isset($entityContext['dealCategoryId']) ? (int)$entityContext['dealCategoryId'] : 'primary';
		$isManual = (isset($_REQUEST['is_manual']) && $_REQUEST['is_manual'] === 'Y');
		$value = isset($_REQUEST['load_target']) ? (int)$_REQUEST['load_target'] : 0;

		$options = \Bitrix\Main\Config\Option::get('crm', $optionName);
		if ($options !== '')
			$options = unserialize($options);

		if (!is_array($options))
			$options = array();

		if ($isManual)
		{
			$options[$optionId] = $value;
		}
		else
		{
			unset($options[$optionId]);
		}

		if (count($options) === 0)
		{
			\Bitrix\Main\Config\Option::delete('crm', array('name' => $optionName));
		}
		else
		{
			\Bitrix\Main\Config\Option::set('crm', $optionName, serialize($options));
		}

		$sendResponse(array('SUCCESS' => true));
		break;
	default:
		die('Unknown action!');
		break;
}