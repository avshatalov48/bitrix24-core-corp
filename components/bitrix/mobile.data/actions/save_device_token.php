<?

use Bitrix\Main\Loader;
use Bitrix\Pull\PushTable;

if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$data = ["status" => "failed"];


/**
 * @var $DB CAllDatabase
 * @var $USER CALLUser
 */


if($_REQUEST["mobile_action"] == "removeToken")
{
	Loader::includeModule("pull");
	$token = $_REQUEST["device_token"];
	$tokenData = PushTable::getList([
		"filter"=> ["=DEVICE_TOKEN" => $token]
	])->fetch();

	if($tokenData)
	{
		PushTable::delete($tokenData["ID"]);
		$data["register_token"] = "removed";
		$data["status"] = "success";
	}
	else
	{
		$data["register_token"] = "unknown";
	}
}
elseif ($_REQUEST["mobile_action"] == "save_device_token")
{
	if($_REQUEST["device_token"])
	{
		$token = $_REQUEST["device_token"];
		$uuid = $_REQUEST["uuid"];
		$data = array(
			"register_token" => "fail",
			"token" => $token,
			"user_id" => $USER->GetID()
		);

		if (CModule::IncludeModule("pull"))
		{
			$voipType = $_REQUEST["device_type"] === \CPushDescription::TYPE_APPLE && isset($_REQUEST["device_token_voip"]) ? \CPushDescription::TYPE_APPLE_VOIP : null;
			$tokenVoip = $_REQUEST["device_token_voip"] ?? null;

			$dbres = CPullPush::GetList(Array(), Array("DEVICE_ID" => $uuid));
			$arToken = $dbres->Fetch();

			$arFields = Array(
				"USER_ID" => $USER->GetID(),
				"DEVICE_NAME" => $_REQUEST["device_name"],
				"DEVICE_TYPE" => $_REQUEST["device_type"],
				"DEVICE_ID" => $_REQUEST["uuid"],
				"DEVICE_TOKEN" => $token,
				"VOIP_TYPE" => $voipType,
				"VOIP_TOKEN" => $tokenVoip,
				"DATE_AUTH"=>ConvertTimeStamp(getmicrotime(),"FULL"),
				"APP_ID" => "Bitrix24" . (CMobile::$isDev ? "_bxdev" : "")
			);

			if ($arToken["ID"])
			{
				$res = CPullPush::Update($arToken["ID"], $arFields);
				$data["register_token"] = "updated";
			}
			else
			{
				$res = CPullPush::Add($arFields);
				if ($res)
					$data["register_token"] = "created";
			}
		}
	}
}

return $data;
