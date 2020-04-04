<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (CModule::IncludeModule('crm'))
{
	$CrmPerms = new CCrmPerms($GLOBALS["USER"]->GetID());
	$arMenuCrm = Array();
	if (!$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("CRM_CONTACT"),
			"#SITE_DIR#crm/contact/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("CRM_COMPANY"),
			"#SITE_DIR#crm/company/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("CRM_DEAL"),
			"#SITE_DIR#crm/deal/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("CRM_LEAD"),
			"#SITE_DIR#crm/lead/",
			Array(),
			Array(),
			""
		);
	}
	
	$arMenuCrm[] = Array(
		GetMessage("CRM_PRODUCT"),
		"#SITE_DIR#crm/product/",
		Array(),
		Array(),
		""
	);	
	
	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE) ||
		!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("CRM_EVENT"),
			"#SITE_DIR#crm/events/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE) ||
		!$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE) || !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	{
		$arMenuCrm[] = Array(
			GetMessage("CRM_REPORTS"),
			CModule::IncludeModule('report') ? "#SITE_DIR#crm/reports/report/" : "#SITE_DIR#crm/reports/",
			Array(),
			Array(),
			""
		);

		$arMenuCrm[] = Array(
			GetMessage("CRM_HELP"),
			"#SITE_DIR#crm/info/",
			Array(),
			Array(),
			""
		);
	}
	if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE, 'WRITE'))
	{
		$arMenuCrm[] = Array(
			GetMessage("CRM_CONFIGS"),
			"#SITE_DIR#crm/configs/",
			Array(),
			Array(),
			""
		);
	}
	$aMenuLinks = array_merge($arMenuCrm, $aMenuLinks);
}

?>