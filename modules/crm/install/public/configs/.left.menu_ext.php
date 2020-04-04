<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (CModule::IncludeModule('crm'))
{
	$aMenuLinksExt = array(
		Array(
			GetMessage("CRM_GUIDES"),
			"#SITE_DIR#crm/configs/status/",
			Array(),
			Array(),
			""
		),
		Array(
			GetMessage("CRM_CURRENCIES"),
			"#SITE_DIR#crm/configs/currency/",
			Array(),
			Array(),
			""
		),		
		Array(
			GetMessage("CRM_PERMS"),
			"#SITE_DIR#crm/configs/perms/",
			Array(),
			Array(),
			""
		),
		Array(
			GetMessage("CRM_BP"),
			"#SITE_DIR#crm/configs/bp/",
			Array(),
			Array(),
			"CModule::IncludeModule('bizproc') && CModule::IncludeModule('bizprocdesigner')"
		),
		Array(
			GetMessage("CRM_FIELDS"),
			"#SITE_DIR#crm/configs/fields/",
			Array(),
			Array(),
			""
		),
		Array(
			GetMessage("CRM_CONFIG"),
			"#SITE_DIR#crm/configs/config/",
			Array(),
			Array(),
			"CModule::IncludeModule('subscribe')"
		),
		Array(
			GetMessage("CRM_SENDSAVE"),
			"#SITE_DIR#crm/configs/sendsave/",
			Array(),
			Array(),
			"CModule::IncludeModule('mail')"
		),
		Array(
			GetMessage("CRM_EXTERNAL_SALE"),
			"#SITE_DIR#crm/configs/external_sale/",
			Array(),
			Array(),
			""
		)
	);

	$aMenuLinks = array_merge($aMenuLinks, $aMenuLinksExt);
}

?>