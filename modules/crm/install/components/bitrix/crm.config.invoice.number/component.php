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

$arResult['NUM_TEMPLATES'] = array(
	"" => GetMessage("CRM_ACCOUNT_NUMBER_TEMPLATE_0"),
	"NUMBER" => GetMessage("CRM_ACCOUNT_NUMBER_TEMPLATE_1"),
	"PREFIX" => GetMessage("CRM_ACCOUNT_NUMBER_TEMPLATE_2"),
	"RANDOM" => GetMessage("CRM_ACCOUNT_NUMBER_TEMPLATE_3"),
	"USER" => GetMessage("CRM_ACCOUNT_NUMBER_TEMPLATE_4"),
	"DATE" => GetMessage("CRM_ACCOUNT_NUMBER_TEMPLATE_5"),
);

$arResult['ACC_NUM_TMPL'] = COption::GetOptionString("sale", "account_number_template", "");

if($arResult['ACC_NUM_TMPL'] != "USER")
	$arResult['ACC_NUM_DATA'] = COption::GetOptionString("sale", "account_number_data", "");

$this->IncludeComponentTemplate();
?>
