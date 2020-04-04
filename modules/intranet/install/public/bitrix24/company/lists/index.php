<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/company/lists/index.php");
$APPLICATION->SetTitle(GetMessage("TITLE"));
?>

<?$APPLICATION->IncludeComponent("bitrix:lists", ".default", array(
	"IBLOCK_TYPE_ID" => "lists",
	"SEF_MODE" => "Y",
	"SEF_FOLDER" => "/company/lists/",
	"CACHE_TYPE" => "A",
	"CACHE_TIME" => "36000000",
	"SEF_URL_TEMPLATES" => array(
		"lists" => "",
		"list" => "#list_id#/view/#section_id#/",
		"list_sections" => "#list_id#/edit/#section_id#/",
		"list_edit" => "#list_id#/edit/",
		"list_fields" => "#list_id#/fields/",
		"list_field_edit" => "#list_id#/field/#field_id#/",
		"list_element_edit" => "#list_id#/element/#section_id#/#element_id#/",
		"list_file" => "#list_id#/file/#section_id#/#element_id#/#field_id#/#file_id#/",
		"bizproc_log" => "#list_id#/bp_log/#document_state_id#/",
		"bizproc_workflow_start" => "#list_id#/bp_start/#element_id#/",
		"bizproc_task" => "#list_id#/bp_task/#section_id#/#element_id#/#task_id#/",
		"bizproc_workflow_admin" => "#list_id#/bp_list/",
		"bizproc_workflow_edit" => "#list_id#/bp_edit/#ID#/",
		"bizproc_workflow_vars" => "#list_id#/bp_vars/#ID#/",
	)
	),
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>