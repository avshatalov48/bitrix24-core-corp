<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet/public_bitrix24/crm/stream/index.php");
global $APPLICATION;
$APPLICATION->SetTitle(GetMessage("TITLE"));
$APPLICATION->SetPageProperty("BodyClass", " page-one-column");
if(CModule::IncludeModule("crm") && CCrmPerms::IsAccessEnabled()):

	$currentUserPerms = CCrmPerms::GetCurrentUserPermissions();
	$canEdit = CCrmLead::CheckUpdatePermission(0, $currentUserPerms)
		|| CCrmContact::CheckUpdatePermission(0, $currentUserPerms)
		|| CCrmCompany::CheckUpdatePermission(0, $currentUserPerms)
		|| CCrmDeal::CheckUpdatePermission(0, $currentUserPerms);

	$APPLICATION->IncludeComponent(
		"bitrix:crm.control_panel",
		"",
		array(
			"ID" => "STREAM",
			"ACTIVE_ITEM_ID" => "STREAM",
			"PATH_TO_COMPANY_LIST" => "/crm/company/",
			"PATH_TO_COMPANY_EDIT" => "/crm/company/edit/#company_id#/",
			"PATH_TO_CONTACT_LIST" => "/crm/contact/",
			"PATH_TO_CONTACT_EDIT" => "/crm/contact/edit/#contact_id#/",
			"PATH_TO_DEAL_LIST" => "/crm/deal/",
			"PATH_TO_DEAL_EDIT" => "/crm/deal/edit/#deal_id#/",
			"PATH_TO_QUOTE_LIST" => "/crm/quote/",
			"PATH_TO_QUOTE_EDIT" => "/crm/quote/edit/#quote_id#/",
			"PATH_TO_INVOICE_LIST" => "/crm/invoice/",
			"PATH_TO_INVOICE_EDIT" => "/crm/invoice/edit/#invoice_id#/",
			"PATH_TO_LEAD_LIST" => "/crm/lead/",
			"PATH_TO_LEAD_EDIT" => "/crm/lead/edit/#lead_id#/",
			"PATH_TO_REPORT_LIST" => "/crm/reports/report/",
			"PATH_TO_DEAL_FUNNEL" => "/crm/reports/",
			"PATH_TO_EVENT_LIST" => "/crm/events/",
			"PATH_TO_PRODUCT_LIST" => "/crm/product/",
			"PATH_TO_SETTINGS" => "/crm/configs/",
			"PATH_TO_SEARCH_PAGE" => "/search/index.php?where=crm"
		)
	);

	// --> IMPORT RESPONSIBILITY SUBSCRIPTIONS
	$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
	if($currentUserID > 0)
	{
		CCrmSonetSubscription::EnsureAllResponsibilityImported($currentUserID);
	}
	// <-- IMPORT RESPONSIBILITY SUBSCRIPTIONS
	$APPLICATION->IncludeComponent("bitrix:crm.entity.livefeed",
		"",
		array(
			"DATE_TIME_FORMAT" => CIntranetUtils::getCurrentDateTimeFormat(),
			"CAN_EDIT" => $canEdit,
			"FORM_ID" => "",
			"PATH_TO_USER_PROFILE" => "/company/personal/user/#user_id#/",
			"PATH_TO_GROUP" => "/workgroups/group/#group_id#/",
			"PATH_TO_CONPANY_DEPARTMENT" => "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#"
		),
		null,
		array("HIDE_ICONS" => "Y")
	);
endif;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>