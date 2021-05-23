<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

global $APPLICATION;
$userId = intval($_REQUEST['user_id']);
$data = $APPLICATION->IncludeComponent("bitrix:mobile.calendar.event.list","", Array("USER_ID" => $userId),false, array("HIDE_ICONS"=>"Y"));
return $data;

?>