<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

$arEventTypes = array("USER_PASS_REQUEST", "USER_PASS_CHANGED", "EXTRANET_INVITATION", "IM_NEW_NOTIFY", "CALENDAR_INVITATION", "IM_NEW_MESSAGE", "IM_NEW_NOTIFY_GROUP");

foreach($arEventTypes as $event)
{
	$rsMess = CEventMessage::GetList($by="", $order="desc", array("TYPE_ID" => $event));
	if ($arMess = $rsMess->Fetch())
	{
		$em = new CEventMessage;
		$arFields = array(
			"EMAIL_FROM" => GetMessage("MAIN_EVENTS_NO_REPLY"),
			"REPLY_TO" => "#DEFAULT_EMAIL_FROM#"
		);
		$em->Update($arMess["ID"], $arFields);
	}
}
?>