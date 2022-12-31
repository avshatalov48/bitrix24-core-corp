<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var string $templateFolder */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CMain $APPLICATION */

use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetMode;
use Bitrix\Main\Loader;
use Bitrix\Main\UI;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Composite;
use Bitrix\Main\Web\Uri;

$mobileContext = new \Bitrix\Mobile\Context();

$APPLICATION->AddHeadScript("/bitrix/components/bitrix/mobile.socialnetwork.log.ex/templates/.default/mobile_files.js");
$APPLICATION->AddHeadScript("/bitrix/components/bitrix/mobile.socialnetwork.log.ex/templates/.default/script_attached.js");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/components/bitrix/voting.current/.userfield/script.js");

$targetHtml = '';

if (
	CModule::IncludeModule("vote")
	&& class_exists("\\Bitrix\\Vote\\UF\\Manager")
)
{
	$APPLICATION->AddHeadScript("/bitrix/components/bitrix/voting.uf/templates/.default/script.js");
	Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL('/bitrix/components/bitrix/voting.uf/templates/.default/style.css').'" type="text/css" rel="stylesheet" />');
}
else if (IsModuleInstalled("vote"))
{
	Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH.'/components/bitrix/voting.current/.userfield/style.css').'" type="text/css" rel="stylesheet" />');
}

if ($arResult['PAGE_MODE'] === 'detail_empty')
{
	Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL('/bitrix/components/bitrix/rating.vote/templates/like_react/style.css').'" type="text/css" rel="stylesheet" />');
	Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL('/bitrix/js/ui/icons/base/ui.icons.base.css').'" type="text/css" rel="stylesheet" />');
	Asset::getInstance()->addString('<link href="'.CUtil::GetAdditionalFileURL('/bitrix/js/ui/icons/b24/ui.icons.b24.css').'" type="text/css" rel="stylesheet" />');
}

$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/log_mobile.js");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/bizproc_mobile.js");
$APPLICATION->SetUniqueJS('live_feed_mobile');
$APPLICATION->SetUniqueCSS('live_feed_mobile');

$coreExtList = [ 'date', 'ls', 'fx', 'comment_aux', 'content_view' ];
if (Loader::includeModule('tasks'))
{
	$coreExtList[] = 'tasks';
	$coreExtList[] = 'tasks_util_query';
}
CUtil::InitJSCore($coreExtList);

UI\Extension::load([
	'mobile.livefeed',
	'mobile.diskfile',
	'ui.buttons',
	'main.rating',
	'mobile.rating.comment',
	'ui.livefeed.background'
]);

if (!empty($arResult['TARGET']))
{
	ob_start();
}

