<?php
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);
define('PUBLIC_AJAX_MODE', true);

if (isset($_REQUEST['site']) && is_string($_REQUEST['site']))
{
	$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2);
	if (!$siteId)
	{
		define('SITE_ID', $siteId);
	}
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

/**
 * @global CUser $USER
 */

global $DB, $APPLICATION;

$sendResponse = function($data, array $errors = array())
{
	if ($data instanceof Bitrix\Main\Result)
	{
		$errors = $data->getErrorMessages();
		$data = $data->getData();
	}

	$result = $data;
	$result['errors'] = $errors;
	$result['success'] = count($errors) === 0;

	header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

	echo \Bitrix\Main\Web\Json::encode($result);
	CMain::FinalActions();
	die();
};
$sendError = function($error) use ($sendResponse)
{
	$sendResponse(array(), (array)$error);
};

if (!CModule::IncludeModule('crm'))
{
	$sendError('Module CRM is not installed.');
}

$curUser = CCrmSecurityHelper::GetCurrentUser();
if (!$curUser || !$curUser->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] !== 'POST')
{
	$sendError('Access denied.');
}

CUtil::JSPostUnescape();

$action = !empty($_REQUEST['action']) ? strtoupper($_REQUEST['action']) : null;

if (empty($action))
	$sendError('Unknown action.');

$saleTargetWidget = \Bitrix\Crm\Widget\Custom\SaleTarget::getInstance();

switch ($action)
{
	case 'GET_CONFIGURATION':
		$configurationId = (int)$_POST['configuration_id'];
		if ($configurationId <= 0)
			$sendError('Incorrect configuration ID');

		$data = $saleTargetWidget->getDataFor($curUser->GetID(), $configurationId);
		list($current, $totalCurrent) = \Bitrix\Crm\Widget\Data\DealSaleTarget::getCurrentValues($data['configuration']);
		$data['current'] = $current;
		$data['totalCurrent'] = $totalCurrent;

		$sendResponse($data);
	break;
	case 'GET_CURRENT_CONFIGURATION':
		$data = $saleTargetWidget->getDataFor($curUser->GetID());
		list($current, $totalCurrent) = \Bitrix\Crm\Widget\Data\DealSaleTarget::getCurrentValues($data['configuration']);
		$data['current'] = $current;
		$data['totalCurrent'] = $totalCurrent;

		$sendResponse($data);
	break;
	case 'GET_CONFIGURATIONS':
		//Check permissions.
		if (!$saleTargetWidget->canEdit($curUser->GetID()))
		{
			$sendResponse(array(
				'canEdit' => false,
				'admins' => array_values($saleTargetWidget->getAdmins()),
			), array('Access denied!'));
		}
		$sendResponse(array(
			'configurations' => $saleTargetWidget->getConfigurations(),
			'users' => $saleTargetWidget->getActiveUsers()
		));
	break;
	case 'SAVE_CONFIGURATION':
		//Check permissions.
		if (!$saleTargetWidget->canEdit($curUser->GetID()))
		{
			$sendError('Access denied!');
		}

		$configurationData = $_POST['configuration'];

		$result = $saleTargetWidget->saveConfiguration(
			$configurationData,
			$curUser->GetID()
		);

		if ($result->isSuccess())
		{
			$sendResponse(array('configuration' => $result->getData()));
		}

		$sendError($result->getErrorMessages());
	break;
	case 'SAVE_CONFIGURATIONS':
		//Check permissions.
		if (!$saleTargetWidget->canEdit($curUser->GetID()))
		{
			$sendError('Access denied!');
		}

		$result = $saleTargetWidget->saveConfigurations(
			$_POST['configurations'],
			$curUser->GetID(),
			$_POST['period_type']
		);

		if ($result->isSuccess())
		{
			$sendResponse(array());
		}

		$sendError($result->getErrorMessages());
	break;
	case 'NOTIFY_ADMIN':
		$userId = (int)$_POST['userId'];
		if ($userId && Bitrix\Main\Loader::includeModule('im'))
		{
			$admins = $saleTargetWidget->getAdmins();
			if (isset($admins[$userId]))
			{
				\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
				\CIMNotify::Add(array(
					'TO_USER_ID' => $userId,
					'FROM_USER_ID' => $curUser->GetID(),
					'NOTIFY_TYPE' => IM_NOTIFY_FROM,
					'NOTIFY_MODULE' => 'crm',
					'NOTIFY_TAG' => 'CRM|NOTIFY_ADMIN|'.$userId.'|'.$curUser->GetID(),
					'NOTIFY_MESSAGE' => \Bitrix\Main\Localization\Loc::getMessage('CRM_WIDGET_SALETARGET_AJAX_NOTIFY_TEXT', array(
						'#URL#' => '/crm/start'
					))
				));
			}
		}
		$sendResponse(array('status' => 'success'));
	break;
}
$sendError('Unknown action!');