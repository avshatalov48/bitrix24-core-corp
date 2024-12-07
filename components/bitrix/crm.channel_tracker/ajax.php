<?
define('BX_SECURITY_SESSION_READONLY', true);
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}

use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Widget\FilterPeriodType;
require_once(__DIR__."/class.php");
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$response = array("status" => "success", "data" => array());

try
{
	$currentUser = CCrmSecurityHelper::GetCurrentUser();
	if (!$currentUser->IsAuthorized() || !check_bitrix_sessid() || !$request->isPost())
		throw new \Bitrix\Main\AccessDeniedException();

	if ($lazyLoadWidgetsComponent = $request->getPost('LAZY_LOAD_COMPONENT'))
	{
		$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
		$isSupervisor = CCrmPerms::IsAdmin($currentUserID)
			|| Bitrix\Crm\Integration\IntranetManager::isSupervisor($currentUserID);

		$showSaleTarget = true;
		if (Bitrix\Main\Loader::includeModule("bitrix24") &&
			!Bitrix\Bitrix24\Feature::isFeatureEnabled("crm_sale_target"))
		{
			$showSaleTarget = false;
		}

		$customWidgets = [];
		if ($showSaleTarget)
		{
			$customWidgets[] = 'saletarget';
		}

		$gUid = $request->getPost('GUID');
		$pathToLeadWidget = $request->getPost('PATH_TO_LEAD_WIDGET');
		$pathToLeadList = $request->getPost('PATH_TO_LEAD_LIST');
		$rowData = $request->getPost('ROW_DATA');
		$demoTitle = $request->getPost('DEMO_TITLE');
		global $APPLICATION;
		$APPLICATION->ShowAjaxHead();
		$start = time();
		$APPLICATION->IncludeComponent(
			'bitrix:crm.widget_panel',
			'',
			array(
				'GUID' => $gUid,
				'LAYOUT' => 'L50R50',
				'ENABLE_NAVIGATION' => false,
				'NOT_CALCULATE_DATA' => false,
				'ENTITY_TYPES' => array(
					CCrmOwnerType::ActivityName,
					CCrmOwnerType::LeadName,
					CCrmOwnerType::DealName,
					CCrmOwnerType::ContactName,
					CCrmOwnerType::CompanyName,
					CCrmOwnerType::InvoiceName
				),
				'DEFAULT_ENTITY_TYPE' => CCrmOwnerType::ActivityName,
				'PATH_TO_WIDGET' => $pathToLeadWidget,
				'PATH_TO_LIST' => $pathToLeadList,
				'PATH_TO_DEMO_DATA' => $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.channel_tracker/templates/.default/widget',
				'IS_SUPERVISOR' => $isSupervisor,
				'ROWS' => $rowData,
				'DEMO_TITLE' => $demoTitle,
				'DEMO_CONTENT' => '',
				'RENDER_HEAD_INTO_VIEW' => 'widget_panel_header',
				'CUSTOM_WIDGETS' => $customWidgets
			)
		);
		$end = time();

		if ($end - $start > 3)
		{
			CUserOptions::SetOption('crm','crm_start_loading_timeout', 5000);
		}
		exit;
	}
	if (($action = $request->getPost("action")) && is_array($action))
	{
		$c = \CCrmStartPageComponentCRMCounters::getInstance();

		$widgetGuid = $request->getPost("WIDGET_GUID") ?: "start_widget";
		//region Filter
		$filterOptions = new \Bitrix\Main\UI\Filter\Options($widgetGuid);
		$filterFields = $filterOptions->getFilter(array(
			array('id' => 'RESPONSIBLE_ID'),
			array('id' => 'PERIOD')
		));
		Filter::convertPeriodFromDateType($filterFields, 'PERIOD');
		$filterFields = Filter::internalizeParams($filterFields);
		Filter::sanitizeParams($filterFields);
		$commonFilter = new Filter($filterFields);
		//endregion
		$c->setFilter($commonFilter);
		$counters = array();

		if (in_array("sale", $action))
			list($counters["GENERAL_SALE_STATISTIC"], $counters["PERSONAL_SALE_STATISTIC"]) = $c->getSaleCounters();
		if (in_array("personal", $action))
			$counters["PERSONAL_ENTITY_STATISTIC"] = $c->getPersonalCounters();

		$response["data"] = $counters;
	}
}
catch(\Exception $e)
{
	$exceptionHandling = \Bitrix\Main\Config\Configuration::getValue("exception_handling");
	if ($exceptionHandling["debug"])
	{
		throw $e;
	}
	else
	{
		$errorCollection = new \Bitrix\Main\ErrorCollection();
		$errorCollection->add(array(new \Bitrix\Main\Error($e->getMessage(), $e->getCode())));

		$errors = array();
		foreach($errorCollection as $error)
		{
			/** @var Error $error */
			$errors[] = array(
				'message' => $error->getMessage(),
				'code' => $error->getCode(),
			);
		}
		unset($error);
		$response = array("status" => "error", "errors" => $errors);
	}
}

\CCrmStartPageComponentCRMCounters::sendJsonResponse($response);
?>
