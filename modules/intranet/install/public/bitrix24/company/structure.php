<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/company/structure.php");
$APPLICATION->SetTitle(GetMessage("TITLE1"));
?>

<?$APPLICATION->IncludeComponent("bitrix:intranet.structure", ".default", Array(
	"SEARCH_URL"	=>	"index.php",
	"PM_URL"	=> "/company/personal/messages/chat/#USER_ID#/",
	"USERS_PER_PAGE"	=>	"25",
	"FILTER_SECTION_CURONLY"	=>	"Y",
	"SHOW_ERROR_ON_NULL"	=>	"N",
	"NAV_TITLE"	=>	GetMessage("NAV_TITLE"),
	"SHOW_NAV_TOP"	=>	"Y",
	"SHOW_NAV_BOTTOM"	=>	"Y",
	"SHOW_UNFILTERED_LIST"	=>	"N",
	"AJAX_MODE" => "N",
	"AJAX_OPTION_SHADOW" => "N",
	"AJAX_OPTION_JUMP" => "N",
	"AJAX_OPTION_STYLE" => "Y",
	"AJAX_OPTION_HISTORY" => "Y",
	"FILTER_1C_USERS"	=>	"N",
	"FILTER_NAME"	=>	"structure",
	"SHOW_FROM_ROOT"	=>	"N",
	"MAX_DEPTH"	=>	"2",
	"MAX_DEPTH_FIRST"	=>	"5",
	"COLUMNS"	=>	"2",
	"COLUMNS_FIRST"	=>	"2",
	"SHOW_SECTION_INFO"	=>	"Y",
	"USER_PROPERTY"	=>	array(
		0	=>	"EMAIL",
		1	=>	"PERSONAL_MOBILE",
		2	=>	"UF_SKYPE",
		4	=>	"WORK_PHONE",
		5	=>	"PERSONAL_PHOTO",
	),
	"PATH_TO_USER" => "/company/personal/user/#user_id#/",
	"PATH_TO_USER_EDIT" => "/company/personal/user/#user_id#/edit/",
	"VIS_STRUCTURE_URL" => "/company/vis_structure.php",
	)
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>