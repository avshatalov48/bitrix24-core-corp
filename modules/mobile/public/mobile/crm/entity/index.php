<?php
require($_SERVER['DOCUMENT_ROOT'] . '/mobile/headers.php');
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$entity = "";
if (
	\Bitrix\Main\Loader::includeModule("crm")
	&& isset($_GET["entity"])
	&& in_array($_GET["entity"], array("contact", "company", "lead", "deal", "quote"))
)
{
	switch ($_GET["entity"])
	{
		case "contact":
			$entity = CCrmOwnerType::ContactName;
			break;
		case "company":
			$entity = CCrmOwnerType::CompanyName;
			break;
		case "lead":
			$entity = CCrmOwnerType::LeadName;
			break;
		case "deal":
			$entity = CCrmOwnerType::DealName;
			break;
		case "quote":
			$entity = CCrmOwnerType::QuoteName;
			break;
	}
}

$event = $_GET["event"] ? $_GET["event"] : "";

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
