<?

use Bitrix\Main\Loader;
use Bitrix\Pull\PushTable;

if (!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$data = ["status" => "failed"];


/**
 * @var $DB CAllDatabase
 * @var $USER CALLUser
 */


if ($_REQUEST["mobile_action"] == "removeToken")
{
	Loader::includeModule("pull");
	$token = $_REQUEST["device_token"];
	$tokenData = PushTable::getList([
		"filter" => ["=DEVICE_TOKEN" => $token]
	])->fetch();

	if ($tokenData)
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
	if (!empty($_REQUEST["device_token"]) || !empty($_REQUEST["device_token_voip"]))
	{

		$uuid = $_REQUEST["uuid"];
		$data = array(
			"register_token" => "fail",
			"user_id" => $USER->GetID()
		);

		if (CModule::IncludeModule("pull"))
		{
			$voipType = $_REQUEST["device_type"] === \CPushDescription::TYPE_APPLE && isset($_REQUEST["device_token_voip"])
				? \CPushDescription::TYPE_APPLE_VOIP
				: null;
			$tokenVoip = $_REQUEST["device_token_voip"] ?? null;
			$token = $_REQUEST["device_token"] ?? null;

			$dbres = CPullPush::GetList(array(), array("DEVICE_ID" => $uuid));
			$arToken = $dbres->Fetch();

			$fields = array(
				"USER_ID" => $USER->GetID(),
				"DEVICE_NAME" => $_REQUEST["device_name"],
				"DEVICE_TYPE" => $_REQUEST["device_type"],
				"DEVICE_ID" => $_REQUEST["uuid"],
				"DATE_AUTH" => ConvertTimeStamp(getmicrotime(), "FULL"),
				"APP_ID" => "Bitrix24" . (CMobile::$isDev ? "_bxdev" : "")
			);

			if ($voipType != null)
			{
				$fields["VOIP_TOKEN"] = $tokenVoip;
				$fields["VOIP_TYPE"] = $voipType;
				$data["token"] = $tokenVoip;
				$data["type"] = $voipType;
			}
			else
			{
				$fields["DEVICE_TOKEN"] = $token;
				$data["token"] = $token;
				$data["type"] = $voipType;
			}

			if (!empty($arToken["ID"]))
			{
				$res = CPullPush::Update($arToken["ID"], $fields);
				$data["register_token"] = "updated";
			}
			else
			{
				$res = CPullPush::Add($fields);
				if ($res)
				{
					$data["register_token"] = "created";
				}
			}
		}
	}
}

return $data;
