<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use \Bitrix\Main\Data\AppCacheManifest;
use \Bitrix\Main\Page\Asset;
use \Bitrix\Main\Loader;
use \Bitrix\Main\UI;

$APPLICATION->AddHeadScript("/bitrix/components/bitrix/mobile.socialnetwork.log.ex/templates/.default/mobile_files.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/mobile.socialnetwork.log.ex/templates/.default/script_attached.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/rating.vote/templates/mobile_comment_like/script_attached.js");
$APPLICATION->AddHeadScript("/bitrix/js/main/rating_like.js");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/components/bitrix/voting.current/.userfield/script.js");
if (CModule::IncludeModule("vote") && class_exists("\\Bitrix\\Vote\\UF\\Manager"))
{
	$APPLICATION->AddHeadScript("/bitrix/components/bitrix/voting.uf/templates/.default/script.js");
	\Bitrix\Main\Page\Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL('/bitrix/components/bitrix/voting.uf/templates/.default/style.css').'" type="text/css" rel="stylesheet" />');
	\Bitrix\Main\Page\Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL('/bitrix/js/ui/buttons/ui.buttons.css').'" type="text/css" rel="stylesheet" />');
}
else if (IsModuleInstalled("vote"))
{
	\Bitrix\Main\Page\Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH.'/components/bitrix/voting.current/.userfield/style.css').'" type="text/css" rel="stylesheet" />');
}

if ($arParams["EMPTY_PAGE"] == "Y")
{
	\Bitrix\Main\Page\Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL('/bitrix/components/bitrix/rating.vote/templates/like_react/style.css').'" type="text/css" rel="stylesheet" />');
	\Bitrix\Main\Page\Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL('/bitrix/js/ui/icons/base/ui.icons.base.css').'" type="text/css" rel="stylesheet" />');
	\Bitrix\Main\Page\Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL('/bitrix/js/ui/icons/b24/ui.icons.b24.css').'" type="text/css" rel="stylesheet" />');
}

$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/log_mobile.js");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/bizproc_mobile.js");
$APPLICATION->SetUniqueJS('live_feed_mobile');
$APPLICATION->SetUniqueCSS('live_feed_mobile');
CUtil::InitJSCore(array('date', 'ls', 'fx', 'comment_aux', 'content_view'));
UI\Extension::load("main.rating"); // js only
UI\Extension::load("socialnetwork.commentaux"); // lang messages only

if (Loader::includeModule('tasks'))
{
	CUtil::InitJSCore(array('tasks', 'tasks_util_query'));
}
if (Loader::includeModule('vote'))
{
	UI\Extension::load("ui.buttons");
}

