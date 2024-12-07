<?php

define("NO_KEEP_STATISTIC", true);
define("BX_STATISTIC_BUFFER_USED", false);
define("NOT_CHECK_PERMISSIONS", true);
define("BX_PUBLIC_TOOLS", true);

$site_id = isset($_REQUEST["site"]) && is_string($_REQUEST["site"]) ? trim($_REQUEST["site"]) : "";
$site_id = mb_substr(preg_replace("/[^a-z0-9_]/i", "", $site_id), 0, 2);
define("SITE_ID", $site_id);

$action = isset($_REQUEST["action"]) && is_string($_REQUEST["action"]) ? trim($_REQUEST["action"]) : "";
$post_id = isset($_REQUEST["post_id"]) ? (int)$_REQUEST["post_id"] : 0;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$arResult = array();

$rsSite = CSite::GetByID($site_id);
if ($arSite = $rsSite->Fetch())
{
	define("LANGUAGE_ID", $arSite["LANGUAGE_ID"]);
}
else
{
	define("LANGUAGE_ID", "en");
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/mobile_app/components/bitrix/socialnetwork.blog.post/mobile/ajax.php");

$strError = false;

if ($post_id <= 0)
{
	$strError = GetMessage("BLOG_MOBILE_AJAX_POST_ID_ERROR");
}
elseif (!$GLOBALS["USER"]->IsAuthorized())
{
	$strError = GetMessage("BLOG_MOBILE_AJAX_USER_NOT_AUTHORIZED_ERROR");
}
elseif (!check_bitrix_sessid())
{
	$strError = GetMessage("BLOG_MOBILE_AJAX_SESSION_ERROR");
}
elseif (!CModule::IncludeModule("blog"))
{
	$strError = GetMessage("BLOG_MOBILE_AJAX_BLOG_MODULE_ERROR");
}
elseif (!CModule::IncludeModule("socialnetwork"))
{
	$strError = GetMessage("BLOG_MOBILE_AJAX_SONET_MODULE_ERROR");
}
elseif (!($arBlogPost = CBlogPost::GetByID($post_id)))
{
	$strError = GetMessage("BLOG_MOBILE_BLOG_POST_ERROR");
}
elseif (!($arBlog = CBlog::GetByID($arBlogPost["BLOG_ID"])))
{
	$strError = GetMessage("BLOG_MOBILE_BLOG_ERROR");
}

if(!$strError)
{
	if ($action == "delete_post")
	{
		$PostPerm = CBlogPost::GetSocNetPostPerms($post_id, true, $GLOBALS["USER"]->GetID(), $arBlogPost["AUTHOR_ID"]);
		if ($PostPerm < BLOG_PERMS_FULL)
		{
			$strError = GetMessage("BLOG_MOBILE_DELETE_PERMISSION_ERROR");
		}
		else
		{
			CBlogPost::DeleteLog($post_id);
			if (CBlogPost::Delete($post_id))
			{
				BXClearCache(True, "/".SITE_ID."/blog/popular_posts/");
				BXClearCache(true, "/blog/socnet_post/".$post_id."/");
				BXClearCache(true, "/blog/socnet_post/gen/".$post_id."/");
			}
			else
			{
				$strError = GetMessage("BLOG_MOBILE_DELETE_ERROR");
			}
		}
	}
	elseif ($action == "read_post")
	{
		$PostPerm = CBlogPost::GetSocNetPostPerms($post_id, true, $GLOBALS["USER"]->GetID(), $arBlogPost["AUTHOR_ID"]);
		if ($PostPerm < BLOG_PERMS_READ)
		{
			$strError = GetMessage("BLOG_MOBILE_BLOG_POST_ERROR");
		}
		else
		{
			if (!function_exists("__OnAfterCBlogUserOptionsSet"))
			{
				function __OnAfterCBlogUserOptionsSet($options, $cache_id, $cache_path)
				{
					global $APPLICATION;
					$APPLICATION->RestartBuffer();
					header('Content-Type:application/json; charset=UTF-8');
					echo \Bitrix\Main\Web\Json::encode(array("SUCCESS" => "Y", "options" => $options));

					/** @noinspection PhpUndefinedClassInspection */
					\CMain::finalActions();
					die;
				}
			}

			$LocalRedirectHandlerId = AddEventHandler('socialnetwork', 'OnAfterCBlogUserOptionsSet', "__OnAfterCBlogUserOptionsSet");
			global $APPLICATION;
			$APPLICATION->IncludeComponent(
				"bitrix:socialnetwork.blog.blog",
				"important",
				Array(
					"BLOG_URL" => "",
					"FILTER" => array("=UF_BLOG_POST_IMPRTNT" => 1, "!POST_PARAM_BLOG_POST_IMPRTNT" => array("USER_ID" => $GLOBALS["USER"]->GetId(), "VALUE" => "Y")),
					"FILTER_NAME" => "",
					"YEAR" => "",
					"MONTH" => "",
					"DAY" => "",
					"CATEGORY_ID" => "",
					"GROUP_ID" => array(),
					"USER_ID" => $GLOBALS["USER"]->GetId(),
					"SOCNET_GROUP_ID" => 0,
					"SORT" => array(),
					"SORT_BY1" => "",
					"SORT_ORDER1" => "",
					"SORT_BY2" => "",
					"SORT_ORDER2" => "",
					//************** Page settings **************************************
					"MESSAGE_COUNT" => 1,
					"NAV_TEMPLATE" => "",
					"PAGE_SETTINGS" => array("bDescPageNumbering" => false, "nPageSize" => 10),
					//************** URL ************************************************
					"BLOG_VAR" => "",
					"POST_VAR" => "",
					"USER_VAR" => "",
					"PAGE_VAR" => "",
					"PATH_TO_BLOG" => "",
					"PATH_TO_BLOG_CATEGORY" => "",
					"PATH_TO_BLOG_POSTS" => "",
					"PATH_TO_POST" => "",
					"PATH_TO_POST_EDIT" => "",
					"PATH_TO_USER" => "",
					"PATH_TO_SMILE" => "",
					//************** ADDITIONAL *****************************************
					"DATE_TIME_FORMAT" => "",
					"NAME_TEMPLATE" => "",
					"SHOW_LOGIN" => "Y",
					"AVATAR_SIZE" => 42,
					"SET_TITLE" => "N",
					"SHOW_RATING" => "N",
					"RATING_TYPE" => "",
					//************** CACHE **********************************************
					"CACHE_TYPE" => "A",
					"CACHE_TIME" => 3600,
					"CACHE_TAGS" => array("IMPORTANT", "IMPORTANT".$GLOBALS["USER"]->GetId()),
					//************** Template Settings **********************************
					"OPTIONS" => array(array("name" => "BLOG_POST_IMPRTNT", "value" => "Y")),
				),
				null
			);
			RemoveEventHandler('socialnetwork', 'OnAfterCBlogUserOptionsSet', $LocalRedirectHandlerId);
		}
	}
	elseif($action === 'get_blog_post_data')
	{
		$arResult = \Bitrix\Mobile\Livefeed\Helper::getBlogPostFullData([
			'postId' => $post_id,
			'nameTemplate' => $_REQUEST['nt'],
			'showLogin' => $_REQUEST['sl']
		]);
	}
	elseif($action == "get_comment_data")
	{
		$comment_id = (
			isset($_REQUEST["comment_id"])
				? intval($_REQUEST["comment_id"])
				: 0
		);

		if (
			$comment_id > 0
			&& ($arComment = CBlogComment::GetByID($comment_id))
			&& $arComment["POST_ID"] == $post_id
		)
		{
			$postPerm = CBlogPost::GetSocNetPostPerms($post_id, false, $GLOBALS["USER"]->GetId(), $arBlogPost["AUTHOR_ID"]);
			if ($postPerm > BLOG_PERMS_DENY)
			{
				if(
					$arComment["AUTHOR_ID"] == $GLOBALS["USER"]->GetId()
					|| CSocNetUser::IsCurrentUserModuleAdmin(SITE_ID, false)
				)
				{
					$arResult["CommentCanEdit"] = 'Y';
				}

				$arResult["CommentDetailText"] = htmlspecialcharsback($arComment["POST_TEXT"]);

				$bDiskOrWebDavInstalled = (IsModuleInstalled('disk') || IsModuleInstalled('webdav'));

				$ufCode = (
					$bDiskOrWebDavInstalled
						? "UF_BLOG_COMMENT_FILE"
						: "UF_BLOG_COMMENT_DOC"
				);

				$arResult["CommentUFCode"] = $ufCode;

				$arResult["CommentFiles"] = CMobileHelper::getUFForPostForm(array(
					"ENTITY_TYPE" => "BLOG_COMMENT",
					"ENTITY_ID" => $comment_id,
					"UF_CODE" => $ufCode,
					"IS_DISK_OR_WEBDAV_INSTALLED" => $bDiskOrWebDavInstalled
				));
			}
		}
	}
	elseif ($action == "file_comment_upload")
	{
		$perm = BLOG_PERMS_DENY;
		$postPerm = CBlogPost::GetSocNetPostPerms($arBlogPost["ID"]);

		if ($postPerm > BLOG_PERMS_DENY)
		{
			if (IsModuleInstalled("bitrix24"))
			{
				$perm = ($arBlogPost["AUTHOR_ID"] != $GLOBALS["USER"]->GetId() ? BLOG_PERMS_WRITE : BLOG_PERMS_FULL);
			}
			else
			{
				$perm = CBlogComment::GetSocNetUserPerms($arBlogPost["ID"], $arBlogPost["AUTHOR_ID"]);
			}
		}

		if ($perm < BLOG_PERMS_PREMODERATE)
		{
			$strError = "Can't save file";
		}

		if (
			!$strError
			&& (
				!is_array($_FILES)
				|| count($_FILES) <= 0
				|| !array_key_exists("file", $_FILES)
			)
		)
		{
			$strError = "Empty file";
		}

		if (!$strError)
		{
			$arFileStorage = CMobileHelper::InitFileStorage();

			if (isset($arFileStorage["ERROR_CODE"]))
			{
				$strError = (!empty($arFileStorage["ERROR_MESSAGE"]) ? $arFileStorage["ERROR_MESSAGE"] : "Cannot init storage");
			}
		}

		if (!$strError)
		{
			$moduleId = "uf";

			$arFile = $_FILES["file"];
			$arFile["MODULE_ID"] = $moduleId;

			$ufCode = (
				isset($arFileStorage["DISC_FOLDER"])
				|| isset($arFileStorage["WEBDAV_DATA"])
					? "UF_BLOG_COMMENT_FILE"
					: "UF_BLOG_COMMENT_DOC"
			);

			$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_COMMENT", 0, LANGUAGE_ID);
			if (empty($arPostFields[$ufCode]))
			{
				$strError = "Userfield not exists";
			}
		}

		if (!$strError)
		{
			$pos = mb_strpos($arFile["name"], '?');
			if ($pos !== false)
			{
				$arFile["name"] = mb_substr($arFile["name"], 0, $pos);
			}

			$res = ''.CFile::CheckImageFile(
				$arFile,
				(
					intval($arPostFields[$ufCode]['SETTINGS']['MAX_ALLOWED_SIZE']) > 0
						? $arPostFields[$ufCode]['SETTINGS']['MAX_ALLOWED_SIZE']
						: 5000000
				),
				0,
				0
			);

			if ($res !== '')
			{
				$strError = "Incorrect file";
			}
		}

		if (!$strError)
		{
			$arSaveResult = CMobileHelper::SaveFile($arFile, $arFileStorage);

			if (
				!$arSaveResult
				|| !isset($arSaveResult["ID"])
			)
			{
				$strError = "Can't save file";
			}
		}

		if (!$strError)
		{
			if (isset($arFileStorage["DISC_FOLDER"]))
			{
				$commentText = "[DISK FILE ID=n".$arSaveResult["ID"]."]";
			}
			elseif (isset($arFileStorage["WEBDAV_DATA"]))
			{
				$commentText = "[DOCUMENT ID=".$arSaveResult["ID"]."]";
			}
			else
			{
				$commentText = ".";
			}

			$UserIP = CBlogUser::GetUserIP();
			$arCommentFields = Array(
				"POST_ID" => $post_id,
				"BLOG_ID" => $arBlogPost["BLOG_ID"],
				"TITLE" => "",
				"POST_TEXT" => $commentText,
				"DATE_CREATE" => ConvertTimeStamp(time() + CTimeZone::GetOffset(), "FULL"),
				"AUTHOR_IP" => $UserIP[0],
				"AUTHOR_IP1" => $UserIP[1],
				"URL" => $arBlog["URL"],
				"PARENT_ID" => false,
				"AUTHOR_ID" => $GLOBALS["USER"]->GetId(),
				$ufCode => array(
					(isset($arFileStorage["DISC_FOLDER"]) ? "n".$arSaveResult["ID"] : $arSaveResult["ID"])
				)
			);

			if ($perm == BLOG_PERMS_PREMODERATE)
			{
				$arCommentFields["PUBLISH_STATUS"] = BLOG_PUBLISH_STATUS_READY;
			}

			$commentUrl = CComponentEngine::MakePathFromTemplate(
				COption::GetOptionString("socialnetwork", "userblogpost_page", false, SITE_ID),
				array(
					"blog" => $arBlog["URL"],
					"post_id" => $arBlogPost["ID"],
					"user_id" => $arBlog["OWNER_ID"]
				)
			);

			$arCommentFields["PATH"] = $commentUrl.(mb_strpos($arCommentFields["PATH"], "?") !== false ? "&" : "?")."commentId=#comment_id###comment_id#";
			if (!($commentId = CBlogComment::Add($arCommentFields)))
			{
				$strError = "Can't add blog comment";
			}
			else
			{
				BXClearCache(true, "/blog/comment/".intval($arBlogPost["ID"] / 100)."/".$arBlogPost["ID"]."/");
			}
		}

		if (!$strError)
		{
			BXClearCache(true, "/blog/comment/".$post_id."/");
			$GLOBALS["DB"]->Query("UPDATE b_blog_image SET COMMENT_ID=".intval($commentId)." WHERE BLOG_ID=".intval($arBlogPost["BLOG_ID"])." AND POST_ID=".intval($post_id)." AND IS_COMMENT = 'Y' AND (COMMENT_ID = 0 OR COMMENT_ID is null) AND USER_ID=".intval($GLOBALS["USER"]->GetId())."", true);

			if (
				$arCommentFields["PUBLISH_STATUS"] != BLOG_PUBLISH_STATUS_PUBLISH
				&& $arCommentFields["PUBLISH_STATUS"] <> ''
			)
			{
				$strError = "Blog comment hasn't been published";
			}
		}

		if (!$strError)
		{
			$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;

			$rsLog = CSocNetLog::GetList(
				array("ID" => "DESC"),
				array(
					"EVENT_ID" => $blogPostLivefeedProvider->getEventId(),
					"SOURCE_ID" => $post_id
				),
				false,
				false,
				array("ID")
			);
			if (!($arLog = $rsLog->Fetch()))
			{
				$strError = "Can't find parent log entry";
			}
		}

		if (!$strError)
		{
			$parserBlog = new blogTextParser();
			$arAllow = array("HTML" => "N", "ANCHOR" => "N", "BIU" => "N", "IMG" => "N", "QUOTE" => "N", "CODE" => "N", "FONT" => "N", "TABLE" => "N", "LIST" => "N", "SMILES" => "N", "NL2BR" => "N", "VIDEO" => "N");
			$text4message = $parserBlog->convert($arCommentFields["POST_TEXT"], false, array(), $arAllow, array("isSonetLog" => true));

			$arFieldsForSocnet = array(
				"ENTITY_TYPE" => SONET_ENTITY_USER,
				"ENTITY_ID" => $arBlog["OWNER_ID"],
				"EVENT_ID" => "blog_comment",
				"=LOG_DATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
				"MESSAGE" => $text4message,
				"TEXT_MESSAGE" => $text4message,
				"URL" => $commentUrl,
				"MODULE_ID" => false,
				"SOURCE_ID" => $commentId,
				"LOG_ID" => $arLog["ID"],
				"RATING_TYPE_ID" => "BLOG_COMMENT",
				"RATING_ENTITY_ID" => intval($commentId),
				"USER_ID" => $GLOBALS["USER"]->GetId()
			);

			if (!($log_comment_id = CSocNetLogComments::Add($arFieldsForSocnet, false, false)))
			{
				$strError = "Can't add socialnetwork comment";
			}
		}

		if (!$strError)
		{
			CSocNetLog::CounterIncrement(
				$log_comment_id,
				false,
				false,
				"LC",
				CSocNetLogRights::CheckForUserAll($arLog["ID"])
			);

			if (isset($arFileStorage["DISC_FOLDER"]))
			{
				$arResult["DISK_FILE_ID"] = $arSaveResult["ID"];
			}
			elseif (isset($arFileStorage["WEBDAV_DATA"]))
			{
				$arResult["DOCUMENT_ID"] = $arSaveResult["ID"];
			}
			else
			{
				$arResult["FILE_ID"] = $arSaveResult["ID"];
			}

			$arResult["BLOG_COMMENT_ID"] = $commentId;
			$arResult["SONET_COMMENT_ID"] = $log_comment_id;

			if (
				CModule::IncludeModule("pull")
				&& CPullOptions::GetNginxStatus()
				&& $arComment = CBlogComment::GetByID($commentId)
			)
			{
				$arParams = array(
					"PATH_TO_USER" => $_REQUEST["p_user"],
					"PATH_TO_POST" => $_REQUEST["p_bpost"],
					"NAME_TEMPLATE" => $_REQUEST["nt"],
					"SHOW_LOGIN" => $_REQUEST["sl"],
					"AVATAR_SIZE_COMMENT" => (isset($_REQUEST["as"]) ? intval($_REQUEST["as"]) : 100),
					"PATH_TO_SMILE" => '',
					"DATE_TIME_FORMAT" => $_REQUEST["dtf"],
					"SHOW_RATING" => $_REQUEST["sr"]
				);

				$arComment["DateFormated"] = FormatDateFromDB($arComment["DATE_CREATE"], $arParams["DATE_TIME_FORMAT"], true);

				if (
					strcasecmp(LANGUAGE_ID, 'EN') !== 0
					&& strcasecmp(LANGUAGE_ID, 'DE') !== 0
				)
				{
					$arComment["DateFormated"] = mb_strtolower($arComment["DateFormated"]);
				}

				$arAuthor = CBlogUser::GetUserInfo(
					$arComment["AUTHOR_ID"],
					$arParams["PATH_TO_USER"],
					array(
						"AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"]
					)
				);
				if (IsModuleInstalled('extranet'))
				{
					CSocNetTools::InitGlobalExtranetArrays();
				}

				$arTmpUser = array(
					"NAME" => $arAuthor["~NAME"],
					"LAST_NAME" => $arAuthor["~LAST_NAME"],
					"SECOND_NAME" => $arAuthor["~SECOND_NAME"],
					"LOGIN" => $arAuthor["~LOGIN"],
					"NAME_LIST_FORMATTED" => "",
				);
				$arAuthor["NAME_FORMATED"] = CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false));

				if (intval($arAuthor["PERSONAL_PHOTO"]) > 0)
				{
					$image_resize = CFile::ResizeImageGet(
						$arAuthor["PERSONAL_PHOTO"],
						array(
							"width" => $arParams["AVATAR_SIZE_COMMENT"],
							"height" => $arParams["AVATAR_SIZE_COMMENT"]
						),
						BX_RESIZE_IMAGE_EXACT
					);
					$arAuthor["PERSONAL_PHOTO_RESIZED"] = array("src" => $image_resize["src"]);
				}

				$p = new blogTextParser(false, '');

				$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("BLOG_COMMENT", $arComment["ID"], LANGUAGE_ID);
				if (is_array($arPostFields[$ufCode]))
				{
					$p->arUserfields = array($ufCode => array_merge($arPostFields[$ufCode], array("TAG" => "DOCUMENT ID")));
				}

				$arAllow = array("HTML" => "N", "ANCHOR" => "Y", "BIU" => "Y", "IMG" => "Y", "QUOTE" => "Y", "CODE" => "Y", "FONT" => "Y", "LIST" => "Y", "SMILES" => "Y", "NL2BR" => "N", "VIDEO" => "Y", "SHORT_ANCHOR" => "Y");
				$arParserParams = Array(
					"imageWidth" => 800,
					"imageHeight" => 800,
				);
				$arComment["TextFormated"] = $p->convert($arComment["POST_TEXT"], false, array(), $arAllow, $arParserParams);

				$p->bMobile = true;
				$arComment["TextFormatedMobile"] = $p->convert($arComment["POST_TEXT"], false, array(), $arAllow, $arParserParams);

				if ($perm >= BLOG_PERMS_MODERATE)
				{
					if ($arComment["PUBLISH_STATUS"] == BLOG_PUBLISH_STATUS_PUBLISH)
					{
						$arComment["CAN_HIDE"] = "Y";
					}
					else
					{
						$arComment["CAN_SHOW"] = "Y";
					}
				}
				else
				{
					$arComment["CAN_SHOW"] = $arComment["CAN_HIDE"] = "N";
				}

				$urlToPost = CComponentEngine::MakePathFromTemplate(
					htmlspecialcharsBack($arParams["PATH_TO_POST"]),
					array(
						"post_id" => "#source_post_id#",
						"user_id" => $arPost["AUTHOR_ID"]
					)
				);

				$urlToPost .= (mb_strpos($urlToPost, "?") !== false ? "&" : "?");

				$arFields = array(
					"POST_ID" => $arBlogPost["ID"],
					"COMMENT_ID" => $commentId,
					"arComment" => $arComment,
					"arAuthor" => $arAuthor,
					"arUrl" => array(
						"LINK" => $urlToPost,
						"SHOW" => $urlToPost."show_comment_id=#comment_id#&comment_post_id=#post_id#&".bitrix_sessid_get(),
						"HIDE" => $urlToPost."hide_comment_id=#comment_id#&comment_post_id=#post_id#&".bitrix_sessid_get(),
						"DELETE" => $urlToPost."delete_comment_id=#comment_id#&comment_post_id=#post_id#&".bitrix_sessid_get(),
						"USER" => htmlspecialcharsback($arParams["PATH_TO_USER"])
					),
					"RATING_TYPE" => "like",
					"SHOW_RATING" => $arParams["SHOW_RATING"]
				);

				$arParams["RATING_TYPE"] = "like";
				CRatingsComponentsMain::GetShowRating($arParams);
				if ($arParams["SHOW_RATING"] == "Y")
				{
					$arFields['arRating'] = CRatings::GetRatingVoteResult('BLOG_COMMENT', array($arFields["arComment"]["ID"]));
				}

				CMobileHelper::SendPullComment("blog", $arFields);
			}
		}
	}
}

if (!$strError)
{
	$arResult["SUCCESS"] = "Y";
}
else
{
	$arResult["ERROR"] = $strError;
}

echo \Bitrix\Main\Web\Json::encode($arResult);

define('PUBLIC_AJAX_MODE', true);
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
die();
