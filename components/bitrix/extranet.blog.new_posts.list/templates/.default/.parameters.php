<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(!CModule::IncludeModule("blog"))
	return false;


$arTemplateParameters = array(
	"NAME_TEMPLATE" => array(
		"TYPE" => "LIST",
		"NAME" => GetMessage("EBMNP_NAME_TEMPLATE"),
		"VALUES" => CComponentUtil::GetDefaultNameTemplates(),
		"MULTIPLE" => "N",
		"ADDITIONAL_VALUES" => "Y",
		"DEFAULT" => "",
	),
	"SHOW_LOGIN" => Array(
		"NAME" => GetMessage("EBMNP_SHOW_LOGIN"),
		"TYPE" => "CHECKBOX",
		"MULTIPLE" => "N",
		"VALUE" => "Y",
		"DEFAULT" =>"Y",
	),			
);

if (CModule::IncludeModule("socialnetwork"))
{
	$arTemplateParameters["PATH_TO_SONET_USER_PROFILE"] = array(
		"NAME" => GetMessage("EBMNP_PATH_TO_SONET_USER_PROFILE"),
		"DEFAULT" => "/extranet/contacts/personal/user/#user_id#/",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);

	$arTemplateParameters["PATH_TO_MESSAGES_CHAT"] = array(
		"NAME" => GetMessage("EBMNP_PATH_TO_MESSAGES_CHAT"),
		"DEFAULT" => "/extranet/contacts/personal/messages/chat/#user_id#/",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);
}

if (IsModuleInstalled("video"))
{
	$arTemplateParameters["PATH_TO_VIDEO_CALL"] = array(
		"NAME" => GetMessage("EBMNP_PATH_TO_VIDEO_CALL"),
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"DEFAULT" => "/extranet/contacts/personal/video/#user_id#/",
		"COLS" => 25,
	); 
}

if (IsModuleInstalled("intranet"))
{
	$arTemplateParameters["PATH_TO_CONPANY_DEPARTMENT"] = array(
		"NAME" => GetMessage("EBMNP_PATH_TO_CONPANY_DEPARTMENT"),
		"DEFAULT" => "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#",
		"TYPE" => "STRING",
		"MULTIPLE" => "N",
		"COLS" => 25,
	);
}
?>