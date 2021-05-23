<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$APPLICATION->SetTitle(GetMessage("LICENSE_RESTRICTION_TITLE"));

$arResult["NUM_AVAILABLE_USERS"] = intval(COption::GetOptionInt("main", "PARAM_MAX_USERS", 0));
$arResult["NUM_ALL_USERS"] = CUser::GetActiveUsersCount();

$this->IncludeComponentTemplate();
?>
