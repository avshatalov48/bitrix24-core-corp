<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['ENTITY_NAME'] = isset($arParams['ENTITY_NAME']) ? substr(strval($arParams['ENTITY_NAME']), 0, 100) : '';
$arResult['ENTITY_NAME'] = ToLower(preg_replace('/[^A-Za-z_0-9]/', '_', $arResult['ENTITY_NAME']));
$arResult['RND'] = rand(1000, 9999).rand(1000, 9999);

$arResult['NUM_TEMPLATES'] = array(
	"" => GetMessage("CRM_NUMBER_TEMPLATE_0"),
	"NUMBER" => GetMessage("CRM_NUMBER_TEMPLATE_1"),
	"PREFIX" => GetMessage("CRM_NUMBER_TEMPLATE_2"),
	"RANDOM" => GetMessage("CRM_NUMBER_TEMPLATE_3"),
	"USER" => GetMessage("CRM_NUMBER_TEMPLATE_4"),
	"DATE" => GetMessage("CRM_NUMBER_TEMPLATE_5"),
);

$optionPrefix = (strlen($arResult['ENTITY_NAME']) > 0) ? $arResult['ENTITY_NAME'].'_' : '';
$arResult['ACC_NUM_TMPL'] = COption::GetOptionString("crm", $optionPrefix."number_template", "");

if($arResult['ACC_NUM_TMPL'] != "USER")
	$arResult['ACC_NUM_DATA'] = COption::GetOptionString("crm", $optionPrefix."number_data", "");

$this->IncludeComponentTemplate();
?>
