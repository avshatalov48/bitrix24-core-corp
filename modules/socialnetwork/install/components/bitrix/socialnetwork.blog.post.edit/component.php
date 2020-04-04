<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @global CCacheManager $CACHE_MANAGER */
/** @global CUserTypeManager $USER_FIELD_MANAGER */

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Loader;
use Bitrix\Socialnetwork\ComponentHelper;

global $CACHE_MANAGER, $USER_FIELD_MANAGER;

if (!Loader::includeModule("blog"))
{
	ShowError(GetMessage("BLOG_MODULE_NOT_INSTALL"));

	return false;
}

if (!Loader::includeModule("socialnetwork"))
{
	return false;
}

$feature = "blog";
$arParams["SOCNET_GROUP_ID"] = IntVal($arParams["SOCNET_GROUP_ID"]);
$arResult["bExtranetUser"] = (Loader::includeModule("extranet") && !CExtranet::IsIntranetUser());
$arResult["bExtranetSite"] = (Loader::includeModule("extranet") && CExtranet::IsExtranetSite());
$arResult["ERROR_MESSAGE"] = "";

$arParams["ID"] = IntVal($arParams["ID"]);
$arParams["LAZY_LOAD"] = 'Y';

$arResult["SHOW_FULL_FORM"] = (
	(
		!empty($_POST)
		&& (
			!isset($_POST["TYPE"])
			|| $_POST["TYPE"] != "AUTH"
		)
	)
	|| $arParams["ID"] > 0
	|| !empty($_REQUEST["WFILES"])
	|| !empty($_REQUEST["bp_setting"])
	|| (
		!empty($arParams["PAGE_ID"])
		&& in_array($arParams["PAGE_ID"], array('user_blog_post_edit_profile', 'user_blog_post_edit_grat'))
	)
);

$arResult["ALLOW_EMAIL_INVITATION"] = (
	ModuleManager::isModuleInstalled('mail')
	&& ModuleManager::isModuleInstalled('intranet')
	&& (
		!Loader::includeModule('bitrix24')
		|| \CBitrix24::isEmailConfirmed()
	)
);

$bCalendar = true;
if (!ModuleManager::isModuleInstalled('intranet')) // Disable calendar feature for non cp
{
	$bCalendar = false;
}

if ($bCalendar && $arResult["bExtranetUser"]) // Disable calendar feature for extranet users
{
	$bCalendar = false;
}

if(IntVal($arParams["SOCNET_GROUP_ID"]) > 0)
{
	$bCalendar = false;
}
elseif (
	!CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
		SONET_ENTITY_USER,
		$USER->getId(),
		"calendar",
		"view"
	)
)
{
	$bCalendar = false;
}

$arParams["B_CALENDAR"] = $bCalendar;

$arResult["bGroupMode"] = false;

if (
	IntVal($arParams["SOCNET_GROUP_ID"]) > 0
	|| IntVal($arParams["USER_ID"]) > 0
)
{
	$arResult["bGroupMode"] = (IntVal($arParams["SOCNET_GROUP_ID"]) > 0);

	if($arResult["bGroupMode"])
	{
		if($arGroupSoNet = CSocNetGroup::GetByID($arParams["SOCNET_GROUP_ID"]))
		{
			if(!CSocNetFeatures::IsActiveFeature(SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], $feature))
			{
				ShowError(GetMessage("BLOG_SONET_GROUP_MODULE_NOT_AVAIBLE"));
				return false;
			}
		}
		else
		{
			return false;
		}
	}
}

if (!is_array($arParams["GROUP_ID"]))
{
	$arParams["GROUP_ID"] = array($arParams["GROUP_ID"]);
}

foreach ($arParams["GROUP_ID"] as $k=>$v)
{
	if (IntVal($v) <= 0)
	{
		unset($arParams["GROUP_ID"][$k]);
	}
}

if (empty($arParams["GROUP_ID"]))
{
	$tmpVal = COption::GetOptionString("socialnetwork", "sonet_blog_group", false, SITE_ID);
	if ($tmpVal)
	{
		$arTmpVal = unserialize($tmpVal);
		if (is_array($arTmpVal))
		{
			$arParams["GROUP_ID"] = $arTmpVal;
		}
		elseif(intval($tmpVal) > 0)
		{
			$arParams["GROUP_ID"] = array($arTmpVal);
		}
	}
}
else
{
	$tmpVal = COption::GetOptionString("socialnetwork", "sonet_blog_group", false, SITE_ID);
	if (!$tmpVal)
	{
		COption::SetOptionString("socialnetwork", "sonet_blog_group", serialize($arParams["GROUP_ID"]), false, SITE_ID);
	}
}

if(strLen($arParams["BLOG_VAR"])<=0)
	$arParams["BLOG_VAR"] = "blog";
if(strLen($arParams["PAGE_VAR"])<=0)
	$arParams["PAGE_VAR"] = "page";
if(strLen($arParams["USER_VAR"])<=0)
	$arParams["USER_VAR"] = "id";
if(strLen($arParams["POST_VAR"])<=0)
	$arParams["POST_VAR"] = "id";

$applicationCurPage = $APPLICATION->GetCurPage();
$arParams["PATH_TO_BLOG"] = trim($arParams["PATH_TO_BLOG"]);
if(strlen($arParams["PATH_TO_BLOG"])<=0)
	$arParams["PATH_TO_BLOG"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=blog&".$arParams["BLOG_VAR"]."=#blog#");

$arParams["PATH_TO_BLOG"] = CHTTP::urlDeleteParams($arParams["PATH_TO_BLOG"], array("WFILES"));

$arParams["PATH_TO_POST"] = trim($arParams["PATH_TO_POST"]);
if(strlen($arParams["PATH_TO_POST"])<=0)
	$arParams["PATH_TO_POST"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=post&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_POST_EDIT"] = trim($arParams["PATH_TO_POST_EDIT"]);
if(strlen($arParams["PATH_TO_POST_EDIT"])<=0)
	$arParams["PATH_TO_POST_EDIT"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=post_edit&".$arParams["BLOG_VAR"]."=#blog#&".$arParams["POST_VAR"]."=#post_id#");

$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if(strlen($arParams["PATH_TO_USER"])<=0)
	$arParams["PATH_TO_USER"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=user&".$arParams["USER_VAR"]."=#user_id#");

$arParams["PATH_TO_DRAFT"] = trim($arParams["PATH_TO_DRAFT"]);
if(strlen($arParams["PATH_TO_DRAFT"])<=0)
	$arParams["PATH_TO_DRAFT"] = htmlspecialcharsbx($applicationCurPage."?".$arParams["PAGE_VAR"]."=draft&".$arParams["BLOG_VAR"]."=#blog#");
$arParams["PATH_TO_GROUP_BLOG"] = trim($arParams["PATH_TO_GROUP_BLOG"]);
if(strlen($arParams["PATH_TO_GROUP_BLOG"])<=0)
	$arParams["PATH_TO_GROUP_BLOG"] = "/workgroups/group/#group_id#/blog/";
if(strlen($arParams["PATH_TO_GROUP_POST"])<=0)
	$arParams["PATH_TO_GROUP_POST"] = "/workgroups/group/#group_id#/blog/#post_id#/";
if(strlen($arParams["PATH_TO_GROUP_POST_EDIT"])<=0)
	$arParams["PATH_TO_GROUP_POST_EDIT"] = "/workgroups/group/#group_id#/blog/edit/#post_id#/";
if(strlen($arParams["PATH_TO_GROUP_DRAFT"])<=0)
	$arParams["PATH_TO_GROUP_DRAFT"] = "/workgroups/group/#group_id#/blog/draft/";
$arParams["PATH_TO_SMILE"] = strlen(trim($arParams["PATH_TO_SMILE"]))<=0 ? false : trim($arParams["PATH_TO_SMILE"]);
$arParams["DATE_TIME_FORMAT"] = trim(empty($arParams["DATE_TIME_FORMAT"]) ? $DB->DateFormatToPHP(CSite::GetDateFormat("FULL")) : $arParams["DATE_TIME_FORMAT"]);

$arParams["USE_CUT"] = ($arParams["USE_CUT"] == "Y") ? "Y" : "N";

$arParams["EDITOR_RESIZABLE"] = $arParams["EDITOR_RESIZABLE"] !== "N";
$arParams["EDITOR_CODE_DEFAULT"] = $arParams["EDITOR_CODE_DEFAULT"] === "Y";
$arParams["EDITOR_DEFAULT_HEIGHT"] = intVal($arParams["EDITOR_DEFAULT_HEIGHT"]);
if(IntVal($arParams["EDITOR_DEFAULT_HEIGHT"]) <= 0)
	$arParams["EDITOR_DEFAULT_HEIGHT"] = '120px';

$user_id = $USER->GetID();
$arResult["UserID"] = $user_id;
$arResult["allowVideo"] = COption::GetOptionString("blog","allow_video", "Y");

$arParams["ALLOW_POST_CODE"] = $arParams["ALLOW_POST_CODE"] !== "N";
$arParams["USE_GOOGLE_CODE"] = $arParams["USE_GOOGLE_CODE"] === "Y";
$arParams["IMAGE_MAX_WIDTH"] = 400;
$arParams["IMAGE_MAX_HEIGHT"] = 400;

$arParams["POST_PROPERTY_SOURCE"] = $arParams["POST_PROPERTY"] = (is_array($arParams["POST_PROPERTY"]) ? $arParams["POST_PROPERTY"] : array($arParams["POST_PROPERTY"]));
$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_DOC";
$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_IMPRTNT";
$arParams["POST_PROPERTY"][] = "UF_IMPRTANT_DATE_END";

if(
	Loader::includeModule("webdav")
	|| Loader::includeModule("disk")
)
{
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_FILE";
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_F_EDIT";
}
if (ModuleManager::isModuleInstalled("vote"))
{
	$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_VOTE";
}
$arParams["POST_PROPERTY"][] = "UF_BLOG_POST_URL_PRV";

$arResult['BLOG_POST_LISTS'] = (
	Loader::includeModule("lists")
	&& CLists::isFeatureEnabled()
	&& !$arResult["bExtranetSite"]
	&& !$arParams["SOCNET_GROUP_ID"]
	&& ModuleManager::isModuleInstalled('intranet')
);

$arResult['BLOG_POST_TASKS'] = ModuleManager::isModuleInstalled("tasks");

if (
	$arResult['BLOG_POST_TASKS']
	&& !CSocNetFeaturesPerms::CurrentUserCanPerformOperation(
		$arResult["bGroupMode"] ? SONET_ENTITY_GROUP : SONET_ENTITY_USER,
		$arResult["bGroupMode"] ? $arParams["SOCNET_GROUP_ID"] : $USER->getId(),
		"tasks",
		"create_tasks"
	)
)
{
	$arResult['BLOG_POST_TASKS'] = false;
}

if (
	$arResult['BLOG_POST_TASKS']
	&& Loader::includeModule('bitrix24')
	&& !\CBitrix24BusinessTools::isToolAvailable($USER->getId(), 'tasks')
)
{
	$arResult['BLOG_POST_TASKS'] = false;
}

if (
	$arResult['BLOG_POST_TASKS']
	&& $arResult["bGroupMode"]
	&& ($arUserActiveFeatures = CSocNetFeatures::GetActiveFeatures(SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"]))
	&& is_array($arUserActiveFeatures)
	&& !in_array('tasks', $arUserActiveFeatures)
)
{
	$arResult['BLOG_POST_TASKS'] = false;
}

$arResult["SELECTOR_VERSION"] = (!empty($arParams["SELECTOR_VERSION"]) ? intval($arParams["SELECTOR_VERSION"]) : 1);

$a = new CAccess;
$a->UpdateCodes();

$arResult["perms"] = BLOG_PERMS_DENY;
if($arResult["bGroupMode"])
{
	if (
		CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "full_post", CSocNetUser::IsCurrentUserModuleAdmin())
		|| $APPLICATION->GetGroupRight("blog") >= "W"
	)
	{
		$arResult["perms"] = BLOG_PERMS_FULL;
	}
	elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "moderate_post"))
	{
		$arResult["perms"] = BLOG_PERMS_MODERATE;
	}
	elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "write_post"))
	{
		$arResult["perms"] = BLOG_PERMS_WRITE;
	}
	elseif (CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_GROUP, $arParams["SOCNET_GROUP_ID"], "blog", "premoderate_post"))
	{
		$arResult["perms"] = BLOG_PERMS_PREMODERATE;
	}
}
elseif (
	$arParams["USER_ID"] == $user_id
	|| $APPLICATION->GetGroupRight("blog") >= "W"
	|| CSocNetFeaturesPerms::CanPerformOperation($user_id, SONET_ENTITY_USER, $arParams["USER_ID"], "blog", "full_post", CSocNetUser::IsCurrentUserModuleAdmin())
)
{
	$arResult["perms"] = BLOG_PERMS_FULL;
}

