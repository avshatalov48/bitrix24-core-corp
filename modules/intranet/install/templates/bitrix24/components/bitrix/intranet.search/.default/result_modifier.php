<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!isset($arParams["PATH_TO_USER_EDIT"]))
{
	if (CModule::IncludeModule("extranet") && CExtranet::IsExtranetSite())
		$arParams["PATH_TO_USER_EDIT"] = SITE_DIR."contacts/personal/user/#user_id#/edit/";
	else
		$arParams["PATH_TO_USER_EDIT"] = SITE_DIR."company/personal/user/#user_id#/edit/";
}
?>