<?php

define("STOP_STATISTICS", true);
define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("DisableEventsCheck", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

use Bitrix\Main\Type\Date;

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : '';
$siteId = mb_substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if (!empty($siteId) && is_string($siteId))
{
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

if (!function_exists('__CrmRecurringFieldEditEndResponse'))
{
	function __CrmRecurringFieldEditEndResponse($result)
	{
		$GLOBALS['APPLICATION']->RestartBuffer();
		header('Content-Type: application/x-javascript; charset=' . LANG_CHARSET);
		if (!empty($result))
		{
			echo CUtil::PhpToJSObject($result);
		}
		require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
		die;
	}
}

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();

if (!CModule::IncludeModule('crm'))
{
	__CrmRecurringFieldEditEndResponse(['ERROR' => 'Could not include crm module.']);
}

if (!CCrmSecurityHelper::IsAuthorized() || !check_bitrix_sessid())
{
	__CrmRecurringFieldEditEndResponse(['ERROR' => 'Access denied.']);
}
if (!$request->isPost())
{
	__CrmRecurringFieldEditEndResponse(['ERROR' => 'Request method is not allowed.']);
}
$request = $request->toArray();

if ($request['PARAMS']['DEAL_DATEPICKER_BEFORE'] <> '')
{
	$request['START_DATE'] = $request['PARAMS']['DEAL_DATEPICKER_BEFORE'];
}

$startDate = ($request['START_DATE'] <> '') ? $request['START_DATE'] : null;
$startDate = new Date($startDate);
$endDate = ($request['PARAMS']['END_DATE'] <> '') ? $request['PARAMS']['END_DATE'] : null;
$endDate = new Date($endDate);

$fields = [
	'START_DATE' => $startDate,
	'IS_LIMIT' => $request['PARAMS']['REPEAT_TILL'],
	'LIMIT_DATE' => $endDate,
	'LIMIT_REPEAT' => $request['PARAMS']['TIMES'],
	'PARAMS' => $request['PARAMS'],
];

if ((int)$request['ENTITY_TYPE'] === \CCrmOwnerType::Deal)
{
	$entity = \Bitrix\Crm\Recurring\Entity\Item\DealNew::create();
}
else
{
	$entity = \Bitrix\Crm\Recurring\Entity\Item\InvoiceNew::create();
}

$entity->initFields($fields);
$nextExecuteDate = $entity->getField('NEXT_EXECUTION');

__CrmRecurringFieldEditEndResponse(['RESULT' => ['NEXT_DATE' => $nextExecuteDate]]);

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
