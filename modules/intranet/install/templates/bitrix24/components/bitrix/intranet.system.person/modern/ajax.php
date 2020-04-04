<?define("NOT_CHECK_PERMISSIONS", true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

if (!CModule::IncludeModule("socialnetwork"))
	return;

if ($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST["active"]) && in_array($_POST["active"], array("D", "Y", "N")) && check_bitrix_sessid())
{
	$userId = intval($_POST["user_id"]);

	$res = false;
	$canEdit = ($USER->CanDoOperation('edit_own_profile') || $USER->IsAdmin()) ? "Y" : "N";
	$CurrentUserPerms = CSocNetUserPerms::InitUserPerms($USER->GetID(), $userId, CSocNetUser::IsCurrentUserModuleAdmin($_POST["site_id"], (CModule::IncludeModule("bitrix24") && CBitrix24::IsPortalAdmin($USER->GetID()) ? false : true)));

	if (
		$CurrentUserPerms["Operations"]["modifyuser_main"]
		&& $canEdit == 'Y'
		&& $userId != $USER->GetID()
	)
	{
		switch ($_POST["active"])
		{
			case "D":
				$res = $USER->Delete($userId);
				break;
			case "Y":
			case "N":
				$res = $USER->Update($userId, array("ACTIVE" => $_POST["active"]));
				break;
		}
	}

	$arJsonData = array();
	if ($res)
	{
		$arJsonData["success"] = "Y";
	}
	else
	{
		$arJsonData["error"] = GetMessage("INTR_ISP_DELETE_ERROR_".$_POST["active"]);
		if ($USER->LAST_ERROR)
		{
			$arJsonData['error'] .= '<br/>' . $USER->LAST_ERROR;
		}
		else if ($ex = $APPLICATION->getException())
		{
			$arJsonData['error'] .= '<br/>' . $ex->getString();
		}
	}
	echo \Bitrix\Main\Web\Json::encode($arJsonData);
}
?>
