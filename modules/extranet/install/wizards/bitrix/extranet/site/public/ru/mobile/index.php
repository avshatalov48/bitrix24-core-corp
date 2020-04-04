<?
define("BX_MOBILE_LOG", true);

require($_SERVER["DOCUMENT_ROOT"]."/mobile/headers.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
AddEventHandler("blog", "BlogImageSize", "ResizeMobileLogImages", 100, $_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/components/bitrix/socialnetwork.blog.post/mobile/functions.php");

if (IsModuleInstalled("bitrix24"))
	GetGlobalID();

if (
	$_POST["ACTION"] == "ADD_POST"
	|| $_POST["ACTION"] == "EDIT_POST"
)
{
	function LocalRedirectHandler(&$url)
	{
		$bSuccess = false;

		if (strpos($url, "?") > 0)
		{
			$arUrlParam = explode("&", substr($url, strpos($url, "?")+1));
			foreach ($arUrlParam as $url_param)
			{
				list($key, $val) = explode("=", $url_param, 2);
				if ($key == "new_post_id")
				{
					$new_post_id = $val;
					break;
				}
			}
		}

		if (
			strpos($url, "success=Y") > 0
			&& intval($new_post_id) > 0
		)
		{
			unset($_SESSION["MFU_UPLOADED_FILES"]);
			unset($_SESSION["MFU_UPLOADED_DOCS"]);
			unset($_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId()]);
			unset($_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId()]);
			$GLOBALS["APPLICATION"]->RestartBuffer();

			$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;

			$rsLogSrc = CSocNetLog::GetList(
				array(),
				array(
					"EVENT_ID" => $blogPostLivefeedProvider->getEventId(),
					"SOURCE_ID" => $new_post_id
				),
				false,
				false,
				array("ID"),
				array(
					"CHECK_RIGHTS" => "Y",
					"USE_SUBSCRIBE" => "N"
				)
			);
			if ($arLogSrc = $rsLogSrc->Fetch())
			{
				ob_start();
				?><?$GLOBALS["APPLICATION"]->IncludeComponent("bitrix:mobile.socialnetwork.log.ex", ".default", array(
						"NEW_LOG_ID" => intval($arLogSrc["ID"]),
						"PATH_TO_LOG_ENTRY" => SITE_DIR."mobile/log/?detail_log_id=#log_id#",
						"PATH_TO_LOG_ENTRY_EMPTY" => SITE_DIR."mobile/log/?empty=Y",
						"PATH_TO_USER" => SITE_DIR."mobile/users/?user_id=#user_id#",
						"SET_LOG_CACHE" => "N",
						"IMAGE_MAX_WIDTH" => 550,
						"DATE_TIME_FORMAT" => ""
					),
					false,
					Array("HIDE_ICONS" => "Y")
				);?><?
				$postText = ob_get_contents();
				ob_end_clean();

				$bSuccess = true;
			}
		}

		$GLOBALS["APPLICATION"]->RestartBuffer();

		if (!$bSuccess)
		{
			echo ($_POST["response_type"] == "json" ? CUtil::PhpToJSObject(array("error" => "*")) : "*");
		}
		else
		{
			echo ($_POST["response_type"] == "json" ? CUtil::PhpToJSObject(array("text" => $postText)) : $postText);
		}

		die();
	}

	$LocalRedirectHandlerId = AddEventHandler('main', 'OnBeforeLocalRedirect', "LocalRedirectHandler");

	$APPLICATION->IncludeComponent("bitrix:socialnetwork.blog.post.edit", "mobile_empty", array(
			"ID" => ($_POST["ACTION"] == "EDIT_POST" && intval($_POST["post_id"]) > 0 ? intval($_POST["post_id"]) : 0),	
			"USER_ID" => ($_POST["ACTION"] == "EDIT_POST" && intval($_POST["post_user_id"]) > 0 ? intval($_POST["post_user_id"]) : $GLOBALS["USER"]->GetID()),
			"PATH_TO_POST_EDIT" => $APPLICATION->GetCurPageParam("success=Y&new_post_id=#post_id#"), // redirect when success
			"PATH_TO_POST" => "/company/personal/user/".$GLOBALS["USER"]->GetID()."/blog/#post_id#/", // search index
			"USE_SOCNET" => "Y",
			"SOCNET_GROUP_ID" => intval($_REQUEST["group_id"]),
			"GROUP_ID" => (IsModuleInstalled("bitrix24") ? $GLOBAL_BLOG_GROUP[SITE_ID] : false),
			"MOBILE" => "Y"
		),
		false,
		Array("HIDE_ICONS" => "Y")
	);

	RemoveEventHandler('main', 'OnBeforeLocalRedirect', $LocalRedirectHandlerId);

	$GLOBALS["APPLICATION"]->RestartBuffer();
	echo ($_POST["response_type"] == "json" ? CUtil::PhpToJSObject(array("error" => "*")) : "*");
	die();
}

