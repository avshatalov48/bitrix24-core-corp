<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();
$params = (array)$request->get('PARAMS');

$APPLICATION->IncludeComponent('bitrix:crm.entity.tree', '', array(
	'ENTITY_TYPE_NAME' => $request->get('ENTITY_TYPE_NAME'),
	'ENTITY_ID' => $request->get('ENTITY_ID'),
	'ADDITIONAL_PARAMS' => $request->get('ADDITIONAL_PARAMS'),

	'FORM_ID' => $params['FORM_ID'],
//	'BLOCK' => $params['BLOCK'],
	'BLOCK_PAGE' => $params['BLOCK_PAGE'],
	'PATH_TO_LEAD_SHOW' => $params['PATH_TO_LEAD_SHOW'],
	'PATH_TO_CONTACT_SHOW' => $params['PATH_TO_CONTACT_SHOW'],
	'PATH_TO_COMPANY_SHOW' => $params['PATH_TO_COMPANY_SHOW'],
	'PATH_TO_DEAL_SHOW' => $params['PATH_TO_DEAL_SHOW'],
	'PATH_TO_QUOTE_SHOW' => $params['PATH_TO_QUOTE_SHOW'],
	'PATH_TO_INVOICE_SHOW' => $params['PATH_TO_INVOICE_SHOW'],
	'PATH_TO_USER_PROFILE' => $params['PATH_TO_USER_PROFILE'],
));

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');