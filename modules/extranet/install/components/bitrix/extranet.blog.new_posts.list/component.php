<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));
	return;
}

if (!CModule::IncludeModule("extranet"))
{
	ShowError(GetMessage("EXTRANET_MODULE_NOT_INSTALL"));
	return;
}

$arParams["MESSAGES_PER_PAGE"] = IntVal($arParams["MESSAGES_PER_PAGE"])>0 ? IntVal($arParams["MESSAGES_PER_PAGE"]): 15;
$arParams["PREVIEW_WIDTH"] = IntVal($arParams["PREVIEW_WIDTH"])>0 ? IntVal($arParams["PREVIEW_WIDTH"]): 100;
$arParams["PREVIEW_HEIGHT"] = IntVal($arParams["PREVIEW_HEIGHT"])>0 ? IntVal($arParams["PREVIEW_HEIGHT"]): 100;
$arParams["SORT_BY1"] = (strlen($arParams["SORT_BY1"])>0 ? $arParams["SORT_BY1"] : "DATE_PUBLISH");
$arParams["SORT_ORDER1"] = (strlen($arParams["SORT_ORDER1"])>0 ? $arParams["SORT_ORDER1"] : "DESC");
$arParams["SORT_BY2"] = (strlen($arParams["SORT_BY2"])>0 ? $arParams["SORT_BY2"] : "ID");
$arParams["SORT_ORDER2"] = (strlen($arParams["SORT_ORDER2"])>0 ? $arParams["SORT_ORDER2"] : "DESC");
$arParams["MESSAGE_LENGTH"] = (IntVal($arParams["MESSAGE_LENGTH"])>0)?$arParams["MESSAGE_LENGTH"]:100;
$arParams["BLOG_URL"] = preg_replace("/[^a-zA-Z0-9_-]/is", "", Trim($arParams["BLOG_URL"]));
$arParams["GROUP_ID"] = IntVal($arParams["GROUP_ID"]);
// activation rating
CRatingsComponentsMain::GetShowRating($arParams);

if ($arParams["CACHE_TYPE"] == "Y" || ($arParams["CACHE_TYPE"] == "A" && COption::GetOptionString("main", "component_cache_on", "Y") == "Y"))
	$arParams["CACHE_TIME"] = intval($arParams["CACHE_TIME"]);
else
	$arParams["CACHE_TIME"] = 0;
