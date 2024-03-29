<?
use Bitrix\Main\Text\HtmlFilter;
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}

$arParams["ID"] = intval($arParams["ID"]);
if(!is_array($arParams["GROUP_ID"]))
	$arParams["GROUP_ID"] = array($arParams["GROUP_ID"]);
foreach($arParams["GROUP_ID"] as $k=>$v)
	if(intval($v) <= 0)
		unset($arParams["GROUP_ID"][$k]);

if($arParams["BLOG_VAR"] == '')
	$arParams["BLOG_VAR"] = "blog";
if($arParams["USER_VAR"] == '')
	$arParams["USER_VAR"] = "id";
if($arParams["PAGE_VAR"] == '')
	$arParams["PAGE_VAR"] = "page";
$arParams["PATH_TO_BLOG"] = trim($arParams["PATH_TO_BLOG"]);
if($arParams["PATH_TO_BLOG"] == '')
	$arParams["PATH_TO_BLOG"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#");
$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if($arParams["PATH_TO_USER"] == '')
	$arParams["PATH_TO_USER"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");
if($arParams["PATH_TO_USER_EDIT"] == '')
	$arParams["PATH_TO_USER_EDIT"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#&mode=edit");
$arParams["PATH_TO_SEARCH"] = trim($arParams["PATH_TO_SEARCH"]);
if($arParams["PATH_TO_SEARCH"] == '')
	$arParams["PATH_TO_SEARCH"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=search");
if(mb_strpos($arParams["PATH_TO_SEARCH"], "?") === false)
	$arParams["PATH_TO_SEARCH"] .= "?";
else
	$arParams["PATH_TO_SEARCH"] .= "&";
$arParams["DATE_TIME_FORMAT"] = trim(empty($arParams["DATE_TIME_FORMAT"]) ? $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")) : $arParams["DATE_TIME_FORMAT"]);

$arResult["urlToCancel"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arParams["ID"]));

$dbUser = CUser::GetByID($arParams["ID"]);
$arResult["arUser"] = $dbUser->GetNext();

$arResult["bEdit"] = ($_REQUEST['mode']=='edit' && ($USER->GetID()==$arParams["ID"] || $USER->IsAdmin())) ? "Y" : "N";

if($arParams["ID"] == ($USER->GetID()) || $USER->IsAdmin())
	$arResult["urlToEdit"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER_EDIT"], array("user_id" => $arParams["ID"]));

if(!is_array($arResult["arUser"]))
{
	$arResult["FATAL_ERROR"] = GetMessage("B_B_USER_NO_USER");
	CHTTP::SetStatus("404 Not Found");
}
else
{
	$BLOG_USER_ID=intval($_POST["BLOG_USER_ID"]);
	if($_SERVER["REQUEST_METHOD"]=="POST" && $_POST["save"] <> '' && check_bitrix_sessid())
	{
		if(CModule::IncludeModule("blog"))
		{
			if($BLOG_USER_ID<=0)
			{
				$BlogUser = CBlogUser::GetByID($arParams["ID"], BLOG_BY_USER_ID);
				
				if(empty($BlogUser))
				{
					$BLOG_USER_ID=CBlogUser::Add(array(
						"USER_ID" => $arParams["ID"],
						"=LAST_VISIT" => $DB->GetNowFunction(),
						"=DATE_REG" => $DB->GetNowFunction(),
						"ALLOW_POST" => "Y",
						"PATH" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arParams["ID"])),
					));
					$BlogUser = CBlogUser::GetByID($BLOG_USER_ID);
				}
				else
				{
					$BLOG_USER_ID = $BlogUser["ID"];
				}
			}
			else
			{
				$BlogUser = CBlogUser::GetByID($BLOG_USER_ID);
			}

			$BlogUser = CBlogTools::htmlspecialcharsExArray($BlogUser);
			if($BlogUser && ($USER->GetID()==$BlogUser["USER_ID"] || $USER -> IsAdmin()))
			{
				$arPICTURE = $_FILES["AVATAR"];
				$arPICTURE["old_file"] = $BlogUser["AVATAR"];
				$arPICTURE["del"] = $_POST["AVATAR_del"];
				$arHobbyDB=array();
				$arHobby=explode(",", $_POST["INTERESTS"]);
				foreach($arHobby as $Hobby)
				{
					$Hobby=trim($Hobby);
					$arHobbyDB[]=$Hobby;
				}
				$arHobbyDB=array_unique($arHobbyDB);
				if(count($arHobbyDB)>0)
					$Hobby=implode(", ", $arHobbyDB);
				else
					$Hobby="";
				$arFields = array(
					"ALIAS" => $_POST["ALIAS"],
					"DESCRIPTION" => $_POST["DESCRIPTION"],
					"AVATAR" => $arPICTURE,
					"INTERESTS" => $Hobby,
					"PATH" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arParams["ID"])),
				);
				$DB->StartTransaction();
				$res = CBlogUser::Update($BLOG_USER_ID, $arFields);
				if($res)
				{
					$arPICTURE = $_FILES["PERSONAL_PHOTO"];
					$arPICTURE["old_file"] = $arResult["arUser"]["PERSONAL_PHOTO"];
					$arPICTURE["del"] = $_POST["PERSONAL_PHOTO_del"];

					$arFields = Array(
						"PERSONAL_WWW" => $_POST["PERSONAL_WWW"],
						"PERSONAL_GENDER" => $_POST["PERSONAL_GENDER"],
						"PERSONAL_BIRTHDAY" => $_POST["PERSONAL_BIRTHDAY"],
						"PERSONAL_PHOTO" => $arPICTURE,
					);
					$GLOBALS["USER_FIELD_MANAGER"]->EditFormAddFields("USER", $arFields);
					$res = $USER->Update($BlogUser["USER_ID"], $arFields);
					if ($res)
					{
						$DB->Commit();
					}
					else
					{
						$DB->Rollback();
						$strErrorMessage .= $USER->LAST_ERROR;
					}
					$arFilter = [
						"OWNER_ID" => $BlogUser["USER_ID"],
						"=ACTIVE" => "Y",
						"GROUP_SITE_ID" => SITE_ID,
					];
					if (!empty($arParams["GROUP_ID"]))
					{
						$arFilter["GROUP_ID"] = $arParams["GROUP_ID"];
					}
						
					$dbBlog = CBlog::GetList(Array("ID" => "DESC"), $arFilter, false, false, Array("ID", "OWNER_ID", "URL", "SOCNET_GROUP_ID"));
					while($arBlog = $dbBlog->Fetch())
					{
						if (intval($arBlog["SOCNET_GROUP_ID"]) > 0 && CModule::IncludeModule("socialnetwork") && method_exists("CSocNetGroup", "GetSite"))
						{
							$arSites = array();
							$rsGroupSite = CSocNetGroup::GetSite($arBlog["SOCNET_GROUP_ID"]);
							while($arGroupSite = $rsGroupSite->Fetch())
								$arSites[] = $arGroupSite["LID"];
						}
						else
							$arSites = array(SITE_ID);

						foreach ($arSites as $site_id_tmp)
							BXClearCache(True, "/".$site_id_tmp."/blog/".$arBlog['URL']);
					}
				}
				else
				{
					$DB->Rollback();
					if($e = $APPLICATION->GetException())
						$strErrorMessage .= $e->GetString();
				}
					
			}
			else
				$strErrorMessage.= GetMessage("B_B_PU_NO_RIGHTS")."<br />";
		}

		if($strErrorMessage == '')
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arParams["ID"])));
		else
			$arResult["ERROR_MESSAGE"] = $strErrorMessage;
	}

	$arResult["BlogUser"] = CBlogUser::GetByID($arParams["ID"], BLOG_BY_USER_ID);
	$arResult["BlogUser"] = CBlogTools::htmlspecialcharsExArray($arResult["BlogUser"]);
	$arResult["arSex"] = array(
			"M"=>GetMessage("B_B_USER_SEX_M"),
			"F"=>GetMessage("B_B_USER_SEX_F"),
		);

	$arResult["userName"] = CBlogUser::GetUserNameEx($arResult["arUser"], $arResult["BlogUser"], $arParams);
	$arResult["User"] = $arResult["arUser"];
	
	$arResult["BlogUser"]["LAST_VISIT_FORMATED"] = FormatDate($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($arResult["BlogUser"]["LAST_VISIT"], CSite::GetDateFormat("FULL")));
	foreach($arResult["BlogUser"] as $k=>$v)
		$arResult["User"][$k] = $v;
		
	if($arParams["SET_TITLE"]=="Y")
	{
		if($arResult["bEdit"] == "Y")
			$APPLICATION->SetTitle(GetMessage("B_B_USER_TITLE")." \"".$arResult["userName"]."\"");
		else
			$APPLICATION->SetTitle(GetMessage("B_B_USER_TITLE_VIEW")." ".$arResult["userName"]);
	}

	$arFilterTmp = ["=ACTIVE" => "Y", "GROUP_SITE_ID" => SITE_ID, "OWNER_ID" => $arParams["ID"]];
	if (!empty($arParams["GROUP_ID"]))
	{
		$arFilterTmp["GROUP_ID"] = $arParams["GROUP_ID"];
	}

	$dbBlog = CBlog::GetList([], $arFilterTmp);
	if($arBlog = $dbBlog->GetNext())
	{
		$arResult["Blog"] = $arBlog;
	}

	if(!empty($arResult["Blog"]))
		$arResult["Blog"]["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arResult["Blog"]["URL"]));
	
	if($arResult["User"]["PERSONAL_WWW"] <> '')
		$arResult["User"]["PERSONAL_WWW"] = ((mb_strpos($arResult["User"]["PERSONAL_WWW"], "http") === false)? "http://" : "").$arResult["User"]["PERSONAL_WWW"];
	
	$arHobby = explode(", ", $arResult["User"]["~INTERESTS"]);
	$arResult["User"]["Hobby"]=Array();
	foreach($arHobby as $Hobby)
	{
		if($Hobby <> '')
			$arResult["User"]["Hobby"][] = Array(
					"link" => $arParams["PATH_TO_SEARCH"].'where=USER&q='.urlencode($Hobby),
					"name" => HtmlFilter::encode($Hobby),
				);
	}
	
	$dbFriends = CBlogUser::GetUserFriends($arParams["ID"], True);
	$arResult["User"]["friendsOf"] = Array();
	while ($arFriends = $dbFriends->GetNext())
	{
		$arResult["User"]["friendsOf"][] = Array(
				"link" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arFriends["URL"])),
				"name" => $arFriends["NAME"],
			);
	}

	$arResult["User"]["friends"] = Array();
	$dbFriends = CBlogUser::GetUserFriends($arParams["ID"], False);
	while ($arFriends = $dbFriends->GetNext())
	{
		$arResult["User"]["friends"][] = Array(
				"link" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arFriends["URL"])),
				"name" => $arFriends["NAME"],
			);
	}
	
	$arResult["User"]["PERSONAL_PHOTO_FILE"] = CFile::GetFileArray($arResult["User"]["PERSONAL_PHOTO"]);
	if ($arResult["User"]["PERSONAL_PHOTO_FILE"] !== false)
		$arResult["User"]["PERSONAL_PHOTO_IMG"] = CFile::ShowImage($arResult["User"]["PERSONAL_PHOTO_FILE"], 150, 150, "border=0", "", true);
		
	$arResult["User"]["AVATAR_FILE"] = CFile::GetFileArray($arResult["User"]["AVATAR"]);
	if ($arResult["User"]["AVATAR_FILE"] !== false)
	{
		$arResult["User"]["Avatar_resized"] = CFile::ResizeImageGet(
					$arResult["User"]["AVATAR_FILE"],
					array("width" => 100, "height" => 100),
					BX_RESIZE_IMAGE_EXACT,
					false
				);

		$arResult["User"]["AVATAR_IMG"] = CFile::ShowImage($arResult["User"]["Avatar_resized"]["src"], 100, 100, "border=0");
	}
	// ********************* User properties ***************************************************
	$arResult["USER_PROPERTIES"] = array("SHOW" => "N");
	if (!empty($arParams["USER_PROPERTY"]))
	{
		$arUserFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", $arParams["ID"], LANGUAGE_ID);
		if (count($arParams["USER_PROPERTY"]) > 0)
		{
			foreach ($arUserFields as $FIELD_NAME => $arUserField)
			{
				if (!in_array($FIELD_NAME, $arParams["USER_PROPERTY"]))
					continue;
				$arUserField["EDIT_FORM_LABEL"] = $arUserField["EDIT_FORM_LABEL"] <> '' ? $arUserField["EDIT_FORM_LABEL"] : $arUserField["FIELD_NAME"];
				$arUserField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arUserField["EDIT_FORM_LABEL"]);
				$arUserField["~EDIT_FORM_LABEL"] = $arUserField["EDIT_FORM_LABEL"];
				$arResult["USER_PROPERTIES"]["DATA"][$FIELD_NAME] = $arUserField;
			}
		}
		if (!empty($arResult["USER_PROPERTIES"]["DATA"]))
			$arResult["USER_PROPERTIES"]["SHOW"] = "Y";
		$arResult["bVarsFromForm"] = $strErrorMessage <> '' ? true : false;
	}
	// ******************** /User properties ***************************************************
}

$this->IncludeComponentTemplate();
?>