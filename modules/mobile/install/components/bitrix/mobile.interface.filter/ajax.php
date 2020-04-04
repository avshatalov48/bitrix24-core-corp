<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if($_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["action"])>0 && check_bitrix_sessid())
{
	CUtil::decodeURIComponent($_POST);

	$action = $_POST["action"];
	$gridId = $_POST["gridId"];
	$curOption = CUserOptions::GetOption("mobile.interface.grid", $gridId);

	switch ($action)
	{
		case "saveFilter":
			$curOption["filters"]["filter_user"] = array();
			if (isset($_POST["fields"]) && is_array($_POST["fields"]))
			{
				foreach($_POST["fields"] as $field => $value)
				{
					$curOption["filters"]["filter_user"]["fields"][$field] = trim($value);
				}
			}
			if (isset($_POST["filter_rows"]) && is_array($_POST["filter_rows"]))
			{
				foreach($_POST["filter_rows"] as $field)
				{
					$curOption["filters"]["filter_user"]["filter_rows"][] = trim($field);
				}
			}

			$curOption["currentFilter"] = "filter_user";

			break;
		case "applyFilter":
			$filterCode = $_POST["filterCode"];
			if (!empty($filterCode))
				$curOption["currentFilter"] = $filterCode;
			elseif(isset($curOption["currentFilter"]))
				unset($curOption["currentFilter"]);

			break;
	}

	CUserOptions::SetOption("mobile.interface.grid", $gridId, $curOption);
}
?>