$arBlog = \Bitrix\Blog\Item\Blog::getByUser(array(
	"GROUP_ID" => $arParams["GROUP_ID"],
	"SITE_ID" => SITE_ID,
	"USER_ID" => $arParams["USER_ID"],
	"USE_SOCNET" => "Y"
));

$arResult["Blog"] = $arBlog;

$arResult["urlToBlog"] = CComponentEngine::MakePathFromTemplate(
	($arResult["bGroupMode"] ? $arParams["PATH_TO_GROUP_BLOG"] : $arParams["PATH_TO_BLOG"]),
	array("blog" => $arBlog["URL"], "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"])
);

$arPostFields = $USER_FIELD_MANAGER->GetUserFields("BLOG_POST", $arParams["ID"], LANGUAGE_ID);
$arResult["POST_PROPERTIES"] = array("SHOW" => "N", "DATA" => array());

$arParams["CACHE_TIME"] = defined("BX_COMP_MANAGED_CACHE") ? 3600*24*365 : 3600*24;
$arResult["PostToShow"]["GRATS"] = array();
$arResult["PostToShow"]["GRATS_DEF"] = false;

$cache = new CPHPCache;
$cache_id = "blog_post_grats_".SITE_ID;
$cache_path = "/blog/form/post/new";

if ($arParams["CACHE_TIME"] > 0 && $cache->InitCache($arParams["CACHE_TIME"], $cache_id, $cache_path))
{
	$Vars = $cache->GetVars();
	$arResult["PostToShow"]["GRATS"] = $Vars["GRATS"];
	$arResult["PostToShow"]["GRATS_DEF"] = $Vars["GRATS_DEF"];
	$honour_iblock_id = $Vars["GRATS_IBLOCK_ID"];
}
else
{
	$honour_iblock_id = 0;
	$cache->StartDataCache();
	if (
		(
			!empty($arParams["POST_PROPERTY"])
			|| ModuleManager::isModuleInstalled("intranet")
		)
		&& !$arResult["bExtranetSite"]
		&& Loader::includeModule("iblock")
	)
	{
		$rsIBlock = CIBlock::GetList(array(), array("CODE" => "honour", "TYPE" => "structure"));
		if ($arIBlock = $rsIBlock->Fetch())
		{
			$honour_iblock_id = $arIBlock["ID"];

			if (defined("BX_COMP_MANAGED_CACHE"))
				$CACHE_MANAGER->StartTagCache($cache_path);

			$rsIBlockPropertyEnum = CIBlockPropertyEnum::GetList(
				array(
					"SORT" => "ASC",
					"XML_ID" => "ASC"
				),
				array(
					"CODE" => "GRATITUDE",
					"IBLOCK_ID" => $arIBlock["ID"]
				)
			);
			while($arIBlockPropertyEnum = $rsIBlockPropertyEnum->Fetch())
			{
				$arResult["PostToShow"]["GRATS"][] = $arIBlockPropertyEnum;
				if ($arIBlockPropertyEnum["DEF"] == "Y")
					$arResult["PostToShow"]["GRATS_DEF"] = $arIBlockPropertyEnum;
			}

			if(defined("BX_COMP_MANAGED_CACHE"))
				$CACHE_MANAGER->EndTagCache();
		}
	}
	$cache->EndDataCache(
		array(
			"GRATS" => $arResult["PostToShow"]["GRATS"],
			"GRATS_DEF" => $arResult["PostToShow"]["GRATS_DEF"],
			"GRATS_IBLOCK_ID" => $honour_iblock_id
		)
	);
}

