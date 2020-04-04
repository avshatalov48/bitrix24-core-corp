<?
define("STOP_STATISTICS", true);
define("BX_SECURITY_SHOW_MESSAGE", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

if(!$USER->IsAdmin())
	die();

if(!CModule::IncludeModule('socialnetwork'))
	die();

IncludeModuleLangFile(__FILE__);

$name = "";

$arSocNetAllowedSubscribeEntityTypesDesc = CSocNetAllowed::GetAllowedEntityTypesDesc();
		
if (
	array_key_exists("CLASS_DESC_GET", $arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]])
	&& array_key_exists("METHOD_DESC_GET", $arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]])
	&& strlen($arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]]["CLASS_DESC_GET"]) > 0
	&& strlen($arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]]["METHOD_DESC_GET"]) > 0
)
{
	$arEntityTmp = call_user_func(
		array(
			$arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]]["CLASS_DESC_GET"], 
			$arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]]["METHOD_DESC_GET"]
		),
		$_POST["entity_id"]
	);

	if (
		array_key_exists("CLASS_DESC_SHOW", $arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]])
		&& array_key_exists("METHOD_DESC_SHOW", $arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]])
		&& strlen($arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]]["CLASS_DESC_SHOW"]) > 0
		&& strlen($arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]]["METHOD_DESC_SHOW"]) > 0
	)
		$name = call_user_func(
			array(
				$arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]]["CLASS_DESC_SHOW"],
				$arSocNetAllowedSubscribeEntityTypesDesc[$_POST["entity_type"]]["METHOD_DESC_SHOW"]
			),
			$arEntityTmp,
			"",
			array()
		);
}

echo $name;
?>