if (strlen($arResult["FatalError"])>0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	if (
		$arParams["LOG_ID"] <= 0
		&& $arParams["EMPTY_PAGE"] != "Y"
		&& !$arResult["AJAX_CALL"]
	)
	{
		?><div id="lenta_wrapper_global"><?
	}
	?>
	<script>
		var bGlobalReload = <?=($arResult["RELOAD"] ? "true" : "false")?>;
	</script>
	<?
	AppCacheManifest::getInstance()->addAdditionalParam("page", "livefeed");
	AppCacheManifest::getInstance()->addAdditionalParam("preventAutoUpdate", "Y");
	AppCacheManifest::getInstance()->addAdditionalParam("MobileAPIVersion", CMobile::getApiVersion());
	AppCacheManifest::getInstance()->addAdditionalParam("MobilePlatform", CMobile::getPlatform());
	AppCacheManifest::getInstance()->addAdditionalParam("UserID", $USER->GetID());
	AppCacheManifest::getInstance()->addAdditionalParam("version", "v8");

	?><script>
		BX.message({
			MSLSiteDir: '<?=CUtil::JSEscape(SITE_DIR)?>',
			MSLPullDownText1: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_NEW_PULL"))?>',
			MSLPullDownText2: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_NEW_PULL_RELEASE"))?>',
			MSLPullDownText3: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_NEW_PULL_LOADING"))?>',

            MOBILE_TASKS_VIEW_TAB_TASK: '<?=CUtil::JSEscape(GetMessage("MOBILE_TASKS_VIEW_TAB_TASK"))?>',
            MOBILE_TASKS_VIEW_TAB_CHECKLIST: '<?=CUtil::JSEscape(GetMessage("MOBILE_TASKS_VIEW_TAB_CHECKLIST"))?>',
            MOBILE_TASKS_VIEW_TAB_FILES: '<?=CUtil::JSEscape(GetMessage("MOBILE_TASKS_VIEW_TAB_FILES"))?>',
            MOBILE_TASKS_VIEW_TAB_COMMENT: '<?=CUtil::JSEscape(GetMessage("MOBILE_TASKS_VIEW_TAB_COMMENT"))?>',

			MSLExtranetSiteId: <?=(!empty($arResult["extranetSiteId"]) ? "'".CUtil::JSEscape($arResult["extranetSiteId"])."'" : "false")?>,
			MSLExtranetSiteDir: <?=(!empty($arResult["extranetSiteDir"]) ? "'".CUtil::JSEscape($arResult["extranetSiteDir"])."'" : "false")?>
		});
	</script><?

	if (
		$arParams["EMPTY_PAGE"] != "Y"
		&& !$arResult["AJAX_CALL"]
		&& !$arResult["RELOAD"]
		&& intval($arParams["GROUP_ID"]) <= 0
		&& intval($arParams["LOG_ID"]) <= 0
		&& $_REQUEST["empty_get_comments"] != "Y"
		&& $_REQUEST["empty_get_comments"] != "Y"
	)
	{
		?><script>
			__MSLOnFeedPreInit({
				arAvailableGroup: <?=CUtil::PhpToJSObject(
					!empty($arResult["arAvailableGroup"])
					&& is_array($arResult["arAvailableGroup"])
						? $arResult["arAvailableGroup"]
						: false
				)?>
			});
		</script><?

		if (
			!$arParams["FILTER"]
			&& (
				!isset($arParams["CREATED_BY_ID"])
				|| intval($arParams["CREATED_BY_ID"]) <= 0
			)
		)
		{
			$frame = \Bitrix\Main\Page\Frame::getInstance();
			$frame->setEnable();
			$frame->setUseAppCache();
			$frame->setPreventAutoUpdate();

			?><script>
				window.appCacheVars = <?=CUtil::PhpToJSObject(AppCacheManifest::getInstance()->getAdditionalParams())?>;
			</script><?

			?><div id="framecache-block-feed"><?
			$feedFrame = $this->createFrame("framecache-block-feed", false)->begin("");
			$feedFrame->setBrowserStorage(true);
		}
	}

	$event_cnt = 0;

	if ($arResult["RELOAD"])
	{
		if ($arResult["RELOAD_JSON"])
		{
			CJSCore::Init(array("fc"), false);
			Asset::getInstance()->startTarget('livefeed_ajax', \Bitrix\Main\Page\AssetMode::STANDARD);
		}
		?><script>
			var bGlobalReload = true;
		</script>
		<div id="bxdynamic_feed_refresh"><?
	}

	$like = COption::GetOptionString("main", "rating_text_like_y", GetMessage("MOBILE_LOG_LIKE"));
	$like2 = str_replace('#LIKE#', $like, GetMessage("MOBILE_LOG_LIKE2_PATTERN"));

	?>
	<script>
		BX.message({
			MSLLike: '<?=CUtil::JSEscape(htmlspecialcharsEx($like))?>',
			MSLLike2: '<?=CUtil::JSEscape(htmlspecialcharsEx($like2))?>',
			MSLPostFormTableOk: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_TABLE_OK")?>',
			MSLPostFormTableCancel: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_TABLE_CANCEL")?>',
			MSLPostFormSend: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_SEND")?>',
			MSLPostDestUA: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_DEST_UA")?>',
			MSLGroupName: '<?=(!empty($arResult["GROUP_NAME"]) ? CUtil::JSEscape($arResult["GROUP_NAME"]) : '')?>',
			MSLIsDenyToAll: '<?=($arResult["bDenyToAll"] ? 'Y' : 'N')?>',
			MSLIsDefaultToAll: '<?=($arResult["bDefaultToAll"] ? 'Y' : 'N')?>',
			MSLPostFormPhotoCamera: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_PHOTO_CAMERA")?>',
			MSLPostFormPhotoGallery: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_PHOTO_GALLERY")?>',
			MSLPostFormUFCode: '<?=CUtil::JSEscape($arResult["postFormUFCode"])?>',
			MSLIsExtranetSite: '<?=($arResult["bExtranetSite"] ? 'Y' : 'N')?>'
			<?
			if (
				$arResult["bDiskInstalled"]
				|| $arResult["bWebDavInstalled"]
			)
			{
				if ($arResult["bDiskInstalled"])
				{
					?>
					, MSLbDiskInstalled: 'Y'
					<?
				}
				else
				{
					?>
					, MSLbWebDavInstalled: 'Y'
					<?
				}
				?>
				, MSLPostFormDisk: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_DISK")?>'
				, MSLPostFormDiskTitle: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_DISK_TITLE")?>'
				<?
			}
			?>
		});

		var initParams = {
			logID: <?=$arParams["LOG_ID"]?>,
			bAjaxCall: <?=($arResult["AJAX_CALL"] ? "true" : "false")?>,
			bReload: bGlobalReload,
			bEmptyPage: <?=($arParams["EMPTY_PAGE"] == "Y" ? "true" : "false")?>,
			bFiltered: <?=(
				$arParams["FILTER"]
				|| (
					isset($arParams["CREATED_BY_ID"])
					&& intval($arParams["CREATED_BY_ID"]) > 0
				)
					? "true"
					: "false"
			)?>,
			bEmptyGetComments: <?=($_REQUEST["empty_get_comments"] == "Y" ? "true" : "false")?>,
			groupID: <?=$arParams["GROUP_ID"]?>,
			curUrl: '<?=$APPLICATION->GetCurPageParam("", array("LAST_LOG_TS", "AJAX_CALL", "RELOAD", "RELOAD_JSON"))?>',
			appCacheDebug: <?=AppCacheManifest::getInstance()->getDebug() ? "true" : "false"?>,
			tmstmp: <?=time()?>,
			strCounterType: '<?=$arResult["COUNTER_TYPE"]?>',
			bFollowDefault: <?=($arResult["FOLLOW_DEFAULT"] != "N" ? "true" : "false")?>,
			bShowExpertMode: <?=($arResult["SHOW_EXPERT_MODE"] == "Y" ? "true" : "false")?>,
			bExpertMode: <?=($arResult["EXPERT_MODE"] == "Y" ? "true" : "false")?>
		};

		BX.ready(function() {
			__MSLOnFeedInit(initParams);
		});

	</script>
	<?
	if (
		$arParams["LOG_ID"] <= 0
		&& $arParams["EMPTY_PAGE"] != "Y"
		&& !$arResult["AJAX_CALL"]
	)
	{
		if (
			isset($arResult["GROUP_NAME"])
			&& strlen($arResult["GROUP_NAME"]) > 0
		)
		{
			$pageTitle = CUtil::JSEscape($arResult["GROUP_NAME"]);
		}
		elseif ($arParams["FILTER"] == "favorites")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_FAVORITES");
		}
		elseif ($arParams["FILTER"] == "my")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_MY");
		}
		elseif ($arParams["FILTER"] == "important")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_IMPORTANT");
		}
		elseif ($arParams["FILTER"] == "work")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_WORK");
		}
		elseif ($arParams["FILTER"] == "bizproc")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_BIZPROC");
		}
		elseif ($arParams["FILTER"] == "blog")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_BLOG");
		}
		else
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_TITLE");
		}

		?><script>
			var arLogTs = {};
			var arCanUserComment = {};
			var bRefreshing = false;
			var bGettingNextPage = false;
			var iPageNumber = 1;
			var nextPageXHR = null;

			BX.message({
				MSLPageId: '<?=CUtil::JSEscape(RandString(4))?>',
				MSLPageNavNum: <?=intval($arResult["PAGE_NAVNUM"])?>,
				MSLSessid: '<?=bitrix_sessid()?>',
				MSLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				MSLSiteDir: '<?=CUtil::JSEscape(SITE_DIR)?>',
				MSLLangId: '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
				MSLDestinationLimit: '<?=intval($arParams["DESTINATION_LIMIT_SHOW"])?>',
				MSLNameTemplate: '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
				MSLShowLogin: '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>',
				MSLShowRating: '<?=CUtil::JSEscape($arParams["SHOW_RATING"])?>',
				MSLNextPostMoreTitle: '<?=GetMessageJS("MOBILE_LOG_NEXT_POST_MORE")?>',
				MSLLogCounter1: '<?=GetMessageJS("MOBILE_LOG_COUNTER_1")?>',
				MSLLogCounter2: '<?=GetMessageJS("MOBILE_LOG_COUNTER_2")?>',
				MSLLogCounter3: '<?=GetMessageJS("MOBILE_LOG_COUNTER_3")?>',
				MSLAddPost: '<?=GetMessageJS("MOBILE_LOG_ADD_POST")?>',
				MSLMenuItemGroupTasks: '<?=GetMessageJS("MB_TASKS_AT_SOCNET_LOG_CPT_MENU_ITEM_LIST")?>',
				MSLMenuItemGroupFiles: '<?=GetMessageJS("MOBILE_LOG_GROUP_FILES")?>',
				MSLPathToTasksRouter: '<?=CUtil::JSEscape($arParams["PATH_TO_TASKS_SNM_ROUTER"])?>',
				MSLPathToLogEntry: '<?=CUtil::JSEscape($arParams["PATH_TO_LOG_ENTRY"])?>',
				MSLPathToUser: '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
				MSLPathToGroup: '<?=CUtil::JSEscape($arParams["PATH_TO_GROUP"])?>',
				MSLPathToCrmLead: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMLEAD"])?>',
				MSLPathToCrmDeal: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMDEAL"])?>',
				MSLPathToCrmContact: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCONTACT"])?>',
				MSLPathToCrmCompany: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCOMPANY"])?>',
				MSLMenuItemFavorites: '<?=GetMessageJS("MOBILE_LOG_MENU_FAVORITES")?>',
				MSLMenuItemMy: '<?=GetMessageJS("MOBILE_LOG_MENU_MY")?>',
				MSLMenuItemImportant: '<?=GetMessageJS("MOBILE_LOG_MENU_IMPORTANT")?>',
				MSLMenuItemRefresh: '<?=GetMessageJS("MOBILE_LOG_MENU_REFRESH")?>',
				MSLFirstPageLastTS : <?=intval($arResult["dateLastPageTS"])?>,
				MSLSliderAddPost: '<?=GetMessageJS("MOBILE_LOG_SLIDER_ADD_POST")?>',
				MSLSliderFavorites: '<?=GetMessageJS("MOBILE_LOG_SLIDER_FAVORITES")?>',
				MSLMobilePlayerErrorMessage: '<?=GetMessageJS("MOBILE_PLAYER_ERROR_MESSAGE")?>',
				MSLLoadScriptsNeeded: '<?=(COption::GetOptionString('main', 'optimize_js_files', 'N') == 'Y' ? 'N' : 'Y')?>',
				MSLLogTitle: '<?=$pageTitle?>',
				MOBILE_LOG_NEW_ERROR: '<?=GetMessageJS("MOBILE_LOG_NEW_ERROR")?>'
				<?
				if ($arParams["USE_FOLLOW"] == "Y")
				{
					?>
					, MSLFollowY: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_Y")?>'
					, MSLFollowN: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_N")?>'
					, MSLMenuItemFollowDefaultY: '<?=GetMessageJS("MOBILE_LOG_MENU_FOLLOW_DEFAULT_Y")?>'
					, MSLMenuItemFollowDefaultN: '<?=GetMessageJS("MOBILE_LOG_MENU_FOLLOW_DEFAULT_N")?>'
					, MSLMenuItemExpertModeY: '<?=GetMessageJS("MOBILE_LOG_MENU_EXPERT_MODE_Y")?>'
					, MSLMenuItemExpertModeN: '<?=GetMessageJS("MOBILE_LOG_MENU_EXPERT_MODE_N")?>'
					<?
				}

				if (
					IsModuleInstalled("timeman")
					|| IsModuleInstalled("tasks")
				)
				{
					?>
					, MSLMenuItemWork: '<?=GetMessageJS("MOBILE_LOG_MENU_WORK")?>'
					<?
				}

				if (CModule::IncludeModule("lists") && CLists::isFeatureEnabled())
				{
					?>
					, MSLMenuItemBizproc: '<?=GetMessageJS("MOBILE_LOG_MENU_BIZPROC")?>'
					<?
				}
				?>
			});
		</script>
		<div class="lenta-notifier" id="lenta_notifier" onclick="__MSLRefresh(true); return false;"><?
			?><span class="lenta-notifier-arrow"></span><?
			?><span class="lenta-notifier-text"><?
				?><span id="lenta_notifier_cnt"></span>&nbsp;<span id="lenta_notifier_cnt_title"></span><?
			?></span><?
		?></div><?
		?><div class="lenta-notifier" id="lenta_notifier_2" onclick="app.exec('pullDownLoadingStart'); __MSLRefresh(true); return false;"><?
			?><span class="lenta-notifier-text"><?=GetMessage("MOBILE_LOG_RELOAD_NEEDED")?></span><?
		?></div><?
		?><div class="lenta-notifier" id="lenta_refresh_error" onclick="__MSLRefreshError(false);"><?
			?><span class="lenta-notifier-text"><?=GetMessage("MOBILE_LOG_RELOAD_ERROR")?></span><?
		?></div><?
	}
	elseif ($arParams["EMPTY_PAGE"] == "Y")
	{
		?><div id="empty_comment" style="display: none;"><?
			?><div class="post-comment-block" style="position: relative;"><?
				?><div class="post-user-wrap"><?
					?><div id="empty_comment_avatar" class="avatar"<?=(strlen($arResult["EmptyComment"]["AVATAR_SRC"]) > 0 ? " style=\"background-image:url('".$arResult["EmptyComment"]["AVATAR_SRC"]."');\"" : "")?>></div><?
					?><div class="post-comment-cont"><?
						?><div class="post-comment-author"><?=$arResult["EmptyComment"]["AUTHOR_NAME"]?></div><?
						?><div class="post-comment-preview-wait"></div><?
						?><div class="post-comment-preview-undelivered"></div><?
					?></div><?
				?></div><?
				?><div id="empty_comment_text" class="post-comment-text"></div><?
			?></div><?
		?></div><?
		?><div style="display: none;" id="comment_send_button_waiter" class="send-message-button-waiter"></div>
		<script>
			BX.message({
				MSLPageId: '<?=CUtil::JSEscape(RandString(4))?>',
				MSLSessid: '<?=bitrix_sessid()?>',
				MSLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				MSLLangId: '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
				MSLDetailCommentsLoading: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_LOADING")?>',
				MSLDetailCommentsFailed: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_FAILED")?>',
				MSLDetailCommentsReload: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_RELOAD")?>',
				MSLPathToLogEntry: '<?=CUtil::JSEscape($arParams["PATH_TO_LOG_ENTRY"])?>',
				MSLPathToUser: '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
				MSLPathToGroup: '<?=CUtil::JSEscape($arParams["PATH_TO_GROUP"])?>',
				MSLPathToCrmLead: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMLEAD"])?>',
				MSLPathToCrmDeal: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMDEAL"])?>',
				MSLPathToCrmContact: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCONTACT"])?>',
				MSLPathToCrmCompany: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCOMPANY"])?>',
				MSLDestinationLimit: '<?=intval($arParams["DESTINATION_LIMIT_SHOW"])?>',
				MSLReply: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_REPLY")?>',
				MSLLikesList: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_LIKES_LIST")?>',
				MSLCommentMenuEdit: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_EDIT")?>',
				MSLCommentMenuDelete: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_DELETE")?>',
				MSLNameTemplate: '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
				MSLShowLogin: '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>',
				MSLShowRating: '<?=CUtil::JSEscape($arParams["SHOW_RATING"])?>',
				MSLDestinationHidden1: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_1")?>',
				MSLDestinationHidden2: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_2")?>',
				MSLDestinationHidden3: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_3")?>',
				MSLDestinationHidden4: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_4")?>',
				MSLDestinationHidden5: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_5")?>',
				MSLDestinationHidden6: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_6")?>',
				MSLDestinationHidden7: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_7")?>',
				MSLDestinationHidden8: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_8")?>',
				MSLDestinationHidden9: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_9")?>',
				MSLDestinationHidden0: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_0")?>',
				MSLDeletePostDescription: '<?=GetMessageJS("MOBILE_LOG_DELETE_POST_DESCRIPTION")?>',
				MSLDeletePostButtonOk: '<?=GetMessageJS("MOBILE_LOG_DELETE_BUTTON_OK")?>',
				MSLDeletePostButtonCancel: '<?=GetMessageJS("MOBILE_LOG_DELETE_BUTTON_CANCEL")?>',
				MSLCurrentTime: '<?=time()?>',
				MSLEmptyDetailCommentFormTitle: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENT_ADD_TITLE")?>',
				MSLEmptyDetailCommentFormButtonTitle: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENT_ADD_BUTTON_SEND")?>',
				MSLLoadScriptsNeeded: '<?=(COption::GetOptionString('main', 'optimize_js_files', 'N') == 'Y' ? 'N' : 'Y')?>',
				MSLMobilePlayerErrorMessage: '<?=GetMessageJS("MOBILE_PLAYER_ERROR_MESSAGE")?>',
				MSLDateTimeFormat: '<?=CUtil::JSEscape(CDatabase::DateFormatToPHP(strlen($arParams["DATE_TIME_FORMAT"]) > 0 ? $arParams["DATE_TIME_FORMAT"] : FORMAT_DATETIME))?>'
			});
		</script><?
	}
	elseif (
		$arParams["LOG_ID"] > 0
		&& $_REQUEST["empty_get_comments"] != "Y"
	)
	{
		?><div style="display: none;" id="comment_send_button_waiter" class="send-message-button-waiter"></div>
		<script>
			BX.message({
				MSLPageId: '<?=CUtil::JSEscape(RandString(4))?>',
				MSLSessid: '<?=bitrix_sessid()?>',
				MSLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				MSLLangId: '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
				MSLLogId: <?=intval($arParams["LOG_ID"])?>,
				MSLPathToUser: '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
				MSLPathToGroup: '<?=CUtil::JSEscape($arParams["PATH_TO_GROUP"])?>',
				MSLPathToCrmLead: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMLEAD"])?>',
				MSLPathToCrmDeal: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMDEAL"])?>',
				MSLPathToCrmContact: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCONTACT"])?>',
				MSLPathToCrmCompany: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCOMPANY"])?>',
				MSLDestinationLimit: '<?=intval($arParams["DESTINATION_LIMIT_SHOW"])?>',
				MSLReply: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_REPLY")?>',
				MSLLikesList: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_LIKES_LIST")?>',
				MSLCommentMenuEdit: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_EDIT")?>',
				MSLCommentMenuDelete: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENTS_DELETE")?>',
				MSLNameTemplate: '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
				MSLShowLogin: '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>',
				MSLShowRating: '<?=CUtil::JSEscape($arParams["SHOW_RATING"])?>',
				MSLDestinationHidden1: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_1")?>',
				MSLDestinationHidden2: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_2")?>',
				MSLDestinationHidden3: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_3")?>',
				MSLDestinationHidden4: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_4")?>',
				MSLDestinationHidden5: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_5")?>',
				MSLDestinationHidden6: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_6")?>',
				MSLDestinationHidden7: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_7")?>',
				MSLDestinationHidden8: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_8")?>',
				MSLDestinationHidden9: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_9")?>',
				MSLDestinationHidden0: '<?=GetMessageJS("MOBILE_LOG_DESTINATION_HIDDEN_0")?>',
				MSLMobilePlayerErrorMessage: '<?=GetMessageJS("MOBILE_PLAYER_ERROR_MESSAGE")?>',
				MSLDateTimeFormat: '<?=CUtil::JSEscape(CDatabase::DateFormatToPHP(strlen($arParams["DATE_TIME_FORMAT"]) > 0 ? $arParams["DATE_TIME_FORMAT"] : FORMAT_DATETIME))?>'
				<?
				if ($arParams["USE_FOLLOW"] == "Y")
				{
					?>
					, MSLFollowY: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_Y")?>'
					, MSLFollowN: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_N")?>'
					<?
				}
				?>
			});

			BX.ready(function() {
				BX.bind(window, 'scroll', oMSL.onScrollDetail);
				oMSL.checkScrollButton();

				BX.onCustomEvent(window, 'BX.UserContentView.onInitCall', [{
					mobile: true,
					ajaxUrl: BX.message('MSLSiteDir') + 'mobile/ajax.php',
					commentsContainerId: 'post-comments-wrap',
					commentsClassName: 'post-comment-wrap'
				}]);
			});
		</script><?
	}

	if ($arParams["NEW_LOG_ID"] <= 0)
	{
		?><div class="lenta-wrapper" id="lenta_wrapper"><?
			?><div class="post-comment-block-scroll post-comment-block-scroll-top" style="" id="post-scroll-button-top" onclick="oMSL.scrollTo('top');"><div class="post-comment-block-scroll-arrow post-comment-block-scroll-arrow-top"></div></div><?
			?><div class="post-comment-block-scroll post-comment-block-scroll-bottom" style="" id="post-scroll-button-bottom" onclick="oMSL.scrollTo('bottom');"><div class="post-comment-block-scroll-arrow post-comment-block-scroll-arrow-bottom"></div></div><? // scroll
			?><span id="blog-post-first-after"></span><?
	}

	if(strlen($arResult["ErrorMessage"])>0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}

	if (
		$arResult["AJAX_CALL"]
		|| $arResult["RELOAD_JSON"]
	)
	{
		if ($arResult["AJAX_CALL"]) // next page
		{
			$APPLICATION->RestartBuffer();
		}

		if ($arParams["NEW_LOG_ID"] <= 0)
		{
			$APPLICATION->sPath2css = array();
			$APPLICATION->arHeadScripts = array();
		}
	}

	if (
		$arParams["LOG_ID"] > 0
		|| $arParams["EMPTY_PAGE"] == "Y"
	)
	{
		?><script type="text/javascript">
			var commentVarSiteID = null;
			var commentVarLanguageID = null;
			var commentVarLogID = null;
			var commentVarAvatarSize = <?=intval($arParams["AVATAR_SIZE_COMMENT"])?>;
			var commentVarNameTemplate = '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>';
			var commentVarShowLogin = '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>';
			var commentVarDateTimeFormat = null;
			var commentVarPathToUser = '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>';
			var commentVarPathToBlogPost = '<?=CUtil::JSEscape($arParams["PATH_TO_USER_MICROBLOG_POST"])?>';
			var commentVarBlogPostID = null;
			var commentVarURL = null;
			var commentVarAction = null;
			var commentVarEntityTypeID = null;
			var commentVarEntityID = null;
			var commentVarRatingType = '<?=CUtil::JSEscape($arParams["RATING_TYPE"])?>';
			var tmp_log_id = 0;

			BX.message({
				MSLSiteDir: '<?=CUtil::JSEscape(SITE_DIR)?>',
				MSLLogEntryTitle: '<?=GetMessageJS("MOBILE_LOG_ENTRY_TITLE")?>',
				MSLSharePost: '<?=GetMessageJS("MOBILE_LOG_SHARE_POST")?>',
				MSLShareTableOk: '<?=GetMessageJS("MOBILE_LOG_SHARE_TABLE_OK")?>',
				MSLShareTableCancel: '<?=GetMessageJS("MOBILE_LOG_SHARE_TABLE_CANCEL")?>',
				MSLIsDenyToAll: '<?=($arResult["bDenyToAll"] ? 'Y' : 'N')?>',
				MSLEditPost: '<?=GetMessageJS("MOBILE_LOG_EDIT_POST")?>',
				MSLDeletePost: '<?=GetMessageJS("MOBILE_LOG_DELETE_POST")?>',
				MSLRefreshComments: '<?=GetMessageJS("MOBILE_LOG_MENU_REFRESH_COMMENTS")?>',
				MSLCreateTask: '<?=GetMessageJS("MOBILE_LOG_MENU_CREATE_TASK")?>',
				MSLCreateTaskEntityLink: '<?=GetMessageJS("MOBILE_LOG_CREATE_TASK_LINK")?>',
				MSLCreateTaskEntityLinkBLOG_POST: '<?=GetMessageJS("MOBILE_LOG_CREATE_TASK_LINK_BLOG_POST")?>',
				MSLCreateTaskEntityLinkBLOG_COMMENT: '<?=GetMessageJS("MOBILE_LOG_CREATE_TASK_LINK_BLOG_COMMENT")?>',
				MSLCreateTaskSuccessTitle: '<?=GetMessageJS("MOBILE_LOG_CREATE_TASK_SUCCESS_TITLE")?>',
				MSLCreateTaskSuccessDescription: '<?=GetMessageJS("MOBILE_LOG_CREATE_TASK_SUCCESS_DESCRIPTION")?>',
				MSLCreateTaskSuccessButtonCancelTitle: '<?=GetMessageJS("MOBILE_LOG_CREATE_TASK_SUCCESS_BUTTON_CANCEL_TITLE")?>',
				MSLCreateTaskSuccessButtonOkTitle: '<?=GetMessageJS("MOBILE_LOG_CREATE_TASK_SUCCESS_BUTTON_OK_TITLE")?>',
				MSLCreateTaskFailureTitle: '<?=GetMessageJS("MOBILE_LOG_CREATE_TASK_FAILURE_TITLE")?>',
				MSLCreateTaskErrorGetData: '<?=GetMessageJS("MOBILE_LOG_CREATE_TASK_ERROR_GET_DATA")?>',
				MSLMobilePlayerErrorMessage: '<?=GetMessageJS("MOBILE_PLAYER_ERROR_MESSAGE")?>',
				MSLCreateTaskTaskPath: '<?=SITE_DIR?>mobile/tasks/snmrouter/?routePage=view&USER_ID=#user_id#&TASK_ID=#task_id#'
				<?
				if ($arParams["USE_FOLLOW"] == "Y")
				{
					?>
					, MSLFollowY: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_Y")?>'
					, MSLFollowN: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_N")?>'
					<?
				}
				?>,
				MSLTextPanelMenuPhoto: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_TEXTPANEL_MENU_PHOTO"))?>',
				MSLTextPanelMenuGallery: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_TEXTPANEL_MENU_GALLERY"))?>',
				MSLAjaxInterfaceFullURI: '<?=CUtil::JSEscape((CMain::IsHTTPS() ? "https" : "http")."://".$_SERVER["HTTP_HOST"].SITE_DIR.'mobile/ajax.php')?>',
				MSLDetailPullDownText1: '<?=GetMessageJS("MOBILE_LOG_NEW_PULL")?>',
				MSLDetailPullDownText2: '<?=GetMessageJS("MOBILE_LOG_NEW_PULL_RELEASE")?>',
				MSLDetailPullDownText3: '<?=GetMessageJS("MOBILE_LOG_DETAIL_NEW_PULL_LOADING")?>'
			});
		</script><?
	}

	if ($arParams["EMPTY_PAGE"] == "Y")
	{
		$frame = \Bitrix\Main\Page\Frame::getInstance();
		$frame->setEnable();
		$frame->setUseAppCache();
		AppCacheManifest::getInstance()->addAdditionalParam("page", "empty_detail");
		AppCacheManifest::getInstance()->addAdditionalParam("MobileAPIVersion", CMobile::getApiVersion());
		AppCacheManifest::getInstance()->addAdditionalParam("MobilePlatform", CMobile::getPlatform());
		AppCacheManifest::getInstance()->addAdditionalParam("version", "v6");

		?><div class="post-wrap" id="lenta_item"><?
			?><div id="post_log_id" data-log-id="" data-ts="" style="display: none;"></div><?
			?><div id="post_item_top_wrap" class="post-item-top-wrap post-item-copyable"><?
				?><div class="post-item-top" id="post_item_top"></div><?
				?><div class="post-item-post-block" id="post_block_check_cont"></div><?

				?><div id="post_inform_wrap_two" class="post-item-inform-wrap"><?

					// rating
					$like = COption::GetOptionString("main", "rating_text_like_y", GetMessage("MOBILE_LOG_LIKE"));

					?><div id="rating_text" class="post-item-informers bx-ilike-block" data-counter="0" style="display: none;"></div><?

					?><span id="comments_control" style="display: none;"><?
						?><div class="post-item-informers post-item-inform-comments" onclick="oMSL.setFocusOnCommentForm();"><?
							?><div class="post-item-inform-left"><?=GetMessage('MOBILE_LOG_COMMENT')?></div><?
						?></div><?
					?></span><?

					?><a id="post_more_limiter"  onclick="oMSL.expandText();" class="post-item-more" ontouchstart="this.classList.toggle('post-item-more-pressed')" ontouchend="this.classList.toggle('post-item-more-pressed')" style="display: none;"><?
						?><?=GetMessage("MOBILE_LOG_EXPAND")?><?
					?></a><?

					?><div id="log_entry_follow" class="post-item-informers <?=($arResult["FOLLOW_DEFAULT"] == 'Y' ? 'post-item-follow-default-active' : 'post-item-follow-default')?>" style="display: none;"><?
						?><div class="post-item-inform-left"></div><?
					?></div><?
				?></div><?

				$bRatingExtended = (
					CModule::IncludeModule("mobileapp")
						? CMobile::getApiVersion() >= 2
						: intval($APPLICATION->GetPageProperty("api_version")) >= 2
				);

				if ($bRatingExtended)
				{
					?><div class="post-item-inform-wrap-tree" id="rating-footer-wrap"></div><?
				}

			?></div><?
			?><div class="post-comments-wrap" id="post-comments-wrap"><span id="post-comment-last-after"></span></div><?
		?></div><?
		?><script type="text/javascript">
			var entryType = null;
		</script><?

		?><div id="post-comments-form-wrap"></div><?
	}
	elseif (
		$arResult["Events"]
		&& is_array($arResult["Events"])
		&& count($arResult["Events"]) > 0
	)
	{
		?><script type="text/javascript">
			if (BX("lenta_block_empty", true))
				BX("lenta_block_empty", true).style.display = "none";
		</script><?

		$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
		$blogPostEventIdList = $blogPostLivefeedProvider->getEventId();

		foreach ($arResult["Events"] as $arEvent)
		{
			$event_cnt++;
			$ind = RandString(8);

			$bUnread = (
				($arParams["SET_LOG_COUNTER"] == "Y" || $arResult["PAGE_NUMBER"] > 1)
				&& $arResult["COUNTER_TYPE"] == "**"
				&& $arEvent["USER_ID"] != $USER->GetID()
				&& intval($arResult["LAST_LOG_TS"]) > 0
				&& (MakeTimeStamp($arEvent["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) > $arResult["LAST_LOG_TS"]
			);

			if(in_array($arEvent["EVENT_ID"], array_merge($blogPostEventIdList, array("blog_comment", "blog_comment_micro"))))
			{
				if (intval($arEvent["SOURCE_ID"]) > 0)
				{
					$arComponentParams = array(
						"PATH_TO_BLOG" => $arParams["PATH_TO_USER_BLOG"],
						"PATH_TO_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
						"PATH_TO_BLOG_CATEGORY" => $arParams["PATH_TO_USER_BLOG_CATEGORY"],
						"PATH_TO_CRMCONTACT" => (!empty($arParams["PATH_TO_CRMCONTACT"]) ? $arParams["PATH_TO_CRMCONTACT"] : '') ,
						"PATH_TO_POST_EDIT" => $arParams["PATH_TO_USER_BLOG_POST_EDIT"],
						"PATH_TO_USER" => $arParams["PATH_TO_USER"],
						"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
						"PATH_TO_SMILE" => $arParams["PATH_TO_BLOG_SMILE"],
						"PATH_TO_MESSAGES_CHAT" => $arResult["PATH_TO_MESSAGES_CHAT"],
						"PATH_TO_LOG_ENTRY" => $arParams["PATH_TO_LOG_ENTRY"],
						"PATH_TO_LOG_ENTRY_EMPTY" => $arParams["PATH_TO_LOG_ENTRY_EMPTY"],
						"SET_NAV_CHAIN" => "N",
						"SET_TITLE" => "N",
						"POST_PROPERTY" => $arParams["POST_PROPERTY"],
						"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
						"DATE_TIME_FORMAT_FROM_LOG" => $arParams["DATE_TIME_FORMAT"],
						"LOG_ID" => $arEvent["ID"],
						"USER_ID" => $arEvent["USER_ID"],
						"ENTITY_TYPE" => $arEvent["ENTITY_TYPE"],
						"ENTITY_ID" => $arEvent["ENTITY_ID"],
						"EVENT_ID" => $arEvent["EVENT_ID"],
						"EVENT_ID_FULLSET" => $arEvent["EVENT_ID_FULLSET"],
						"IND" => $ind,
						"SONET_GROUP_ID" => $arParams["GROUP_ID"],
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
						"SHOW_YEAR" => $arParams["SHOW_YEAR"],
						"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
						"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
						"USE_SHARE" => $arParams["USE_SHARE"],
						"SHARE_HIDE" => $arParams["SHARE_HIDE"],
						"SHARE_TEMPLATE" => $arParams["SHARE_TEMPLATE"],
						"SHARE_HANDLERS" => $arParams["SHARE_HANDLERS"],
						"SHARE_SHORTEN_URL_LOGIN" => $arParams["SHARE_SHORTEN_URL_LOGIN"],
						"SHARE_SHORTEN_URL_KEY" => $arParams["SHARE_SHORTEN_URL_KEY"],
						"SHOW_RATING" => $arParams["SHOW_RATING"],
						"RATING_TYPE" => $arParams["RATING_TYPE"],
						"IMAGE_MAX_WIDTH" => $arParams["IMAGE_MAX_WIDTH"],
						"IMAGE_MAX_HEIGHT" => $arParams["IMAGE_MAX_HEIGHT"],
						"ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"],
						"ID" => $arEvent["SOURCE_ID"],
						"FROM_LOG" => ($arParams['LOG_ID'] <= 0 ? "Y" : "N"),
						"ADIT_MENU" => $arAditMenu,
						"IS_LIST" => (intval($arParams["LOG_ID"]) <= 0),
						"IS_UNREAD" => $bUnread,
						"IS_HIDDEN" => false,
						"LAST_LOG_TS" => ($arResult["LAST_LOG_TS"] > 0 ? $arResult["LAST_LOG_TS"] + $arResult["TZ_OFFSET"] : 0),
						"CACHE_TIME" => $arParams["CACHE_TIME"],
						"CACHE_TYPE" => $arParams["CACHE_TYPE"],
						"ALLOW_VIDEO"  => $arParams["BLOG_COMMENT_ALLOW_VIDEO"],
						"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"],
						"USE_CUT" => $arParams["BLOG_USE_CUT"],
						"MOBILE" => "Y",
						"ATTACHED_IMAGE_MAX_WIDTH_FULL" => 640,
						"ATTACHED_IMAGE_MAX_HEIGHT_FULL" => 832,
						"RETURN_DATA" => ($arParams["LOG_ID"] > 0 ? "Y" : "N"),
						"AVATAR_SIZE_COMMENT" => $arParams["AVATAR_SIZE_COMMENT"],
						"AVATAR_SIZE" => $arParams["AVATAR_SIZE"],
						"CHECK_PERMISSIONS_DEST" => $arParams["CHECK_PERMISSIONS_DEST"],
						"COMMENTS_COUNT" => $arEvent["COMMENTS_COUNT"],
						"USE_FOLLOW" => $arParams["USE_FOLLOW"],
						"USE_FAVORITES" => (isset($arResult["GROUP_READ_ONLY"]) && $arResult["GROUP_READ_ONLY"] == "Y" ? "N" : "Y"),
						"GROUP_READ_ONLY" => (isset($arResult["GROUP_READ_ONLY"]) && $arResult["GROUP_READ_ONLY"] == "Y" ? "Y" : "N"),
						"TOP_RATING_DATA" => (!empty($arResult['TOP_RATING_DATA'][$arEvent["ID"]]) ? $arResult['TOP_RATING_DATA'][$arEvent["ID"]] : false)
					);

					if ($arParams["USE_FOLLOW"] == "Y")
					{
						$arComponentParams["FOLLOW"] = $arEvent["FOLLOW"];
						$arComponentParams["FOLLOW_DEFAULT"] = $arResult["FOLLOW_DEFAULT"];
					}

					if (
						strlen($arEvent["RATING_TYPE_ID"])>0
						&& $arEvent["RATING_ENTITY_ID"] > 0
						&& $arParams["SHOW_RATING"] == "Y"
					)
					{
						$arComponentParams["RATING_ENTITY_ID"] = $arEvent["RATING_ENTITY_ID"];
					}

					if (!empty($arEvent['CONTENT_ID']))
					{
						$arComponentParams['CONTENT_ID'] = $arEvent['CONTENT_ID'];
					}

					if ($USER->IsAuthorized())
					{
						$arComponentParams["FAVORITES"] = (
							array_key_exists("FAVORITES_USER_ID", $arEvent)
							&& intval($arEvent["FAVORITES_USER_ID"]) > 0
								? "Y"
								: "N"
						);
					}

					$APPLICATION->IncludeComponent(
						"bitrix:socialnetwork.blog.post",
						"mobile",
						$arComponentParams,
						$component,
						Array("HIDE_ICONS" => "Y")
					);
				}
			}
			else
			{
				$arComponentParams = array_merge($arParams, array(
						"LOG_ID" => $arEvent["ID"],
						"IS_LIST" => (intval($arParams["LOG_ID"]) <= 0),
						"LAST_LOG_TS" => $arResult["LAST_LOG_TS"],
						"COUNTER_TYPE" => $arResult["COUNTER_TYPE"],
						"AJAX_CALL" => $arResult["AJAX_CALL"],
						"PATH_TO_LOG_ENTRY_EMPTY" => $arParams["PATH_TO_LOG_ENTRY_EMPTY"],
						"bReload" => $arResult["bReload"],
						"IND" => $ind,
						"CURRENT_PAGE_DATE" => $arResult["CURRENT_PAGE_DATE"],
						"EVENT" => array(
							"IS_UNREAD" => $bUnread,
							"LOG_DATE" => $arEvent["LOG_DATE"],
							"COMMENTS_COUNT" => $arEvent["COMMENTS_COUNT"],
						),
						"TOP_RATING_DATA" => (!empty($arResult['TOP_RATING_DATA'][$arEvent["ID"]]) ? $arResult['TOP_RATING_DATA'][$arEvent["ID"]] : false)
					)
				);

				if ($USER->IsAuthorized())
				{
					if ($arParams["USE_FOLLOW"] == "Y")
					{
						$arComponentParams["EVENT"]["FOLLOW"] = $arEvent["FOLLOW"];
						$arComponentParams["EVENT"]["DATE_FOLLOW"] = $arEvent["DATE_FOLLOW"];
						$arComponentParams["FOLLOW_DEFAULT"] = $arResult["FOLLOW_DEFAULT"];
					}

					$arComponentParams["EVENT"]["FAVORITES"] = (
						array_key_exists("FAVORITES_USER_ID", $arEvent)
						&& intval($arEvent["FAVORITES_USER_ID"]) > 0
							? "Y"
							: "N"
					);
				}

				if (
					strlen($arEvent["RATING_TYPE_ID"])>0
					&& $arEvent["RATING_ENTITY_ID"] > 0
					&& $arParams["SHOW_RATING"] == "Y"
				)
				{
					$arComponentParams["RATING_TYPE"] = $arParams["RATING_TYPE"];
					$arComponentParams["EVENT"]["RATING_TYPE_ID"] = $arEvent["RATING_TYPE_ID"];
					$arComponentParams["EVENT"]["RATING_ENTITY_ID"] = $arEvent["RATING_ENTITY_ID"];
				}

				if (!empty($arEvent['CONTENT_ID']))
				{
					$arComponentParams['CONTENT_ID'] = $arEvent['CONTENT_ID'];
				}

				if (isset($arResult["CRM_ACTIVITY2TASK"]))
				{
					$arComponentParams["CRM_ACTIVITY2TASK"] = $arResult["CRM_ACTIVITY2TASK"];
				}

				$APPLICATION->IncludeComponent(
					"bitrix:mobile.socialnetwork.log.entry",
					"",
					$arComponentParams,
					$component,
					Array("HIDE_ICONS" => "Y")
				);
			}


		} // foreach ($arResult["Events"] as $arEvent)
	} // if ($arResult["Events"] && is_array($arResult["Events"]) && count($arResult["Events"]) > 0)
	elseif (
		intval($arParams["LOG_ID"]) > 0
		&& $_REQUEST["empty_get_comments"] == "Y"
		&& (
			!$arResult["Events"]
			|| !is_array($arResult["Events"])
			|| count($arResult["Events"]) <= 0
		)
	)
	{
		$APPLICATION->RestartBuffer();
		$res = array(
			'ERROR_MESSAGE' => \Bitrix\Main\Localization\Loc::getMessage('MOBILE_LOG_ERROR_ENTRY_NOT_FOUND')
		);
		echo CUtil::PhpToJSObject($res);

		if(CModule::IncludeModule("compression"))
			CCompress::DisableCompression();
		CMain::FinalActions();
		die();
	}
	elseif (!$arResult["AJAX_CALL"])
	{
		if ($arParams["LOG_ID"] <= 0)
		{
			?><div class="lenta-block-empty" id="lenta_block_empty"><?=GetMessage("MOBILE_LOG_MESSAGE_EMPTY");?></div><?
		}
		else
		{
			?><div class="post-wrap">
				<div class="lenta-block-empty"><?=GetMessage("MOBILE_LOG_ERROR_ENTRY_NOT_FOUND");?></div>
			</div><?
		}
	}

	if ($arResult["AJAX_CALL"])
	{
		$uri = new \Bitrix\Main\Web\Uri(htmlspecialcharsback(POST_FORM_ACTION_URI));
		$uri->deleteParams([
			"LAST_LOG_TS",
			"AJAX_CALL",
			"PAGEN_".$arResult["PAGE_NAVNUM"],
			"pagesize",
			"RELOAD",
			"pplogid"
		]);

		$uriParams = [
			'LAST_LOG_TS' => $arResult["LAST_LOG_TS"],
			'AJAX_CALL' => 'Y',
			'PAGEN_'.$arResult["PAGE_NAVNUM"] => ($arResult["PAGE_NUMBER"] + 1)
		];

		if (intval($arResult["NEXT_PAGE_SIZE"]) > 0)
		{
			$uriParams['pagesize'] = intval($arResult["NEXT_PAGE_SIZE"]);
		}
		$uri->addParams($uriParams);

		?><script>
			<?
			if (
				$event_cnt > 0
				&& $event_cnt >= $arParams["PAGE_SIZE"]
			)
			{
				?>
				url_next = '<?=CUtil::JSEscape(htmlspecialcharsEx($uri->getUri()))?>';
				<?
			}
			else
			{
				?>
				oMSL.initScroll(false, true);
				<?
				if ($arParams["NEW_LOG_ID"] > 0)
				{
					?>
					setTimeout(function() {
						oMSL.registerBlocksToCheck();
						oMSL.checkNodesHeight();
					}, 1000);
					<?
				}
			}
			?>
			BitrixMobile.LazyLoad.showImages(); // when load next page
		</script><?

		if ($arParams["NEW_LOG_ID"] <= 0)
		{
			$arCSSListNew = $APPLICATION->sPath2css;
			$arCSSNew = array();

			foreach ($arCSSListNew as $i => $css_path)
			{
				if(
					strtolower(substr($css_path, 0, 7)) != 'http://'
					&& strtolower(substr($css_path, 0, 8)) != 'https://'
				)
				{
					$css_file = (
						($p = strpos($css_path, "?")) > 0
							? substr($css_path, 0, $p)
							: $css_path
					);

					if(file_exists($_SERVER["DOCUMENT_ROOT"].$css_file))
					{
						$arCSSNew[] = $css_path;
					}
				}
				else
				{
					$arCSSNew[] = $css_path;
				}
			}

			$arCSSNew = array_unique($arCSSNew);

			$arHeadScriptsNew = $APPLICATION->arHeadScripts;

			if(!$APPLICATION->oAsset->optimizeJs())
			{
				$arHeadScriptsNew = array_merge(CJSCore::GetScriptsList(), $arHeadScriptsNew);
			}

			$arAdditionalData = CMobileHelper::getPageAdditionals();
			$strText = ob_get_clean();

			$res = array(
				"PROPS" => array(
					"CONTENT" => $strText,
					"STRINGS" => array(),
					"JS" => $arAdditionalData["SCRIPTS"],
					"CSS" => $arAdditionalData["CSS"]
				),
				"LAST_TS" => ($arResult["dateLastPageTS"] ? intval($arResult["dateLastPageTS"]) : 0)
			);

			if ($arResult["COUNTER_TO_CLEAR"])
			{
				$res["COUNTER_TO_CLEAR"] = $arResult["COUNTER_TO_CLEAR"];
			}
			if (isset($arResult["COUNTER_SERVER_TIME"]))
			{
				$res["COUNTER_SERVER_TIME"] = $arResult["COUNTER_SERVER_TIME"];
			}
			if (isset($arResult["COUNTER_SERVER_TIME_UNIX"]))
			{
				$res["COUNTER_SERVER_TIME_UNIX"] = $arResult["COUNTER_SERVER_TIME_UNIX"];
			}

			echo CUtil::PhpToJSObject($res);

			if(CModule::IncludeModule("compression"))
				CCompress::DisableCompression();
			CMain::FinalActions();
			die();
		}
	}

	if ($arParams["NEW_LOG_ID"] <= 0)
	{
		if (
			$arParams["LOG_ID"] <= 0
			&& $arParams["EMPTY_PAGE"] != "Y"
		)
		{
			if ($event_cnt >= $arParams["PAGE_SIZE"])
			{
				?><div id="next_post_more" class="lenta-item">
					<div class="bx-placeholder-wrap">
						<div class="bx-placeholder">
							<table class="bx-feed-curtain">
								<tr class="bx-curtain-row-0"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-2 transparent"></td><td class="bx-curtain-cell-3"></td><td class="bx-curtain-cell-4"></td><td class="bx-curtain-cell-5"></td><td class="bx-curtain-cell-6"></td><td class="bx-curtain-cell-7"></td></tr>
								<tr class="bx-curtain-row-1 2"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-2 transparent"></td><td class="bx-curtain-cell-3"></td><td class="bx-curtain-cell-4 transparent"></td><td class="bx-curtain-cell-5" colspan="3"></td></tr>
								<tr class="bx-curtain-row-2 3"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-2 transparent" rowspan="2"><div class="bx-bx-curtain-avatar"></div></td><td class="bx-curtain-cell-3" colspan="5"></td></tr>
								<tr class="bx-curtain-row-1"><td class="bx-curtain-cell-1"></td><td class="bx-curtain-cell-3"></td><td class="bx-curtain-cell-4 transparent" colspan="3"></td><td class="bx-curtain-cell-7"></td></tr>
								<tr class="bx-curtain-row-2"><td class="bx-curtain-cell-1" colspan="7"></td></tr>
								<tr class="bx-curtain-row-1"><td class="bx-curtain-cell-1" colspan="3"></td><td class="bx-curtain-cell-4 transparent" colspan="3"></td><td class="bx-curtain-cell-7"></td></tr>
								<tr class="bx-curtain-row-2"><td class="bx-curtain-cell-1" colspan="7"></td></tr>
							</table>
						</div>
					</div>
				</div><?
			}
			?></div><? // lenta-wrapper, lenta-wrapper-outer, lenta-wrapper-outer-cont
		}

		$uri = new \Bitrix\Main\Web\Uri(htmlspecialcharsback(POST_FORM_ACTION_URI));
		$uri->deleteParams([
			"LAST_LOG_TS",
			"AJAX_CALL",
			"PAGEN_".$arResult["PAGE_NAVNUM"],
			"pagesize",
			"RELOAD",
			"pplogid"
		]);

		$uriParams = [
			'LAST_LOG_TS' => $arResult["LAST_LOG_TS"],
			'AJAX_CALL' => 'Y',
			'PAGEN_'.$arResult["PAGE_NAVNUM"] => ($arResult["PAGE_NUMBER"] + 1)
		];

		if (intval($arResult["NEXT_PAGE_SIZE"]) > 0)
		{
			$uriParams['pagesize'] = intval($arResult["NEXT_PAGE_SIZE"]);
		}
		if (
			is_array($arResult["arLogTmpID"])
			&& !empty($arResult["arLogTmpID"])
		)
		{
			$uriParams['pplogid'] = implode("|", $arResult["arLogTmpID"]);
		}
		$uri->addParams($uriParams);

		// sonet_log_content
		?><script type="text/javascript">

			var isPullDownEnabled = false;
			var isPullDownLocked = false;

			var url_next = '<?=CUtil::JSEscape(htmlspecialcharsEx($uri->getUri()))?>';
			<?
			if (
				($arParams["LOG_ID"] > 0 || $arParams["EMPTY_PAGE"] == "Y")
				&& $_REQUEST["BOTTOM"] == "Y"
			)
			{
				?>
				__MSLDetailMoveBottom();
				<?
			}
		?>
		</script>
		<?
		if ($arResult["RELOAD"])
		{
			?></div><?

			if ($arResult["RELOAD_JSON"])
			{
				$strText = ob_get_clean();

				AddEventHandler("main", "OnEndBufferContent", function($staticContent) use($strText, $arResult)
				{
					$manifest = AppCacheManifest::getInstance();

					$server = \Bitrix\Main\Context::getCurrent()->getServer();
					$manifest->setPageURI($server->get("HTTP_BX_APPCACHE_URL"));
					$manifestId = $manifest->getCurrentManifestID();
					$manifestCache = $manifest->readManifestCache($manifestId);

					$manifestCacheFiles = array();
					if (!empty($manifestCache['FILE_DATA']))
					{
						if (!empty($manifestCache['FILE_DATA']['FILE_TIMESTAMPS']))
						{
							foreach ($manifestCache['FILE_DATA']['FILE_TIMESTAMPS'] as $key => $value)
							{
								$manifestCacheFiles[] = $key.$value;
							}
						}
						if (!empty($manifestCache['FILE_DATA']['CSS_FILE_IMAGES']))
						{
							foreach ($manifestCache['FILE_DATA']['CSS_FILE_IMAGES'] as $cssFile => $images)
							{
								if (!empty($images))
								{
									foreach($images as $key => $value)
									{
										$manifestCacheFiles[] = $value;
									}
								}
							}
						}
					}

					Asset::getInstance()->stopTarget('livefeed_ajax');
					$resources = \Bitrix\Main\Page\Asset::getInstance()->getAssetInfo('livefeed_ajax', \Bitrix\Main\Page\AssetMode::STANDARD);
					$files1 = array(
						"FULL_FILE_LIST" => array(),
						"FILE_TIMESTAMPS" => array(),
						"CSS_FILE_IMAGES" => array()
					);

					if (
						!empty($resources['STRINGS'])
						&& is_array($resources['STRINGS'])
					)
					{
						foreach($resources['STRINGS'] as $stringContent)
						{
							$files = $manifest->getFilesFromContent($stringContent);

							if (
								!empty($files["FULL_FILE_LIST"])
								&& is_array($files["FULL_FILE_LIST"])
							)
							{
								foreach($files["FULL_FILE_LIST"] as $file)
								{
									$files1["FULL_FILE_LIST"][] = $file;
								}
							}
							if (
								!empty($files["FILE_TIMESTAMPS"])
								&& is_array($files["FILE_TIMESTAMPS"])
							)
							{
								foreach($files["FILE_TIMESTAMPS"] as $file => $timestamp)
								{
									$files1["FILE_TIMESTAMPS"][$file] = $timestamp;
								}
							}
							if (
								!empty($files["CSS_FILE_IMAGES"])
								&& is_array($files["CSS_FILE_IMAGES"])
							)
							{
								foreach($files["CSS_FILE_IMAGES"] as $file => $imagesList)
								{
									$files1["CSS_FILE_IMAGES"][$file] = $imagesList;
								}
							}
						}
					}

					$files1["FULL_FILE_LIST"] = array_unique($files1["FULL_FILE_LIST"]);

					$files2 = $manifest->getFilesFromContent($staticContent);

					$manifestFiles = array_values(array_unique(array_merge($files1["FULL_FILE_LIST"], (!empty($files2["FULL_FILE_LIST"]) ? $files2["FULL_FILE_LIST"] : array()))));
					$manifestFileTimestamps =  array_merge($files1["FILE_TIMESTAMPS"], (!empty($files2["FILE_TIMESTAMPS"]) ? $files2["FILE_TIMESTAMPS"] : array()));
					$manifestCSSFileImages =  array_merge($files1["CSS_FILE_IMAGES"], (!empty($files2["CSS_FILE_IMAGES"]) ? $files2["CSS_FILE_IMAGES"] : array()));

					$sameFiles = (!array_diff($manifestFiles, $manifestCacheFiles) && !array_diff($manifestCacheFiles, $manifestFiles));
					$isManifestUpdated = (!$manifestCache || !$sameFiles || $manifest->getDebug());

					if ($isManifestUpdated)
					{
						$cache = new \CPHPCache();
						$cachePath = AppCacheManifest::getCachePath($manifestId);
						$cache->CleanDir($cachePath);

						$manifest->setFiles($manifestFiles);
						$currentHashSum = md5(serialize($manifestFiles).serialize($manifest->getFallbackPages()).serialize($manifest->getNetworkFiles()).serialize($manifest->getExcludeImagePatterns()));
						$manifest->setNetworkFiles(Array("*"));

						$manifestFields = array(
							"ID" => $manifestId,
							"TEXT" => $manifest->getManifestContent(),
							"FILE_HASH" => $currentHashSum,
							"EXCLUDE_PATTERNS_HASH"=> md5(serialize($manifest->getExcludeImagePatterns())),
							"FILE_DATA" => array(
								"FILE_TIMESTAMPS" => $manifestFileTimestamps,
								"CSS_FILE_IMAGES" => $manifestCSSFileImages
							)
						);

						$cache = new \CPHPCache();
						$cache->StartDataCache(3600 * 24 * 365, $manifestId, $cachePath);
						$cache->EndDataCache($manifestFields);
					}

					$res = array(
						"PROPS" => array(
							"CONTENT" => $strText,
							"JS" => $resources["SCRIPTS"],
							"CSS" => $resources["CSS"],
//							"STRINGS" => $resources["STRINGS"]
							"STRINGS" => ""
						),
						"TS" => time(),
						"REWRITE_FRAMECACHE" => ($arResult["USE_FRAMECACHE"] ? 'Y' : 'N'),
						"isManifestUpdated" => $isManifestUpdated
					);

					if ($arResult["COUNTER_TO_CLEAR"])
					{
						$res["COUNTER_TO_CLEAR"] = $arResult["COUNTER_TO_CLEAR"];
					}
					if (isset($arResult["COUNTER_SERVER_TIME"]))
					{
						$res["COUNTER_SERVER_TIME"] = $arResult["COUNTER_SERVER_TIME"];
					}
					if (isset($arResult["COUNTER_SERVER_TIME_UNIX"]))
					{
						$res["COUNTER_SERVER_TIME_UNIX"] = $arResult["COUNTER_SERVER_TIME_UNIX"];
					}

					echo CUtil::PhpToJSObject($res);

					if(CModule::IncludeModule("compression"))
						CCompress::DisableCompression();
					CMain::FinalActions();
					die();
				});
			}
			else
			{
				if(CModule::IncludeModule("compression"))
					CCompress::DisableCompression();
				CMain::FinalActions();
				die();
			}

		}

		if (isset($feedFrame))
		{
			$feedFrame->end();
			?></div><?
		}
	}

	if (
		$arParams["LOG_ID"] <= 0
		&& $arParams["EMPTY_PAGE"] != "Y"
		&& !$arResult["AJAX_CALL"]
	)
	{
		?></div><? // lenta_wrapper_global
	}
}
?>