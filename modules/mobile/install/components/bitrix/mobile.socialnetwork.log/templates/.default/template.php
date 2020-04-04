<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->AddHeadString('<script src="'.CUtil::GetAdditionalFileURL("/bitrix/components/bitrix/mobile.socialnetwork.log/templates/.default/script_attached.js").'"></script>', true);

if (strlen($arResult["FatalError"])>0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	$event_cnt = 0;

	if (
		$arParams["LOG_ID"] <= 0
		&& !$arResult["AJAX_CALL"]
	)
	{
		?><script type="text/javascript">

		var arLogTs = {};
		var arLikeRandomID = {};

		<?
		if (!$arResult["AJAX_CALL"])
		{
			?>
			var LiveFeedID = parseInt(Math.random() * 100000);
			<?
		}

		if ($arParams["GROUP_ID"] > 0)
		{
			?>
			if (app.enableInVersion(3))
			{
				app.menuCreate({
					items: [
						{
							name: "<?=GetMessageJS("MOBILE_LOG_ADD_POST")?>",
							action: function(){
								if (BMAjaxWrapper.offline === true)
									BMAjaxWrapper.OfflineAlert();
								else
									app.showModalDialog({
										url: "/mobile/log/new_post.php?feed_id=" + LiveFeedID + "&group_id=<?=$arParams["GROUP_ID"]?>"
									});
							},
							arrowFlag: false,
							icon: "add"
						},
						{
							name: '<?php echo GetMessageJS('MB_TASKS_AT_SOCNET_LOG_CPT_MENU_ITEM_LIST'); ?>',
							icon: 'checkbox',
							arrowFlag: true,
							action: function() {
								var path = '<?php echo CUtil::JSEscape($arParams['PATH_TO_TASKS_SNM_ROUTER']); ?>';
								path = path
									.replace('__ROUTE_PAGE__', 'list')
									.replace('#USER_ID#',
										<?php echo (int) $GLOBALS['USER']->GetID(); ?>);
								BXMobileApp.PageManager.loadPageUnique({url: path, bx24ModernStyle: true});
							}
						},
						{
							name: "<?=GetMessageJS("MOBILE_LOG_GROUP_FILES")?>",
							action: function(){
								app.openBXTable({
									url: "/mobile/webdav/group/<?=intval($arParams["GROUP_ID"])?>/",
									table_settings : {
										type : "files",
										useTagsInSearch : false
									}
								});
							},
							arrowFlag: true,
							icon: "file"
						}
					]
				});

				if(app.enableInVersion(10))
				{
					BXMobileApp.UI.Page.TopBar.title.setText("<?=GetMessageJS("MOBILE_LOG_TITLE")?>");
					BXMobileApp.UI.Page.TopBar.title.setCallback(function (){app.menuShow();});
				}
				else
				{
					app.addButtons({
						menuButton: {
							type: "context-menu",
							style: "custom",
							callback: function ()
							{
								app.menuShow();
							}
						}
					});
				}

			}
			else
			{
				app.addButtons({
					addPostButton: {
						type: "plus",
						style: "custom",
						callback: function(){
							if (BMAjaxWrapper.offline === true)
								BMAjaxWrapper.OfflineAlert();
							else
								app.showModalDialog({
									url: "/mobile/log/new_post.php?feed_id=" + LiveFeedID + "&group_id=<?=$arParams["GROUP_ID"]?>"
								});
						}
					}
				});
			}
			<?
		}
		else
		{
			?>
			app.addButtons({
				addPostButton:{
					type: "plus",
					style:"custom",
					callback:function(){
						if (BMAjaxWrapper.offline === true)
							BMAjaxWrapper.OfflineAlert();
						else
							app.showModalDialog({
								url: "/mobile/log/new_post.php?feed_id=" + LiveFeedID
							});
					}
				}
			});
			<?
		}
		?>

		BXMobileApp.addCustomEvent("onMPFSent", function(post_data) {

			if (post_data.LiveFeedID != LiveFeedID)
				return;

			window.scrollTo(0,0);

			__MSLPullDownInit(false);
			__MSLScrollInit(false);

			if (BX('blog-post-new-waiter'))
				BX('blog-post-new-waiter').style.display = 'block';

			BMAjaxWrapper.Wrap({
				'type': 'html',
				'method': 'POST',
				'url': '<?=$APPLICATION->GetCurPageParam("", array("LAST_LOG_TS", "AJAX_CALL"))?>',
				'data': post_data.data,
				'callback': function(post_response_data)
				{
					if (post_response_data != "*")
					{
						if (BX('blog-post-new-waiter'))
							BX('blog-post-new-waiter').style.display = 'none';

						var new_post_id = 'new_post_ajax_' + Math.random();
						var new_post = BX.create('DIV', { props: { id: new_post_id }, html: post_response_data});
						BX('blog-post-first-after').parentNode.insertBefore(new_post, BX('blog-post-first-after').nextSibling);

						var ob = BX(new_post_id);
						var obNew = BX.processHTML(ob.innerHTML, true);
						var scripts = obNew.SCRIPT;
						BX.ajax.processScripts(scripts, true);
					}
					else
					{
						if (BX('blog-post-new-error'))
						{
							BX('blog-post-new-error').style.display = 'block';
							BX.bind(BX('blog-post-new-error'), 'click', __MSLOnErrorClick);
						}
					}
					if (BX('blog-post-new-waiter'))
						BX('blog-post-new-waiter').style.display = 'none';
					__MSLPullDownInit(true);
					__MSLScrollInit(true);
				},
				'callback_failure': function() {
					if (BX('blog-post-new-waiter'))
						BX('blog-post-new-waiter').style.display = 'none';
					__MSLPullDownInit(true);
					__MSLScrollInit(true);
				}
			});
		});

		BX.addCustomEvent("onStreamRefresh", function(data) {
			document.location.reload();
		});

		BXMobileApp.addCustomEvent("onLogEntryRead", function(data) {
			__MSLLogEntryRead(data.log_id, data.ts, (data.bPull === true || data.bPull === 'YES' ? true : false));
		});

		BXMobileApp.addCustomEvent("onLogEntryCommentAdd", function(data) {
			__MSLLogEntryCommentAdd(data.log_id);
		});

		BXMobileApp.addCustomEvent("onLogEntryRatingLike", function(data) {
			__MSLLogEntryRatingLike(data.rating_id, data.voteAction);
		});

		BXMobileApp.addCustomEvent("onLogEntryFollow", function(data) {
			__MSLLogEntryFollow(data.log_id);
		});

		BX.message({
			MSLNextPostMoreTitle: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_NEXT_POST_MORE"))?>',
			MSLPullDownText1: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_NEW_PULL"))?>',
			MSLPullDownText2: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_NEW_PULL_RELEASE"))?>',
			MSLPullDownText3: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_NEW_PULL_LOADING"))?>',
			MSLLogCounter1: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_COUNTER_1"))?>',
			MSLLogCounter2: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_COUNTER_2"))?>',
			MSLLogCounter3: '<?=CUtil::JSEscape(GetMessage("MOBILE_LOG_COUNTER_3"))?>'
			<?
			if ($arParams["USE_FOLLOW"] == "Y"):
				?>
				, MSLFollowY: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_Y")?>'
				, MSLFollowN: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_N")?>'
				<?
			endif;
			?>
		});
		</script>
		<div class="lenta-notifier" id="lenta_notifier" onclick="if (!BMAjaxWrapper.offline) { app.BasicAuth({'success': function() { document.location.reload(); }, 'failture': function() { } }); return false; }"><span class="lenta-notifier-arrow"></span><span class="lenta-notifier-text"><span id="lenta_notifier_cnt"></span>&nbsp;<span id="lenta_notifier_cnt_title"></span></span></div><?
	}
	elseif ($arParams["LOG_ID"] > 0)
	{
		?><div style="display: none;" id="comment_send_button_waiter" class="send-message-button-waiter"></div>
		<script type="text/javascript">
			BXMobileApp.onCustomEvent('onLogEntryRead', { log_id: <?=$arParams["LOG_ID"]?>, ts: <?=time()?>, bPull: false }, true);

			if (window.platform != "android")
				app.enableScroll(false);

			BX.message({
				MSLSessid: '<?=bitrix_sessid()?>',
				MSLSiteId: '<?=CUtil::JSEscape(SITE_ID)?>',
				MSLLangId: '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
				MSLLogId: <?=intval($arParams["LOG_ID"])?>,
				MSLPathToUser: '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
				MSLDestinationLimit: '<?=intval($arParams["DESTINATION_LIMIT"])?>',
				MSLNameTemplate: '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
				MSLShowLogin: '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>'
				<?
				if ($arParams["USE_FOLLOW"] == "Y"):
					?>
					, MSLFollowY: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_Y")?>'
					, MSLFollowN: '<?=GetMessageJS("MOBILE_LOG_FOLLOW_N")?>'
					<?
				endif;
				?>
			});
		</script><?
	}

	if (!$arResult["AJAX_CALL"])
	{
		?><script type="text/javascript">
			var arBlockToCheck = [];
		</script><?
	}

	if ($arParams["LOG_ID"] > 0)
	{
		?><div class="post-card-wrap" id="post-card-wrap" onclick=""><?
	}
	else
	{
		?><div class="lenta-wrapper" id="lenta_wrapper"><?
			?><div class="lenta-item post-without-informers new-post-message" id="blog-post-new-waiter" style="display: none;"><?
				?><div class="post-item-top-wrap"><?
					?><div class="new-post-waiter"></div><?
				?></div><?
			?></div><?
			?><div class="lenta-item post-without-informers new-post-message" id="blog-post-new-error" style="display: none;"><?
				?><div class="post-item-top-wrap"><div class="post-item-post-block"><div class="post-item-text" style="text-align: center;"><?=GetMessage("MOBILE_LOG_NEW_ERROR")?></div></div></div><?
			?></div><?
			?><span id="blog-post-first-after"></span><?
	}

	if(strlen($arResult["ErrorMessage"])>0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}

	if($arResult["AJAX_CALL"])
	{
		$GLOBALS["APPLICATION"]->RestartBuffer();

		?><script type="text/javascript">
			arBlockToCheck = []; // empty array to check height
		</script><?
	}

	if (
		$arResult["EventsNew"]
		&& is_array($arResult["EventsNew"])
		&& count($arResult["EventsNew"]) > 0
	)
	{
		?><script type="text/javascript">
			if (BX("lenta_block_empty", true))
				BX("lenta_block_empty", true).style.display = "none";
		</script><?

		foreach ($arResult["EventsNew"] as $arEvent)
		{
			if (!empty($arEvent["EVENT"]))
			{
				$bBottomShow = false;
				$event_cnt++;
				$ind = RandString(8);

				$bUnread = (
					($arParams["SHOW_UNREAD"] == "Y")
					&& $arResult["COUNTER_TYPE"] == "**"
					&& $arEvent["EVENT"]["USER_ID"] != $GLOBALS["USER"]->GetID()
					&& intval($arResult["LAST_LOG_TS"]) > 0
					&& (MakeTimeStamp($arEvent["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) > $arResult["LAST_LOG_TS"]
				);
				$is_hidden = (array_key_exists("VISIBLE", $arEvent) && $arEvent["VISIBLE"] == "N");

				$strTopic = "";
				if (
					array_key_exists("DESTINATION", $arEvent["EVENT_FORMATTED"])
					&& is_array($arEvent["EVENT_FORMATTED"]["DESTINATION"])
					&& count($arEvent["EVENT_FORMATTED"]["DESTINATION"]) > 0
				)
				{
					if (
						array_key_exists("TITLE_24", $arEvent["EVENT_FORMATTED"])
						&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24"]) > 0
					)
						$strTopic .= '<div class="post-item-top-text post-item-top-arrow'.(strlen($arEvent["EVENT_FORMATTED"]["STYLE"]) > 0 ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE_24"].'</div>';

					if (in_array($arEvent["EVENT"]["EVENT_ID"], array("system", "system_groups", "system_friends")))
					{
						foreach($arEvent["EVENT_FORMATTED"]["DESTINATION"] as $arDestination)
						{
							if (strlen($arDestination["URL"]) > 0)
								$strTopic .= '<a href="'.$arDestination["URL"].'" class="post-item-topic-description"><span'.(strlen($arDestination["STYLE"]) > 0 ? ' class="post-item-top-text-'.$arDestination["STYLE"].'"' : '').'>'.$arDestination["TITLE"].'</span></a>';
							else
								$strTopic .= '<span class="post-item-topic-description"><span'.(strlen($arDestination["STYLE"]) > 0 ? ' class="post-item-top-text-'.$arDestination["STYLE"].'"' : '').'>'.$arDestination["TITLE"].'</span></span>';
						}
					}
					else
					{
						$i = 0;
						foreach($arEvent["EVENT_FORMATTED"]["DESTINATION"] as $arDestination)
						{
							if ($i > 0)
								$strTopic .= ', ';

							if (strlen($arDestination["URL"]) > 0)
								$strTopic .= '<a href="'.$arDestination["URL"].'" class="post-item-destination'.(strlen($arDestination["STYLE"]) > 0 ? ' post-item-dest-'.$arDestination["STYLE"] : '').'">'.$arDestination["TITLE"].'</a>';
							else
								$strTopic .= '<span class="post-item-destination'.(strlen($arDestination["STYLE"]) > 0 ? ' post-item-dest-'.$arDestination["STYLE"] : '').'">'.$arDestination["TITLE"].'</span>';

							$i++;
						}
						if (intval($arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"]) > 0)
						{
							$moreClick = ($arParams["LOG_ID"] > 0 ? " onclick=\"__MSLGetHiddenDestinations(".$arEvent["EVENT"]["ID"].", ".$arEvent["EVENT"]["USER_ID"].", this);\"" : "");
							$strTopic .= "<span class=\"post-destination-more\"".$moreClick." ontouchstart=\"BX.toggleClass(this, 'post-destination-more-touch');\" ontouchend=\"BX.toggleClass(this, 'post-destination-more-touch');\">".str_replace("#COUNT#", $arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"], GetMessage("MOBILE_LOG_DESTINATION_MORE"))."</span>";
						}
					}
				}
				elseif (
					array_key_exists("TITLE_24", $arEvent["EVENT_FORMATTED"])
					&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24"]) > 0
				)
					$strTopic .= '<div class="post-item-top-text'.(strlen($arEvent["EVENT_FORMATTED"]["STYLE"]) > 0 ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE_24"].'</div>';
				else
					$strTopic .= '<div class="post-item-top-text'.(strlen($arEvent["EVENT_FORMATTED"]["STYLE"]) > 0 ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE"].'</div>';

				$strCreatedBy = "";
				if (
					array_key_exists("CREATED_BY", $arEvent)
					&& is_array($arEvent["CREATED_BY"])
				)
				{
					if (
						array_key_exists("TOOLTIP_FIELDS", $arEvent["CREATED_BY"])
						&& is_array($arEvent["CREATED_BY"]["TOOLTIP_FIELDS"])
					)
						$strCreatedBy .= '<a class="post-item-top-title" href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"], $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"], ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</a>';
					elseif (
						array_key_exists("FORMATTED", $arEvent["CREATED_BY"])
						&& strlen($arEvent["CREATED_BY"]["FORMATTED"]) > 0
					)
						$strCreatedBy .= '<div class="post-item-top-title">'.$arEvent["CREATED_BY"]["FORMATTED"].'</div>';
				}
				elseif (
					in_array($arEvent["EVENT"]["EVENT_ID"], array("data", "news", "system"))
					&& array_key_exists("ENTITY", $arEvent)
				)
				{
					if (
						array_key_exists("TOOLTIP_FIELDS", $arEvent["ENTITY"])
						&& is_array($arEvent["ENTITY"]["TOOLTIP_FIELDS"])
					)
						$strCreatedBy .= '<a class="post-item-top-title" href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arEvent["ENTITY"]["TOOLTIP_FIELDS"]["ID"], $arEvent["ENTITY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arEvent["ENTITY"]["TOOLTIP_FIELDS"], ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</a>';
					elseif (
						array_key_exists("FORMATTED", $arEvent["ENTITY"])
						&& array_key_exists("NAME", $arEvent["ENTITY"]["FORMATTED"])
					)
						$strCreatedBy .= '<div class="post-item-top-title">'.$arEvent["ENTITY"]["FORMATTED"]["NAME"].'</div>';
				}

				$strDescription = "";
				if (
					array_key_exists("DESCRIPTION", $arEvent["EVENT_FORMATTED"])
					&& (
						(!is_array($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) && strlen($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) > 0)
						|| (is_array($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) && count($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) > 0)
					)
				)
					$strDescription = '<div class="post-item-description'.(strlen($arEvent["EVENT_FORMATTED"]["DESCRIPTION_STYLE"]) > 0 ? ' post-item-description-'.$arEvent["EVENT_FORMATTED"]["DESCRIPTION_STYLE"].'"' : '').'">'.(is_array($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) ? '<span>'.implode('</span> <span>', $arEvent["EVENT_FORMATTED"]["DESCRIPTION"]).'</span>' : $arEvent["EVENT_FORMATTED"]["DESCRIPTION"]).'</div>';

				if ($arParams["LOG_ID"] <= 0)
				{
					?><script type="text/javascript">
						arLogTs.entry_<?=intval($arEvent["EVENT"]["ID"])?> = <?=intval($arResult["LAST_LOG_TS"])?>;
					</script><?
				}

				if ($arParams["LOG_ID"] <= 0)
				{
					if (
						isset($arEvent['EVENT'])
						&& isset($arEvent['EVENT']['MODULE_ID'])
						&& ($arEvent['EVENT']['MODULE_ID'] === 'tasks')
						&& isset($arEvent['EVENT']['EVENT_ID'])
						&& ($arEvent['EVENT']['EVENT_ID'] === 'tasks')
						&& isset($arEvent['EVENT']['SOURCE_ID'])
						&& ($arEvent['EVENT']['SOURCE_ID'] > 0)
						&& (intval($GLOBALS["APPLICATION"]->GetPageProperty("api_version")) >= 2)
					)
					{
						$strPath = str_replace(
							array('__ROUTE_PAGE__', '#USER_ID#'),
							array('view', (int) $GLOBALS['USER']->GetID()),
							$arParams['PATH_TO_TASKS_SNM_ROUTER'] 
							. '&TASK_ID=' . (int) $arEvent['EVENT']['SOURCE_ID']
						);
					}
					else
						$strPath = str_replace("#log_id#", $arEvent["EVENT"]["ID"], $arParams["PATH_TO_LOG_ENTRY"]);
				}

				$strOnClick = ($arParams["LOG_ID"] <= 0 ? " onclick=\"__MSLOpenLogEntry(".intval($arEvent["EVENT"]["ID"]).", '".$strPath."', false, event);\"" : "");

				$bHasNoCommentsOrLikes = (
					(
						!isset($arEvent["EVENT_FORMATTED"]) 
						|| !isset($arEvent["EVENT_FORMATTED"]["HAS_COMMENTS"])
						|| $arEvent["EVENT_FORMATTED"]["HAS_COMMENTS"] != "Y"
					)
					&& (
						$arParams["SHOW_RATING"] != "Y" 
						|| strlen($arParams["EVENT"]["RATING_TYPE_ID"]) <= 0 
						|| intval($arParams["EVENT"]["RATING_ENTITY_ID"]) <= 0
					)
				);

				$item_class = ($arParams["LOG_ID"] > 0 ? "post-wrap" : "lenta-item".($bUnread ? " lenta-item-new" : "")).($bHasNoCommentsOrLikes ? " post-without-informers" : "");

				if (
					array_key_exists("EVENT_FORMATTED", $arEvent)
					&& array_key_exists("DATETIME_FORMATTED", $arEvent["EVENT_FORMATTED"])
					&& strlen($arEvent["EVENT_FORMATTED"]["DATETIME_FORMATTED"]) > 0
				)
					$datetime = $arEvent["EVENT_FORMATTED"]["DATETIME_FORMATTED"];
				elseif (
					array_key_exists("DATETIME_FORMATTED", $arEvent)
					&& strlen($arEvent["DATETIME_FORMATTED"]) > 0
				)
					$datetime = $arEvent["DATETIME_FORMATTED"];
				elseif ($arEvent["LOG_DATE_DAY"] == ConvertTimeStamp())
					$datetime = $arEvent["LOG_TIME_FORMAT"];
				else
					$datetime = $arEvent["LOG_DATE_DAY"]." ".$arEvent["LOG_TIME_FORMAT"];

				?><div class="<?=($item_class)?>" id="lenta_item_<?=$arEvent["EVENT"]["ID"]?>">

					<div class="post-item-top-wrap">
						<div class="post-item-top">
							<div class="avatar<?=(strlen($arEvent["EVENT_FORMATTED"]["AVATAR_STYLE"]) > 0 ? " ".$arEvent["EVENT_FORMATTED"]["AVATAR_STYLE"] : "")?>"<?=(strlen($arEvent["AVATAR_SRC"]) > 0 ? " style=\"background:url('".$arEvent["AVATAR_SRC"]."') 0 0 no-repeat; background-size: 29px 29px;\"" : "")?>></div>
							<div class="post-item-top-cont">
								<?=$strCreatedBy?><?
								if ($arParams["LOG_ID"] > 0)
								{
									?><div class="post-date"><?=$datetime?></div><?
								}
								?><div class="post-item-top-topic"><?=$strTopic ?></div><?
								if (strlen($strDescription) > 0)
									echo $strDescription;
							?></div><?
							if ($arParams["LOG_ID"] <= 0)
							{
								?><div class="lenta-item-time"><?=$datetime?></div><?
							}
						?></div><?

						ob_start();

						if (
							array_key_exists("HAS_COMMENTS", $arEvent)
							&& $arEvent["HAS_COMMENTS"] == "Y"
						)
						{
							$bHasComments = true;
							$strOnClickComments = ($arParams["LOG_ID"] <= 0 ? " onclick=\"__MSLOpenLogEntry(".intval($arEvent["EVENT"]["ID"]).", '".$strPath."', true);\"" : " onclick=\"__MSLDetailMoveBottom();\"");
							?><div class="post-item-informers post-item-inform-comments"<?=$strOnClickComments?>><div class="post-item-inform-left"></div><div class="post-item-inform-right" id="informer_comments_<?=$arEvent["EVENT"]["ID"]?>"><?
							if (
								($arParams["USE_FOLLOW"] != "Y" || $arEvent["EVENT"]["FOLLOW"] == "Y")
								&& is_array($arResult["NEW_COMMENTS"])
								&& array_key_exists($arEvent["EVENT"]["ID"], $arResult["NEW_COMMENTS"])
								&& intval($arResult["NEW_COMMENTS"][$arEvent["EVENT"]["ID"]]) > 0
							)
							{
								?><span id="informer_comments_all_<?=$arEvent["EVENT"]["ID"]?>"><?
									$old_comments = intval(abs(intval($arEvent["COMMENTS_COUNT"]) - intval($arResult["NEW_COMMENTS"][$arEvent["EVENT"]["ID"]])));
									echo ($old_comments > 0 ? $old_comments : '');
								?></span><?
								?><span id="informer_comments_new_<?=$arEvent["EVENT"]["ID"]?>">+<?=intval($arResult["NEW_COMMENTS"][$arEvent["EVENT"]["ID"]])?></span><?
							}
							else
							{
								?><?=intval($arEvent["COMMENTS_COUNT"])?><?
							}
							?></div></div><?
						}
						else
							$bHasComments = false;

						if (
							strlen($arEvent["EVENT"]["RATING_TYPE_ID"]) > 0
							&& $arEvent["EVENT"]["RATING_ENTITY_ID"] > 0
							&& $arParams["SHOW_RATING"] == "Y"
						)
						{
							$arResultVote = $APPLICATION->IncludeComponent(
								"bitrix:rating.vote", "mobile_like",
								Array(
									"ENTITY_TYPE_ID" => $arEvent["EVENT"]["RATING_TYPE_ID"],
									"ENTITY_ID" => $arEvent["EVENT"]["RATING_ENTITY_ID"],
									"OWNER_ID" => $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"],
									"USER_VOTE" => $arEvent["EVENT"]["RATING_USER_VOTE_VALUE"],
									"USER_HAS_VOTED" => $arEvent["EVENT"]["RATING_USER_VOTE_VALUE"] == 0? 'N': 'Y',
									"TOTAL_VOTES" => $arEvent["EVENT"]["RATING_TOTAL_VOTES"],
									"TOTAL_POSITIVE_VOTES" => $arEvent["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"],
									"TOTAL_NEGATIVE_VOTES" => $arEvent["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"],
									"TOTAL_VALUE" => $arEvent["EVENT"]["RATING_TOTAL_VALUE"],
									"PATH_TO_USER_PROFILE" => $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"],
									"EXTENDED" => ($arParams["LOG_ID"] > 0 ? "Y" : "N"),
									"VOTE_RAND" => ($arParams["LOG_ID"] > 0 && intval($_REQUEST["LIKE_RANDOM_ID"]) > 0 ? intval($_REQUEST["LIKE_RANDOM_ID"]) : false)
								),
								$component,
								array("HIDE_ICONS" => "Y")
							);

							$bRatingExtended = (
								$arParams["LOG_ID"] > 0
								&& intval($GLOBALS["APPLICATION"]->GetPageProperty("api_version")) >= 2
							);

							$bRatingExtendedOpen = (
								$bRatingExtended
								&& intval($arResultVote["TOTAL_VOTES"]) > 0
							);

							if (
								$arParams["LOG_ID"] <= 0
								&& intval($arResultVote["VOTE_RAND"]) > 0
							)
							{
								?><script type="text/javascript">
									arLikeRandomID.entry_<?=intval($arEvent["EVENT"]["ID"])?> = <?=intval($arResultVote["VOTE_RAND"])?>;
								</script><?
							}
						}

						if (
							$bHasComments
							&& array_key_exists("FOLLOW", $arEvent["EVENT"])
						)
						{
							$follow_type_default = " post-item-follow-default".($arResult["FOLLOW_DEFAULT"] == "Y" ? "-active" : "");
							$follow_type = " post-item-follow".($arEvent["EVENT"]["FOLLOW"] == "Y" ? "-active" : "");
							?><div id="log_entry_follow_<?=intval($arEvent["EVENT"]["ID"])?>" data-follow="<?=($arEvent["EVENT"]["FOLLOW"] == "Y" ? "Y" : "N")?>" class="post-item-informers<?=$follow_type_default?><?=$follow_type?>" onclick="__MSLSetFollow(<?=$arEvent["EVENT"]["ID"]?>)">
								<div class="post-item-inform-left"></div>
							</div><?
						}

						if ($_REQUEST["show_full"] != "Y")
						{
							if ($arParams["LOG_ID"] <= 0)
								$strOnClickMore = "onclick=\"__MSLOpenLogEntry(".intval($arEvent["EVENT"]["ID"]).", '".str_replace("#log_id#", $arEvent["EVENT"]["ID"], $arParams["PATH_TO_LOG_ENTRY"])."&show_full=Y');\"";
							else
								$strOnClickMore = "onclick=\"__MSLExpandText(".intval($arEvent["EVENT"]["ID"]).");\"";

							?><div <?=$strOnClickMore?> class="post-item-more" ontouchstart="BX.toggleClass(this, 'post-item-more-pressed');" ontouchend="BX.toggleClass(this, 'post-item-more-pressed');" style="display: none;" id="post_block_check_more_<?=$arEvent["EVENT"]["ID"]?>"><?=GetMessage("MOBILE_LOG_MORE")?></div><?
						}

						if ($bRatingExtended)
						{
							?><div class="post-item-inform-footer" id="rating-footer"></div><?
						}

						$strBottomBlock = ob_get_contents();
						ob_end_clean();

						$post_more_block = ($_REQUEST["show_full"] != "Y" ? '<div class="post-more-block" id="post_more_block_'.$arEvent["EVENT"]["ID"].'"></div>' : '');
						$post_more_corner = ($_REQUEST["show_full"] != "Y" ? '<div class="post-item-corner" id="post_more_corner_'.$arEvent["EVENT"]["ID"].'"></div>' : '');

						$post_item_style = ($arParams["LOG_ID"] > 0 && $_REQUEST["show_full"] == "Y" ? "post-item-post-block-full" : "post-item-post-block");

						if(in_array($arEvent["EVENT"]["EVENT_ID"], Array("blog_post", "blog_post_micro", "blog_comment", "blog_comment_micro")))
						{
							?><div class="<?=$post_item_style?>"<?=$strOnClick?> id="post_block_check_cont_<?=$arEvent["EVENT"]["ID"]?>"><?

								$arComponentParams = array(
									"PATH_TO_BLOG" => $arParams["PATH_TO_USER_BLOG"],
									"PATH_TO_POST" => $arParams["PATH_TO_USER_MICROBLOG_POST"],
									"PATH_TO_BLOG_CATEGORY" => $arParams["PATH_TO_USER_BLOG_CATEGORY"],
									"PATH_TO_POST_EDIT" => $arParams["PATH_TO_USER_BLOG_POST_EDIT"],
									"PATH_TO_USER" => $arParams["PATH_TO_USER"],
									"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
									"PATH_TO_SMILE" => $arParams["PATH_TO_BLOG_SMILE"],
									"PATH_TO_MESSAGES_CHAT" => $arResult["PATH_TO_MESSAGES_CHAT"],
									"SET_NAV_CHAIN" => "N",
									"SET_TITLE" => "N",
									"POST_PROPERTY" => $arParams["POST_PROPERTY"],
									"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
									"LOG_ID" => $arEvent["EVENT"]["ID"],
									"USER_ID" => $arEvent["EVENT"]["USER_ID"],
									"ENTITY_TYPE" => $arEvent["EVENT"]["ENTITY_TYPE"],
									"ENTITY_ID" => $arEvent["EVENT"]["ENTITY_ID"],
									"EVENT_ID" => $arEvent["EVENT"]["EVENT_ID"],
									"EVENT_ID_FULLSET" => $arEvent["EVENT"]["EVENT_ID_FULLSET"],
									"IND" => $ind,
//									"GROUP_ID" => $arParams["BLOG_GROUP_ID"],
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
									"ID" => $arEvent["EVENT"]["SOURCE_ID"],
									"FROM_LOG" => "Y",
									"ADIT_MENU" => $arAditMenu,
									"IS_UNREAD" => $bUnread,
									"IS_HIDDEN" => $is_hidden,
									"LAST_LOG_TS" => ($arResult["LAST_LOG_TS"]+$arResult["TZ_OFFSET"]),
									"CACHE_TIME" => $arParams["CACHE_TIME"],
									"CACHE_TYPE" => $arParams["CACHE_TYPE"],
									"ALLOW_VIDEO"  => $arParams["BLOG_COMMENT_ALLOW_VIDEO"],
									"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"],
									"USE_CUT" => $arParams["BLOG_USE_CUT"],
									"MOBILE" => "Y",
									"ATTACHED_IMAGE_MAX_WIDTH_FULL" => 640,
									"ATTACHED_IMAGE_MAX_HEIGHT_FULL" => 832,
									"RETURN_DATA" => ($arParams["LOG_ID"] > 0 ? "Y" : "N")
								);

								if ($arParams["USE_FOLLOW"] == "Y")
									$arComponentParams["FOLLOW"] = $arEvent["EVENT"]["FOLLOW"];

								if (
									strlen($arEvent["EVENT"]["RATING_TYPE_ID"])>0
									&& $arEvent["EVENT"]["RATING_ENTITY_ID"] > 0
									&& $arParams["SHOW_RATING"] == "Y"
								)
								{
									$arComponentParams["RATING_ENTITY_ID"] = $arEvent["EVENT"]["RATING_ENTITY_ID"];
									$arComponentParams["RATING_USER_VOTE_VALUE"] = $arEvent["EVENT"]["RATING_USER_VOTE_VALUE"];
									$arComponentParams["RATING_TOTAL_VOTES"] = $arEvent["EVENT"]["RATING_TOTAL_VOTES"];
									$arComponentParams["RATING_TOTAL_POSITIVE_VOTES"] = $arEvent["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"];
									$arComponentParams["RATING_TOTAL_NEGATIVE_VOTES"] = $arEvent["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"];
									$arComponentParams["RATING_TOTAL_VALUE"] = $arEvent["EVENT"]["RATING_TOTAL_VALUE"];
								}

								$arBlogPostResult = $APPLICATION->IncludeComponent(
									"bitrix:socialnetwork.blog.post",
									"mobile",
									$arComponentParams,
									$component,
									Array("HIDE_ICONS" => "Y")
								);

								echo $post_more_block;

							?></div><?
						}
						elseif (in_array($arEvent["EVENT"]["EVENT_ID"], array("photo", "photo_photo")))
						{
							if ($arEvent["EVENT"]["EVENT_ID"] == "photo")
							{
								?><div class="post-item-post-block-full"<?=$strOnClick?>><?
							}
							else
							{
								?><div class="post-item-post-img-block"<?=$strOnClick?>><?
							}

								$arPhotoItems = array();
								$photo_section_id = false;
								if ($arEvent["EVENT"]["EVENT_ID"] == "photo")
								{
									$photo_section_id = $arEvent["EVENT"]["SOURCE_ID"];
									if (strlen($arEvent["EVENT"]["PARAMS"]) > 0)
									{
										$arEventParams = unserialize(htmlspecialcharsback($arEvent["EVENT"]["PARAMS"]));
										if (
											$arEventParams
											&& is_array($arEventParams)
											&& array_key_exists("arItems", $arEventParams)
											&& is_array($arEventParams["arItems"])
										)
											$arPhotoItems = $arEventParams["arItems"];
									}
								}
								elseif ($arEvent["EVENT"]["EVENT_ID"] == "photo_photo")
								{
									if (intval($arEvent["EVENT"]["SOURCE_ID"]) > 0)
										$arPhotoItems = array($arEvent["EVENT"]["SOURCE_ID"]);

									if (strlen($arEvent["EVENT"]["PARAMS"]) > 0)
									{
										$arEventParams = unserialize(htmlspecialcharsback($arEvent["EVENT"]["PARAMS"]));
										if (
											$arEventParams
											&& is_array($arEventParams)
											&& array_key_exists("SECTION_ID", $arEventParams)
											&& intval($arEventParams["SECTION_ID"]) > 0
										)
											$photo_section_id = $arEventParams["SECTION_ID"];
									}
								}

								if (strlen($arEvent["EVENT"]["PARAMS"]) > 0)
								{
									$arEventParams = unserialize(htmlspecialcharsback($arEvent["EVENT"]["PARAMS"]));

									$photo_iblock_type = $arEventParams["IBLOCK_TYPE"];
									$photo_iblock_id = $arEventParams["IBLOCK_ID"];

									if (is_array($arEventParams) && array_key_exists("ALIAS", $arEventParams))
										$alias = $arEventParams["ALIAS"];
								else
										$alias = false;

									if ($arEvent["EVENT"]["EVENT_ID"] == "photo")
									{
										$photo_detail_url = $arEventParams["DETAIL_URL"];
										if ($photo_detail_url && IsModuleInstalled("extranet") && $arEvent["EVENT"]["ENTITY_TYPE"] == SONET_ENTITY_GROUP)
											$photo_detail_url = str_replace("#GROUPS_PATH#", $arResult["WORKGROUPS_PAGE"], $photo_detail_url);
									}
									elseif ($arEvent["EVENT"]["EVENT_ID"] == "photo_photo")
										$photo_detail_url = $arEvent["EVENT"]["URL"];

									if (!$photo_detail_url)
										$photo_detail_url = $arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_ELEMENT"];

									if (
										strlen($photo_iblock_type) > 0
										&& intval($photo_iblock_id) > 0
										&& intval($photo_section_id) > 0
										&& count($arPhotoItems) > 0
									)
									{
										?><?$APPLICATION->IncludeComponent(
											"bitrix:photogallery.detail.list.ex",
											"mobile",
											Array(
												"IBLOCK_TYPE" => $photo_iblock_type,
												"IBLOCK_ID" => $photo_iblock_id,
												"SHOWN_PHOTOS" => (count($arPhotoItems) > $arParams["PHOTO_COUNT"]
													? array_slice(
														$arPhotoItems,
														-($arParams["PHOTO_COUNT"]),
														$arParams["PHOTO_COUNT"]
													)
													: $arPhotoItems
												),
												"DRAG_SORT" => "N",
												"MORE_PHOTO_NAV" => "N",
												"LIVEFEED_EVENT_ID" => $arEvent["EVENT"]["EVENT_ID"],
												"THUMBNAIL_SIZE" => ($arEvent["EVENT"]["EVENT_ID"] == "photo" ? $arParams["PHOTO_THUMBNAIL_SIZE"] : 568),
												"THUMBNAIL_RESIZE_METHOD" => ($arEvent["EVENT"]["EVENT_ID"] == "photo" ? "EXACT" : false),
												"SHOW_CONTROLS" => "N",
												"USE_RATING" => ($arParams["PHOTO_USE_RATING"] == "Y" || $arParams["SHOW_RATING"] == "Y" ? "Y" : "N"),
												"SHOW_RATING" => $arParams["SHOW_RATING"],
												"SHOW_SHOWS" => "N",
												"SHOW_COMMENTS" => "Y",
												"MAX_VOTE" => $arParams["PHOTO_MAX_VOTE"],
												"VOTE_NAMES" => isset($arParams["PHOTO_VOTE_NAMES"])? $arParams["PHOTO_VOTE_NAMES"]: Array(),
												"DISPLAY_AS_RATING" => $arParams["SHOW_RATING"] == "Y"? "rating_main": isset($arParams["PHOTO_DISPLAY_AS_RATING"])? $arParams["PHOTO_DISPLAY_AS_RATING"]: "rating",
												"RATING_MAIN_TYPE" => $arParams["SHOW_RATING"] == "Y"? $arParams["RATING_TYPE"]: "",

												"BEHAVIOUR" => "SIMPLE",
												"SET_TITLE" => "N",
												"CACHE_TYPE" => "A",
												"CACHE_TIME" => $arParams["CACHE_TIME"],
												"CACHE_NOTES" => "",
												"SECTION_ID" => $photo_section_id,
												"ELEMENT_LAST_TYPE"	=> "none",
												"ELEMENT_SORT_FIELD" => "ID",
												"ELEMENT_SORT_ORDER" => "asc",
												"ELEMENT_SORT_FIELD1" => "",
												"ELEMENT_SORT_ORDER1" => "asc",
												"PROPERTY_CODE" => array(),

												"INDEX_URL" => CComponentEngine::MakePathFromTemplate(
													$arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO"],
													array(
														"user_id" => $arEvent["EVENT"]["ENTITY_ID"],
														"group_id" => $arEvent["EVENT"]["ENTITY_ID"]
													)
												),
												"DETAIL_URL" => CComponentEngine::MakePathFromTemplate(
													$photo_detail_url,
													array(
														"user_id" => $arEvent["EVENT"]["ENTITY_ID"],
														"group_id" => $arEvent["EVENT"]["ENTITY_ID"],
													)
												),
												"GALLERY_URL" => "",
												"SECTION_URL" => CComponentEngine::MakePathFromTemplate(
													$arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_SECTION"],
													array(
														"user_id" => $arEvent["EVENT"]["ENTITY_ID"],
														"group_id" => $arEvent["EVENT"]["ENTITY_ID"],
														"section_id" => ($arEvent["EVENT"]["EVENT_ID"] == "photo_photo" ? $photo_section_id : $arEvent["EVENT"]["SOURCE_ID"])
													)
												),
												"PATH_TO_USER" => "",
												"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
												"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],

												"USE_PERMISSIONS" => "N",
												"GROUP_PERMISSIONS" => array(),
												"PAGE_ELEMENTS" => $arParams["PHOTO_COUNT"],
												"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT_DETAIL"],
												"SET_STATUS_404" => "N",
												"ADDITIONAL_SIGHTS" => array(),
												"PICTURES_SIGHT" => "real",
												"USE_COMMENTS" => $arParams["PHOTO_USE_COMMENTS"],
												"COMMENTS_TYPE" => ($arParams["PHOTO_COMMENTS_TYPE"] == "blog" ? "blog" : "forum"),
												"FORUM_ID" => $arParams["PHOTO_FORUM_ID"],
												"BLOG_URL" => $arParams["PHOTO_BLOG_URL"],
												"USE_CAPTCHA" => $arParams["PHOTO_USE_CAPTCHA"],
												"SHOW_LINK_TO_FORUM" => "N",
												"IS_SOCNET" => "Y",
												"USER_ALIAS"	=> ($alias ? $alias : ($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "group" : "user"."_".$arEvent["EVENT"]["ENTITY_ID"])),
												//these two params below used to set action url and unique id - for any ajax actions
												"~UNIQUE_COMPONENT_ID" => 'bxfg_ucid_from_req_'.$photo_iblock_id.'_'.($arEvent["EVENT"]["EVENT_ID"] == "photo_photo" ? $photo_section_id : $arEvent["EVENT"]["SOURCE_ID"])."_".$arEvent["EVENT"]["ID"],
												"ACTION_URL" => CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_".($arEvent["EVENT"]["ENTITY_TYPE"] == SONET_SUBSCRIBE_ENTITY_GROUP ? "GROUP" : "USER")."_PHOTO_SECTION"], array("user_id" => $arEvent["EVENT"]["ENTITY_ID"],"group_id" => $arEvent["EVENT"]["ENTITY_ID"],"section_id" => ($arEvent["EVENT"]["EVENT_ID"] == "photo_photo" ? $photo_section_id : $arEvent["EVENT"]["SOURCE_ID"]))),
											),
											$component,
											array(
												"HIDE_ICONS" => "Y"
											)
										);?><?
									}
								}

							?></div><?
						}
						elseif (strlen($arEvent["EVENT_FORMATTED"]["MESSAGE"]) > 0)
						{

							// body

							if (
								array_key_exists("EVENT_FORMATTED", $arEvent)
								&& array_key_exists("IS_IMPORTANT", $arEvent["EVENT_FORMATTED"])
								&& $arEvent["EVENT_FORMATTED"]["IS_IMPORTANT"]
							)
							{
								$news_item_style = ($arParams["LOG_ID"] > 0 && $_REQUEST["show_full"] == "Y" ? "lenta-info-block-wrapp-full" : "lenta-info-block-wrapp");

								?><div class="<?=$news_item_style?>"<?=$strOnClick?> id="post_block_check_cont_<?=$arEvent["EVENT"]["ID"]?>"><?
									?><div class="lenta-info-block <?=(in_array($arEvent["EVENT"]["EVENT_ID"], array("intranet_new_user", "bitrix24_new_user")) ? "lenta-block-new-employee" : "info-block-important")?>"><?
										if (in_array($arEvent["EVENT"]["EVENT_ID"], array("intranet_new_user", "bitrix24_new_user")))
										{
											echo CSocNetTextParser::closetags(htmlspecialcharsback($arEvent["EVENT_FORMATTED"]["MESSAGE"]));
										}
										else
										{
											if (
												array_key_exists("IS_IMPORTANT", $arEvent["EVENT_FORMATTED"])
												&& $arEvent["EVENT_FORMATTED"]["IS_IMPORTANT"]
												&& array_key_exists("TITLE_24_2", $arEvent["EVENT_FORMATTED"])
												&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24_2"]) > 0
											)
											{
													?><div class="lenta-important-block-title"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></div><?
											}

											?><div class="lenta-important-block-text"><?=CSocNetTextParser::closetags(htmlspecialcharsback($arEvent["EVENT_FORMATTED"]["MESSAGE"]))?><i></i></div><?
										}
									?></div><?

									echo $post_more_block;

								?></div><?
							}
							elseif (in_array($arEvent["EVENT"]["EVENT_ID"], array("files", "commondocs")))
							{
								?><div class="post-item-post-block-full"<?=$strOnClick?>>
									<div class="post-item-attached-file-wrap">
										<div class="post-item-attached-file"><span><?=$arEvent["EVENT"]["TITLE"]?></span></div>
									</div><?
								?></div><?
							}
							elseif (in_array($arEvent["EVENT"]["EVENT_ID"], array("tasks")))
							{
								?><div class="lenta-info-block-wrapp"<?=$strOnClick?>><?=CSocNetTextParser::closetags(htmlspecialcharsback($arEvent["EVENT_FORMATTED"]["MESSAGE"]))?></div><?
							}
							elseif (in_array($arEvent["EVENT"]["EVENT_ID"], array("timeman_entry", "report")))
							{
								?><div class="lenta-info-block-wrapp"<?=$strOnClick?>><?=CSocNetTextParser::closetags(htmlspecialcharsback($arEvent["EVENT_FORMATTED"]["MESSAGE"]))?></div><?
							}
							elseif (!in_array($arEvent["EVENT"]["EVENT_ID"], array("system", "system_groups", "system_friends")) && strlen($arEvent["EVENT_FORMATTED"]["MESSAGE"]) > 0) // all other events
							{
								?><div class="<?=$post_item_style?>"<?=$strOnClick?> id="post_block_check_cont_<?=$arEvent["EVENT"]["ID"]?>"><?
									if (
										array_key_exists("TITLE_24_2", $arEvent["EVENT_FORMATTED"])
										&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24_2"]) > 0
									)
									{
										?><div class="post-text-title" id="post_text_title_<?=$arEvent["EVENT"]["ID"]?>"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></div><?
									}
									?><div class="post-item-text" id="post_block_check_<?=$arEvent["EVENT"]["ID"]?>"><?=CSocNetTextParser::closetags(htmlspecialcharsback($arEvent["EVENT_FORMATTED"]["MESSAGE"]))?></div><?

									echo $post_more_block;

								?></div><?
							}
						}

						echo $post_more_corner;

					?></div><? // post-item-top-wrap

					if (
						strlen($strBottomBlock) > 0
						&& !in_array($arEvent["EVENT"]["EVENT_ID"], array("system", "system_group", "system_friends", "photo"))
					)
					{
						?><div <?=($bRatingExtended ? 'id="post_item_inform_wrap"' : '')?> id="post_inform_wrap_<?=$arEvent["EVENT"]["ID"]?>" class="post-item-inform-wrap<?=($bRatingExtendedOpen ? " post-item-inform-action" : "")?>"><?=$strBottomBlock;?></div><?
					}

				?></div><? // post-wrap / lenta-item
				
				?><script type="text/javascript">
				arBlockToCheck[arBlockToCheck.length] = {
					lenta_item_id: 'lenta_item_<?=$arEvent["EVENT"]["ID"]?>',
					text_block_id: 'post_block_check_cont_<?=$arEvent["EVENT"]["ID"]?>',
					title_block_id: 'post_block_check_title_<?=$arEvent["EVENT"]["ID"]?>',
					more_block_id: 'post_block_check_more_<?=$arEvent["EVENT"]["ID"]?>',
					more_overlay_id: 'post_more_block_<?=$arEvent["EVENT"]["ID"]?>',
					more_corner_id: 'post_more_corner_<?=$arEvent["EVENT"]["ID"]?>',
					post_inform_wrap_id: 'post_inform_wrap_<?=$arEvent["EVENT"]["ID"]?>'
				};
				</script>
				<?

				if (
					$arParams["LOG_ID"] > 0
					&& array_key_exists("HAS_COMMENTS", $arEvent)
					&& $arEvent["HAS_COMMENTS"] == "Y"
				)
				{
					if(in_array($arEvent["EVENT"]["EVENT_ID"], Array("blog_post", "blog_post_micro", "blog_comment", "blog_comment_micro")))
					{
						$APPLICATION->IncludeComponent(
							"bitrix:socialnetwork.blog.post.comment",
							".default",
							Array(
								"PATH_TO_BLOG" => $arParams["PATH_TO_USER_BLOG"],
								"PATH_TO_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
								"PATH_TO_POST_MOBILE" => $APPLICATION->GetCurPageParam("", array("LAST_LOG_TS")),
								"PATH_TO_USER" => $arParams["PATH_TO_USER"],
								"PATH_TO_SMILE" => $arParams["PATH_TO_BLOG_SMILE"],
								"PATH_TO_MESSAGES_CHAT" => $arResult["PATH_TO_MESSAGES_CHAT"],
								"ID" => $arEvent["EVENT"]["SOURCE_ID"],
								"LOG_ID" => $arEvent["EVENT"]["ID"],
								"CACHE_TIME" => $arParams["CACHE_TIME"],
								"CACHE_TYPE" => $arParams["CACHE_TYPE"],
								"COMMENTS_COUNT" => "5",
								"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
								"USER_ID" => $GLOBALS["USER"]->GetID(),
//								"GROUP_ID" => $arParams["BLOG_GROUP_ID"],
								"SONET_GROUP_ID" => $arParams["GROUP_ID"],
								"NOT_USE_COMMENT_TITLE" => "Y",
								"USE_SOCNET" => "Y",
								"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
								"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
								"SHOW_YEAR" => $arParams["SHOW_YEAR"],
								"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
								"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
								"SHOW_RATING" => $arParams["SHOW_RATING"],
								"RATING_TYPE" => $arParams["RATING_TYPE"],
								"IMAGE_MAX_WIDTH" => $arParams["IMAGE_MAX_WIDTH"],
								"IMAGE_MAX_HEIGHT" => $arParams["IMAGE_MAX_HEIGHT"],
								"ALLOW_VIDEO"  => $arParams["BLOG_COMMENT_ALLOW_VIDEO"],
								"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"],
								"ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"],
								"AJAX_POST" => "Y",
								"POST_DATA" => $arBlogPostResult["POST_DATA"],
								"BLOG_DATA" => $arBlogPostResult["BLOG_DATA"],
								"FROM_LOG" => false,
								"bFromList" => false,
								"LAST_LOG_TS" => $arResult["LAST_LOG_TS"] + $arResult["TZ_OFFSET"],
								"AVATAR_SIZE" => $arParams["AVATAR_SIZE_COMMENT"],
								"MOBILE" => "Y",
								"ATTACHED_IMAGE_MAX_WIDTH_FULL" => 640,
								"ATTACHED_IMAGE_MAX_HEIGHT_FULL" => 832
							),
							$component,
							array("HIDE_ICONS" => "Y")
						);
					}
					else
					{
						?><div class="post-comments-wrap" id="post-comments-wrap"><?
							if (is_array($arEvent["COMMENTS"]))
							{
								foreach($arEvent["COMMENTS"] as $arComment)
								{
									if (
										!$bMoreShown
										&& count($arEvent["COMMENTS"]) > 0
										&& intval($arEvent["COMMENTS_COUNT"]) > count($arEvent["COMMENTS"])
									)
									{
										$bMoreShown = true;
										?><div id="post-comment-more" class="post-comments-button" ontouchstart="BX.toggleClass(this, 'post-comments-button-press');" ontouchend="BX.toggleClass(this, 'post-comments-button-press');"><?=str_replace("#COMMENTS#", $arEvent["COMMENTS_COUNT"], GetMessage("MOBILE_LOG_COMMENT_BUTTON_MORE"))?></div>
										<script>
										BX.bind(BX('post-comment-more'), 'click', function(e)
										{
											var moreButton = BX('post-comment-more');
											if (moreButton)
												BX.addClass(moreButton, 'post-comments-button-waiter');

											var get_data = {
												'sessid': '<?=bitrix_sessid()?>',
												'site': '<?=CUtil::JSEscape(SITE_ID)?>',
												'lang': '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
												'logid': <?=$arParams["LOG_ID"]?>,
												'last_comment_id': <?=intval($arComment["EVENT"]["ID"])?>,
												'as': <?=intval($arParams["AVATAR_SIZE_COMMENT"])?>,
												'nt': '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
												'sl': '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>',
												'dtf': '<?=CUtil::JSEscape($arParams["DATE_TIME_FORMAT"])?>',
												'p_user': '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
												'action': 'get_comments'
											};

											BMAjaxWrapper.Wrap({
												'type': 'json',
												'method': 'POST',
												'url': '/bitrix/components/bitrix/mobile.socialnetwork.log/ajax.php',
												'data': get_data,
												'callback': function(get_response_data)
												{
													if (moreButton)
														BX.removeClass(moreButton, 'post-comments-button-waiter');
													if (get_response_data["arComments"] != 'undefined')
													{
														__MSLShowComments(get_response_data["arComments"]);
													}
												},
												'callback_failure': function() {
													if (moreButton)
														BX.removeClass(moreButton, 'post-comments-button-waiter');
												}
											});
										});
										</script>
										<div id="post-comment-hidden" style="display:none; overflow:hidden;"></div><?
									}

									$strCreatedBy = "";
									if (
										array_key_exists("CREATED_BY", $arComment)
										&& is_array($arComment["CREATED_BY"])
										&& array_key_exists("FORMATTED", $arComment["CREATED_BY"])
										&& strlen($arComment["CREATED_BY"]["FORMATTED"]) > 0
									)
										$strCreatedBy = $arComment["CREATED_BY"]["FORMATTED"];

									$bUnread = (
										($arResult["COUNTER_TYPE"] == "**")
										&& $arComment["EVENT"]["USER_ID"] != $GLOBALS["USER"]->GetID()
										&& intval($arResult["LAST_LOG_TS"]) > 0
										&& (MakeTimeStamp($arComment["EVENT"]["LOG_DATE"]) - intval($arResult["TZ_OFFSET"])) > $arResult["LAST_LOG_TS"]
									);
									?><div class="post-comment-block<?=($bUnread ? " post-comment-new" : "")?>">
										<div class="avatar"<?=(strlen($arComment["AVATAR_SRC"]) > 0 ? " style=\"background:url('".$arComment["AVATAR_SRC"]."') no-repeat; background-size: 29px 29px;\"" : "")?>></div>
										<div class="post-comment-cont"><?
											if (strlen($arComment["CREATED_BY"]["URL"]) > 0)
											{
												?><a href="<?=$arComment["CREATED_BY"]["URL"]?>" class="post-comment-author"><?=$strCreatedBy?></a><?
											}
											else
											{
												?><div class="post-comment-author"><?=$strCreatedBy?></div><?
											}
											?><div class="post-comment-text"><?
												$message = (array_key_exists("EVENT_FORMATTED", $arComment) && array_key_exists("MESSAGE", $arComment["EVENT_FORMATTED"]) ? $arComment["EVENT_FORMATTED"]["MESSAGE"] : $arComment["EVENT"]["MESSAGE"]);
												if (strlen($message) > 0)
													echo CSocNetTextParser::closetags(htmlspecialcharsback($message));
											?></div>
											<div class="post-comment-time"><?
												echo (
													array_key_exists("EVENT_FORMATTED", $arComment)
													&& array_key_exists("DATETIME", $arComment["EVENT_FORMATTED"])
													&& strlen($arComment["EVENT_FORMATTED"]["DATETIME"]) > 0
														? $arComment["EVENT_FORMATTED"]["DATETIME"]
														: ($arComment["LOG_DATE_DAY"] == ConvertTimeStamp() ? $arComment["LOG_TIME_FORMAT"] : $arComment["LOG_DATE_DAY"]." ".$arComment["LOG_TIME_FORMAT"])
												);
											?></div><?
											$strBottomBlockComments = "";

											ob_start();

											if (
												strlen($arComment["EVENT"]["RATING_TYPE_ID"]) > 0
												&& $arComment["EVENT"]["RATING_ENTITY_ID"] > 0
												&& $arParams["SHOW_RATING"] == "Y"
											)
											{
												$APPLICATION->IncludeComponent(
													//"bitrix:rating.vote", "mobile_comment_".$arParams["RATING_TYPE"],
													"bitrix:rating.vote", "mobile_comment_like",
													Array(
														"ENTITY_TYPE_ID" => $arComment["EVENT"]["RATING_TYPE_ID"],
														"ENTITY_ID" => $arComment["EVENT"]["RATING_ENTITY_ID"],
														"OWNER_ID" => $arComment["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"],
														"USER_VOTE" => $arComment["EVENT"]["RATING_USER_VOTE_VALUE"],
														"USER_HAS_VOTED" => $arComment["EVENT"]["RATING_USER_VOTE_VALUE"] == 0? 'N': 'Y',
														"TOTAL_VOTES" => $arComment["EVENT"]["RATING_TOTAL_VOTES"],
														"TOTAL_POSITIVE_VOTES" => $arComment["EVENT"]["RATING_TOTAL_POSITIVE_VOTES"],
														"TOTAL_NEGATIVE_VOTES" => $arComment["EVENT"]["RATING_TOTAL_NEGATIVE_VOTES"],
														"TOTAL_VALUE" => $arComment["EVENT"]["RATING_TOTAL_VALUE"],
														"PATH_TO_USER_PROFILE" => $arComment["CREATED_BY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]
													),
													$component,
													array("HIDE_ICONS" => "Y")
												);
											}

											$strBottomBlockComments = ob_get_contents();
											ob_end_clean();

											if (strlen($strBottomBlockComments) > 0)
											{
												?><?=$strBottomBlockComments;?><? // comments rating
											}

										?></div>
									</div><?
								}
							}
							?><span id="post-comment-last-after"></span>
						</div><? // post-comments-wrap

						if ($arParams["LOG_ID"] > 0)
						{
							?></div><? // post-card-wrap
						}

						if (
							array_key_exists("CAN_ADD_COMMENTS", $arEvent)
							&& $arEvent["CAN_ADD_COMMENTS"] == "Y"
						)
						{
							?><form class="send-message-block" id="comment_send_form">
								<input type="hidden" id="comment_send_form_logid" name="sonet_log_comment_logid" value="<?=$arParams["LOG_ID"]?>">
								<textarea id="comment_send_form_comment" class="send-message-input" placeholder="<?=GetMessage("MOBILE_LOG_COMMENT_ADD_TITLE")?>"></textarea>
								<input type="button" id="comment_send_button" class="send-message-button" value="<?=GetMessage("MOBILE_LOG_COMMENT_ADD_BUTTON_SEND")?>" ontouchstart="BX.toggleClass(this, 'send-message-button-press');" ontouchend="BX.toggleClass(this, 'send-message-button-press');">
							</form>
							<script>

							document.addEventListener("DOMContentLoaded", function() {
								BitrixMobile.Utils.autoResizeForm(
										document.getElementById("comment_send_form_comment"),
										document.getElementById("post-card-wrap")
								);
							}, false);

							BX.bind(BX('comment_send_button'), 'click', function(e)
							{
								if (BX('comment_send_form_comment').value.length > 0)
								{
									__MSLDisableSubmitButton(true);

									var post_data = {
										'sessid': '<?=bitrix_sessid()?>',
										'site': '<?=CUtil::JSEscape(SITE_ID)?>',
										'lang': '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
										'log_id': BX('comment_send_form_logid').value,
										'message': BX('comment_send_form_comment').value,
										'action': 'add_comment'
									};

									BMAjaxWrapper.Wrap({
										'type': 'json',
										'method': 'POST',
										'url': '/bitrix/components/bitrix/mobile.socialnetwork.log/ajax.php',
										'data': post_data,
										'callback': function(post_response_data)
										{
											if (post_response_data["commentID"] != 'undefined' && parseInt(post_response_data["commentID"]) > 0)
											{
												var commentID = post_response_data["commentID"];
												get_data = {
													'sessid': '<?=bitrix_sessid()?>',
													'site': '<?=CUtil::JSEscape(SITE_ID)?>',
													'lang': '<?=CUtil::JSEscape(LANGUAGE_ID)?>',
													'cid': commentID,
													'as': <?=intval($arParams["AVATAR_SIZE_COMMENT"])?>,
													'nt': '<?=CUtil::JSEscape($arParams["NAME_TEMPLATE"])?>',
													'sl': '<?=CUtil::JSEscape($arParams["SHOW_LOGIN"])?>',
													'dtf': '<?=CUtil::JSEscape($arParams["DATE_TIME_FORMAT"])?>',
													'p_user': '<?=CUtil::JSEscape($arParams["PATH_TO_USER"])?>',
													'action': 'get_comment'
												};

												BMAjaxWrapper.Wrap({
													'type': 'json',
													'method': 'POST',
													'url': '/bitrix/components/bitrix/mobile.socialnetwork.log/ajax.php',
													'data': get_data,
													'callback': function(get_response_data)
													{
														__MSLDisableSubmitButton(false);
														if (get_response_data["arCommentFormatted"] != 'undefined')
															__MSLShowNewComment(get_response_data["arCommentFormatted"]);
														BitrixMobile.Utils.resetAutoResize(BX("comment_send_form_comment"), BX("post-card-wrap"));

														var followBlock = BX('log_entry_follow_' + post_data.log_id, true);
														if (followBlock)
														{
															var strFollowOld = (followBlock.getAttribute("data-follow") == "Y" ? "Y" : "N");
															if (strFollowOld == "N")
															{
																BX.removeClass(followBlock, 'post-item-follow');
																BX.addClass(followBlock, 'post-item-follow-active');
																followBlock.setAttribute("data-follow", "Y");
															}
														}
													},
													'callback_failure': function() { __MSLDisableSubmitButton(false); }
												});
											}
											else
											{
												__MSLDisableSubmitButton(false);
											}
										},
										'callback_failure': function() { __MSLDisableSubmitButton(false); }
									});
								}
							});
							</script><?
						}
					}
				}
			}
		} // foreach ($arResult["EventsNew"] as $arEvent)
	} // if ($arResult["EventsNew"] && is_array($arResult["EventsNew"]) && count($arResult["EventsNew"]) > 0)
	elseif
	(
		$arParams["LOG_ID"] <= 0
		&& !$arResult["AJAX_CALL"]
	)
	{
		?><div class="lenta-block-empty" id="lenta_block_empty"><?=GetMessage("MOBILE_LOG_MESSAGE_EMPTY");?></div><?
	}

	if($arResult["AJAX_CALL"])
	{
		$strParams = "LAST_LOG_TS=".$arResult["LAST_LOG_TS"]."&AJAX_CALL=Y&PAGEN_".$arResult["PAGE_NAVNUM"]."=".($arResult["PAGE_NUMBER"] + 1);

		?><script type="text/javascript">
			<?
			if (
				$event_cnt > 0
				&& $event_cnt >= $arParams["PAGE_SIZE"]
			)
			{
				?>
				url_next = '<?=$APPLICATION->GetCurPageParam($strParams, array("LAST_LOG_TS", "AJAX_CALL", "PAGEN_".$arResult["PAGE_NAVNUM"]));?>';
				<?
			}
			else
			{
				?>
				__MSLScrollInit(false, true);
				<?
			}
			?>
		</script><?
		die();
	}

	if ($arParams["LOG_ID"] <= 0)
	{
		if ($event_cnt >= $arParams["PAGE_SIZE"])
		{
			?><div id="next_post_more" class="next-post-more"></div><?
		}
		?></div><? // lenta-wrapper
	}

	$strParams = "LAST_LOG_TS=".$arResult["LAST_LOG_TS"]."&AJAX_CALL=Y&PAGEN_".$arResult["PAGE_NAVNUM"]."=".($arResult["PAGE_NUMBER"] + 1);

	// sonet_log_content
	?><script type="text/javascript">
		var maxScroll = 0;
		var isPullDownEnabled = false;
		var url_next = '<?=$APPLICATION->GetCurPageParam($strParams, array("LAST_LOG_TS", "AJAX_CALL", "PAGEN_".$arResult["PAGE_NAVNUM"]));?>';
		BX.ready(function() {
			app.pullDownLoadingStop();

			<?
			if (
				$arParams["LOG_ID"] <= 0
				&& !$arResult["AJAX_CALL"]
			)
			{
				?>
				var windowSize = BX.GetWindowSize();
				maxScroll = windowSize.scrollHeight - windowSize.innerHeight - 190;
				__MSLScrollInit(true);

				BX.bind(document, "offline", function(){
					app.pullDownLoadingStop();
					__MSLScrollInit(false, true);
				});

				BX.bind(document, "online", function(){
					__MSLPullDownInit(true);
					__MSLScrollInit(true, true);
				});

				BX.addCustomEvent("UIApplicationDidBecomeActiveNotification", function(params) {
					var networkState = navigator.network.connection.type;

					if (networkState == Connection.UNKNOWN || networkState == Connection.NONE)
					{
						app.pullDownLoadingStop();
						__MSLScrollInit(false, true);
					}
					else
					{
						__MSLPullDownInit(true);
						__MSLScrollInit(true, true);
					}
				});

				BX.addCustomEvent("onUpdateSocnetCounters", function(params) {
					if (parseInt(params["<?=$arResult["COUNTER_TYPE"]?>"]) > 0)
						__MSLShowNotifier(params["<?=$arResult["COUNTER_TYPE"]?>"]);
					else
						__MSLHideNotifier();
				});
				<?
			}
			?>
			setTimeout(function() { __MSLCheckNodesHeight(); }, 1000);
			<?
			if ($arParams["LOG_ID"] <= 0)
			{
				?>
				__MSLPullDownInit(true);
				<?
			}
			?>
		});
		<?
		if (
			$arParams["LOG_ID"] > 0
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
}
?>