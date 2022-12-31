<?define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Localization\Loc::loadMessages(__DIR__ . '/template.php');

if ($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST["active"]) && check_bitrix_sessid())
{
	if (CModule::IncludeModule("socialnetwork"))
	{
		$res = false;
		$canEdit = ($USER->CanDoOperation('edit_own_profile') || $USER->IsAdmin()) ? "Y" : "N";
		$userId = intval($_POST["user_id"]);
		$CurrentUserPerms = CSocNetUserPerms::InitUserPerms($USER->GetID(), $userId, CSocNetUser::IsCurrentUserModuleAdmin($_POST["site_id"], (CModule::IncludeModule("bitrix24") && CBitrix24::IsPortalAdmin($USER->GetID()) ? false : true)));

		if (
			$CurrentUserPerms["Operations"]["modifyuser_main"]
			&& $canEdit == 'Y'
			&& $userId != $USER->GetID()
		)
		{
			if ($_POST["active"] == "D")
			{
				$res = $USER->Delete($userId);
			}
			else
			{
				$arFields = array();
				if ($_POST["active"] == "N")
					$arFields["ACTIVE"] = "N";
				elseif ($_POST["active"] == "Y")
					$arFields["ACTIVE"] = "Y";
				$res = $USER->Update($userId, $arFields);
			}
		}

		if ($_REQUEST['json'])
		{
			$error = getMessage('SONET_ERROR_DELETE');
			if ($USER->LAST_ERROR)
			{
				$error .= '<br/>' . $USER->LAST_ERROR;
			}
			else if ($ex = $APPLICATION->getException())
			{
				$error .= '<br/>' . $ex->getString();
			}

			echo \Bitrix\Main\Web\Json::encode(array(
				'result' => $res ? 'success' : 'error',
				'error' => $res ? null : $error,
			));
		}
		else
		{
			echo $res ? 1 : 0;
		}
	}
}

if ($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST["action"]) && check_bitrix_sessid())
{
	$action = trim($_POST["action"]);
	$arJsonData = array();

	$userId = intval($_POST["userId"]);

	switch ($action)
	{
		case "deactivate":
			if (CModule::IncludeModule("security"))
			{
				$numDays = intval($_POST["numDays"]);
				$res = CSecurityUser::DeactivateUserOtp($userId, $numDays);
				if ($res)
					$arJsonData["success"] = "Y";
				else
					$arJsonData["error"] = "Y";
			}
			else
			{
				$arJsonData["error"] = "Y";
			}
			break;

		case "activate":
			if (CModule::IncludeModule("security"))
			{
				$res = CSecurityUser::ActivateUserOtp($userId);
				if ($res)
					$arJsonData["success"] = "Y";
				else
					$arJsonData["error"] = "Y";
			}
			else
			{
				$arJsonData["error"] = "Y";
			}
			break;

		case "defer":
			if (CModule::IncludeModule("security"))
			{
				$numDays = intval($_POST["numDays"]);
				$res = CSecurityUser::DeferUserOtp($userId, $numDays);
				if ($res)
					$arJsonData["success"] = "Y";
				else
					$arJsonData["error"] = "Y";
			}
			else
			{
				$arJsonData["error"] = "Y";
			}
			break;
	}

	echo \Bitrix\Main\Web\Json::encode($arJsonData);

}
?>
