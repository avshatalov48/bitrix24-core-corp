<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?

if ($this->__component->__parent && $this->__component->__parent->arParams && array_key_exists("NAME_TEMPLATE", $this->__component->__parent->arParams))
	$arParams["NAME_TEMPLATE"] = $this->__component->__parent->arParams["NAME_TEMPLATE"];

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if ($this->__parent && $this->__parent->arParams && array_key_exists("SHOW_LOGIN", $this->__parent->arParams))
	$arParams["SHOW_LOGIN"] = $this->__parent->arParams["SHOW_LOGIN"];
$bUseLogin = $arParams["SHOW_LOGIN"] != "N" ? true : false;

if (!is_array($arResult["User"]))
	$arResult["User"] = array();

if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_MESSAGES_CHAT", $this->__component->__parent->arResult))
	$arParams["PATH_TO_MESSAGES_CHAT"] = $this->__component->__parent->arResult["PATH_TO_MESSAGES_CHAT"];
	
if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_VIDEO_CALL", $this->__component->__parent->arResult))
	$arParams["PATH_TO_VIDEO_CALL"] = $this->__component->__parent->arResult["PATH_TO_VIDEO_CALL"];

if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_SETTINGS_EDIT", $this->__component->__parent->arResult))
	$arParams["PATH_TO_USER_SETTINGS_EDIT"] = $this->__component->__parent->arResult["PATH_TO_USER_SETTINGS_EDIT"];

if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_REQUESTS", $this->__component->__parent->arResult))
	$arParams["PATH_TO_USER_REQUESTS"] = $this->__component->__parent->arResult["PATH_TO_USER_REQUESTS"];

if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_FEATURES", $this->__component->__parent->arResult))
	$arParams["PATH_TO_USER_FEATURES"] = $this->__component->__parent->arResult["PATH_TO_USER_FEATURES"];

if ($this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_USER_SUBSCRIBE", $this->__component->__parent->arResult))
	$arParams["PATH_TO_USER_SUBSCRIBE"] = $this->__component->__parent->arResult["PATH_TO_USER_SUBSCRIBE"];

$arResult["User"]["NAME_FORMATTED"] = CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["User"], $bUseLogin, false);

if (intval($arResult["User"]["PERSONAL_PHOTO"]) <= 0)
{
	switch ($arResult["User"]["PERSONAL_GENDER"])
	{
		case "M":
			$suffix = "male";
			break;
		case "F":
			$suffix = "female";
			break;
		default:
			$suffix = "unknown";
	}
	$arResult["User"]["PERSONAL_PHOTO"] = COption::GetOptionInt("socialnetwork", "default_user_picture_".$suffix, false, SITE_ID);
}

$arResult["User"]["PersonalPhotoFile"] = array("src" => "");

if (intval($arResult["User"]["PERSONAL_PHOTO"]) > 0)
{

	$imageFile = CFile::GetFileArray($arResult["User"]["PERSONAL_PHOTO"]);
	if ($imageFile !== false)
	{
		$arFileTmp = CFile::ResizeImageGet(
			$imageFile,
			array("width" => 100, "height" => 100),
			BX_RESIZE_IMAGE_EXACT,
			true
		);
	}

	if($arFileTmp && array_key_exists("src", $arFileTmp))
		$arResult["User"]["PersonalPhotoFile"] = $arFileTmp;
}

if(!CModule::IncludeModule("video"))
	$arResult["CurrentUserPerms"]["Operations"]["videocall"] = false;

if ($arResult["User"]["ID"] == $GLOBALS["USER"]->GetID())
{
	$arResult["CurrentUserPerms"]["Operations"]["message"] = false;
	$arResult["CurrentUserPerms"]["Operations"]["videocall"] = false;
}

$arResult["Urls"]["MessageChat"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_MESSAGES_CHAT"], array("user_id" => $arResult["User"]["ID"]));
$arResult["Urls"]["VideoCall"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIDEO_CALL"], array("user_id" => $arResult["User"]["ID"]));
$arResult["Urls"]["Settings"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_SETTINGS_EDIT"], array("user_id" => $arResult["User"]["ID"]));
$arResult["Urls"]["UserRequests"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_REQUESTS"], array("user_id" => $arResult["User"]["ID"]));
$arResult["Urls"]["Features"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_FEATURES"], array("user_id" => $arResult["User"]["ID"]));
$arResult["Urls"]["Subscribe"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_SUBSCRIBE"], array("user_id" => $arResult["User"]["ID"]));

$arResult["IS_ONLINE"] = ($arResult["User"]["IS_ONLINE"] === "Y");

if (CModule::IncludeModule('intranet'))
{
	$arResult['IS_HONOURED'] = CIntranetUtils::IsUserHonoured($arResult["User"]["ID"]);
	$arResult['IS_ABSENT'] = CIntranetUtils::IsUserAbsent($arResult["User"]["ID"]);
}
if ($arResult["User"]['PERSONAL_BIRTHDAY'] <> '')
{
	$arBirthDate = ParseDateTime($arResult["User"]['PERSONAL_BIRTHDAY'], CSite::GetDateFormat('SHORT'));
	$arResult['IS_BIRTHDAY'] = (intval($arBirthDate['MM']) == date('n') && intval($arBirthDate['DD']) == date('j'));
}

if (
	$GLOBALS["USER"]->IsAuthorized()
	&& $GLOBALS["USER"]->GetID() == $arResult["User"]["ID"]
	&& CBXFeatures::IsFeatureEnabled("WebMessenger")
	&& $this->__component->__parent && $this->__component->__parent->arResult && array_key_exists("PATH_TO_MESSAGES_USERS", $this->__component->__parent->arResult)
)
	$arResult["Urls"]["MessagesUsers"] = CComponentEngine::MakePathFromTemplate($this->__component->__parent->arResult["PATH_TO_MESSAGES_USERS"], array("user_id" => $GLOBALS["USER"]->GetID()));
else
	$arResult["Urls"]["MessagesUsers"] = "";
?>