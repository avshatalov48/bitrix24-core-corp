<?if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
$data = array();
if($_REQUEST["service_id"])
{
	switch ($_REQUEST["service_id"])
	{
		case "server_utc":
			date_default_timezone_set("UTC");
			$data = array("server_utc_time"=> time());
		break;
	}
}
else if($_REQUEST["mobile_action"] == "list")
{
	$actionList = new Bitrix\Mobile\Action();
	$data = array_keys($actionList->actions);
}


return $data;
