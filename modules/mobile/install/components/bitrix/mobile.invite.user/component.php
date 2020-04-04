<?if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
if (!IsModuleInstalled("bitrix24") || !$USER->CanDoOperation('bitrix24_invite'))
	die();

include(dirname(__FILE__)."/functions.php");

$arResult["ERRORS"] = "";

if(
	$_SERVER["REQUEST_METHOD"] === "POST"
	&& check_bitrix_sessid()
)
{
	if ($_POST["reinvite"] == "Y" && intval($_POST["user_id"]) > 0)
	{
		ReinviteUser(SITE_ID, intval($_POST["user_id"]));
		$APPLICATION->RestartBuffer();
		return;
	}
	elseif(strlen($_POST["EMAIL"]) > 0)
	{
		$ID = RegisterNewUser(SITE_ID, $_POST);
		if(is_array($ID))
		{
			$arResult["ERRORS"] = implode("<br/>",$ID);
		}
		elseif (intval($ID))
		{
			$arResult["SUCCESS"] = "Y";
		}
	}
}
$this->IncludeComponentTemplate();
?>