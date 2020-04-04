<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if($_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["action"])>0 && check_bitrix_sessid())
{
	$action = $_POST["action"];
	$gridId = $_POST["gridId"];
	$curOption = CUserOptions::GetOption("mobile.interface.grid", $gridId);

	if ($action == "sort")
	{
		$sortBy = $_POST["sortBy"];
		if (!empty($sortBy))
			$curOption["sort_by"] = $sortBy;
		elseif(isset($curOption["sort_by"]))
			unset($curOption["sort_by"]);

		$sortOrder = $_POST["sortOrder"];
		if (!empty($sortOrder))
			$curOption["sort_order"] = $sortOrder;
		elseif(isset($curOption["sort_order"]))
			unset($curOption["sort_order"]);

		CUserOptions::SetOption("mobile.interface.grid", $gridId, $curOption);
	}
}
?>