$filter = false;
if ($_GET["favorites"] == "Y")
{
	$filter = "favorites";
}
elseif ($_GET["my"] == "Y")
{
	$filter = "my";
}
elseif ($_GET["important"] == "Y")
{
	$filter = "important";
}
elseif ($_GET["work"] == "Y")
{
	$filter = "work";
}
elseif ($_GET["bizproc"] == "Y")
{
	$filter = "bizproc";
}
elseif ($_GET["blog"] == "Y")
{
	$filter = "blog";
}

?><?$APPLICATION->IncludeComponent("bitrix:mobile.socialnetwork.log.ex", ".default", array(
		"GROUP_ID" => intval($_GET["group_id"]),
		"LOG_ID" => intval($_GET["detail_log_id"]),
		"FAVORITES" => ($_GET["favorites"] == "Y" ? "Y" : "N"),
		"FILTER" => $filter,
		"CREATED_BY_ID" => (isset($_GET["created_by_id"]) && intval($_GET["created_by_id"]) > 0 ? intval($_GET["created_by_id"]) : false),
		"PATH_TO_LOG_ENTRY" => SITE_DIR."mobile/log/?detail_log_id=#log_id#",
		"PATH_TO_LOG_ENTRY_EMPTY" => SITE_DIR."mobile/log/?empty=Y",
		"PATH_TO_USER" => SITE_DIR."mobile/users/?user_id=#user_id#",
		"PATH_TO_GROUP" => SITE_DIR."mobile/log/?group_id=#group_id#",
		"PATH_TO_CRMCOMPANY" => SITE_DIR."mobile/crm/company/view.php?company_id=#company_id#",
		"PATH_TO_CRMCONTACT" => SITE_DIR."mobile/crm/contact/view.php?contact_id=#contact_id#",
		"PATH_TO_CRMLEAD" => SITE_DIR."mobile/crm/lead/view.php?lead_id=#lead_id#",
		"PATH_TO_CRMDEAL" => SITE_DIR."mobile/crm/deal/view.php?deal_id=#deal_id#",
		'PATH_TO_TASKS_SNM_ROUTER' => SITE_DIR.'mobile/tasks/snmrouter/'
			. '?routePage=__ROUTE_PAGE__'
			. '&USER_ID=#USER_ID#'
			. '&GROUP_ID=' . (int) $_GET['group_id']
			. '&LIST_MODE=TASKS_FROM_GROUP',
		"SET_LOG_CACHE" => "Y",
		"IMAGE_MAX_WIDTH" => 550,
		"DATE_TIME_FORMAT" => ((intval($_GET["detail_log_id"]) > 0 || $_REQUEST["ACTION"] == "CONVERT") ? "j F Y G:i" : ""),
		"CHECK_PERMISSIONS_DEST" => "N",
	),
	false,
	Array("HIDE_ICONS" => "Y")
);?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php")?>