if ($arResult["FatalError"] <> '')
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?php
}
else
{
	if (in_array($arResult['PAGE_MODE'], [ 'first', 'refresh' ], true))
	{
		?><div id="post-balloon-container" class="post-balloon-box"></div><?php
		?><div id="lenta_wrapper_global" class="lenta-list-wrap"><?php
	}
	?>
	<script>
		var bGlobalReload = <?= ($arResult['PAGE_MODE'] === 'refresh' ? 'true' : 'false') ?>;
	</script>
	<?php
	Composite\AppCache::getInstance()->addAdditionalParam("page", "livefeed");
	Composite\AppCache::getInstance()->addAdditionalParam("preventAutoUpdate", "Y");
	Composite\AppCache::getInstance()->addAdditionalParam("MobileAPIVersion", CMobile::getApiVersion());
	Composite\AppCache::getInstance()->addAdditionalParam("MobilePlatform", CMobile::getPlatform());
	Composite\AppCache::getInstance()->addAdditionalParam("UserID", $arResult['currentUserId']);
	Composite\AppCache::getInstance()->addAdditionalParam("version", "v8");
	Composite\AppCache::getInstance()->addAdditionalParam("MobileModuleVersion", $mobileContext->version);

	?><script>
		BX.message({
			MSLSiteDir: '<?=CUtil::JSEscape(SITE_DIR)?>',

			MOBILE_TASKS_VIEW_TAB_TASK: '<?=CUtil::JSEscape(Loc::getMessage("MOBILE_TASKS_VIEW_TAB_TASK"))?>',
			MOBILE_TASKS_VIEW_TAB_CHECKLIST: '<?=CUtil::JSEscape(Loc::getMessage("MOBILE_TASKS_VIEW_TAB_CHECKLIST"))?>',
			MOBILE_TASKS_VIEW_TAB_FILES: '<?=CUtil::JSEscape(Loc::getMessage("MOBILE_TASKS_VIEW_TAB_FILES"))?>',
			MOBILE_TASKS_VIEW_TAB_COMMENT: '<?=CUtil::JSEscape(Loc::getMessage("MOBILE_TASKS_VIEW_TAB_COMMENT"))?>',

			MSLExtranetSiteId: <?=(!empty($arResult["extranetSiteId"]) ? "'".CUtil::JSEscape($arResult["extranetSiteId"])."'" : "false")?>,
			MSLExtranetSiteDir: <?=(!empty($arResult["extranetSiteDir"]) ? "'".CUtil::JSEscape($arResult["extranetSiteDir"])."'" : "false")?>
		});
	</script><?php

	if (
		$arResult['PAGE_MODE'] === 'first'
		&& (int)$arParams['GROUP_ID'] <= 0
	)
	{
		?><script>
			BX.ready(function() {
				BX.MobileLivefeed.Instance.initListOnce({
					arAvailableGroup: <?= CUtil::PhpToJSObject(
						!empty($arResult["arAvailableGroup"])
						&& is_array($arResult["arAvailableGroup"])
							? $arResult["arAvailableGroup"]
							: false
					) ?>
				});
			});
		</script><?php

		if (
			!$arParams["FILTER"]
			&& (
				!isset($arParams["CREATED_BY_ID"])
				|| (int)$arParams["CREATED_BY_ID"] <= 0
			)
		)
		{
			Composite\Engine::setEnable();
			Composite\Engine::setUseAppCache();
			Composite\Engine::setAutoUpdate(false);

			?><script>
				window.appCacheVars = <?=CUtil::PhpToJSObject(Composite\AppCache::getInstance()->getAdditionalParams())?>;
			</script><?php

			?><div id="framecache-block-feed"><?php
			$feedFrame = $this->createFrame("framecache-block-feed", false)->begin("");
			$feedFrame->setBrowserStorage(true);
		}
	}

	$event_cnt = 0;

	if ($arResult['PAGE_MODE'] === 'refresh')
	{
		if ($arResult["RELOAD_JSON"])
		{
			CJSCore::Init(array("fc"), false);
			Asset::getInstance()->startTarget('livefeed_ajax', AssetMode::STANDARD);
		}
		?><script>
			var bGlobalReload = true;
		</script>
		<div id="bxdynamic_feed_refresh"><?php
	}

	$like = COption::GetOptionString("main", "rating_text_like_y", Loc::getMessage("MOBILE_LOG_LIKE"));
	$like2 = str_replace('#LIKE#', $like, Loc::getMessage("MOBILE_LOG_LIKE2_PATTERN"));

	?>
	<script>
		BX.message({
			MSLLike: '<?=CUtil::JSEscape(htmlspecialcharsEx($like))?>',
			MSLLike2: '<?=CUtil::JSEscape(htmlspecialcharsEx($like2))?>',
			MSLPostFormTableOk: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_TABLE_OK")?>',
			MSLPostFormTableCancel: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_TABLE_CANCEL")?>',
			MSLPostFormSend: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_SEND")?>',
			MSLPostFormCancel: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_CANCEL")?>',
			MSLPostDestUA: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_DEST_UA")?>',
			MSLGroupName: '<?=(!empty($arResult["GROUP_NAME"]) ? CUtil::JSEscape($arResult["GROUP_NAME"]) : '')?>',
			MSLPostFormPhotoCamera: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_PHOTO_CAMERA")?>',
			MSLPostFormPhotoGallery: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_PHOTO_GALLERY")?>',
			MSLIsExtranetSite: '<?=($arResult["bExtranetSite"] ? 'Y' : 'N')?>'
			<?php
			if (
				$arResult["bDiskInstalled"]
				|| $arResult["bWebDavInstalled"]
			)
			{
				?>
				, MSLPostFormDisk: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_DISK")?>'
				, MSLPostFormDiskTitle: '<?=GetMessageJS("MOBILE_LOG_POST_FORM_DISK_TITLE")?>'
			<?php
			}
			?>
		});

		var initParams = {
			logID: <?=$arParams["LOG_ID"]?>,
			bAjaxCall: <?=($arResult["AJAX_CALL"] ? "true" : "false")?>,
			bReload: bGlobalReload,
			bEmptyPage: <?=($arParams["EMPTY_PAGE"] === "Y" ? "true" : "false")?>,
			pathToEmptyPage: '<?=CUtil::JSescape($arParams["PATH_TO_LOG_ENTRY_EMPTY"])?>',
			pathToTasksRouter: '<?=CUtil::JSescape($arParams["PATH_TO_TASKS_SNM_ROUTER"])?>',
			bFiltered: <?=(
				$arParams["FILTER"]
				|| (
					isset($arParams["CREATED_BY_ID"])
					&& (int)$arParams["CREATED_BY_ID"] > 0
				)
					? "true"
					: "false"
			)?>,
			groupID: <?=$arParams["GROUP_ID"]?>,
			canAddPost: <?= ((int)$arResult["MICROBLOG_USER_ID"] > 0 ? 'true' : 'false') ?>,
			groupImage: '<?=$arResult["GROUP_IMAGE"]?>',
			curUrl: '<?=$APPLICATION->GetCurPageParam("", array("LAST_LOG_TS", "AJAX_CALL", "RELOAD", "RELOAD_JSON"))?>',
			appCacheDebug: <?= Composite\AppCache::getInstance()->getDebug() ? "true" : "false" ?>,
			tmstmp: <?=time()?>,
			strCounterType: '<?=$arResult["COUNTER_TYPE"]?>',
			bFollowDefault: <?=($arResult["FOLLOW_DEFAULT"] !== "N" ? "true" : "false")?>,
			bShowExpertMode: <?=($arResult["SHOW_EXPERT_MODE"] === "Y" ? "true" : "false")?>,
			bExpertMode: <?=($arResult["EXPERT_MODE"] === "Y" ? "true" : "false")?>,
			ftMinTokenSize: <?=(int)$arResult["ftMinTokenSize"]?>,
			signedParameters: '<?=$this->getComponent()->getSignedParameters()?>',
			destinationToAllDeny: <?=($arResult["bDenyToAll"]) ? 'true' : 'false'?>,
			destinationToAllDefault: <?=($arResult["bDefaultToAll"]) ? 'true' : 'false'?>,
			backgroundImagesData: <?=CUtil::PhpToJSObject($arResult["BACKGROUND_IMAGES_DATA"])?>,
			backgroundCommon: <?=CUtil::PhpToJSObject($arResult["BACKGROUND_COMMON"])?>,
			medalsList: <?=CUtil::PhpToJSObject($arResult["MEDALS_LIST"])?>,
			importantData: <?=CUtil::PhpToJSObject($arResult["IMPORTANT_DATA"])?>,
			postFormData: <?=CUtil::PhpToJSObject($arResult["POST_FORM_DATA"])?>,
			unreadLogIdData: '<?= $arResult['unreadLogIdData'] ?>',
			unreadLogCommentIdData: '<?= $arResult['unreadLogCommentIdData'] ?>',
			unreadBlogCommentIdData: '<?= $arResult['unreadBlogCommentIdData'] ?>',
		};

		BX.ready(function() {
			__MSLOnFeedInit(initParams);
		});

	</script>
	<?php
	if (in_array($arResult['PAGE_MODE'], [ 'first', 'refresh' ], true))
	{
		if (
			isset($arResult["GROUP_NAME"])
			&& $arResult["GROUP_NAME"] <> ''
		)
		{
			$pageTitle = CUtil::JSEscape($arResult["GROUP_NAME"]);
		}
		elseif ($arParams["FILTER"] === "favorites")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_FAVORITES");
		}
		elseif ($arParams["FILTER"] === "my")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_MY");
		}
		elseif ($arParams["FILTER"] === "important")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_IMPORTANT");
		}
		elseif ($arParams["FILTER"] === "work")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_WORK");
		}
		elseif ($arParams["FILTER"] === "bizproc")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_BIZPROC");
		}
		elseif ($arParams["FILTER"] === "blog")
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_BLOG");
		}
		else
		{
			$pageTitle = GetMessageJS("MOBILE_LOG_TITLE_NEWS");
		}

		?><script>
			var arLogTs = {};
			var arCanUserComment = {};

			BX.message({
				MSLPageId: '<?=CUtil::JSEscape(RandString(4))?>',
				MSLPageNavNum: <?= (int)$arResult["PAGE_NAVNUM"] ?>,
				MSLSessid: '<?=bitrix_sessid()?>',
				MSLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				MSLSiteDir: '<?=CUtil::JSEscape(SITE_DIR)?>',
				MSLDestinationLimit: '<?= (int)$arParams["DESTINATION_LIMIT_SHOW"] ?>',
				MSLNameTemplate: '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
				MSLShowLogin: '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>',
				MSLShowRating: '<?=CUtil::JSEscape($arParams["SHOW_RATING"])?>',
				MSLNextPostMoreTitle: '<?=GetMessageJS("MOBILE_LOG_NEXT_POST_MORE")?>',
				MSLPathToTasksRouter: '<?=CUtil::JSEscape($arParams["PATH_TO_TASKS_SNM_ROUTER"])?>',
				MSLPathToLogEntry: '<?=CUtil::JSEscape($arParams["PATH_TO_LOG_ENTRY"])?>',
				MSLPathToUser: '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
				MSLPathToGroup: '<?=CUtil::JSEscape($arParams["PATH_TO_GROUP"])?>',
				MSLPathToCrmLead: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMLEAD"])?>',
				MSLPathToCrmDeal: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMDEAL"])?>',
				MSLPathToCrmContact: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCONTACT"])?>',
				MSLPathToCrmCompany: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCOMPANY"])?>',
				MSLPathToKnowledgeGroup: '<?=CUtil::JSEscape($arResult["KNOWLEDGE_PATH"])?>',
				MSLTitleKnowledgeGroup: '<?=GetMessageJS("MOBILE_LOG_MENU_KNOWLEDGE")?>',
				MSLFirstPageLastTS : <?= (int)$arResult["dateLastPageTS"] ?>,
				MSLFirstPageLastId : <?= (int)$arResult["lastPageId"] ?>,
				MSLSliderAddPost: '<?=GetMessageJS("MOBILE_LOG_SLIDER_ADD_POST")?>',
				MSLSliderFavorites: '<?=GetMessageJS("MOBILE_LOG_SLIDER_FAVORITES")?>',
				MSLLoadScriptsNeeded: '<?=(COption::GetOptionString('main', 'optimize_js_files', 'N') === 'Y' ? 'N' : 'Y')?>',
				MSLLogTitle: '<?=$pageTitle?>'
				<?php
				if ($arParams["USE_FOLLOW"] === "Y")
				{
					?>
					, MSLMenuItemFollowDefaultY: '<?=GetMessageJS("MOBILE_LOG_MENU_FOLLOW_DEFAULT_Y")?>'
					, MSLMenuItemFollowDefaultN: '<?=GetMessageJS("MOBILE_LOG_MENU_FOLLOW_DEFAULT_N")?>'
					, MSLMenuItemExpertModeY: '<?=GetMessageJS("MOBILE_LOG_MENU_EXPERT_MODE_Y")?>'
					, MSLMenuItemExpertModeN: '<?=GetMessageJS("MOBILE_LOG_MENU_EXPERT_MODE_N")?>'
				<?php
				}
				?>
			});
		</script><?php

		require($_SERVER['DOCUMENT_ROOT'] . $templateFolder . '/include/informer.php');
		require($_SERVER['DOCUMENT_ROOT'] . $templateFolder . '/include/pinned.php');
	}
	elseif ($arResult['PAGE_MODE'] === 'detail_empty')
	{
		?><div id="empty_comment" style="display: none;"><?php
			?><div class="post-comment-block" style="position: relative;"><?php
				?><div class="post-user-wrap"><?php
					?><div id="empty_comment_avatar" class="avatar"<?=($arResult["EmptyComment"]["AVATAR_SRC"] <> '' ? " style=\"background-image:url('".$arResult["EmptyComment"]["AVATAR_SRC"]."');\"" : "")?>></div><?php
					?><div class="post-comment-cont"><?php
						?><div class="post-comment-author"><?=$arResult["EmptyComment"]["AUTHOR_NAME"]?></div><?php
						?><div class="post-comment-preview-wait"></div><?php
						?><div class="post-comment-preview-undelivered"></div><?php
					?></div><?php
				?></div><?php
				?><div id="empty_comment_text" class="post-comment-text"></div><?php
			?></div><?php
		?></div><?php
		?><div style="display: none;" id="comment_send_button_waiter" class="send-message-button-waiter"></div>
		<script>
			BX.message({
				MSLPageId: '<?=CUtil::JSEscape(RandString(4))?>',
				MSLSessid: '<?=bitrix_sessid()?>',
				MSLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
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
				MSLDestinationLimit: '<?= (int)$arParams["DESTINATION_LIMIT_SHOW"] ?>',
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
				MSLCurrentTime: '<?=time()?>',
				MSLEmptyDetailCommentFormTitle: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENT_ADD_TITLE")?>',
				MSLEmptyDetailCommentFormButtonTitle: '<?=GetMessageJS("MOBILE_LOG_EMPTY_COMMENT_ADD_BUTTON_SEND")?>',
				MSLLoadScriptsNeeded: '<?=(COption::GetOptionString('main', 'optimize_js_files', 'N') === 'Y' ? 'N' : 'Y')?>',
				MSLDateTimeFormat: '<?=CUtil::JSEscape(CDatabase::DateFormatToPHP($arParams["DATE_TIME_FORMAT"] <> '' ? $arParams["DATE_TIME_FORMAT"] : FORMAT_DATETIME))?>'
			});
		</script><?php
	}
	elseif ($arResult['PAGE_MODE'] === 'detail')
	{
		if ($arResult['TARGET'] === 'ENTRIES_ONLY_PINNED')
		{
			require($_SERVER["DOCUMENT_ROOT"].$templateFolder."/include/pinned.php");
		}
		?><div style="display: none;" id="comment_send_button_waiter" class="send-message-button-waiter"></div>
		<script>
			BX.message({
				MSLPageId: '<?=CUtil::JSEscape(RandString(4))?>',
				MSLSessid: '<?=bitrix_sessid()?>',
				MSLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				MSLLogId: <?= (int)$arParams["LOG_ID"] ?>,
				MSLPathToUser: '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
				MSLPathToGroup: '<?=CUtil::JSEscape($arParams["PATH_TO_GROUP"])?>',
				MSLPathToCrmLead: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMLEAD"])?>',
				MSLPathToCrmDeal: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMDEAL"])?>',
				MSLPathToCrmContact: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCONTACT"])?>',
				MSLPathToCrmCompany: '<?=CUtil::JSEscape($arParams["PATH_TO_CRMCOMPANY"])?>',
				MSLDestinationLimit: '<?= (int)$arParams["DESTINATION_LIMIT_SHOW"] ?>',
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
				MSLDateTimeFormat: '<?=CUtil::JSEscape(CDatabase::DateFormatToPHP($arParams["DATE_TIME_FORMAT"] <> '' ? $arParams["DATE_TIME_FORMAT"] : FORMAT_DATETIME))?>'
			});

			BX.ready(function() {
				BX.bind(window, 'scroll', oMSL.onScrollDetail);
				BX.MobileLivefeed.Instance.setLogId(<?= (int)$arParams['LOG_ID'] ?>);
				BX.MobileLivefeed.PageScrollInstance.checkScrollButton();

				BX.onCustomEvent(window, 'BX.UserContentView.onInitCall', [{
					mobile: true,
					ajaxUrl: BX.message('MSLSiteDir') + 'mobile/ajax.php',
					commentsContainerId: 'post-comments-wrap',
					commentsClassName: 'post-comment-wrap'
				}]);
			});
		</script><?php
	}

	if ($arParams['NEW_LOG_ID'] <= 0)
	{
		if ((int)$arResult["MICROBLOG_USER_ID"] > 0)
		{
			?><div class="feed-add-post-button" id="feed-add-post-button"></div><?php
		}

		?><div class="lenta-wrapper" id="lenta_wrapper"><?php
			?><div class="post-comment-block-scroll post-comment-block-scroll-top"><div class="post-comment-block-scroll-arrow post-comment-block-scroll-arrow-top"></div></div><?php
			?><div class="post-comment-block-scroll post-comment-block-scroll-bottom"><div class="post-comment-block-scroll-arrow post-comment-block-scroll-arrow-bottom"></div></div><?php
			?><span id="blog-post-first-after"></span><?php
	}

	if($arResult["ErrorMessage"] <> '')
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?php
	}

	if (
		$arResult['PAGE_MODE'] === 'next'
		|| $arResult["RELOAD_JSON"]
	)
	{
		if ($arResult['PAGE_MODE'] === 'next')
		{
			$APPLICATION->RestartBuffer();
		}

		if ($arParams["NEW_LOG_ID"] <= 0)
		{
			$APPLICATION->sPath2css = array();
			$APPLICATION->arHeadScripts = array();
		}
	}

	if (in_array($arResult['PAGE_MODE'], [ 'detail', 'detail_comments' ], true))
	{
		?><script>
			var commentVarLogID = null;
			var commentVarAvatarSize = <?= (int)$arParams["AVATAR_SIZE_COMMENT"] ?>;
			var commentVarNameTemplate = '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>';
			var commentVarShowLogin = '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>';
			var commentVarDateTimeFormat = null;
			var commentVarPathToUser = '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>';
			var commentVarPathToBlogPost = '<?=CUtil::JSEscape($arParams["PATH_TO_USER_MICROBLOG_POST"])?>';
			var commentVarURL = null;
			var commentVarAction = null;
			var commentVarEntityTypeID = null;
			var commentVarEntityID = null;
			var commentVarRatingType = '<?=CUtil::JSEscape($arParams["RATING_TYPE"])?>';
			var tmp_log_id = 0;

			BX.message({
				MSLSiteDir: '<?=CUtil::JSEscape(SITE_DIR)?>',
				MSLLogEntryTitle: '<?=GetMessageJS("MOBILE_LOG_ENTRY_TITLE")?>',
				MSLEditPost: '<?=GetMessageJS("MOBILE_LOG_EDIT_POST")?>'
			});
		</script><?php
	}

	if ($arResult['PAGE_MODE'] === 'detail_empty')
	{
		Composite\Engine::setEnable();
		Composite\Engine::setUseAppCache();

		Composite\AppCache::getInstance()->addAdditionalParam("page", "empty_detail");
		Composite\AppCache::getInstance()->addAdditionalParam("MobileAPIVersion", CMobile::getApiVersion());
		Composite\AppCache::getInstance()->addAdditionalParam("MobilePlatform", CMobile::getPlatform());
		Composite\AppCache::getInstance()->addAdditionalParam("version", "v6");
		Composite\AppCache::getInstance()->addAdditionalParam("MobileModuleVersion", $mobileContext->version);

		?><div class="post-wrap" id="lenta_item"><?php
			?><div id="post_log_id" data-log-id="" data-ts="" style="display: none;"></div><?php
			?><div id="post_item_top_wrap" class="post-item-top-wrap post-item-copyable"><?php
				?><div class="post-item-top" id="post_item_top"></div><?php
				?><div class="post-item-post-block" id="post_block_check_cont"></div><?php
				?><div class="post-item-attached-file-wrap post-item-attached-disk-file-wrap" id="post_block_files"></div><?php

				$bRatingExtended = (
					CModule::IncludeModule("mobileapp")
						? CMobile::getApiVersion() >= 2
						: (int)$APPLICATION->GetPageProperty("api_version") >= 2
				);

				if ($bRatingExtended)
				{
					?><div class="post-item-inform-wrap-tree" id="rating-footer-wrap"></div><?php
				}

				?><div id="post_inform_wrap_two" class="post-item-inform-wrap"><?php

					?><div class="post-item-inform-wrap-left"><?php

						// rating
						$like = COption::GetOptionString("main", "rating_text_like_y", Loc::getMessage("MOBILE_LOG_LIKE"));

						?><div id="rating_text" class="post-item-informers bx-ilike-block" data-counter="0" style="display: none;"></div><?php

						?><div id="comments_control" style="display: none;" class="post-item-informers post-item-inform-comments"><?php
							?><div class="post-item-inform-comments-box"><?php
								?><span class="post-item-inform-icon"></span><?php
								?><div class="post-item-inform-left"><?=Loc::getMessage('MOBILE_LOG_COMMENT')?></div><?php
							?></div><?php
						?></div><?php

						?><div id="log_entry_follow" class="post-item-informers <?=($arResult["FOLLOW_DEFAULT"] === 'Y' ? 'post-item-follow-default-active' : 'post-item-follow-default')?>" style="display: none;"><?php
							?><div class="post-item-inform-left"></div><?php
						?></div><?php

					?></div><?php

					?><div class="post-item-inform-wrap-right"><?php
						?><a id="post_more_limiter" class="post-item-more" ontouchstart="this.classList.toggle('post-item-more-pressed')" ontouchend="this.classList.toggle('post-item-more-pressed')" style="visibility: hidden;"><?php
							?><?=GetMessage("MOBILE_LOG_EXPAND")?><?php
						?></a><?php
					?></div><?php

				?></div><?php

			?></div><?php
			?><div class="post-comments-wrap" id="post-comments-wrap"><span id="post-comment-last-after"></span></div><?php
		?></div><?php
		?><script>
			var entryType = null;
		</script><?php

		?><div id="post-comments-form-wrap"></div><?php
	}
	elseif (
		$arResult["Events"]
		&& is_array($arResult["Events"])
		&& count($arResult["Events"]) > 0
	)
	{
		?><script>
			if (BX("lenta_block_empty", true))
				BX("lenta_block_empty", true).style.display = "none";
		</script><?php

		$blogPostLivefeedProvider = new \Bitrix\Socialnetwork\Livefeed\BlogPost;
		$blogPostEventIdList = $blogPostLivefeedProvider->getEventId();

		if ($arResult['TARGET'] === 'ENTRIES_ONLY')
		{
			ob_start();
		}

		foreach ($arResult["Events"] as $arEvent)
		{
			$event_cnt++;
			$ind = RandString(8);

			$bUnread = (
				(
					$arParams["SET_LOG_COUNTER"] === "Y"
					|| $arResult["PAGE_NUMBER"] > 1
				)
				&& $arResult["COUNTER_TYPE"] === "**"
				&& $arEvent["USER_ID"] != $arResult['currentUserId']
				&& (int)$arResult["LAST_LOG_TS"] > 0
				&& (MakeTimeStamp($arEvent["LOG_DATE"]) - (int)$arResult["TZ_OFFSET"]) > $arResult["LAST_LOG_TS"]
			);

			if(in_array($arEvent["EVENT_ID"], array_merge($blogPostEventIdList, array("blog_comment", "blog_comment_micro"))))
			{
				require($_SERVER["DOCUMENT_ROOT"].$templateFolder."/include/blog_post.php");
			}
			else
			{
				require($_SERVER["DOCUMENT_ROOT"].$templateFolder."/include/log_entry.php");
			}
		} // foreach ($arResult["Events"] as $arEvent)

		if ($arResult['TARGET'] === 'ENTRIES_ONLY')
		{
			$targetHtml = ob_get_contents();
		}

	} // if ($arResult["Events"] && is_array($arResult["Events"]) && count($arResult["Events"]) > 0)
	elseif (
		in_array($arResult['PAGE_MODE'], [ 'detail', 'detail_comments' ], true)
		&& (
			!$arResult["Events"]
			|| !is_array($arResult["Events"])
			|| count($arResult["Events"]) <= 0
		)
	)
	{
		$APPLICATION->RestartBuffer();
		$res = array(
			'ERROR_MESSAGE' => Loc::getMessage('MOBILE_LOG_ERROR_ENTRY_NOT_FOUND2')
		);

		CMain::FinalActions(CUtil::PhpToJSObject($res));
	}
	elseif ($arResult['PAGE_MODE'] !== 'next')
	{
		if ($arParams["LOG_ID"] > 0)
		{
			?><div class="post-wrap">
				<div class="lenta-block-empty"><?= Loc::getMessage('MOBILE_LOG_ERROR_ENTRY_NOT_FOUND2') ?></div>
			</div><?php
		}
		elseif (empty($arResult['pinnedEvents']))
		{
			?><div class="lenta-block-empty" id="lenta_block_empty"><?=Loc::getMessage("MOBILE_LOG_MESSAGE_EMPTY");?></div><?php
		}
	}

	if ($arResult['PAGE_MODE'] === 'next')
	{
		$uri = new Uri(htmlspecialcharsback(POST_FORM_ACTION_URI));
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

		if ((int)$arResult["NEXT_PAGE_SIZE"] > 0)
		{
			$uriParams['pagesize'] = (int)$arResult["NEXT_PAGE_SIZE"];
		}
		$uri->addParams($uriParams);

		?><script>
		BX.ready(function() {
		<?php
			if (
				$event_cnt > 0
				&& $event_cnt >= $arParams["PAGE_SIZE"]
			)
			{
				?>
				BX.MobileLivefeed.PageInstance.setNextPageUrl('<?=CUtil::JSEscape(htmlspecialcharsEx($uri->getUri()))?>');
				<?php
			}
			else
			{
				?>
				BX.MobileLivefeed.PageInstance.initScroll(false, true);
				<?php
				if ($arParams["NEW_LOG_ID"] > 0)
				{
					?>
					setTimeout(function() {
						oMSL.registerBlocksToCheck();
						oMSL.checkNodesHeight();
					}, 1000);
					<?php
				}
			}
			?>
			BitrixMobile.LazyLoad.showImages(); // when load next page
		});
		</script><?php

		if ($arParams["NEW_LOG_ID"] <= 0)
		{
			$arCSSListNew = $APPLICATION->sPath2css;
			$arCSSNew = array();

			foreach ($arCSSListNew as $i => $css_path)
			{
				if(
					mb_strtolower(mb_substr($css_path, 0, 7)) !== 'http://'
					&& mb_strtolower(mb_substr($css_path, 0, 8)) !== 'https://'
				)
				{
					$css_file = (
					($p = mb_strpos($css_path, "?")) > 0
						? mb_substr($css_path, 0, $p)
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
				"LAST_TS" => ($arResult["dateLastPageTS"] ? (int)$arResult["dateLastPageTS"] : 0),
				'LAST_ID' => ($arResult['lastPageId'] ? (int)$arResult['lastPageId'] : 0),
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

			CMain::FinalActions(CUtil::PhpToJSObject($res));
		}
	}

	if (
		$arParams["NEW_LOG_ID"] <= 0
		&& (
			!isset($arParams["TARGET"])
			|| $arParams["TARGET"] == ''
		)
	)
	{
		if (!in_array($arResult['PAGE_MODE'], [ 'detail', 'detail_comments', 'detail_empty' ], true))
		{
			if ($event_cnt >= $arParams["PAGE_SIZE"])
			{
				require($_SERVER['DOCUMENT_ROOT'] . $templateFolder . '/include/nextpage_loader.php');
			}

			?></div><?php // lenta-wrapper, lenta-wrapper-outer, lenta-wrapper-outer-cont
		}

		$uri = new Uri(htmlspecialcharsback(POST_FORM_ACTION_URI));
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

		if ((int)$arResult["NEXT_PAGE_SIZE"] > 0)
		{
			$uriParams['pagesize'] = (int)$arResult["NEXT_PAGE_SIZE"];
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
		?><script>

			BX.MobileLivefeed.Instance.isPullDownEnabled = false;
			BX.MobileLivefeed.Instance.isPullDownLocked = false;

			BX.MobileLivefeed.PageInstance.setNextPageUrl('<?=CUtil::JSEscape(htmlspecialcharsEx($uri->getUri()))?>');

			<?php
			if (
				($arParams['LOG_ID'] > 0 || $arParams['EMPTY_PAGE'] === "Y")
				&& $_REQUEST['BOTTOM'] === 'Y'
			)
			{
				?>
				BX.MobileLivefeed.Post.moveBottom();
			<?php
			}
		?>
		</script>
		<?php
		if ($arResult['PAGE_MODE'] === 'refresh')
		{
			?></div><?php

			if ($arResult["RELOAD_JSON"])
			{
				$strText = ob_get_contents();

				AddEventHandler("main", "OnEndBufferContent", static function($staticContent) use($strText, $arResult)
				{
					$manifest = Composite\AppCache::getInstance();

					$server = \Bitrix\Main\Context::getCurrent()->getServer();
					$manifest->setPageURI($server->get("HTTP_BX_APPCACHE_URL"));
					$manifestId = $manifest->getCurrentManifestID();
					$manifestCache = Composite\AppCache::readManifestCache($manifestId);

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
							foreach ($manifestCache['FILE_DATA']['CSS_FILE_IMAGES'] as $images)
							{
								if (!empty($images))
								{
									foreach($images as $value)
									{
										$manifestCacheFiles[] = $value;
									}
								}
							}
						}
					}

					Asset::getInstance()->stopTarget('livefeed_ajax');
					$resources = Asset::getInstance()->getAssetInfo('livefeed_ajax', AssetMode::STANDARD);
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
					$isManifestUpdated = (!$manifestCache || !$sameFiles || Composite\AppCache::getDebug());

					if ($isManifestUpdated)
					{
						$cache = new \CPHPCache();
						$cachePath = Composite\AppCache::getCachePath($manifestId);
						$cache->CleanDir($cachePath);

						$manifest->setFiles($manifestFiles);
						$currentHashSum = md5(serialize($manifestFiles).serialize($manifest->getFallbackPages()).serialize($manifest->getNetworkFiles()).serialize($manifest->getExcludeImagePatterns()));
						$manifest->setNetworkFiles(Array("*"));

						$manifestFields = array(
							"ID" => $manifestId,
							"TEXT" => $manifest->getManifestContent(),
							"FILE_HASH" => $currentHashSum,
							"EXCLUDE_PATTERNS_HASH" => md5(serialize($manifest->getExcludeImagePatterns())),
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

					CMain::FinalActions(CUtil::PhpToJSObject($res));
				});
			}
			else
			{
				CMain::FinalActions();
			}

		}

		if (isset($feedFrame))
		{
			$feedFrame->end();
			?></div><?php
		}
	}

	if (in_array($arResult['PAGE_MODE'], [ 'first', 'refresh' ], true))
	{
		?></div><?php // lenta_wrapper_global
	}
}

if ($targetHtml <> '')
{
	$APPLICATION->RestartBuffer();
	echo $targetHtml;
}