$arParams["DATE_TIME_FORMAT"] = trim(empty($arParams["DATE_TIME_FORMAT"]) ? $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")) : $arParams["DATE_TIME_FORMAT"]);

if(strLen($arParams["BLOG_VAR"])<=0)
	$arParams["BLOG_VAR"] = "blog";
if(strLen($arParams["PAGE_VAR"])<=0)
	$arParams["PAGE_VAR"] = "page";
if(strLen($arParams["USER_VAR"])<=0)
	$arParams["USER_VAR"] = "id";
if(strLen($arParams["POST_VAR"])<=0)
	$arParams["POST_VAR"] = "id";
if(strLen($arParams["CATEGORY_NAME_VAR"])<=0)
	$arParams["CATEGORY_NAME_VAR"] = "category_name";

if (array_key_exists($arParams["CATEGORY_NAME_VAR"], $_REQUEST))
	$arParams["CATEGORY"] = htmlspecialcharsback(urldecode($_REQUEST[$arParams["CATEGORY_NAME_VAR"]]));

$arParams["PATH_TO_BLOG"] = trim($arParams["PATH_TO_BLOG"]);
if(strlen($arParams["PATH_TO_BLOG"])<=0)
	$arParams["PATH_TO_BLOG"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#");

$arParams["PATH_TO_SMILE"] = strlen(trim($arParams["PATH_TO_SMILE"]))<=0 ? false : trim($arParams["PATH_TO_SMILE"]);

$arParams["PATH_TO_POST"] = trim($arParams["PATH_TO_POST"]);
if(strlen($arParams["PATH_TO_POST"])<=0)
	$arParams["PATH_TO_POST"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=post&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if(strlen($arParams["PATH_TO_USER"])<=0)
	$arParams["PATH_TO_USER"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");

$arParams["PATH_TO_BLOG_CATEGORY"] = trim($arParams["PATH_TO_BLOG_CATEGORY"]);
if(strlen($arParams["PATH_TO_BLOG_CATEGORY"])<=0)
	$arParams["PATH_TO_BLOG_CATEGORY"] = htmlspecialcharsbx($APPLICATION->GetCurPage()."?".$arParams["CATEGORY_NAME_VAR"]."=#category_name#");

if (strlen(trim($arParams["NAME_TEMPLATE"])) <= 0)
	$arParams["NAME_TEMPLATE"] = CSite::GetNameFormat();
$arParams['SHOW_LOGIN'] = $arParams['SHOW_LOGIN'] != "N" ? "Y" : "N";

if (IsModuleInstalled('intranet') && !array_key_exists("PATH_TO_CONPANY_DEPARTMENT", $arParams))
	$arParams["PATH_TO_CONPANY_DEPARTMENT"] = "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#";

if (IsModuleInstalled("video"))
	if(!isset($arParams["PATH_TO_VIDEO_CALL"]))
		$arParams["PATH_TO_VIDEO_CALL"] = $arParams["~PATH_TO_VIDEO_CALL"] = "/extranet/contacts/personal/video/#user_id#/";

if (IsModuleInstalled("socialnetwork")):
	if(!isset($arParams["PATH_TO_MESSAGES_CHAT"]) && IsModuleInstalled("intranet")):
		$arParams["PATH_TO_MESSAGES_CHAT"] = "/extranet/contacts/personal/messages/chat/#user_id#/";
	elseif(!isset($arParams["PATH_TO_MESSAGES_CHAT"])):
		$arParams["PATH_TO_MESSAGES_CHAT"] = "/club/messages/chat/#user_id#/";
	endif;
	$arParams["~PATH_TO_MESSAGES_CHAT"] = $arParams["PATH_TO_MESSAGES_CHAT"];

	if(!isset($arParams["PATH_TO_SONET_USER_PROFILE"]) && IsModuleInstalled("intranet")):
		$arParams["PATH_TO_SONET_USER_PROFILE"] = "/extranet/contacts/personal/user/#user_id#/";
	elseif(!isset($arParams["PATH_TO_SONET_USER_PROFILE"])):
		$arParams["PATH_TO_SONET_USER_PROFILE"] = "/club/user/#user_id#/";
	endif;

endif;

if($arParams["SET_TITLE"]=="Y")
	$APPLICATION->SetTitle(GetMessage("EBNPL_TITLE"));

$cache = new CPHPCache;
$cache_id = "blog_last_messages_".serialize($arParams)."_".$USER->GetID()."_".CDBResult::NavStringForCache($arParams["BLOG_COUNT"])."_extranet";
if(($tzOffset = CTimeZone::GetOffset()) <> 0)
	$cache_id .= "_".$tzOffset;
$cache_path = "/".SITE_ID."/blog/last_messages_list/";

if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
{
	$Vars = $cache->GetVars();
	foreach($Vars["arResult"] as $k=>$v)
		$arResult[$k] = $v;
	CBitrixComponentTemplate::ApplyCachedData($Vars["templateCachedData"]);
	$cache->Output();
}
else
{
	if ($arParams["CACHE_TIME"] > 0)
		$cache->StartDataCache($arParams["CACHE_TIME"], $cache_id, $cache_path);

	$arFilter = Array(
			"<=DATE_PUBLISH" => ConvertTimeStamp(time()+$tzOffset, "FULL", false),
			"PUBLISH_STATUS" => BLOG_PUBLISH_STATUS_PUBLISH,
			"BLOG_ACTIVE" => "Y",
			"BLOG_GROUP_SITE_ID" => SITE_ID,
			">PERMS" => BLOG_PERMS_DENY
		);
	if(strlen($arParams["BLOG_URL"]) > 0)
		$arFilter["BLOG_URL"] = $arParams["BLOG_URL"];
	if(IntVal($arParams["GROUP_ID"]) > 0)
		$arFilter["BLOG_GROUP_ID"] = $arParams["GROUP_ID"];

	if(strlen($arParams["CATEGORY"]) > 0)
	{
		$arFilter["CATEGORY_ID_F"] = array();
		$arFilterCategory = Array("NAME" => $arParams["CATEGORY"]);
		$arSelectedFieldsCategory = Array("ID");
		$dbCategory = CBlogCategory::GetList(array(), $arFilterCategory, false, false, $arSelectedFieldsCategory);
		while ($arCategory = $dbCategory->Fetch())
			$arFilter["CATEGORY_ID_F"][] = $arCategory["ID"];
	}

	if($USER->IsAdmin())
		unset($arFilter[">PERMS"]);

	if(CModule::IncludeModule("socialnetwork") && IntVal($arParams["SOCNET_GROUP_ID"]) <= 0 && IntVal($arParams["USER_ID"]) <= 0)
	{
		unset($arFilter[">PERMS"]);
		$cacheSoNet = new CPHPCache;
		$cache_idSoNet = "blog_sonet_".SITE_ID."_".$USER->GetID()."_extranet";
		$cache_pathSoNet = "/".SITE_ID."/blog/sonet/";

		if ($arParams["CACHE_TIME"] > 0 && $cacheSoNet->InitCache($arParams["CACHE_TIME"], $cache_idSoNet, $cache_pathSoNet))
		{
			$Vars = $cacheSoNet->GetVars();
			$arAvBlog = $Vars["arAvBlog"];
			CBitrixComponentTemplate::ApplyCachedData($Vars["templateCachedData"]);
			$cacheSoNet->Output();
		}
		else
		{
			if ($arParams["CACHE_TIME"] > 0)
				$cacheSoNet->StartDataCache($arParams["CACHE_TIME"], $cache_idSoNet, $cache_pathSoNet);

			$arUsersInMyGroupsID = CExtranet::GetMyGroupsUsers(SITE_ID);
			$arUsersInMyGroupsID[] = $USER->GetID();
			$arPublicUsersID = CExtranet::GetPublicUsers();
			$arUsersToFilter = array_merge($arUsersInMyGroupsID, $arPublicUsersID);

			$arAvBlog = Array();

			$arFilterTmp = Array("ACTIVE" => "Y", "GROUP_SITE_ID" => SITE_ID);
			if(IntVal($arParams["GROUP_ID"]) > 0)
				$arFilterTmp["GROUP_ID"] = $arParams["GROUP_ID"];

			$dbBlog = CBlog::GetList(Array(), $arFilterTmp);

			while($arBlog = $dbBlog->Fetch())
			{
				if(IntVal($arBlog["SOCNET_GROUP_ID"]) > 0)
				{
					if (CSocNetFeaturesPerms::CanPerformOperation($USER->GetID(), SONET_ENTITY_GROUP, $arBlog["SOCNET_GROUP_ID"], "blog", "view_post"))
						$arAvBlog[] = $arBlog["ID"];
				}
				else
				{
					if (in_array($arBlog["OWNER_ID"], $arUsersToFilter) && CSocNetFeaturesPerms::CanPerformOperation($USER->GetID(), SONET_ENTITY_USER, $arBlog["OWNER_ID"], "blog", "view_post"))
						$arAvBlog[] = $arBlog["ID"];
				}
			}
			if ($arParams["CACHE_TIME"] > 0)
				$cacheSoNet->EndDataCache(array("templateCachedData" => $this->GetTemplateCachedData(), "arAvBlog" => $arAvBlog));
		}
		$arFilter["BLOG_ID"] = $arAvBlog;
	}


	if(!empty($arFilter["BLOG_ID"]))
	{

		$arSelectedFields = array("ID", "BLOG_ID", "TITLE", "DATE_PUBLISH", "AUTHOR_ID", "DETAIL_TEXT", "BLOG_ACTIVE", "BLOG_URL", "BLOG_GROUP_ID", "BLOG_GROUP_SITE_ID", "AUTHOR_LOGIN", "AUTHOR_NAME", "AUTHOR_LAST_NAME", "BLOG_USER_ALIAS", "BLOG_OWNER_ID", "VIEWS", "NUM_COMMENTS", "ATTACH_IMG", "BLOG_SOCNET_GROUP_ID", "DETAIL_TEXT_TYPE");

		$SORT = Array($arParams["SORT_BY1"]=>$arParams["SORT_ORDER1"], $arParams["SORT_BY2"]=>$arParams["SORT_ORDER2"]);

		if($arParams["MESSAGES_PER_PAGE"])
			$COUNT = array("nPageSize"=>$arParams["MESSAGES_PER_PAGE"], "bShowAll" => false);
		else
			$COUNT = false;

		$arResult = Array();

		$dbPosts = CBlogPost::GetList(
			$SORT,
			$arFilter,
			false,
			$COUNT,
			$arSelectedFields
		);
		$arResult["NAV_STRING"] = $dbPosts->GetPageNavString(GetMessage("B_B_GR_TITLE"), $arParams["NAV_TEMPLATE"]);

		$arResult["IDS"] = Array();

		$p = new blogTextParser(false, $arParams["PATH_TO_SMILE"]);
		while ($arPost = $dbPosts->GetNext())
		{
			$arResult["IDS"][] = $arPost["ID"];
			$arTmp = $arPost;

			if($arTmp["AUTHOR_ID"] == $arTmp["BLOG_OWNER_ID"])
			{
				$arTmp["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arPost["BLOG_URL"], "user_id" => $arPost["AUTHOR_ID"]));
			}
			else
			{
				$arOwnerBlog = CBlog::GetByOwnerID($arTmp["AUTHOR_ID"]);
				if(!empty($arOwnerBlog))
					$arTmp["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arOwnerBlog["URL"], "user_id" => $arOwnerBlog["OWNER_ID"]));
				else
					$arTmp["urlToBlog"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("blog" => $arPost["BLOG_URL"], "user_id" => $arPost["AUTHOR_ID"]));
			}

			if(IntVal($arPost["BLOG_SOCNET_GROUP_ID"]) > 0)
				$arTmp["urlToPost"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_BLOG_POST"], array("blog" => $arPost["BLOG_URL"], "post_id"=>$arPost["ID"], "group_id" => $arPost["BLOG_SOCNET_GROUP_ID"]));
			else
				$arTmp["urlToPost"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST"], array("blog" => $arPost["BLOG_URL"], "post_id"=>$arPost["ID"], "user_id" => $arPost["BLOG_OWNER_ID"]));

			$arTmp["urlToAuthor"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_USER"], array("user_id" => $arPost["AUTHOR_ID"]));

			$arTmp["AuthorName"] = CBlogUser::GetUserName($arPost["BLOG_USER_ALIAS"], $arPost["AUTHOR_NAME"], $arPost["AUTHOR_LAST_NAME"], $arPost["AUTHOR_LOGIN"]);

			$arImage = array();
			$res = CBlogImage::GetList(array("ID"=>"ASC"),array("POST_ID"=>$arPost['ID']));
			while ($arImage = $res->Fetch())
				$arImages[$arImage['ID']] = $arImage['FILE_ID'];

			if (preg_match("/(\[CUT\])/i",$arTmp['DETAIL_TEXT']) || preg_match("/(<CUT>)/i",$arTmp['DETAIL_TEXT']))
				$arTmp["CUT"] = "Y";

			if($arTmp["DETAIL_TEXT_TYPE"] == "html")
				$arTmp["TEXT_FORMATED"] = $p->convert($arTmp["~DETAIL_TEXT"], true, $arImages, array("HTML" => "Y", "ANCHOR" => "Y", "IMG" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "QUOTE" => "Y", "CODE" => "Y"));
			else
				$arTmp["TEXT_FORMATED"] = $p->convert($arTmp["~DETAIL_TEXT"], true, $arImages);
			$arTmp["IMAGES"] = $arImages;

			$arTmp["DATE_PUBLISH_FORMATED"] = date($arParams["DATE_TIME_FORMAT"], MakeTimeStamp($arTmp["DATE_PUBLISH"], CSite::GetDateFormat("FULL")));

			$dbCategory = CBlogPostCategory::GetList(Array("NAME" => "ASC"), Array("POST_ID" => $arTmp["ID"], "BLOG_ID" => $arPost["BLOG_ID"]));
			while($arCategory = $dbCategory->GetNext())
			{
				$arCategory["urlToCategory"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG_CATEGORY"], array("category_name" => urlencode($arCategory["NAME"])));
				$arTmp["CATEGORY"][] = $arCategory;
			}

			$arResult["POSTS"][] = $arTmp;
		}

		if($arParams["SHOW_RATING"] == "Y" && !empty($arResult["IDS"]))
			$arResult["RATING"] = CRatings::GetRatingVoteResult("BLOG_POST", $arResult["IDS"]);

		if ($arParams["CACHE_TIME"] > 0)
			$cache->EndDataCache(array("templateCachedData" => $this->GetTemplateCachedData(), "arResult" => $arResult));
	}
}
$this->IncludeComponentTemplate();
?>