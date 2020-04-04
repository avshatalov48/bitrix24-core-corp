<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');
/** @var \Bitrix\Main\HttpRequest $request */

$request = \Bitrix\Main\Context::getCurrent()->getRequest();
$entity = $request->getQuery("entity");
if (($event = $request->getQuery("event")) === null)
	$event = "onCRMEntityWasChosen";
$GLOBALS['APPLICATION']->IncludeComponent(
	'bitrix:mobile.crm.entity.list',
	'',
	array(
		"GRID_ID" => "mobile_crm_entity_list",
		"ENTITY_TYPES" => $entity,
		"EVENT_NAME" => $event
	)
);
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