if(
	$arParams["ID"] > 0
	&& $arPost = CBlogPost::GetByID($arParams["ID"])
)
{
	$arPost = CBlogTools::htmlspecialcharsExArray($arPost);

	$arPost['DETAIL_TEXT'] = preg_replace("/\[tag\](.+?)\[\/tag\]/is".BX_UTF_PCRE_MODIFIER, "\\1", $arPost['DETAIL_TEXT']);
	$arPost['~DETAIL_TEXT'] = preg_replace("/\[tag\](.+?)\[\/tag\]/is".BX_UTF_PCRE_MODIFIER, "\\1", $arPost['~DETAIL_TEXT']);

	$arResult["Post"] = $arPost;
	if($arParams["SET_TITLE"]=="Y")
	{
		$APPLICATION->SetTitle(GetMessage("BLOG_POST_EDIT"));
	}

	if(
		$arParams["USER_ID"] == $user_id
		|| (
			$_POST["apply"]
			&& CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
		)
		|| $APPLICATION->GetGroupRight("blog") >= "W"
	)
	{
		$arResult["perms"] = BLOG_PERMS_FULL;
	}
	else
	{
		$arResult["perms"] = CBlogPost::GetSocNetPostPerms($arPost["ID"], true, false, $arPost["AUTHOR_ID"]);
	}

	// Get UF_GRATITUDE
	if (
		ModuleManager::isModuleInstalled("intranet")
		&& Loader::includeModule("iblock")
		&& isset($arPostFields["UF_GRATITUDE"])
		&& is_array($arPostFields["UF_GRATITUDE"])
		&& intval($arPostFields["UF_GRATITUDE"]["VALUE"]) > 0
	)
	{
		if ($honour_iblock_id > 0)
		{
			$arGrat = array(
				"ID" => false,
				"USERS" => array(),
				"USERS_FOR_JS" => array(),
				"TYPE" => false
			);
			$rsElementProperty = CIBlockElement::GetProperty(
				$honour_iblock_id,
				$arPostFields["UF_GRATITUDE"]["VALUE"]
			);
			while ($arElementProperty = $rsElementProperty->Fetch())
			{
				if (!$arGrat["ID"])
					$arGrat["ID"] = htmlspecialcharsbx($arPostFields["UF_GRATITUDE"]["VALUE"]);

				if ($arElementProperty["CODE"] == "USERS")
					$arGrat["USERS"][] = htmlspecialcharsbx($arElementProperty["VALUE"]);
				elseif ($arElementProperty["CODE"] == "GRATITUDE")
					$arGrat["TYPE"] = array(
						"VALUE_ENUM" => $arElementProperty["VALUE_ENUM"],
						"XML_ID" => $arElementProperty["VALUE_XML_ID"]
					);
			}
			if ($arGrat["ID"])
			{
				$dbUsers = CUser::GetList(
					($sort_by = Array('last_name'=>'asc', 'IS_ONLINE'=>'desc')),
					($dummy=''),
					array(
						"ID" => implode("|", $arGrat["USERS"]),
						array(
							"FIELDS" => array("ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "WORK_POSITION", "PERSONAL_PROFESSION")
						)
					)
				);

				while($arGratUser = $dbUsers->Fetch())
				{
					$sName = trim(CUser::FormatName(empty($arParams["NAME_TEMPLATE"]) ? CSite::GetNameFormat(false) : $arParams["NAME_TEMPLATE"], $arGratUser));
					$arGrat["USERS_FOR_JS"]["U".$arGratUser["ID"]] = array(
						"id" => "U".$arGratUser["ID"],
						"entityId" => $arGratUser["ID"],
						"name" => $sName,
						"avatar" => "",
						"desc" => $arGratUser["WORK_POSITION"] ? $arGratUser["WORK_POSITION"] : ($arGratUser["PERSONAL_PROFESSION"] ? $arGratUser["PERSONAL_PROFESSION"] : "&nbsp;")
					);
				}

				$arResult["PostToShow"]["GRAT_CURRENT"] = $arGrat;
			}
		}
	}
}
else
{
	$arParams["ID"] = 0;
	if($arParams["SET_TITLE"] == "Y")
	{
		$APPLICATION->SetTitle(GetMessage("BLOG_NEW_MESSAGE"));
	}
}

if (IntVal($_GET["delete_blog_post_id"]) > 0 && $_GET["ajax_blog_post_delete"] == "Y")
{
	if (check_bitrix_sessid())
	{
		$delId = IntVal($_GET["delete_blog_post_id"]);
		if($arPost = CBlogPost::GetByID($delId))
		{
			$perms = (
				$arPost["AUTHOR_ID"] == $user_id
					? Bitrix\Blog\Item\Permissions::FULL
					: CBlogPost::GetSocNetPostPerms($_GET["delete_blog_post_id"], true)
			);

			if (
				$perms < Bitrix\Blog\Item\Permissions::FULL
				&& (
					CSocNetUser::isCurrentUserModuleAdmin()
					|| $APPLICATION->getGroupRight("blog") >= "W"
				)
			)
			{
				$perms = Bitrix\Blog\Item\Permissions::FULL;
			}

			if($perms >= Bitrix\Blog\Item\Permissions::FULL)
			{
				CBlogPost::DeleteLog($delId);
				BXClearCache(True, ComponentHelper::getBlogPostCacheDir(array(
					'TYPE' => 'posts_popular',
					'SITE_ID' => SITE_ID
				)));
				BXClearCache(true, ComponentHelper::getBlogPostCacheDir(array(
					'TYPE' => 'post',
					'POST_ID' => $delId
				)));
				BXClearCache(true, ComponentHelper::getBlogPostCacheDir(array(
					'TYPE' => 'post_general',
					'POST_ID' => $delId
				)));
				BXClearCache(true, ComponentHelper::getBlogPostCacheDir(array(
					'TYPE' => 'posts_last_blog',
					'SITE_ID' => SITE_ID
				)));
				BXClearCache(true, CComponentEngine::MakeComponentPath("bitrix:socialnetwork.blog.blog"));

				if (!CBlogPost::Delete($delId))
				{
					$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_ERROR");
				}
				else
				{
					$arResult["OK_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_OK");
				}
			}
			else
			{
				$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_NO_RIGHTS");
			}
		}
		else
		{
			$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_BLOG_MES_DEL_ERROR");
		}
	}
	else
	{
		$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BLOG_SESSID_WRONG");
	}

	$arResult["delete_blog_post"] = "Y";
	$this->IncludeComponentTemplate();

	return true;
}

/*if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['mfi_mode']) && ($_REQUEST['mfi_mode'] == "upload"))
{
	CBlogImage::AddImageResizeHandler(array(
		'width' => $arParams["IMAGE_MAX_WIDTH"],
		'height' => $arParams["IMAGE_MAX_HEIGHT"]
	));
}*/
$isPostBeingEdited = $arParams["ID"] > 0;
if ($isPostBeingEdited)
{
	$periodsOfShowingImportantPost = ["ALWAYS", "CUSTOM"];
}
else
{
	$periodsOfShowingImportantPost = ["ALWAYS", "ONE_DAY", "TWO_DAYS", "WEEK", "MONTH", "CUSTOM"];
}
$arResult["REMAIN_IMPORTANT_TILL"] = [];
foreach ($periodsOfShowingImportantPost as $period)
{
	$attributesForPopupList = [
		"VALUE" => $period,
		"TEXT_KEY"  => ("IMPORTANT_FOR_$period"),
	];
	$arResult["REMAIN_IMPORTANT_TILL"][] = $attributesForPopupList;
}
if (
	(
		$arParams["ID"] == 0
		&& $arResult["perms"] >= BLOG_PERMS_PREMODERATE
	)
	|| (
		$arParams["ID"] > 0
		&& $arResult["perms"] >= BLOG_PERMS_FULL
		&& $arPost["BLOG_ID"] == $arBlog["ID"]
	)
)
{
	$arP = Array();
	if (
		IntVal($arParams["ID"]) > 0
		&& $arPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_READY
		&& $arPost["AUTHOR_ID"] == $user_id
	)
	{
		$arResult["OK_MESSAGE"] = GetMessage("BPE_HIDDEN_POSTED");
	}

	$bAllowToAll = ComponentHelper::getAllowToAllDestination();

	$bDefaultToAll = (
		$bAllowToAll
			? (COption::GetOptionString("socialnetwork", "default_livefeed_toall", "Y") == "Y")
			: false
	);

	if (
		($_POST["apply"] || $_POST["save"] || $_POST["do_upload"] || $_POST["draft"])
		&& (!isset($_POST["changePostFormTab"]) || $_POST["changePostFormTab"] != 'tasks')
	)
	{
		if(check_bitrix_sessid())
		{
			if ($_POST["decode"] == "Y")
			{
				CUtil::JSPostUnescape();
			}

			if (
				empty($arBlog)
				&& !empty($arParams["GROUP_ID"])
			)
			{
				$arBlog = ComponentHelper::createUserBlog(array(
					"BLOG_GROUP_ID" => (is_array($arParams["GROUP_ID"])) ? IntVal($arParams["GROUP_ID"][0]) : IntVal($arParams["GROUP_ID"]),
					"USER_ID" => $arParams["USER_ID"],
					"SITE_ID" => SITE_ID,
					"PATH_TO_BLOG" => $arParams["PATH_TO_BLOG"]
				));

				if (!$arBlog)
				{
					$arResult["ERROR_MESSAGE"] .= GetMessage("B_B_MES_NO_BLOG");
				}
			}
		}
		else
		{
			$arResult["ERROR_MESSAGE"] .= GetMessage("BPE_SESS");
		}
	}

	if (
		$_GET["image_upload_frame"] == "Y"
		|| $_GET["image_upload"]
		|| $_POST["do_upload"]
		|| $_GET["del_image_id"]
	)
	{
		if (check_bitrix_sessid())
		{
			if(IntVal($_GET["del_image_id"]) > 0)
			{
				$del_image_id = IntVal($_GET["del_image_id"]);
				$aImg = CBlogImage::GetByID($del_image_id);
				if (
					$aImg["BLOG_ID"] == $arBlog["ID"]
					&& $aImg["POST_ID"] == IntVal($arParams["ID"])
				)
				{
					CBlogImage::Delete($del_image_id);
				}
				$APPLICATION->RestartBuffer();
				die();
			}
			else
			{
				$arResult["imageUploadFrame"] = "Y";
				$arResult["imageUpload"] = "Y";
				$APPLICATION->RestartBuffer();
				header("Pragma: no-cache");

				$arFields = Array();
				if ($_FILES["BLOG_UPLOAD_FILE"]["size"] > 0)
				{
					$arFields = array(
						"BLOG_ID"	=> $arBlog["ID"],
						"POST_ID"	=> $arParams["ID"],
						"USER_ID"	=> $arResult["UserID"],
						"=TIMESTAMP_X"	=> $DB->GetNowFunction(),
						"TITLE"		=> $_POST["IMAGE_TITLE"],
						"IMAGE_SIZE"	=> $_FILES["BLOG_UPLOAD_FILE"]["size"]
					);
					$arImage=array_merge(
						$_FILES["BLOG_UPLOAD_FILE"],
						array(
							"MODULE_ID" => "blog",
							"del" => "Y"
						)
					);
					$arFields["FILE_ID"] = $arImage;
				}
				elseif ($_POST["do_upload"] && $_FILES["FILE_ID"]["size"] > 0)
				{
					$arFields = array(
						"BLOG_ID"	=> $arBlog["ID"],
						"POST_ID"	=> $arParams["ID"],
						"USER_ID"	=> $arResult["UserID"],
						"=TIMESTAMP_X"	=> $DB->GetNowFunction(),
						"TITLE"		=> $_POST["IMAGE_TITLE"],
						"IMAGE_SIZE"	=> $_FILES["FILE_ID"]["size"],
						"URL" => $arBlog["URL"],
					);
					$arImage=array_merge(
						$_FILES["FILE_ID"],
						array(
							"MODULE_ID" => "blog",
							"del" => "Y"
						)
					);
					$arFields["FILE_ID"] = $arImage;
				}
				if(!empty($arFields))
				{
					if ($imgID = CBlogImage::Add($arFields))
					{
						$aImg = CBlogImage::GetByID($imgID);
						$aImg = CBlogTools::htmlspecialcharsExArray($aImg);

						$aImgNew = CFile::ResizeImageGet(
							$aImg["FILE_ID"],
							array("width" => 90, "height" => 90),
							BX_RESIZE_IMAGE_EXACT,
							true
						);
						$aImg["source"] = CFile::ResizeImageGet(
							$aImg["FILE_ID"],
							array("width" => $arParams["IMAGE_MAX_WIDTH"], "height" => $arParams["IMAGE_MAX_HEIGHT"]),
							BX_RESIZE_IMAGE_PROPORTIONAL,
							true
						);
						$aImg["params"] = CFile::_GetImgParams($aImg["FILE_ID"]);
						$aImg["fileName"] = substr($aImgNew["src"], strrpos($aImgNew["src"], "/")+1);
						$file = "<img src=\"".$aImgNew["src"]."\" width=\"".$aImgNew["width"]."\" height=\"".$aImgNew["height"]."\" id=\"".$aImg["ID"]."\" border=\"0\" style=\"cursor:pointer\" onclick=\"InsertBlogImage_LHEPostFormId_blogPostForm('".$aImg["ID"]."', '".$aImg["source"]['src']."', '".$aImgNew["source"]['width']."');\" title=\"".GetMessage("BLOG_P_INSERT")."\">";

						$file = str_replace("'","\'",$file);
						$file = str_replace("\r"," ",$file);
						$file = str_replace("\n"," ",$file);
						$arResult["ImageModified"] = $file;
						$arResult["Image"] = $aImg;
					}
					elseif ($ex = $APPLICATION->GetException())
					{
						$arResult["ERROR_MESSAGE"] .= $ex->GetString();
					}
				}
			}
		}
	}
	else
	{
		$mapping = [
			'DEST_CODES' => 'SPERM',
			'GRAT_DEST_CODES' => 'GRAT',
			'EVENT_DEST_CODES' => 'EVENT_PERM'
		];

		foreach($mapping as $from => $to)
		{
			if (isset($_POST[$from]))
			{
				$_POST[$to] = [
					'UA' => [],
					'U' => [],
					'UE' => [],
					'SG' => [],
					'DR' => []
				];
				if ($from == 'DEST_CODES')
				{
					$_POST[$to]['UP'] = [];
				}

				foreach($_POST[$from] as $destCode)
				{
					if ($destCode == 'UA')
					{
						$_POST[$to]['UA'][] = 'UA';
					}
					elseif (preg_match('/^UE(.+)$/i', $destCode, $matches))
					{
						$_POST[$to]['UE'][] = $matches[1];
					}
					elseif (preg_match('/^U(\d+)$/i', $destCode, $matches))
					{
						$_POST[$to]['U'][] = 'U'.$matches[1];
					}
					elseif (
						$from == 'DEST_CODES'
						&& preg_match('/^UP(\d+)$/i', $destCode, $matches)
						&& $arResult["perms"] = BLOG_PERMS_FULL
					)
					{
						$_POST[$to]['UP'][] = 'UP'.$matches[1];
					}
					elseif (preg_match('/^SG(\d+)$/i', $destCode, $matches))
					{
						$_POST[$to]['SG'][] = 'SG'.$matches[1];
					}
					elseif (preg_match('/^DR(\d+)$/i', $destCode, $matches))
					{
						$_POST[$to]['DR'][] = 'DR'.$matches[1];
					}
				}
				unset($_POST[$from]);
			}
		}

		// Save calendar event from Socialnetwork live feed form
		if (
			$_POST["save"] == "Y"
			&& $_POST["changePostFormTab"] == "calendar"
			&& check_bitrix_sessid()
		)
		{
			if (isset($_POST['EVENT_PERM']))
			{
				$arAccessCodes = array();
				foreach($_POST["EVENT_PERM"] as $v => $k)
				{
					if(strlen($v) > 0 && is_array($k) && !empty($k))
					{
						foreach($k as $vv)
						{
							if(strlen($vv) > 0)
							{
								$arAccessCodes[] = $vv;
							}
						}
					}
				}
			}

			$rrule = $_POST['EVENT_RRULE'];
			if ($_POST['rrule_endson'] == 'never')
			{
				unset($rrule['COUNT']);
				unset($rrule['UNTIL']);
			}
			elseif ($_POST['rrule_endson'] == 'count')
			{
				unset($rrule['UNTIL']);
			}
			elseif ($_POST['rrule_endson'] == 'until')
			{
				unset($rrule['COUNT']);
			}

			$arFields = array(
				"ID" => intVal($_POST['EVENT_ID']),
				"DT_FROM_TS" => $_POST['EVENT_FROM_TS'], // For calendar < 16.x.x
				"DT_TO_TS" => $_POST['EVENT_TO_TS'], // For calendar < 16.x.x
				"DATE_FROM" => $_POST['DATE_FROM'],
				"DATE_TO" => $_POST['DATE_TO'],
				"TIME_FROM" => $_POST['TIME_FROM'],
				"TIME_TO" => $_POST['TIME_TO'],
				"TZ_FROM" => $_POST['TZ_FROM'],
				"TZ_TO" => $_POST['TZ_TO'],
				"DEFAULT_TZ" => $_POST['DEFAULT_TZ'],
				"SKIP_TIME" => $_POST['EVENT_FULL_DAY'] == 'Y',
				'NAME' => trim($_POST['EVENT_NAME']),
				'DESCRIPTION' => trim($_POST['EVENT_DESCRIPTION']),
				'SECTION' => intVal($_POST['EVENT_SECTION']),
				'ACCESSIBILITY' => $_POST['EVENT_ACCESSIBILITY'],
				'IMPORTANCE' => $_POST['EVENT_IMPORTANCE'],
				'RRULE' => $rrule,
				'LOCATION' => $_POST['EVENT_LOCATION'],
				"REMIND" => isset($_POST['EVENT_REMIND']) ? array(0 => array('count' => $_POST['EVENT_REMIND_COUNT'], 'type' => $_POST['EVENT_REMIND_TYPE'])) : null
			);

			// Userfields for event
			$arUFFields = array();
			foreach ($_POST as $field => $value)
			{
				if (substr($field, 0, 3) == "UF_")
				{
					$arUFFields[$field] = $value;
				}
			}

			CCalendarLiveFeed::EditCalendarEventEntry($arFields, $arUFFields, $arAccessCodes, array(
				'type' => 'user',
				'userId' => $arBlog["OWNER_ID"]
			));

			$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $arBlog["OWNER_ID"]));

			$uri = new Bitrix\Main\Web\Uri($redirectUrl);
			$uri->deleteParams(array("b24statAction", "b24statTab"));
			$redirectUrl = $uri->getUri();

			LocalRedirect($redirectUrl);
		}

		if (
			$_POST["save"] == "Y"
			&& $_POST["changePostFormTab"] == "lists"
			&& check_bitrix_sessid()
		)
		{
			$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $arBlog["OWNER_ID"]));

			$uri = new Bitrix\Main\Web\Uri($redirectUrl);
			$uri->deleteParams(array("b24statAction", "b24statTab"));
			$redirectUrl = $uri->getUri();

			LocalRedirect($redirectUrl);
		}

		if (
			(
				$_POST["apply"]
				|| $_POST["save"]
				|| $_POST["draft"]
			)
			&& empty($_POST["reset"])
			&& (!isset($_POST["changePostFormTab"]) || $_POST["changePostFormTab"] != 'tasks')
		) // Save on button click
		{
			if (check_bitrix_sessid())
			{
				if(strlen($arResult["ERROR_MESSAGE"]) <= 0)
				{
					$DB->StartTransaction();

					$CATEGORYtmp = array();
					if(!empty($_POST["TAGS"]))
					{
						$dbCategory = CBlogCategory::GetList(Array(), Array("BLOG_ID" => $arBlog["ID"]));
						while($arCategory = $dbCategory->Fetch())
						{
							$arCatBlog[ToLower($arCategory["NAME"])] = $arCategory["ID"];
						}
						$tags = explode (",", $_POST["TAGS"]);
						foreach($tags as $tg)
						{
							$tg = trim($tg);
							if(
								!in_array($arCatBlog[ToLower($tg)], $CATEGORYtmp)
								&& strlen($tg) > 0
							)
							{
								$CATEGORYtmp[] = (
									IntVal($arCatBlog[ToLower($tg)]) > 0
									? $arCatBlog[ToLower($tg)]
									: CBlogCategory::Add(array("BLOG_ID" => $arBlog["ID"], "NAME" => $tg))
								);
								$tagList[] = $tg;
							}
						}
					}
					elseif (!empty($_POST["CATEGORY_ID"]))
					{
						foreach($_POST["CATEGORY_ID"] as $v)
						{
							$CATEGORYtmp[] = (
								substr($v, 0, 4) == "new_"
									? \CBlogCategory::add(array(
											"BLOG_ID" => $arBlog["ID"],
											"NAME" => substr($v, 4)
										))
									: $v
							);
						}
					}
					else
					{
						$CATEGORY_ID = "";
					}

					$DATE_PUBLISH = "";
					if (strlen($_POST["DATE_PUBLISH_DEF"]) > 0)
					{
						$DATE_PUBLISH = $_POST["DATE_PUBLISH_DEF"];
					}
					elseif (strlen($_POST["DATE_PUBLISH"]) <= 0)
					{
						$DATE_PUBLISH = ConvertTimeStamp(time()+CTimeZone::GetOffset(), "FULL");
					}
					else
					{
						$DATE_PUBLISH = $_POST["DATE_PUBLISH"];
					}

					$PUBLISH_STATUS = (strlen($_POST["draft"]) > 0 ? BLOG_PUBLISH_STATUS_DRAFT : BLOG_PUBLISH_STATUS_PUBLISH);

					$arFields = array(
						"TITLE" => trim($_POST["POST_TITLE"]),
						"DETAIL_TEXT" => (isset($_POST["MOBILE"]) && $_POST["MOBILE"] == "Y" ? htmlspecialcharsEx($_POST['POST_MESSAGE']) : $_POST["POST_MESSAGE"]),
						"DETAIL_TEXT_TYPE" => "text",
						"DATE_PUBLISH" => $DATE_PUBLISH,
						"PUBLISH_STATUS" => $PUBLISH_STATUS,
						"PATH" => CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => "#post_id#", "user_id" => $arBlog["OWNER_ID"])),
						"URL" => $arBlog["URL"],
					);

					if(\Bitrix\Main\Config\Configuration::getValue("utf_mode") === true)
					{
						$conn = \Bitrix\Main\Application::getConnection();
						$table = \Bitrix\Blog\PostTable::getTableName();

						if ($arFields["TITLE"] <> '')
						{
							if (!$conn->isUtf8mb4($table, 'TITLE'))
							{
								$arFields["TITLE"] = \Bitrix\Main\Text\Emoji::encode($arFields["TITLE"]);
							}
						}

						if ($arFields["DETAIL_TEXT"] <> '')
						{
							if (!$conn->isUtf8mb4($table, 'DETAIL_TEXT'))
							{
								$arFields["DETAIL_TEXT"] = \Bitrix\Main\Text\Emoji::encode($arFields["DETAIL_TEXT"]);
							}
						}
					}

					if($arParams["ALLOW_POST_CODE"] && strlen(trim($_POST["CODE"])) > 0)
					{
						$arFields["CODE"] = trim($_POST["CODE"]);
						$arPCFilter = array("BLOG_ID" => $arBlog["ID"], "CODE" => $arFields["CODE"]);
						if(IntVal($arParams["ID"]) > 0)
							$arPCFilter["!ID"] = $arParams["ID"];
						$db = CBlogPost::GetList(Array(), $arPCFilter, false, Array("nTopCount" => 1), Array("ID", "CODE", "BLOG_ID"));
						if($db->Fetch())
						{
							$uind = 0;
							do
							{
								$uind++;
								$arFields["CODE"] = $arFields["CODE"].$uind;
								$arPCFilter["CODE"]  = $arFields["CODE"];
								$db = CBlogPost::GetList(Array(), $arPCFilter, false, Array("nTopCount" => 1), Array("ID", "CODE", "BLOG_ID"));
							}
							while ($db->Fetch());
						}
					}
					$arFields["PERMS_POST"] = array();
					$arFields["PERMS_COMMENT"] = array();

					$arFields["MICRO"] = "N";
					$checkTitle = false;

					if (
						$_POST["ACTION"] == "EDIT_POST"
						&& isset($_POST["MOBILE"])
						&& $_POST["MOBILE"] == "Y"
						&& isset($arPost)
						&& isset($arPost["TITLE"])
					)
					{
						$arFields["TITLE"] = $arPost["~TITLE"];
						$arFields["MICRO"] = $arPost["MICRO"];
					}
					elseif (
						strlen($arFields["TITLE"]) <= 0
						|| $_POST["show_title"] == "N"
					)
					{
						$arFields["MICRO"] = "Y";
						$arFields["TITLE"] = preg_replace(array("/\n+/is".BX_UTF_PCRE_MODIFIER, "/\s+/is".BX_UTF_PCRE_MODIFIER), " ", blogTextParser::killAllTags($arFields["DETAIL_TEXT"]));

						$parser = new \CTextParser();
						$parser->allow = array('CLEAR_SMILES' => 'Y');

						$arFields["TITLE"] = preg_replace("/&nbsp;/is".BX_UTF_PCRE_MODIFIER, "", $parser->convertText($arFields["TITLE"]));
						$arFields["TITLE"] = trim($arFields["TITLE"], " \t\n\r\0\x0B\xA0");

						$checkTitle = true;
					}

					$arTagPrev = array();

					if(!empty($CATEGORYtmp))
					{
						$res = CBlogCategory::getList(
							array(),
							array(
								'@ID' => $CATEGORYtmp
							),
							false,
							false,
							array('NAME')
						);
						while($arCategory = $res->fetch())
						{
							$arTagPrev[] = $arCategory["NAME"];
						}
					}

					$newCategoryIdList = array();
					$postItem = new \Bitrix\Blog\Item\Post;
					$postItem->setFields($arFields);

					$codeList = array('DETAIL_TEXT');
					if (
						!isset($arFields['MICRO'])
						|| $arFields['MICRO'] != 'Y'
					)
					{
						$codeList[] = 'TITLE';
					}
					$arTagInline = \Bitrix\Socialnetwork\Util::detectTags($arFields, $codeList);

					$arTag = array_merge($arTagPrev, $arTagInline);
					$arTag = array_intersect_key($arTag, array_unique(array_map('ToLower', $arTag)));

					if (count($arTag) > count($arTagPrev))
					{
						$arTagPrevLower = array_unique(array_map('ToLower', $arTagPrev));
						$newTagList = array();

						foreach($arTagInline as $tagInline)
						{
							if (!in_array(ToLower($tagInline), $arTagPrevLower))
							{
								$newTagList[] = $tagInline;
							}
						}

						if (!empty($newTagList))
						{
							$newTagList = array_unique($newTagList);

							$existingCategoriesList = array();
							$res = CBlogCategory::getList(
								array(),
								array(
									"@NAME" => $newTagList,
									"BLOG_ID" => $arBlog["ID"]
								),
								false,
								false,
								array('ID', 'NAME')
							);
							while ($arCategory = $res->fetch())
							{
								$existingCategoriesList[$arCategory['NAME']] = $arCategory['ID'];
							}

							foreach($newTagList as $newTag)
							{
								if (array_key_exists($newTag, $existingCategoriesList))
								{
									$newCategoryIdList[] = $existingCategoriesList[$newTag];
								}
								else
								{
									$newCategoryIdList[] = CBlogCategory::add(array("BLOG_ID" => $arBlog["ID"], "NAME" => $newTag));
								}
							}
						}
					}

					$CATEGORYtmp = array_merge($CATEGORYtmp, $newCategoryIdList);
					$CATEGORY_ID = implode(",", $CATEGORYtmp);

					$arFields["CATEGORY_ID"] = $CATEGORY_ID;
					$arFields["SOCNET_RIGHTS"] = array();

					$bError = false;

					if (!empty($_POST["SPERM"]))
					{
						ComponentHelper::processBlogPostNewMailUser($_POST, $arResult);

						$resultFields = array(
							'ERROR_MESSAGE' => false,
							'PUBLISH_STATUS' => $arFields['PUBLISH_STATUS']
						);

						$destParams = array(
							'POST_ID' => $arParams["ID"],
							'PERM' => $_POST["SPERM"],
							'IS_REST' => false,
							'IS_EXTRANET_USER' => $arResult["bExtranetUser"]
						);
						if ($arParams["ID"] <= 0)
						{
							$destParams['AUTHOR_ID'] = $user_id;
						}

						$arFields["SOCNET_RIGHTS"] = ComponentHelper::convertBlogPostPermToDestinationList($destParams, $resultFields);

						$arFields["PUBLISH_STATUS"] = $resultFields['PUBLISH_STATUS'];
						if (!empty($resultFields['ERROR_MESSAGE']))
						{
							$arResult["ERROR_MESSAGE"] = $resultFields['ERROR_MESSAGE'];
							$bError = true;
						}
					}

					if (
						!$bError
						&& empty($arFields["SOCNET_RIGHTS"])
					)
					{
						$bError = true;
						$arResult["ERROR_MESSAGE"] .= GetMessage("BLOG_BPE_DESTINATION_EMPTY");
					}

					$mentionList = $mentionListOld = array();

					if(!$bError)
					{
						$fieldName = 'UF_BLOG_POST_DOC';
						if (
							isset($GLOBALS[$fieldName])
							&& is_array($GLOBALS[$fieldName])
						)
						{
							$arOldFiles = array();
							if($arParams["ID"] > 0 && strlen($_POST["blog_upload_cid"]) <= 0)
							{
								$dbP = CBlogPost::GetList(array(), array("ID" => $arParams["ID"]), false, false, array("ID", $fieldName));
								if($arP = $dbP->Fetch())
								{
									$arOldFiles = $arP[$fieldName];
								}
							}
							$arAttachedFiles = array();
							foreach($GLOBALS[$fieldName] as $fileID)
							{
								$fileID = intval($fileID);

								if ($fileID <= 0)
								{
									continue;
								}
								elseif(
									(
										!is_array($_SESSION["MFI_UPLOADED_FILES_".$_POST["blog_upload_cid"]])
										|| !in_array($fileID, $_SESSION["MFI_UPLOADED_FILES_".$_POST["blog_upload_cid"]])
									)
									&& ( // mobile
										!is_array($_SESSION["MFU_UPLOADED_FILES_".$USER->GetId()])
										|| !in_array($fileID, $_SESSION["MFU_UPLOADED_FILES_".$USER->GetId()])
									)
								)
								{
									if (
										empty($arOldFiles)
										|| !in_array($fileID, $arOldFiles)
									)
									{
										continue;
									}
								}

								$arFile = CFile::GetFileArray($fileID);
								if (CFile::CheckImageFile(CFile::MakeFileArray($fileID)) === null)
								{
									$arImgFields = array(
										"BLOG_ID" => $arBlog["ID"],
										"POST_ID" => 0,
										"USER_ID" => $arResult["UserID"],
										"=TIMESTAMP_X" => $DB->GetNowFunction(),
										"TITLE" => $arFile["FILE_NAME"],
										"IMAGE_SIZE" => $arFile["FILE_SIZE"],
										"FILE_ID" => $fileID,
										"URL" => $arBlog["URL"],
										"IMAGE_SIZE_CHECK" => "N",
									);
									$imgID = CBlogImage::Add($arImgFields);
									if (intval($imgID) <= 0)
									{
										$APPLICATION->ThrowException("Error Adding file by CBlogImage::Add");
									}
									else
									{
										$arFields["DETAIL_TEXT"] = str_replace("[IMG ID=".$fileID."file", "[IMG ID=".$imgID."", $arFields["DETAIL_TEXT"]);
									}
								}
								else
								{
									$arAttachedFiles[] = $fileID;
								}
							}
							if (
								is_array($arPostFields)
								&& is_array($arPostFields[$fieldName])
								&& is_array($arPostFields[$fieldName]["VALUE"])
							)
							{
								$arAttachedFiles = array_unique(array_merge($arAttachedFiles, array_intersect($GLOBALS[$fieldName], $arPostFields[$fieldName]["VALUE"])));
							}
							$GLOBALS[$fieldName] = $arAttachedFiles;
						}

						CSocNetLogComponent::checkEmptyUFValue('UF_BLOG_POST_FILE');

						if (!empty($arParams["POST_PROPERTY"]))
						{
							$USER_FIELD_MANAGER->EditFormAddFields("BLOG_POST", $arFields);
						}

						preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, $_POST["POST_MESSAGE"], $arMention);
						$mentionList = (!empty($arMention) ? $arMention[1] : array());

						$APPLICATION->ResetException();
						$bAdd = false;

						if (
							array_key_exists("GRAT", $_POST)
							&& isset($_POST["GRAT"]["U"])
							&& is_array($_POST["GRAT"]["U"])
							&& array_key_exists("GRAT_TYPE", $_POST)
							&& array_key_exists("changePostFormTab", $_POST)
							&& (
								$_POST["changePostFormTab"] == "grat"
								|| (
									isset($arParams["PAGE_ID"])
									&& in_array($arParams["PAGE_ID"], [ "user_blog_post_edit_grat", "user_grat" ])
								)
							)
						)
						{
							$bNeedAddGrat = true;
						}

						if (
							!empty($_POST["attachedFilesRaw"])
							&& is_array($_POST["attachedFilesRaw"])
						)
						{
							CSocNetLogComponent::saveRawFilesToUF(
								$_POST["attachedFilesRaw"],
								(
									ModuleManager::isModuleInstalled("webdav")
									|| ModuleManager::isModuleInstalled("disk")
										? "UF_BLOG_POST_FILE"
										: "UF_BLOG_POST_DOC"
								),
								$arFields
							);
						}

						if(
							$checkTitle
							&& strlen($arFields["TITLE"]) <= 0
							&& !empty($arFields["UF_BLOG_POST_FILE"])
							&& is_array($arFields["UF_BLOG_POST_FILE"])
						)
						{
							foreach($arFields["UF_BLOG_POST_FILE"] as $val)
							{
								if (!empty($val))
								{
									$arFields["TITLE"] = GetMessage("BLOG_EMPTY_TITLE_PLACEHOLDER2");
									break;
								}
							}
						}

						if (
							$checkTitle
							&& strlen($arFields["TITLE"]) <= 0
							&& isset($_POST["MOBILE"])
							&& $_POST["MOBILE"] == "Y"
						)
						{
							$arFields["TITLE"] = GetMessage("BLOG_EMPTY_TITLE_PLACEHOLDER3");
						}

						$arFields["SEARCH_GROUP_ID"] = \Bitrix\Main\Config\Option::get("socialnetwork", "userbloggroup_id", false, SITE_ID);
						if (isset($_POST["postShowingDuration"]) && in_array($_POST["postShowingDuration"], $periodsOfShowingImportantPost))
						{
							if ($_POST["postShowingDuration"] !== "CUSTOM")
							{
								$userDateTimeNow = \Bitrix\Main\Type\DateTime::createFromTimestamp(time() + CTimeZone::GetOffset());
								if ($_POST["postShowingDuration"] == "ALWAYS")
								{
									$arFields["UF_IMPRTANT_DATE_END"] = null;
								}
								else
								{
									switch ($_POST["postShowingDuration"])
									{
										case "ONE_DAY":
											$showEndTime = $userDateTimeNow->setTime(23, 59, 59);
											break;
										case "TWO_DAYS":
											$showEndTime = $userDateTimeNow->setTime(23, 59, 59)->add("1D");
											break;
										case "WEEK":
											$showEndTime = $userDateTimeNow->setTime(23, 59, 59)->add("7D");
											break;
										case "MONTH":
											$showEndTime = $userDateTimeNow->setTime(23, 59, 59)->add("1M");
											break;
										default:
											break;
									}
									$arFields["UF_IMPRTANT_DATE_END"] = \Bitrix\Main\Type\DateTime::createFromTimestamp($showEndTime->getTimestamp() - CTimeZone::GetOffset());
								}
							}
							else
							{
								$postEndingServerTime = \Bitrix\Main\Type\DateTime::createFromUserTime($arFields["UF_IMPRTANT_DATE_END"]);
								$postEndingServerTime->add("-T1S");
								$arFields["UF_IMPRTANT_DATE_END"] = $postEndingServerTime;
							}
						}

						$newGratData = [];
						$arUsersFromPOST = [];
						if (
							!empty($_POST["GRAT_TYPE"])
							&& !empty($_POST["GRAT"])
							&& !empty($_POST["GRAT"]["U"])
							&& is_array($_POST["GRAT"]["U"])
						)
						{
							foreach($_POST["GRAT"]["U"] as $code)
							{
								if (preg_match('/^U(\d+)$/', $code, $matches))
								{
									$arUsersFromPOST[] = $matches[1];
								}
							}

							$newGratData = [
								'TYPE' => $_POST["GRAT_TYPE"],
								'USERS' => array_diff($arUsersFromPOST, (
									!empty($arResult["PostToShow"]["GRAT_CURRENT"])
									&& !empty($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"])
									&& is_array($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"])
										? $arResult["PostToShow"]["GRAT_CURRENT"]["USERS"]
										: array()
								))
							];
						}

						if ($arParams["ID"] > 0)
						{
							if (
								is_array($arUsersFromPOST)
								&& array_key_exists("GRAT_TYPE", $_POST)
							)
							{
								$bGratFromForm = true;

								if (
									is_array($arResult["PostToShow"]["GRAT_CURRENT"])
									&& count(array_diff($arResult["PostToShow"]["GRAT_CURRENT"]["USERS"], $arUsersFromPOST)) == 0
									&& count(array_diff($arUsersFromPOST, $arResult["PostToShow"]["GRAT_CURRENT"]["USERS"])) == 0
									&& isset($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"]["XML_ID"])
									&& ToLower($arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"]["XML_ID"]) == ToLower($_POST["GRAT_TYPE"])
								)
								{
									$bNeedAddGrat = false;
									$bGratSimilar = true;
								}
							}

							if (
								(
									!isset($arParams["MOBILE"])
									|| $arParams["MOBILE"] != "Y"
								)
								&& (
									$_POST["changePostFormTab"] != "grat"
									|| (
										$bGratFromForm
										&& !$bGratSimilar
									)
								)
								&& (
									is_array($arResult["PostToShow"]["GRAT_CURRENT"])
									&& intval($arResult["PostToShow"]["GRAT_CURRENT"]["ID"]) > 0
									&& Loader::includeModule("iblock")
								)
							)
							{
								CIBlockElement::Delete($arResult["PostToShow"]["GRAT_CURRENT"]["ID"]);

								if ($_POST["changePostFormTab"] != "grat")
								{
									CBlogPost::Update($arParams["ID"], array(
										"DETAIL_TEXT_TYPE" => "text",
										"UF_GRATITUDE" => false
									));
								}
							}

							$arOldPost = CBlogPost::GetByID($arParams["ID"]);

							if(
								$arParams["MOBILE"] == "Y"
								&& in_array("UF_BLOG_POST_URL_PRV", $arParams["POST_PROPERTY"])
								&& empty($arFields["UF_BLOG_POST_URL_PRV"])
								&& (
									empty($arPostFields['UF_BLOG_POST_URL_PRV'])
									|| empty($arPostFields['UF_BLOG_POST_URL_PRV']['VALUE'])
								)
								&& !empty($arFields["DETAIL_TEXT"])
								&& ($urlPreviewValue = ComponentHelper::getUrlPreviewValue($arFields["DETAIL_TEXT"]))
							)
							{
								$arFields["UF_BLOG_POST_URL_PRV"] = $urlPreviewValue;
							}

							preg_match_all("/\[user\s*=\s*([^\]]*)\](.+?)\[\/user\]/is".BX_UTF_PCRE_MODIFIER, $arOldPost["DETAIL_TEXT"], $arMentionOld);
							$mentionListOld = (!empty($arMentionOld) ? $arMentionOld[1] : array());

							$socnetRightsOld = CBlogPost::GetSocnetPerms($arParams["ID"]);

							unset($arFields["DATE_PUBLISH"]);

							if($newID = CBlogPost::Update($arParams["ID"], $arFields))
							{
								BXClearCache(true, ComponentHelper::getBlogPostCacheDir(array(
									'TYPE' => 'post',
									'POST_ID' => $arParams["ID"]
								)));
								BXClearCache(true, ComponentHelper::getBlogPostCacheDir(array(
									'TYPE' => 'post_general',
									'POST_ID' => $arParams["ID"]
								)));
								BXClearCache(True, ComponentHelper::getBlogPostCacheDir(array(
									'TYPE' => 'posts_popular',
									'SITE_ID' => SITE_ID
								)));

								$arFields["AUTHOR_ID"] = $arOldPost["AUTHOR_ID"];
								if (
									$arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_DRAFT
									&& $arOldPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
								)
								{
									CBlogPost::DeleteLog($newID);
								}
								elseif (
									$arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
									&& $arOldPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
								)
								{
									$arParamsUpdateLog = Array(
										"allowVideo" => $arResult["allowVideo"],
										"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
									);
									CBlogPost::UpdateLog($newID, $arFields, $arBlog, $arParamsUpdateLog);
								}
								elseif (
									$arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
									&& $arOldPost["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_READY
								)
								{
									CBlogPost::notifyImPublish(array(
										"TYPE" => "POST",
										"TITLE" => (isset($arFields["TITLE"]) ? $arFields["TITLE"] : $arOldPost["TITLE"]),
										"TO_USER_ID" => $arFields["AUTHOR_ID"],
										"POST_URL" => CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => $newID, "user_id" => $arBlog["OWNER_ID"])),
										"POST_ID" => $newID,
									));
								}
							}
						}
						else
						{
							$arFields["=DATE_CREATE"] = $DB->GetNowFunction();
							$arFields["AUTHOR_ID"] = $arResult["UserID"];
							$arFields["BLOG_ID"] = $arBlog["ID"];

							$ar = (is_array($arFields["UF_BLOG_POST_FILE"]) ? array_values($arFields["UF_BLOG_POST_FILE"]) : array());
							$dbDuplPost = CBlogPost::GetList(
								array("ID" => "DESC"),
								array("BLOG_ID" => $arBlog["ID"]),
								false,
								array("nTopCount" => 1),
								array("ID", "BLOG_ID", "AUTHOR_ID", "DETAIL_TEXT", "TITLE")
							);
							if($arDuplPost = $dbDuplPost->Fetch())
							{
								$liveFeedEntity = \Bitrix\Socialnetwork\Livefeed\Provider::init(array(
									'ENTITY_TYPE' => 'BLOG_POST',
									'ENTITY_ID' => $arDuplPost['ID'],
								));
								$logRights = $liveFeedEntity->getLogRights();
								foreach($logRights as $key => $groupCode)
								{
									if (
										$groupCode == 'SA'
										|| $groupCode == 'U'.$arFields["AUTHOR_ID"]
										|| preg_match('/^US(\d+)$/i', $groupCode, $matches)
										|| preg_match('/^OSG(\d+)/i', $groupCode, $matches)
										|| preg_match('/^SG(\d+)_/i', $groupCode, $matches)
									)
									{
										unset($logRights[$key]);
									}
									elseif ($groupCode == 'G2')
									{
										$logRights[$key] = 'UA';
									}
								}

								$diff1 = array_diff($logRights, $arFields["SOCNET_RIGHTS"]);
								$diff2 = array_diff($arFields["SOCNET_RIGHTS"], $logRights);

								if(
									empty($ar[0]) // no files
									&& !$bNeedAddGrat // no gratitudes
									&& $arDuplPost["BLOG_ID"] == $arFields["BLOG_ID"]
									&& IntVal($arDuplPost["AUTHOR_ID"]) == IntVal($arFields["AUTHOR_ID"])
									&& md5($arDuplPost["DETAIL_TEXT"]) == md5($arFields["DETAIL_TEXT"])
									&& md5($arDuplPost["TITLE"]) == md5($arFields["TITLE"])
									&& empty($diff1)
									&& empty($diff2)
								)
								{
									$bError = true;
									$arResult["ERROR_MESSAGE"] = GetMessage("B_B_PC_DUPLICATE_POST");
								}
							}

							if(
								!$bError
								&& $arParams["MOBILE"] == "Y"
								&& in_array("UF_BLOG_POST_URL_PRV", $arParams["POST_PROPERTY"])
								&& empty($arFields["UF_BLOG_POST_URL_PRV"])
								&& ($urlPreviewValue = ComponentHelper::getUrlPreviewValue($arFields["DETAIL_TEXT"]))
							)
							{
								$arFields["UF_BLOG_POST_URL_PRV"] = $urlPreviewValue;
							}

							if(
								!$bError
								&& strlen(trim($arFields['DETAIL_TEXT'])) <= 0
								&& !empty($arFields["UF_BLOG_POST_FILE"])
								&& is_array($arFields["UF_BLOG_POST_FILE"])
							)
							{
								foreach($arFields["UF_BLOG_POST_FILE"] as $val)
								{
									if (!empty($val))
									{
										$arFields["DETAIL_TEXT"] = '[B][/B]';
										break;
									}
								}
							}

							if(!$bError)
							{
								$newID = CBlogPost::Add($arFields);
								$socnetRightsOld = Array("U" => Array());

								$bAdd = true;
								$bNeedMail = false;
							}
						}

						if(IntVal($newID) > 0)
						{
							if (
								$bNeedAddGrat
								&& !empty($arUsersFromPOST)
								&& Loader::includeModule("iblock")
							)
							{
								$arGratFromPOST = false;

								foreach ($arResult["PostToShow"]["GRATS"] as $arGrat)
								{
									if (ToLower($arGrat["XML_ID"]) == ToLower($_POST["GRAT_TYPE"]))
									{
										$arGratFromPOST = $arGrat;
										break;
									}
								}

								if ($arGratFromPOST)
								{
									$el = new CIBlockElement;
									$new_grat_element_id = $el->Add(
										array(
											"IBLOCK_ID" => $honour_iblock_id,
											"DATE_ACTIVE_FROM" => ConvertTimeStamp(false, "FULL"),
											"NAME" => str_replace("#GRAT_NAME#", $arGratFromPOST["VALUE"], GetMessage("BLOG_GRAT_IBLOCKELEMENT_NAME"))
										),
										false,
										false
									);
									if ($new_grat_element_id > 0)
									{
										CIBlockElement::SetPropertyValuesEx(
											$new_grat_element_id,
											$honour_iblock_id,
											array(
												"USERS" => $arUsersFromPOST,
												"GRATITUDE" => array("VALUE" => $arGratFromPOST["ID"])
											)
										);

										if (
											defined("BX_COMP_MANAGED_CACHE")
											&& !empty($arUsersFromPOST)
											&& is_array($arUsersFromPOST)
										)
										{
											foreach($arUsersFromPOST as $gratUserId)
											{
												$CACHE_MANAGER->clearByTag("BLOG_POST_GRATITUDE_TO_USER_".$gratUserId);
											}
										}

										CBlogPost::Update($newID, array(
											"DETAIL_TEXT_TYPE" => "text",
											"UF_GRATITUDE" => $new_grat_element_id
										));
									}
								}
							}

							CBlogPostCategory::DeleteByPostID($newID);
							foreach($CATEGORYtmp as $v)
							{
								CBlogPostCategory::Add(Array("BLOG_ID" => $arBlog["ID"], "POST_ID" => $newID, "CATEGORY_ID"=>$v));
							}

							$DB->Query("UPDATE b_blog_image SET POST_ID=".$newID." WHERE BLOG_ID=".$arBlog["ID"]." AND POST_ID=0", true);

							$bHasImg = false;
							$bHasTag = false;
							$bHasProps = false;
							$bHasOnlyAll = false;

							if(!empty($CATEGORYtmp))
								$bHasTag = true;

							$dbImg = CBlogImage::GetList(Array(), Array("BLOG_ID" => $arBlog["ID"], "POST_ID" => $newID, "IS_COMMENT" => "N"), false, false, Array("ID"));
							if($dbImg->Fetch())
								$bHasImg = true;

							$arPostFieldsOLD = $arPostFields;

							$arPostFields = $USER_FIELD_MANAGER->GetUserFields("BLOG_POST", $newID, LANGUAGE_ID);
							if (
								($arPostFields["UF_BLOG_POST_IMPRTNT"]["VALUE"] != $arPostFieldsOLD["UF_BLOG_POST_IMPRTNT"]["VALUE"])
								|| (
									$arParams["ID"] > 0
									&& (
										$arResult["Post"]["~DETAIL_TEXT"] != $arFields["DETAIL_TEXT"]
										|| $arResult["Post"]["~TITLE"] != $arFields["TITLE"]
									)
								)
							)
							{
								if ($arPostFields["UF_BLOG_POST_IMPRTNT"]["VALUE"] != $arPostFieldsOLD["UF_BLOG_POST_IMPRTNT"]["VALUE"])
								{
									if ($arPostFields["UF_BLOG_POST_IMPRTNT"]["VALUE"])
										CBlogUserOptions::SetOption($newID, "BLOG_POST_IMPRTNT", "Y", $USER->GetID());
									else
										CBlogUserOptions::DeleteOption($newID, "BLOG_POST_IMPRTNT", $USER->GetID());
								}

								if (defined("BX_COMP_MANAGED_CACHE"))
								{
									$CACHE_MANAGER->ClearByTag('blogpost_important_all');
								}
							}
							foreach ($arPostFields as $FIELD_NAME => $arPostField)
							{
								if(!empty($arPostField["VALUE"]))
								{
									$bHasProps = true;
									break;
								}
							}

							if(!empty($arFields["SOCNET_RIGHTS"]) && count($arFields["SOCNET_RIGHTS"]) == 1 && in_array("UA", $arFields["SOCNET_RIGHTS"]))
								$bHasOnlyAll = true;

							$arFieldsHave = array(
								"HAS_IMAGES" => ($bHasImg ? "Y" : "N"),
								"HAS_TAGS" => ($bHasTag ? "Y" : "N"),
								"HAS_PROPS" => ($bHasProps ? "Y" : "N"),
								"HAS_SOCNET_ALL" => ($bHasOnlyAll ? "Y" : "N"),
							);
							CBlogPost::Update($newID, $arFieldsHave, false);
						}

						$logEntryActivated = false;
						if (
							is_array($arOldPost)
							&& $arOldPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_READY
							&& $arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
						)
						{
							if ($postItem = \Bitrix\Blog\Item\Post::getById($newID))
							{
								if ($logEntryActivated = $postItem->activateLogEntry())
								{
									$logId = $postItem->getLogId();
								}
							}
						}

						if (
							(
								$bAdd
								&& $newID
								&& $arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
							)
							|| (
								$arOldPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH
								&& $arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH
							)
						)
						{
							$arFields["ID"] = $newID;
							if (!$logEntryActivated)
							{
								$arParamsNotify = Array(
									"bSoNet" => true,
									"UserID" => $arResult["UserID"],
									"allowVideo" => $arResult["allowVideo"],
									"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
									"PATH_TO_POST" => $arParams["PATH_TO_POST"],
									"SOCNET_GROUP_ID" => $arParams["SOCNET_GROUP_ID"],
									"user_id" => $arParams["USER_ID"],
									"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
									"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
								);
								$logId = CBlogPost::Notify($arFields, $arBlog, $arParamsNotify);
							}

							\Bitrix\Blog\Util::sendBlogPing(array(
								'siteId' => SITE_ID,
								'serverName' => $serverName,
								'pathToBlog' => $arParams["PATH_TO_BLOG"],
								'blogFields' => $arBlog
							));
						}
					}

					if (
						isset($newID)
						&& $newID > 0
						&& strlen($arResult["ERROR_MESSAGE"]) <= 0
					) // Record saved successfully
					{
						$eventId = \Bitrix\Blog\Integration\Socialnetwork\Log::EVENT_ID_POST;
						$arPostFields = $USER_FIELD_MANAGER->GetUserFields("BLOG_POST", $newID, LANGUAGE_ID);

						if (
							isset($arPostFields["UF_BLOG_POST_IMPRTNT"])
							&& isset($arPostFields["UF_BLOG_POST_IMPRTNT"]["VALUE"])
							&& intval($arPostFields["UF_BLOG_POST_IMPRTNT"]["VALUE"]) > 0
						)
						{
							$eventId = \Bitrix\Blog\Integration\Socialnetwork\Log::EVENT_ID_POST_IMPORTANT;
						}
						elseif (
							isset($arPostFields["UF_BLOG_POST_VOTE"])
							&& isset($arPostFields["UF_BLOG_POST_VOTE"]["VALUE"])
							&& intval($arPostFields["UF_BLOG_POST_VOTE"]["VALUE"]) > 0
						)
						{
							$eventId = \Bitrix\Blog\Integration\Socialnetwork\Log::EVENT_ID_POST_VOTE;
						}
						elseif (
							isset($arPostFields["UF_GRATITUDE"])
							&& isset($arPostFields["UF_GRATITUDE"]["VALUE"])
							&& intval($arPostFields["UF_GRATITUDE"]["VALUE"]) > 0
						)
						{
							$eventId = \Bitrix\Blog\Integration\Socialnetwork\Log::EVENT_ID_POST_GRAT;
						}

						if (
							!isset($logId)
							|| intval($logId) <= 0
						)
						{
							$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;

							$res = \Bitrix\Socialnetwork\LogTable::getList(array(
								'filter' => array(
									'EVENT_ID' => $blogPostLivefeedProvider->getEventId(),
									'SOURCE_ID' => $newID
								),
								'select' => array('ID')
							));
							if ($logFields = $res->fetch())
							{
								$logId = $logFields['ID'];
							}
						}

						if (
							isset($logId)
							&& intval($logId) > 0
						)
						{
							$logFields = array(
								"EVENT_ID" => $eventId
							);
							if ($post = \Bitrix\Blog\Item\Post::getById($newID))
							{
								$logFields["TAG"] = $post->getTags();
							}
							CSocNetLog::Update(intval($logId), $logFields);
						}

						$DB->Commit();
						$postUrl = CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("post_id" => $newID, "user_id" => $arBlog["OWNER_ID"]));

						if($arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH)
						{
							BXClearCache(true, ComponentHelper::getBlogPostCacheDir(array(
								'TYPE' => 'posts_last',
								'SITE_ID' => SITE_ID
							)));
							BXClearCache(true, ComponentHelper::getBlogPostCacheDir(array(
								'TYPE' => 'posts_last_blog',
								'SITE_ID' => SITE_ID
							)));

							ComponentHelper::notifyBlogPostCreated([
								'post' => [
									'ID' => $newID,
									'TITLE' => $arFields["TITLE"],
									'AUTHOR_ID' => $arParams["USER_ID"]
								],
								'siteId' => SITE_ID,
								'postUrl' => $postUrl,
								'socnetRights' => $arFields["SOCNET_RIGHTS"],
								'socnetRightsOld' => (!empty($socnetRightsOld) ? $socnetRightsOld : []),
								'mentionListOld' => $mentionListOld,
								'mentionList' => $mentionList,
								'gratData' => (!empty($newGratData) ? $newGratData : [])
							]);

							if (!empty($mentionList))
							{
								$arMentionedDestCode = array();
								foreach($mentionList as $val)
								{
									if (!in_array($val, $mentionListOld))
									{
										$arMentionedDestCode[] = "U".$val;
									}
								}

								if (!empty($arMentionedDestCode))
								{
									\Bitrix\Main\FinderDestTable::merge(array(
										"CONTEXT" => "mention",
										"CODE" => array_unique($arMentionedDestCode)
									));
								}
							}
						}
						elseif (
							$arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_READY
							&& (
								!isset($arOldPost)
								|| !isset($arOldPost["PUBLISH_STATUS"])
								|| $arOldPost["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_READY
							)
							&& !empty($arFields["SOCNET_RIGHTS"])
						)
						{
							CBlogPost::NotifyImReady(array(
								"TYPE" => "POST",
								"POST_ID" => $newID,
								"TITLE" => $arFields["TITLE"],
								"POST_URL" => $postUrl,
								"FROM_USER_ID" => $arParams["USER_ID"],
								"TO_SOCNET_RIGHTS" => $arFields["SOCNET_RIGHTS"]
							));
						}

						$arParams["ID"] = $newID;
						if(!empty($_POST["SPERM"]["SG"]))
						{
							foreach($_POST["SPERM"]["SG"] as $v)
							{
								$group_id_tmp = substr($v, 2);
								if(IntVal($group_id_tmp) > 0)
									CSocNetGroup::SetLastActivity(IntVal($group_id_tmp));
							}
						}

						if (in_array($arParams["PAGE_ID"], array("user_blog_post_edit_profile", "user_blog_post_edit_grat")))
						{
							$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_EDIT_PROFILE"], array("post_id"=>$newID, "user_id" => $arBlog["OWNER_ID"])).'?IFRAME=Y';
						}
						elseif (strlen($_POST["apply"]) <= 0)
						{
							if($arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_DRAFT || strlen($_POST["draft"]) > 0)
							{
								$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_DRAFT"], array("user_id" => $arBlog["OWNER_ID"]));
							}
							elseif($arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_READY)
							{
								$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_EDIT"], array("post_id"=>$newID, "user_id" => $arBlog["OWNER_ID"]))."?moder=y";
							}
							else
							{
								$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $arBlog["OWNER_ID"]));
							}

							if (
								!$bAdd
								&& (
									!isset($arParams["MOBILE"])
									|| $arParams["MOBILE"] != "Y"
								)
							)
							{
								$redirectUrl .= '#post'.$newID;
							}
						}
						else
						{
							$redirectUrl = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_POST_EDIT"], array("post_id"=>$newID, "user_id" => $arBlog["OWNER_ID"]));
						}
						$as = new CAutoSave(); // It is necessary to clear autosave buffer
						$as->Reset();

						if (Loader::includeModule('pull'))
						{
							\Bitrix\Pull\Event::send();
						}

						$uri = new Bitrix\Main\Web\Uri($redirectUrl);
						$uri->deleteParams(array("b24statAction", "b24statTab"));
						$redirectUrl = $uri->getUri();

						LocalRedirect($redirectUrl);
					}
					else
					{
						$DB->Rollback();

						if(strlen($arResult["ERROR_MESSAGE"]) <= 0)
						{
							if ($ex = $APPLICATION->GetException())
								$arResult["ERROR_MESSAGE"] = $ex->GetString();
							else
								$arResult["ERROR_MESSAGE"] = "Error saving data to database.<br />";
						}
					}
				}
			}
			else
			{
				$arResult["ERROR_MESSAGE"] = GetMessage("BPE_SESS");
			}
		}
		elseif($_POST["reset"])
		{
			if($arFields["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_DRAFT)
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_DRAFT"], array("user_id" => $arBlog["OWNER_ID"])));
			elseif($arResult["bGroupMode"])
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_GROUP_BLOG"], array("group_id" => $arParams["SOCNET_GROUP_ID"])));
			else
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_BLOG"], array("user_id" => $arBlog["OWNER_ID"])));
		}

		if (
			$arParams["ID"] > 0
			&& strlen($arResult["ERROR_MESSAGE"]) <= 0
		) // Edit post
		{
			$arResult["PostToShow"]["TITLE"] = ($arPost["MICRO"] != "Y" ? $arPost["TITLE"] : "");
			$arResult["PostToShow"]["DETAIL_TEXT"] = $arPost["DETAIL_TEXT"];
			$arResult["PostToShow"]["~DETAIL_TEXT"] = $arPost["~DETAIL_TEXT"];
			$arResult["PostToShow"]["DETAIL_TEXT_TYPE"] = $arPost["DETAIL_TEXT_TYPE"];
			$arResult["PostToShow"]["PUBLISH_STATUS"] = $arPost["PUBLISH_STATUS"];
			$arResult["PostToShow"]["ENABLE_TRACKBACK"] = $arPost["ENABLE_TRACKBACK"] == "Y";
			$arResult["PostToShow"]["ENABLE_COMMENTS"] = $arPost["ENABLE_COMMENTS"];
			$arResult["PostToShow"]["ATTACH_IMG"] = $arPost["ATTACH_IMG"];
			$arResult["PostToShow"]["DATE_PUBLISH"] = $arPost["DATE_PUBLISH"];
			$arResult["PostToShow"]["CATEGORY_ID"] = $arPost["CATEGORY_ID"];
			$arResult["PostToShow"]["FAVORITE_SORT"] = $arPost["FAVORITE_SORT"];
			$arResult["PostToShow"]["MICRO"] = $arPost["MICRO"];
			if ($arParams["ALLOW_POST_CODE"])
			{
				$arResult["PostToShow"]["CODE"] = $arPost["CODE"];
			}

			$arResult["PostToShow"]["SPERM"] = CBlogPost::GetSocnetPerms($arPost["ID"]);
			if (
				is_array($arResult["PostToShow"]["SPERM"]["U"][$arPost["AUTHOR_ID"]])
				&& in_array("US".$arPost["AUTHOR_ID"], $arResult["PostToShow"]["SPERM"]["U"][$arPost["AUTHOR_ID"]])
			)
			{
				$arResult["PostToShow"]["SPERM"]["U"]["A"] = Array();
			}

			if (
				!is_array($arResult["PostToShow"]["SPERM"]["U"][$arPost["AUTHOR_ID"]])
				|| !in_array("U".$arPost["AUTHOR_ID"], $arResult["PostToShow"]["SPERM"]["U"][$arPost["AUTHOR_ID"]])
			)
			{
				unset($arResult["PostToShow"]["SPERM"]["U"][$arPost["AUTHOR_ID"]]);
			}
		}
		else
		{
			$arResult["PostToShow"]["TITLE"] = htmlspecialcharsEx($_POST["POST_TITLE"]);
			$arResult["PostToShow"]["CATEGORY_ID"] = $_POST["CATEGORY_ID"];
			$arResult["PostToShow"]["CategoryText"] = htmlspecialcharsEx($_POST["TAGS"]);
			$arResult["PostToShow"]["DETAIL_TEXT"] = $_POST["POST_MESSAGE"];
			$arResult["PostToShow"]["~DETAIL_TEXT"] = $_POST["POST_MESSAGE"];
			$arResult["PostToShow"]["PUBLISH_STATUS"] = htmlspecialcharsEx($_POST["PUBLISH_STATUS"]);
			$arResult["PostToShow"]["ENABLE_COMMENTS"] = htmlspecialcharsEx($_POST["ENABLE_COMMENTS"]);
			$arResult["PostToShow"]["DATE_PUBLISH"] = $_POST["DATE_PUBLISH"] ? htmlspecialcharsEx($_POST["DATE_PUBLISH"]) : ConvertTimeStamp(time()+CTimeZone::GetOffset(),"FULL");

			if($arParams["ALLOW_POST_CODE"])
				$arResult["PostToShow"]["CODE"] = htmlspecialcharsEx($_POST["CODE"]);

			$arResult["PostToShow"]["SPERM"] = CBlogTools::htmlspecialcharsExArray($_POST["SPERM"]);
			if(empty($arResult["PostToShow"]["SPERM"]))
				$arResult["PostToShow"]["SPERM"] = array();
			if(empty($_POST["SPERM"]))
			{
				if(IntVal($arParams["SOCNET_GROUP_ID"]) > 0)
					$arResult["PostToShow"]["SPERM"]["SG"][IntVal($arParams["SOCNET_GROUP_ID"])] = "";
				if(IntVal($arParams["SOCNET_USER_ID"]) > 0)
					$arResult["PostToShow"]["SPERM"]["U"][IntVal($arParams["SOCNET_USER_ID"])] = "";
			}
			else
			{
				foreach($_POST["SPERM"] as $k => $v)
				{
					foreach($v as $vv1)
					{
						if(strlen($vv1) > 0)
						{
							if($vv1 == "UA")
							{
								$arResult["PostToShow"]["SPERM"]["U"][] = "A";
							}
							else
							{
								$arResult["PostToShow"]["SPERM"][$k][str_replace($k, "", $vv1)] = "";
							}
						}
					}
				}
			}

			if (
				(
					array_key_exists("GRAT", $_POST)
					&& isset($_POST["GRAT"]["U"])
				)
				|| isset($_POST["GRAT_TYPE"])
				|| isset($_GET["gratCode"])
			)
			{
				if (
					array_key_exists("GRAT", $_POST)
					&& isset($_POST["GRAT"]["U"])
					&& is_array($_POST["GRAT"]["U"])
					&& count($_POST["GRAT"]["U"]) > 0
				)
				{
					$arUsersFromPOST = array();

					foreach($_POST["GRAT"]["U"] as $code)
					{
						if (preg_match('/^U(\d+)$/', $code, $matches))
						{
							$arUsersFromPOST[] = $matches[1];
						}
					}

					if (count($arUsersFromPOST) > 0)
					{
						$dbUsers = CUser::GetList(
							($sort_by = Array('last_name'=>'asc', 'IS_ONLINE'=>'desc')),
							($dummy=''),
							array(
								"ID" => implode("|", $arUsersFromPOST),
								array(
									"FIELDS" => array("ID", "LAST_NAME", "NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO", "WORK_POSITION", "PERSONAL_PROFESSION")
								)
							)
						);
						while($arGratUser = $dbUsers->Fetch())
						{
							$arResult["PostToShow"]["GRAT_CURRENT"]["USERS"][] = $arGratUser["ID"];

							$sName = trim(CUser::FormatName(empty($arParams["NAME_TEMPLATE"]) ? CSite::GetNameFormat(false) : $arParams["NAME_TEMPLATE"], $arGratUser));
							$arResult["PostToShow"]["GRAT_CURRENT"]["USERS_FOR_JS"]["U".$arGratUser["ID"]] = array(
								"id" => "U".$arGratUser["ID"],
								"entityId" => $arGratUser["ID"],
								"name" => $sName,
								"avatar" => "",
								"desc" => $arGratUser["WORK_POSITION"] ? $arGratUser["WORK_POSITION"] : ($arGratUser["PERSONAL_PROFESSION"] ? $arGratUser["PERSONAL_PROFESSION"] : "&nbsp;")
							);
						}
					}
				}

				$gratType = false;
				if (
					isset($_POST["GRAT_TYPE"])
					&& strlen($_POST["GRAT_TYPE"]) > 0
				)
				{
					$gratType = $_POST["GRAT_TYPE"];
				}
				elseif (
					isset($_GET["gratCode"])
					&& strlen($_GET["gratCode"]) > 0
				)
				{
					$gratType = $_GET["gratCode"];
				}

				if (
					$gratType
					&& is_array($arResult["PostToShow"]["GRATS"])
				)
					foreach ($arResult["PostToShow"]["GRATS"] as $arGrat)
					{
						if ($arGrat["XML_ID"] == $gratType)
						{
							$arResult["PostToShow"]["GRAT_CURRENT"]["TYPE"] = $arGrat;
							break;
						}
					}
			}

			if($_REQUEST["moder"] == "y")
				$arResult["OK_MESSAGE"] = GetMessage("BPE_HIDDEN_POSTED");
		}

		if ($arResult["SHOW_FULL_FORM"])
		{
			/* @deprecated */
			$arResult["Smiles"] = CBlogSmile::GetSmilesList();
		}

		$arResult["Images"] = Array();
		if(!empty($arBlog) && ($arPost["ID"] > 0 || strlen($arResult["ERROR_MESSAGE"]) > 0))
		{
			$arFilter = array(
					"POST_ID" => $arParams["ID"],
					"BLOG_ID" => $arBlog["ID"],
					"IS_COMMENT" => "N",
				);
			if ($arParams["ID"]==0)
				$arFilter["USER_ID"] = $arResult["UserID"];

			$res = CBlogImage::GetList(array("ID"=>"ASC"), $arFilter);
			while($aImg = $res->Fetch())
			{
				$aImgNew = CFile::ResizeImageGet(
					$aImg["FILE_ID"],
					array("width" => 90, "height" => 90),
					BX_RESIZE_IMAGE_EXACT,
					true
				);
				$aImgNew["source"] = CFile::ResizeImageGet(
					$aImg["FILE_ID"],
					array("width" => $arParams["IMAGE_MAX_WIDTH"], "height" => $arParams["IMAGE_MAX_HEIGHT"]),
					BX_RESIZE_IMAGE_PROPORTIONAL,
					true
				);
				$aImgNew["ID"] = $aImg["ID"];
				$aImgNew["params"] = CFile::_GetImgParams($aImg["FILE_ID"]);
				$aImgNew["fileName"] = substr($aImgNew["src"], strrpos($aImgNew["src"], "/")+1);
				$aImgNew["fileShow"] = "<img src=\"".$aImgNew["src"]."\" width=\"".$aImgNew["width"]."\" height=\"".$aImgNew["height"]."\" border=\"0\" style=\"cursor:pointer\" onclick=\"InsertBlogImage_LHEPostFormId_blogPostForm('".$aImg["ID"]."', '".$aImgNew["source"]['src']."', '".$aImgNew["source"]['width']."');\" title=\"".GetMessage("BLOG_P_INSERT")."\">";
				$aImgNew["SRC"] = $aImgNew["source"]["src"];

				$aImgNew["FILE_NAME"] = $aImgNew["fileName"];
				$aImgNew["FILE_SIZE"] = $aImgNew["source"]["size"];
				$aImgNew["URL"] = $aImgNew["src"];
				$aImgNew["CONTENT_TYPE"] = "image/xyz";
				$aImgNew["THUMBNAIL"] = $aImgNew["src"];
				$aImgNew["DEL_URL"] = $APPLICATION->GetCurPageParam(
					"del_image_id=".$aImg["ID"]."&".bitrix_sessid_get(),
					Array("sessid", "image_upload_frame", "image_upload", "do_upload","del_image_id"));
				$arResult["Images"][] = $aImgNew;
			}
		}

		if(strpos($arResult["PostToShow"]["CATEGORY_ID"], ",")!==false)
			$arResult["PostToShow"]["CATEGORY_ID"] = explode(",", trim($arResult["PostToShow"]["CATEGORY_ID"]));

		$arResult["Category"] = Array();

		if(strlen($arResult["PostToShow"]["CategoryText"]) <= 0 && !empty($arResult["PostToShow"]["CATEGORY_ID"]))
		{
			$res = CBlogCategory::GetList(array("NAME"=>"ASC"),array("BLOG_ID"=>$arBlog["ID"]));
			while ($arCategory=$res->GetNext())
			{
				if (is_array($arResult["PostToShow"]["CATEGORY_ID"]))
				{
					if (in_array($arCategory["ID"], $arResult["PostToShow"]["CATEGORY_ID"]))
					{
						$arCategory["Selected"] = "Y";
					}
				}
				elseif (IntVal($arCategory["ID"]) == IntVal($arResult["PostToShow"]["CATEGORY_ID"]))
				{
					$arCategory["Selected"] = "Y";
				}

				if ($arCategory["Selected"] == "Y")
				{
					$arResult["PostToShow"]["CategoryText"] .= $arCategory["~NAME"].",";
				}

				$arResult["Category"][$arCategory["ID"]] = $arCategory;
			}
			$arResult["PostToShow"]["CategoryText"] = substr($arResult["PostToShow"]["CategoryText"], 0, strlen($arResult["PostToShow"]["CategoryText"])-1);
		}

		foreach ($arParams["POST_PROPERTY"] as $FIELD_NAME)
		{
			$arPostField = $arPostFields[$FIELD_NAME];
			if (!!$arPostField)
			{
				if (
					!empty($arResult["ERROR_MESSAGE"])
					&& !empty($_POST[$FIELD_NAME])
				)
				{
					$arPostField["VALUE"] = $_POST[$FIELD_NAME];
				}

				$arPostField["~EDIT_FORM_LABEL"] = ($arPostField["EDIT_FORM_LABEL"] !== "" ? $arPostField["EDIT_FORM_LABEL"] : $arPostField["FIELD_NAME"]);
				$arPostField["EDIT_FORM_LABEL"] = htmlspecialcharsEx($arPostField["~EDIT_FORM_LABEL"]);
				$arResult["POST_PROPERTIES"]["DATA"][$FIELD_NAME] = $arPostField;
				$arResult["POST_PROPERTIES"]["SHOW"] = "Y";
			}
		}

		if(
			isset($_REQUEST["WFILES"])
			&& !empty($_REQUEST["WFILES"])
			&& is_array($_REQUEST["WFILES"])
			&& !$_POST["save"]
		)
		{
			$isDiskProperty = (
				isset($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]['USER_TYPE_ID'])
				&& $arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]['USER_TYPE_ID'] === 'disk_file'
			);

			foreach($_REQUEST["WFILES"] as $val)
			{
				$val = intval($val);
				if($val <= 0)
				{
					continue;
				}
				if($isDiskProperty)
				{
					//@see Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX
					$val = 'n' . $val;
				}
				$arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"][] = $val;
			}
			if(!empty($arResult["POST_PROPERTIES"]["DATA"]["UF_BLOG_POST_FILE"]["VALUE"]))
			{
				$arResult["needShow"] = true;
			}
		}

		$arResult["urlToDelImage"] = $APPLICATION->GetCurPageParam("del_image_id=#del_image_id#&".bitrix_sessid_get(), Array("sessid", "image_upload_frame", "image_upload", "do_upload","del_image_id"));

		$serverName = "";
		$dbSite = CSite::GetByID(SITE_ID);
		$arSite = $dbSite->Fetch();
		$serverName = htmlspecialcharsEx($arSite["SERVER_NAME"]);
		if (strLen($serverName) <=0)
		{
			$serverName = (
				defined("SITE_SERVER_NAME")
				&& strlen (SITE_SERVER_NAME) > 0
					? SITE_SERVER_NAME
					: COption::GetOptionString("main", "server_name", "www.bitrixsoft.com")
			);

			if (strLen($serverName) <= 0)
			{
				$serverName = $_SERVER["HTTP_HOST"];
			}
		}
		$serverName = "http://".$serverName;

		$arResult["PATH_TO_POST"] = CComponentEngine::MakePathFromTemplate(htmlspecialcharsBack($arParams["PATH_TO_POST"]), array("blog" => $arBlog["URL"], "post_id" => "#post_id#", "user_id" => $arBlog["OWNER_ID"], "group_id" => $arParams["SOCNET_GROUP_ID"]));
		$arResult["PATH_TO_POST1"] = $serverName.substr($arResult["PATH_TO_POST"], 0, strpos($arResult["PATH_TO_POST"], "#post_id#"));
		$arResult["PATH_TO_POST2"] = substr($arResult["PATH_TO_POST"], strpos($arResult["PATH_TO_POST"], "#post_id#") + strlen("#post_id#"));
	}

	CJSCore::Init(array('socnetlogdest'));
	// socialnetwork

	if ($arResult["SHOW_FULL_FORM"])
	{
		$dataAdditional = array();
		$arResult["DEST_SORT"] = CSocNetLogDestination::GetDestinationSort(array(
			"DEST_CONTEXT" => "BLOG_POST",
			"ALLOW_EMAIL_INVITATION" => $arResult["ALLOW_EMAIL_INVITATION"]
		), $dataAdditional);

		$arResult["PostToShow"]["FEED_DESTINATION"]['LAST'] = array();
		CSocNetLogDestination::fillLastDestination(
			$arResult["DEST_SORT"],
			$arResult["PostToShow"]["FEED_DESTINATION"]['LAST'],
			array(
				"EMAILS" => ($arResult["ALLOW_EMAIL_INVITATION"] ? 'Y' : 'N'),
				"DATA_ADDITIONAL" => $dataAdditional
			)
		);

		$limitReached = false;
		$arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS'] = ComponentHelper::getSonetGroupAvailable(array(
			'limit' => 100
		), $limitReached);

		if (
			$arResult["bExtranetUser"]
			&& !empty($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['SONETGROUPS'])
			&& !$limitReached
		)
		{
			$sonetGroupAvailable = (
				!empty($arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS'])
					? $arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS']
					: []
			);

			foreach ($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['SONETGROUPS'] as $key => $value)
			{
				if (!in_array($value, $sonetGroupAvailable))
				{
					unset($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['SONETGROUPS'][$key]);
				}
			}
		}

		if(
			!empty($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['SONETGROUPS'])
			&& !empty($arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS'])
		)
		{
			$arDestSonetGroup = array();
			foreach ($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['SONETGROUPS'] as $value)
			{
				if (!array_key_exists($value,$arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS']))
				{
					$arDestSonetGroup[] = intval(substr($value, 2));
				}
			}
			if (!empty($arDestSonetGroup))
			{
				$sonetGroupsAdditionalList = CSocNetLogDestination::getSocnetGroup(array(
					'features' => array("blog", array("premoderate_post", "moderate_post", "write_post", "full_post")),
					'id' => $arDestSonetGroup
				));
				if (!empty($sonetGroupsAdditionalList))
				{
					$arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS'] = array_merge($arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS'], $sonetGroupsAdditionalList);
				}
			}
		}

		$arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS_LIMITED'] = ($limitReached ? 'Y' : 'N');
		$arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS_FEATURES'] = array("blog", array("premoderate_post", "moderate_post", "write_post", "full_post"));

		$arDestUser = array(
			'LAST' => array(),
			'SELECTED' => array()
		);
		$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED'] = Array();

		if (empty($arResult["PostToShow"]["SPERM"]))
		{
			if ($arResult["bExtranetUser"])
			{
				if(!empty($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['SONETGROUPS']))
				{
					foreach ($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['SONETGROUPS'] as $val)
					{
						$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED'][$val] = "sonetgroups";
					}
				}
				else
				{
					foreach ($arResult["PostToShow"]["FEED_DESTINATION"]['SONETGROUPS'] as $k => $val)
					{
						$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED'][$k] = "sonetgroups";
					}
				}

				if (empty($arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']))
				{
					$arResult["FATAL_MESSAGE"] .= GetMessage("BLOG_SONET_MODULE_NOT_AVAIBLE");
				}
			}
			elseif ($bDefaultToAll)
			{
				if (ModuleManager::isModuleInstalled("intranet"))
				{
					$siteDepartmentID = COption::GetOptionString("main", "wizard_departament", false, SITE_ID, true);
					if (intval($siteDepartmentID) > 0)
					{
						$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['DR'.intval($siteDepartmentID)] = 'department';
					}
					else
					{
						$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['UA'] = 'groups';
					}
				}
				else
				{
					$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['UA'] = 'groups';
				}
			}
		}
		else
		{
			foreach ($arResult["PostToShow"]["SPERM"] as $type => $ar)
			{
				if(is_array($ar))
				{
					foreach ($ar as $value => $ar2)
					{
						if (
							$type == 'U'
							&& $value == 'A'
							&& (
								$bDefaultToAll
								|| $arParams["ID"] > 0
							)
						)
						{
							$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['UA'] = 'groups';
						}
						elseif ($type == 'U')
						{
							$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['U'.$value] = 'users';
							$arDestUser['SELECTED'][] = $value;
						}
						elseif ($type == 'SG')
						{
							$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['SG'.$value] = 'sonetgroups';
						}
						elseif ($type == 'DR')
						{
							$arResult["PostToShow"]["FEED_DESTINATION"]['SELECTED']['DR'.$value] = 'department';
						}
					}
				}
			}
		}

		$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"] = array();
		$arHiddenGroups = array();
		$arUserCodesSelected = $arDepartmentCodesSelected = array();

		if(!empty($arResult["PostToShow"]["FEED_DESTINATION"]["SELECTED"]))
		{
			foreach($arResult["PostToShow"]["FEED_DESTINATION"]["SELECTED"] as $gID => $value)
			{
				if(
					$value == "sonetgroups"
					&& empty($arResult["PostToShow"]["FEED_DESTINATION"]["SONETGROUPS"][$gID])
				)
				{
					$arHiddenGroups[] = substr($gID, 2);
				}
				elseif ($value == "users")
				{
					$arUserCodesSelected[] = $gID;
				}
				elseif ($value == "department")
				{
					$arDepartmentCodesSelected[] = $gID;
				}
			}
		}

		if(!empty($arHiddenGroups))
		{
			$res = \Bitrix\Socialnetwork\WorkgroupTable::getList(array(
				'filter' => array(
					"@ID" => $arHiddenGroups
				),
				'select' => array("ID", "NAME", "DESCRIPTION", "OPENED")
			));

			while($group = $res->Fetch())
			{
				if (
					$group['OPENED'] == "Y"
					|| CSocNetUser::isCurrentUserModuleAdmin()
				)
				{
					$arResult["PostToShow"]["FEED_DESTINATION"]["SONETGROUPS"]['SG'.$group["ID"]] = array(
						"id" => 'SG'.$group["ID"],
						"entityId" => $group["ID"],
						"name" => $group["NAME"],
						"desc" => $group["DESCRIPTION"]
					);
				}
				else
				{
					$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"][$group["ID"]] = array(
						"ID" => $group["ID"],
						"NAME" => $group["NAME"],
						"TYPE" => 'sonetgroups',
						"PREFIX" => 'SG'
					);
				}
			}

			if (!CSocNetUser::IsCurrentUserModuleAdmin() && is_object($USER))
			{
				$arGroupID = CSocNetLogTools::GetAvailableGroups(
					($arResult["bExtranetUser"] ? "Y" : "N"),
					($arResult["bExtranetSite"] ? "Y" : "N")
				);

				foreach($arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"] as $group_code => $arBlogSPerm)
				{
					if (!in_array($group_code, $arGroupID))
					{
						$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"][$group_code]["NAME"] = GetMessage("B_B_HIDDEN_GROUP");
					}
				}
			}
		}

		$tmp = $arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"];
		$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"] = array();
		foreach ($tmp as $key => $value)
		{
			$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"]['SG'.$key] = $value;
		}

		$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_ITEMS"] = $arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_GROUPS"];

		// intranet structure
		$arStructure = CSocNetLogDestination::GetStucture(array("LAZY_LOAD" => true));
		$arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT'] = $arStructure['department'];
		$arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
		$arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

		// users
		if ($arResult["bExtranetUser"])
		{
			$arResult["PostToShow"]["FEED_DESTINATION"]['EXTRANET_USER'] = 'Y';
			$arResult["PostToShow"]["FEED_DESTINATION"]['USERS'] = CSocNetLogDestination::GetExtranetUser();
		}
		else
		{
			if(!empty($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['USERS']))
			{
				foreach ($arResult["PostToShow"]["FEED_DESTINATION"]['LAST']['USERS'] as $value)
				{
					$arDestUser['LAST'][] = str_replace('U', '', $value);
				}
			}

			$arResult["PostToShow"]["FEED_DESTINATION"]['EXTRANET_USER'] = 'N';

			$destinationUsersLast = $destinationUsersSelected = array();
			if (!empty($arDestUser['LAST']))
			{
				$destinationUsersLast = CSocNetLogDestination::GetUsers(array(
					'id' => $arDestUser['LAST'],
					'CRM_ENTITY' => ModuleManager::isModuleInstalled('crm')
				));
			}

			if (!empty($arDestUser['SELECTED']))
			{
				$destinationUsersSelected = CSocNetLogDestination::GetUsers(array(
					'id' => $arDestUser['SELECTED'],
					'CRM_ENTITY' => ModuleManager::isModuleInstalled('crm'),
					'IGNORE_ACTIVITY' => 'Y'
				));
			}
			$arResult["PostToShow"]["FEED_DESTINATION"]['USERS'] = array_merge($destinationUsersLast, $destinationUsersSelected);

			if ($arResult["ALLOW_EMAIL_INVITATION"])
			{
				ComponentHelper::fillSelectedUsersToInvite($_POST, $arParams, $arResult);
				CSocNetLogDestination::fillEmails($arResult["PostToShow"]["FEED_DESTINATION"]);
			}
		}

		foreach($arUserCodesSelected as $selectedUserCode)
		{
			if (!array_key_exists($selectedUserCode, $arResult["PostToShow"]["FEED_DESTINATION"]['USERS']))
			{
				$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_ITEMS"][$selectedUserCode] = array(
					"ID" => substr($selectedUserCode, 1),
					"NAME" => GetMessage("B_B_HIDDEN_USER"),
					"TYPE" => 'users',
					"PREFIX" => 'U'
				);
			}
		}

		foreach($arDepartmentCodesSelected as $selectedDepartmentCode)
		{
			$departrmentIdToCheckList = array();
			if (!array_key_exists($selectedDepartmentCode, $arResult["PostToShow"]["FEED_DESTINATION"]['DEPARTMENT']))
			{
				$departrmentIdToCheckList[] = substr($selectedDepartmentCode, 2);
			}

			if (
				!empty($departrmentIdToCheckList)
				&& Loader::includeModule('iblock')
				&& (($structureIBlockId = \Bitrix\Main\Config\Option::get('intranet', 'iblock_structure', 0)) > 0)
			)
			{
				$res = CIBlockSection::getList(
					array(),
					array(
						'=IBLOCK_ID' => $structureIBlockId,
						'ID' => $departrmentIdToCheckList,
						'=ACTIVE' => 'Y'
					),
					false,
					array('ID')
				);

				while($section = $res->fetch())
				{
					$arResult["PostToShow"]["FEED_DESTINATION"]["HIDDEN_ITEMS"][$selectedDepartmentCode] = array(
						"ID" => $section['ID'],
						"NAME" => GetMessage("B_B_HIDDEN_DEPARTMENT"),
						"TYPE" => 'department',
						"PREFIX" => 'DR'
					);
				}
			}
		}

		$arResult["PostToShow"]["FEED_DESTINATION"]["USERS_VACATION"] = Bitrix\Socialnetwork\Integration\Intranet\Absence\User::getDayVacationList();
		$arResult["PostToShow"]["FEED_DESTINATION"]["DENY_TOALL"] = !$bAllowToAll;
	}
}
else
{
	$arResult["FATAL_MESSAGE"] = GetMessage("BLOG_ERR_NO_RIGHTS");
}

CSocNetTools::InitGlobalExtranetArrays();
Loader::includeModule('intranet'); // for gov/public language messages
$this->IncludeComponentTemplate();
?>