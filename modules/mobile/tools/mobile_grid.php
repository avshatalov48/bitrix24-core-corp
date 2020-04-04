<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
IncludeModuleLangFile(__FILE__);

if($_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["action"])>0 && check_bitrix_sessid())
{
	$arErrorMessage = array();
	$arSuccessMessage = array();
	$arJsonData = array();

	$action = $_POST["action"];
	$gridId = $_POST["gridId"];
	$curOption = CUserOptions::GetOption("mobile.interface.grid", $gridId);

	switch ($action)
	{
		case "sort":
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

			break;
		case "fields":
			$fields = $_POST["fields"];
			if (is_array($fields))
				$curOption["fields"] = $fields;
			elseif(isset($curOption["fields"]))
				unset($curOption["fields"]);

			break;
		case "applyFilter":
			$filterCode = $_POST["filterCode"];
			if (!empty($filter))
				$curOption["currentFilter"] = $filterCode;
			elseif(isset($curOption["currentFilter"]))
				unset($curOption["currentFilter"]);

			break;
	}

	CUserOptions::SetOption("mobile.interface.grid", $gridId, $curOption);

	$APPLICATION->RestartBuffer();

	/*if (!empty($arErrorMessage))
		$arJsonData["error"] = implode("<br>", $arErrorMessage);
	else
		$arJsonData["success"] = empty($arSuccessMessage) ? "" : implode("<br>", $arSuccessMessage);*/

	echo \Bitrix\Main\Web\Json::encode($arJsonData);

	die();
}
?>
