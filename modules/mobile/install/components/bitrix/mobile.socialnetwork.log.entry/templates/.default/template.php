<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

if (strlen($arResult["FatalError"]) > 0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?
}
else
{
	if (strlen($arResult["ErrorMessage"]) > 0)
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?
	}

	if (
		$arResult["Event"]
		&& is_array($arResult["Event"])
		&& !empty($arResult["Event"])
	)
	{
		$arEvent = $arResult["Event"];
		$bUnread = $arParams["EVENT"]["IS_UNREAD"];

		$strTopic = "";
		if (
			isset($arEvent["EVENT_FORMATTED"]["DESTINATION"])
			&& is_array($arEvent["EVENT_FORMATTED"]["DESTINATION"])
			&& count($arEvent["EVENT_FORMATTED"]["DESTINATION"]) > 0
		)
		{
			if (
				array_key_exists("TITLE_24", $arEvent["EVENT_FORMATTED"])
				&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24"]) > 0
			)
			{
				$strTopic .= '<div class="post-item-top-text post-item-top-arrow'.(strlen($arEvent["EVENT_FORMATTED"]["STYLE"]) > 0 ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE_24"].' </div>';
			}

			$i = 0;
			foreach($arEvent["EVENT_FORMATTED"]["DESTINATION"] as $arDestination)
			{
				$strTopic .= ($i > 0 ? ', ' : ' ');

				if (!empty($arDestination["CRM_PREFIX"]))
				{
					$strTopic .= ' <span class="post-item-dest-crm-prefix">'.$arDestination["CRM_PREFIX"].':&nbsp;</span>';
				}

				if (strlen($arDestination["URL"]) > 0)
				{
					$strTopic .= '<a href="'.$arDestination["URL"].'" class="post-item-destination'.(strlen($arDestination["STYLE"]) > 0 ? ' post-item-dest-'.$arDestination["STYLE"] : '').'">'.$arDestination["TITLE"].'</a>';
				}
				else
				{
					$strTopic .= '<span class="post-item-destination'.(strlen($arDestination["STYLE"]) > 0 ? ' post-item-dest-'.$arDestination["STYLE"] : '').'">'.$arDestination["TITLE"].'</span>';
				}

				$i++;
			}

			if (intval($arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"]) > 0)
			{
				$more_cnt = intval($arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"]);
				$suffix = (
					($more_cnt % 100) > 10
					&& ($more_cnt % 100) < 20
						? 5
						: $more_cnt % 10
				);

				$moreClick = " onclick=\"__MSLGetHiddenDestinations(".$arEvent["EVENT"]["ID"].", ".$arEvent["EVENT"]["USER_ID"].", this);\"";
				$strTopic .= "<span class=\"post-destination-more\"".$moreClick." ontouchstart=\"BX.toggleClass(this, 'post-destination-more-touch');\" ontouchend=\"BX.toggleClass(this, 'post-destination-more-touch');\">&nbsp;".GetMessage("MOBILE_LOG_DESTINATION_MORE_".$suffix, array("#COUNT#" => $arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"]))."</span>";
			}
		}
		else
		{
			$strTopic .= (
				array_key_exists("TITLE_24", $arEvent["EVENT_FORMATTED"])
				&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24"]) > 0
					? '<div class="post-item-top-text'.(strlen($arEvent["EVENT_FORMATTED"]["STYLE"]) > 0 ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE_24"].'</div>'
					: '<div class="post-item-top-text'.(strlen($arEvent["EVENT_FORMATTED"]["STYLE"]) > 0 ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE"].'</div>'
			);
		}

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
			{
				$strCreatedBy .= '<a class="post-item-top-title" href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"], $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"], ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</a>';
			}
			elseif (
				array_key_exists("FORMATTED", $arEvent["CREATED_BY"])
				&& strlen($arEvent["CREATED_BY"]["FORMATTED"]) > 0
			)
			{
				$strCreatedBy .= '<div class="post-item-top-title">'.$arEvent["CREATED_BY"]["FORMATTED"].'</div>';
			}
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
			{
				$strCreatedBy .= '<a class="post-item-top-title" href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arEvent["ENTITY"]["TOOLTIP_FIELDS"]["ID"], $arEvent["ENTITY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arEvent["ENTITY"]["TOOLTIP_FIELDS"], ($arParams["SHOW_LOGIN"] != "N" ? true : false)).'</a>';
			}
			elseif (
				array_key_exists("FORMATTED", $arEvent["ENTITY"])
				&& array_key_exists("NAME", $arEvent["ENTITY"]["FORMATTED"])
			)
			{
				$strCreatedBy .= '<div class="post-item-top-title">'.$arEvent["ENTITY"]["FORMATTED"]["NAME"].'</div>';
			}
		}

		$strDescription = "";
		if (
			array_key_exists("DESCRIPTION", $arEvent["EVENT_FORMATTED"])
			&& (
				(!is_array($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) && strlen($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) > 0)
				|| (is_array($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) && count($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) > 0)
			)
		)
		{
			$strDescription = '<div class="post-item-description'.(strlen($arEvent["EVENT_FORMATTED"]["DESCRIPTION_STYLE"]) > 0 ? ' post-item-description-'.$arEvent["EVENT_FORMATTED"]["DESCRIPTION_STYLE"].'"' : '').'">'.(is_array($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) ? '<span>'.implode('</span> <span>', $arEvent["EVENT_FORMATTED"]["DESCRIPTION"]).'</span>' : $arEvent["EVENT_FORMATTED"]["DESCRIPTION"]).'</div>';
		}

		if ($arParams["IS_LIST"])
		{
			?><script type="text/javascript">
				arLogTs.entry_<?=intval($arEvent["EVENT"]["ID"])?> = <?=intval($arResult["LAST_LOG_TS"])?>;
			</script><?
		}

		if ($arParams["IS_LIST"])
		{
			$arOnClickParams = array(
				"path" => $arParams["PATH_TO_LOG_ENTRY_EMPTY"],
				"log_id" => intval($arEvent["EVENT"]["ID"]),
				"entry_type" => "non-blog",
				"use_follow" => ($arParams["USE_FOLLOW"] == 'N' ? 'N' : 'Y'),
				"use_tasks" => ($arResult["bTasksAvailable"] && $arResult["canGetPostContent"] ? 'Y' : 'N'),
				"post_content_type_id" => (!empty($arResult["POST_CONTENT_TYPE_ID"]) ? $arResult["POST_CONTENT_TYPE_ID"] : ''),
				"post_content_id" => (!empty($arResult["POST_CONTENT_ID"]) ? $arResult["POST_CONTENT_ID"] : 0),
				"site_id" => SITE_ID,
				"language_id" => LANGUAGE_ID,
				"datetime_format" => $arParams["DATE_TIME_FORMAT"],
				"entity_xml_id" => $arEvent["COMMENTS_PARAMS"]["ENTITY_XML_ID"],
				"focus_form" => false,
				"focus_comments" => false,
				"show_full" => in_array($arEvent["EVENT"]["EVENT_ID"], array("timeman_entry", "report", "calendar"))
			);

			$taskId = false;

			if (
				isset($arEvent["EVENT"])
				&& isset($arEvent["EVENT"]["MODULE_ID"])
				&& ($arEvent["EVENT"]["MODULE_ID"] === "tasks")
				&& isset($arEvent["EVENT"]["EVENT_ID"])
				&& ($arEvent["EVENT"]["EVENT_ID"] === "tasks")
				&& isset($arEvent["EVENT"]["SOURCE_ID"])
				&& ($arEvent["EVENT"]["SOURCE_ID"] > 0)
			)
			{
				$taskId = (int)$arEvent["EVENT"]["SOURCE_ID"];
			}
			elseif (
				isset($arEvent["EVENT"])
				&& isset($arEvent["EVENT"]["MODULE_ID"])
				&& ($arEvent["EVENT"]["MODULE_ID"] === "crm_shared")
				&& isset($arEvent["EVENT"]["EVENT_ID"])
				&& ($arEvent["EVENT"]["EVENT_ID"] === "crm_activity_add")
				&& isset($arEvent["EVENT"]["ENTITY_ID"])
				&& ($arEvent["EVENT"]["ENTITY_ID"] > 0)
				&& isset($arParams["CRM_ACTIVITY2TASK"])
				&& isset($arParams["CRM_ACTIVITY2TASK"][$arEvent["EVENT"]["ENTITY_ID"]])
			)
			{
				$taskId = (int)$arParams["CRM_ACTIVITY2TASK"][$arEvent["EVENT"]["ENTITY_ID"]];
			}

			if ($taskId)
			{
				$strTaskPath = str_replace(
					array("__ROUTE_PAGE__", "#USER_ID#"),
					array("view", (int) $GLOBALS["USER"]->GetID()),
					$arParams["PATH_TO_TASKS_SNM_ROUTER"]
					."&TASK_ID=".$taskId
				);
				$arOnClickParams["path"] = $strTaskPath;
				$strOnClickParams = CUtil::PhpToJSObject($arOnClickParams);
				$strOnClick = " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParams.", event);\"";
			}
			elseif (
				isset($arEvent["EVENT"])
				&& isset($arEvent["EVENT"]["EVENT_ID"])
				&& ($arEvent["EVENT"]["EVENT_ID"] === "calendar")
				&& isset($arEvent["EVENT"]["SOURCE_ID"])
				&& ($arEvent["EVENT"]["SOURCE_ID"] > 0)
			)
			{
				$strEventPath = "/mobile/calendar/view_event.php?event_id=".intval($arEvent["EVENT"]["SOURCE_ID"]);
				$arOnClickParams["pathComments"] = $arOnClickParams["path"];
				$arOnClickParams["path"] = $strEventPath;
				$strOnClickParams = CUtil::PhpToJSObject($arOnClickParams);
				$strOnClick = " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParams.", event);\"";
			}
			else
			{
				$strOnClickParams = CUtil::PhpToJSObject($arOnClickParams);
				$strOnClick = " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParams.", event);\"";
			}
		}
		else
		{
			$strOnClick = "";
		}

		$timestamp = $arEvent["LOG_DATE_TS"];

		$arFormat = Array(
			"tommorow" => "tommorow, ".GetMessage("MOBILE_LOG_COMMENT_FORMAT_TIME"),
			"today" => "today, ".GetMessage("MOBILE_LOG_COMMENT_FORMAT_TIME"),
			"yesterday" => "yesterday, ".GetMessage("MOBILE_LOG_COMMENT_FORMAT_TIME"),
			"" => (date("Y", $timestamp) == date("Y") ? GetMessage("MOBILE_LOG_COMMENT_FORMAT_DATE") : GetMessage("MOBILE_LOG_COMMENT_FORMAT_DATE_YEAR"))
		);
		$datetime_detail = FormatDate($arFormat, $timestamp);

		if (
			array_key_exists("EVENT_FORMATTED", $arEvent)
			&& array_key_exists("DATETIME_FORMATTED", $arEvent["EVENT_FORMATTED"])
			&& strlen($arEvent["EVENT_FORMATTED"]["DATETIME_FORMATTED"]) > 0
		)
		{
			$datetime_list = $arEvent["EVENT_FORMATTED"]["DATETIME_FORMATTED"];
		}
		elseif (
			array_key_exists("DATETIME_FORMATTED", $arEvent)
			&& strlen($arEvent["DATETIME_FORMATTED"]) > 0
		)
		{
			$datetime_list = $arEvent["DATETIME_FORMATTED"];
		}
		elseif ($arEvent["LOG_DATE_DAY"] == ConvertTimeStamp())
		{
			$datetime_list = $arEvent["LOG_TIME_FORMAT"];
		}
		else
		{
			$datetime_list = $arEvent["LOG_DATE_DAY"]." ".$arEvent["LOG_TIME_FORMAT"];
		}

		$bHasNoCommentsOrLikes = (
			(
				!array_key_exists("HAS_COMMENTS", $arEvent)
				|| $arEvent["HAS_COMMENTS"] != "Y"
			)
			&& (
				$arParams["SHOW_RATING"] != "Y"
				|| strlen($arEvent["RATING_TYPE_ID"]) <= 0
				|| intval($arEvent["RATING_ENTITY_ID"]) <= 0
			)
		);

		$item_class = (!$arParams["IS_LIST"] ? "post-wrap" : "lenta-item".($bUnread ? " lenta-item-new" : "")).($bHasNoCommentsOrLikes ? " post-without-informers" : "");

		?><div class="<?=($item_class)?>" id="lenta_item_<?=$arEvent["EVENT"]["ID"]?>"><?
			?><div 
				id="post_item_top_wrap_<?=$arEvent["EVENT"]["ID"]?>"
				class="post-item-top-wrap<?=($arParams["FOLLOW_DEFAULT"] == "N" && $arEvent["EVENT"]["FOLLOW"] == "Y" ? " post-item-follow" : "")?> post-item-copyable"
			><?
				?><div class="post-item-top" id="post_item_top_<?=$arEvent["EVENT"]["ID"]?>"><?
					?><div class="avatar<?=(strlen($arEvent["EVENT_FORMATTED"]["AVATAR_STYLE"]) > 0 ? " ".$arEvent["EVENT_FORMATTED"]["AVATAR_STYLE"] : "")?>"<?=(strlen($arEvent["AVATAR_SRC"]) > 0 ? " style=\"background-image:url('".$arEvent["AVATAR_SRC"]."')\"" : "")?>></div><?
					?><div class="post-item-top-cont"><?
						?><?=$strCreatedBy?><?
						?><div class="post-item-top-topic"><?=$strTopic ?></div><?
						?><div class="lenta-item-time" id="datetime_block_detail_<?=$arEvent["EVENT"]["ID"]?>" ><?=$datetime_detail?></div><?
					?></div><?

					?><div class="lenta-item-right-corner"><?
						if (
							!isset($arParams["USE_FAVORITES"])
							|| $arParams["USE_FAVORITES"] != "N"
						)
						{
							$bFavorites = (array_key_exists("FAVORITES", $arParams["EVENT"]) && $arParams["EVENT"]["FAVORITES"] == "Y");
							?><div id="log_entry_favorites_<?=$arEvent["EVENT"]["ID"]?>" data-favorites="<?=($bFavorites ? "Y" : "N")?>"  class="lenta-item-fav<?=($bFavorites ? " lenta-item-fav-active" : "")?>" onclick="__MSLSetFavorites(<?=$arEvent["EVENT"]["ID"]?>, this, '<?=($bFavorites ? "N" : "Y")?>'); return BX.PreventDefault(this);"></div><?
						}
						else
						{
							?><div class="lenta-item-fav-placeholder"></div><?
						}
					?></div><?

					if (strlen($strDescription) > 0)
					{
						echo $strDescription;
					}
				?></div><?

				ob_start();

				if (
					strlen($arEvent["EVENT"]["RATING_TYPE_ID"]) > 0
					&& $arEvent["EVENT"]["RATING_ENTITY_ID"] > 0
					&& $arParams["SHOW_RATING"] == "Y"
				)
				{
					$voteId = $arEvent["EVENT"]["RATING_TYPE_ID"].'_'.$arEvent["EVENT"]["RATING_ENTITY_ID"].'-'.(time()+rand(0, 1000));
					$emotion = (!empty($arEvent["RATING"]["USER_REACTION"]) ? strtoupper($arEvent["RATING"]["USER_REACTION"]) : 'LIKE');

					?><span class="post-item-informers bx-ilike-block" id="rating_block_<?=$arEvent["EVENT"]["ID"]?>" data-counter="<?=intval($arEvent["RATING"]["TOTAL_VOTES"])?>"><?
						?><span data-rating-vote-id="<?=htmlspecialcharsbx($voteId)?>" id="bx-ilike-button-<?=htmlspecialcharsbx($voteId)?>" class="post-item-informer-like feed-inform-ilike"><?
						?><span class="bx-ilike-left-wrap<?=(isset($arEvent["RATING"]["USER_HAS_VOTED"]) && $arEvent["RATING"]["USER_HAS_VOTED"] == "Y" ? ' bx-you-like-button' : '')?>"><span class="bx-ilike-text"><?=\CRatingsComponentsMain::getRatingLikeMessage($emotion)?></span></span><?
						?></span><?
					?></span><?
				}

				if (
					array_key_exists("HAS_COMMENTS", $arEvent)
					&& $arEvent["HAS_COMMENTS"] == "Y"
				)
				{
					$bHasComments = true;

					$arOnClickParamsCommentsTop = $arOnClickParams;
					if (!empty($arOnClickParamsCommentsTop['pathComments']))
					{
						$arOnClickParamsCommentsTop['path'] = $arOnClickParamsCommentsTop['pathComments'];
						unset($arOnClickParamsCommentsTop['pathComments']);
					}
					$arOnClickParamsCommentsTop["focus_comments"] = true;
					$arOnClickParamsCommentsTop["show_full"] = in_array($arEvent["EVENT"]["EVENT_ID"], array("timeman_entry", "report", "calendar"));
					$strOnClickParamsCommentsTop = CUtil::PhpToJSObject($arOnClickParamsCommentsTop);
					$strOnClickCommentsTop = ($arParams["IS_LIST"] ? " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParamsCommentsTop.", event);\"" : "");

					?><div id="comments_control_<?=intval($arEvent["EVENT"]["ID"])?>" class="post-item-informers post-item-inform-comments"<?=$strOnClickCommentsTop?>><?
						$num_comments = intval($arParams["EVENT"]["COMMENTS_COUNT"]);
						?><div class="post-item-inform-left" id="informer_comments_text2_<?=$arEvent["EVENT"]["ID"]?>" style="display: <?=($num_comments > 0 ? "inline-block" : "none")?>;"><?
							?><?=GetMessage('MOBILE_LOG_COMMENTS_2')?><?
						?></div><?
						?><div class="post-item-inform-left" id="informer_comments_text_<?=$arEvent["EVENT"]["ID"]?>" style="display: <?=($num_comments <= 0 ? "inline-block" : "none")?>;"><?
							?><?=GetMessage('MOBILE_LOG_COMMENTS')?><?
						?></div><?

						?><div class="post-item-inform-right" id="informer_comments_<?=$arEvent["EVENT"]["ID"]?>" style="display: <?=($num_comments > 0 ? 'inline-block' : 'none')?>;"><?
							if (
								($arParams["USE_FOLLOW"] != "Y" || $arEvent["EVENT"]["FOLLOW"] == "Y")
								&& intval($arResult["NEW_COMMENTS"]) > 0
							)
							{
								?><span id="informer_comments_all_<?=$arEvent["EVENT"]["ID"]?>"><?
									$old_comments = intval(abs($num_comments - intval($arResult["NEW_COMMENTS"])));
									?><?=($old_comments > 0 ? $old_comments : '')?><?
								?></span><?
								?><span id="informer_comments_new_<?=$arEvent["EVENT"]["ID"]?>" class="post-item-inform-right-new"><?
									?><span class="post-item-inform-right-new-sign">+</span><?
									?><span class="post-item-inform-right-new-value"><?=intval($arResult["NEW_COMMENTS"])?></span><?
								?></span><?
							}
							else
							{
								?><?=$num_comments?><?
							}

						?></div><?
					?></div><?
				}
				else
				{
					$bHasComments = false;
				}

				if ($bHasComments)
				{
					?><div id="log_entry_follow_<?=intval($arEvent["EVENT"]["ID"])?>" data-follow="<?=($arEvent["EVENT"]["FOLLOW"] == "Y" ? "Y" : "N")?>" style="display: none;"></div><?
				}

				if (
					!in_array(
						$arEvent["EVENT"]["EVENT_ID"], 
						array("photo", "photo_photo", "files", "commondocs", "timeman_entry", "report", "calendar", "crm_activity_add")
					)
				)
				{
					if ($arParams["IS_LIST"])
					{
						$arOnClickParams["focus_comments"] = false;
						$arOnClickParams["show_full"] = true;
						$strOnClickParams = CUtil::PhpToJSObject($arOnClickParams);
						$strOnClickMore = ' onclick="__MSLOpenLogEntryNew('.$strOnClickParams.', event);"';
					}
					else
					{
						$strOnClickMore = ' onclick="oMSL.expandText('.intval($arEvent["EVENT"]["ID"]).');"';
					}

					?><a id="post_more_limiter_<?=intval($arEvent["EVENT"]["ID"])?>" <?=$strOnClickMore?> class="post-item-more" ontouchstart="this.classList.toggle('post-item-more-pressed')" ontouchend="this.classList.toggle('post-item-more-pressed')" style="display: none;"><?
						?><?=GetMessage("MOBILE_LOG_EXPAND")?><?
					?></a><?
				}

				$strBottomBlock = ob_get_contents();
				ob_end_clean();

				$post_item_style = (!$arParams["IS_LIST"] && $_REQUEST["show_full"] == "Y" ? "post-item-post-block-full" : "post-item-post-block post-item-contentview");

				if (in_array($arEvent["EVENT"]["EVENT_ID"], array("photo", "photo_photo")))
				{
					include($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log.entry/templates/.default/photo.php");
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
						$news_item_style = (
							!$arParams["IS_LIST"]
							&& $_REQUEST["show_full"] == "Y"
								? "lenta-info-block-wrapp-full"
								: "lenta-info-block-wrapp post-item-block-inner post-item-contentview"
						);

						?><div class="<?=$news_item_style?>"<?=$strOnClick?> id="post_block_check_cont_<?=$arEvent["EVENT"]["ID"]?>" bx-content-view-xml-id="<?=(!empty($arResult["CONTENT_ID"]) ? htmlspecialcharsBx($arResult["CONTENT_ID"]) : "")?>"><?
							?><div class="post-item-full-content post-item-copytext lenta-info-block <?=(in_array($arEvent["EVENT"]["EVENT_ID"], array("intranet_new_user", "bitrix24_new_user")) ? "lenta-block-new-employee" : "info-block-important")?>"><?
								if (in_array($arEvent["EVENT"]["EVENT_ID"], array("intranet_new_user", "bitrix24_new_user")))
								{
									echo CSocNetTextParser::closetags($arEvent["EVENT_FORMATTED"]["MESSAGE"]);
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
										?><div class="lenta-important-block-title" id="post_block_check_title_<?=$arEvent["EVENT"]["ID"]?>"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></div><?
									}

									?><div class="lenta-important-block-text" id="post_block_check_<?=$arEvent["EVENT"]["ID"]?>"><?
										?><?=CSocNetTextParser::closetags($arEvent["EVENT_FORMATTED"]["MESSAGE"])?><?
										?><span class="lenta-block-angle"></span><?
									?></div><?
								}
							?></div><?

							?><div class="post-more-block" id="post_more_block_<?=$arEvent["EVENT"]["ID"]?>"></div><?
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
					elseif (in_array($arEvent["EVENT"]["EVENT_ID"], array("tasks", "timeman_entry", "report", "calendar", "crm_activity_add")))
					{
						?><div id="post_block_check_cont_<?=intval($arEvent["EVENT"]["ID"])?>" class="lenta-info-block-wrapp-full post-item-block-inner post-item-contentview" bx-content-view-xml-id="<?=(!empty($arResult["CONTENT_ID"]) ? htmlspecialcharsBx($arResult["CONTENT_ID"]) : "")?>"<?=$strOnClick?>><?
							?><?=$arEvent["EVENT_FORMATTED"]["MESSAGE"]?><?
						?></div><?
					}
					elseif (strlen($arEvent["EVENT_FORMATTED"]["MESSAGE"]) > 0) // all other events
					{
						?><div class="<?=$post_item_style?>"<?=$strOnClick?> id="post_block_check_cont_<?=$arEvent["EVENT"]["ID"]?>" bx-content-view-xml-id="<?=(!empty($arResult["CONTENT_ID"]) ? htmlspecialcharsBx($arResult["CONTENT_ID"]) : "")?>"><?
							if (
								array_key_exists("TITLE_24_2", $arEvent["EVENT_FORMATTED"])
								&& strlen($arEvent["EVENT_FORMATTED"]["TITLE_24_2"]) > 0
							)
							{
								?><div class="post-text-title" id="post_text_title_<?=$arEvent["EVENT"]["ID"]?>"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></div><?
							}
							?><div class="post-item-text post-item-copytext" id="post_block_check_<?=$arEvent["EVENT"]["ID"]?>"><?=$arEvent["EVENT_FORMATTED"]["MESSAGE"]?></div><?

							if (
								array_key_exists("EVENT_FORMATTED", $arEvent)
								&& is_array($arEvent["EVENT_FORMATTED"]["UF"])
								&& count($arEvent["EVENT_FORMATTED"]["UF"]) > 0
							)
							{
								?><div class="post-item-attached-file-wrap" id="post_block_check_files_<?=$arEvent["EVENT"]["ID"]?>"><?
									$eventHandlerID = false;
									$eventHandlerID = AddEventHandler("main", "system.field.view.file", "__logUFfileShowMobile");
									foreach ($arEvent["EVENT_FORMATTED"]["UF"] as $FIELD_NAME => $arUserField)
									{
										if(!empty($arUserField["VALUE"]))
										{
											$APPLICATION->IncludeComponent(
												"bitrix:system.field.view",
												$arUserField["USER_TYPE"]["USER_TYPE_ID"],
												array(
													"arUserField" => $arUserField,
													"ACTION_PAGE" => str_replace("#log_id#", $arEvent["EVENT"]["ID"], $arParams["PATH_TO_LOG_ENTRY"]),
													"MOBILE" => "Y"
												),
												null,
												array("HIDE_ICONS"=>"Y")
											);
										}
									}

									if (
										$eventHandlerID !== false 
										&& (intval($eventHandlerID) > 0)
									)
									{
										RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
									}
								?></div><?
							}

							?><div class="post-more-block" id="post_more_block_<?=$arEvent["EVENT"]["ID"]?>"></div><?
						?></div><?
					}
				}

				if (strlen($strBottomBlock) > 0)
				{
					?><div id="post_item_inform_wrap" class="post-item-inform-wrap"><?
						?><?=$strBottomBlock;?><?
					?></div><?
				}

				if (
					$arParams["SHOW_RATING"] == "Y"
					&& !empty($voteId)
				)
				{
					?><div class="post-item-inform-wrap-tree" id="<?=(!$arParams["IS_LIST"] ? 'rating-footer-wrap' : 'rating-footer-wrap_'.intval($arEvent["EVENT"]["ID"]))?>"><?
						?><div class="feed-post-emoji-top-panel-outer"><?
							?><div id="feed-post-emoji-top-panel-container-<?=htmlspecialcharsbx($voteId)?>" class="feed-post-emoji-top-panel-box <?=(intval($arEvent["RATING"]["TOTAL_POSITIVE_VOTES"]) > 0 ? 'feed-post-emoji-top-panel-container-active' : '')?>"><?
								$APPLICATION->IncludeComponent(
									"bitrix:rating.vote",
									"like_react",
									array(
										"MOBILE" => "Y",
										"ENTITY_TYPE_ID" => $arEvent["EVENT"]["RATING_TYPE_ID"],
										"ENTITY_ID" => $arEvent["EVENT"]["RATING_ENTITY_ID"],
										"OWNER_ID" => $arResult["Post"]["AUTHOR_ID"],
										"USER_VOTE" => $arEvent["RATING"]["USER_VOTE"],
										"USER_REACTION" => $arEvent["RATING"]["USER_REACTION"],
										"USER_HAS_VOTED" => $arEvent["RATING"]["USER_HAS_VOTED"],
										"TOTAL_VOTES" => $arEvent["RATING"]["TOTAL_VOTES"],
										"TOTAL_POSITIVE_VOTES" => $arEvent["RATING"]["TOTAL_POSITIVE_VOTES"],
										"TOTAL_NEGATIVE_VOTES" => $arEvent["RATING"]["TOTAL_NEGATIVE_VOTES"],
										"TOTAL_VALUE" => $arEvent["RATING"]["TOTAL_VALUE"],
										"REACTIONS_LIST" => $arEvent["RATING"]["REACTIONS_LIST"],
										"PATH_TO_USER_PROFILE" => $arParams["~PATH_TO_USER"],
										'TOP_DATA' => (!empty($arResult['TOP_RATING_DATA']) ? $arResult['TOP_RATING_DATA'] : false),
										'VOTE_ID' => $voteId
									),
									$component,
									array("HIDE_ICONS" => "Y")
								);

							?></div><?
						?></div><?
					?></div><?
				}
			?></div><? // post-item-top-wrap

			?><script>
				<?
				if (!$arParams["IS_LIST"])
				{
					$arEntityXMLID = array(
						"tasks" => "TASK",
						"forum" => "FORUM",
						"photo_photo" => "PHOTO",
						"sonet" => "SOCNET",
					);

					$entity_xml_id = (
						array_key_exists($arEvent["EVENT"]["EVENT_ID"], $arEntityXMLID)
						&& $arEvent["EVENT"]["SOURCE_ID"] > 0
							? $arEntityXMLID[$arEvent["EVENT"]["EVENT_ID"]]."_".$arEvent["EVENT"]["SOURCE_ID"]
							: strtoupper($arEvent["EVENT"]["EVENT_ID"])."_".$arEvent["EVENT"]["ID"]
					);

					?>
					BX.ready(function()
					{
						oMSL.InitDetail({
							commentsType: 'log',
							detailPageId: 'log_' + <?=$arEvent["EVENT"]["ID"]?>,
							logId: <?=$arEvent["EVENT"]["ID"]?>,
							entityXMLId: '<?=CUtil::JSEscape($entity_xml_id)?>',
							bUseFollow: <?=($arParams["USE_FOLLOW"] == 'N' ? 'false' : 'true')?>,
							bFollow: <?=($arParams["FOLLOW"] == 'N' ? 'false' : 'true')?>,
							entryParams: {
								post_content_type_id: <?=(!empty($arResult["POST_CONTENT_TYPE_ID"]) ? $arResult["POST_CONTENT_TYPE_ID"] : '')?>,
								post_content_id: <?=(!empty($arResult["POST_CONTENT_ID"]) ? $arResult["POST_CONTENT_ID"] : 0)?>
							}
						});
					});
					<?
				}
			?></script><?

			if (!$arParams["IS_LIST"])
			{
				$records = $arResult["RECORDS"];

				$eventHandlerID = AddEventHandler('main', 'system.field.view.file', Array('CBlogTools', 'blogUFfileShow'));

				$edit = SITE_DIR."mobile/ajax.php?".
					"action=get_comment_data&mobile_action=get_log_comment_data&".
					"log_id=".($arEvent["EVENT"]["ID"])."&".
					"cid=#ID#";

				$delete = SITE_DIR."mobile/ajax.php?".
					"action=delete_comment&mobile_action=delete_comment&".
					"log_id=".($arEvent["EVENT"]["ID"])."&".
					"delete_id=#ID#";

				$commentsList = (
					intval($arParams["EVENT"]["COMMENTS_COUNT"]) > 0
						? array_fill(0, intval($arParams["EVENT"]["COMMENTS_COUNT"]), null)
						: array()
				);

				$db_res = new CDBResult();
				$db_res->InitFromArray($commentsList);
				$db_res->navNum = 1;
				$db_res->navStart(count($records), false, 1);

				$arResult["OUTPUT_LIST"] = $APPLICATION->IncludeComponent(
					"bitrix:main.post.list",
					"",
					array(
						"TEMPLATE_ID" => '',
						"RATING_TYPE_ID" => (
							$arParams["SHOW_RATING"] == "Y"
								? CSocNetLogComponent::getCommentRatingType($arEvent["EVENT"]["EVENT_ID"], $arEvent["EVENT"]["ID"])
								: ""
						),
						"ENTITY_XML_ID" => $arEvent["COMMENTS_PARAMS"]["ENTITY_XML_ID"],
						"POST_CONTENT_TYPE_ID" => $arResult["POST_CONTENT_TYPE_ID"],
						"COMMENT_CONTENT_TYPE_ID" => $arResult["COMMENT_CONTENT_TYPE_ID"],
						"RECORDS" => array_reverse($records, true),
						"NAV_STRING" => SITE_DIR.'mobile/ajax.php?'.http_build_query(array(
								"logid" => $arEvent["EVENT"]["ID"],
								"as" => $arParams["AVATAR_SIZE_COMMENT"],
								"nt" => $arParams["NAME_TEMPLATE"],
								"sl" => $arParams['SHOW_LOGIN'],
								"dtf" => GetMessage("MOBILE_LOG_COMMENT_FORMAT_DATE"),
								"p_user" => $arParams["PATH_TO_USER"],
								"action" => 'get_comments',
								"mobile_action" => 'get_comments',
								"last_comment_ts" => $arResult["LAST_COMMENT_TS"],
								"last_log_ts" => $arResult["LAST_LOG_TS"],
								"counter_type" => $arResult["COUNTER_TYPE"]
							)),
						"NAV_RESULT" => $db_res,
						"PREORDER" => "N",
						"RIGHTS" => array(
							"MODERATE" => "N",
							"EDIT" => $arResult["COMMENT_RIGHTS_EDIT"],
							"DELETE" => $arResult["COMMENT_RIGHTS_DELETE"],
							"CREATETASK" => ($arResult["bTasksAvailable"] ? "Y" : "N")
						),
						"VISIBLE_RECORDS_COUNT" => count($records),

						"ERROR_MESSAGE" => ($arResult["ERROR_MESSAGE"] ?: $arResult["COMMENT_ERROR"]),
						"OK_MESSAGE" => $arResult["MESSAGE"],
						"RESULT" => ($arResult["ajax_comment"] ?: $_GET["commentId"]),
						"PUSH&PULL" => $arResult["PUSH&PULL"],
						"VIEW_URL" => str_replace("#log_id#", $arEvent["EVENT"]["ID"], $arParams["PATH_TO_LOG_ENTRY"]).(strpos($arParams["PATH_TO_LOG_ENTRY"], "?") === false ? "?" : "&")."empty_get_comments=Y",
						"EDIT_URL" => $edit,
						"MODERATE_URL" => "",
						"DELETE_URL" => $delete,
						"AUTHOR_URL" => $arParams["PATH_TO_USER"],

						"AVATAR_SIZE" => $arParams["AVATAR_SIZE_COMMENT"],
						"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
						"SHOW_LOGIN" => $arParams['SHOW_LOGIN'],

						"DATE_TIME_FORMAT" => (true ? GetMessage("MOBILE_LOG_COMMENT_FORMAT_DATE") : GetMessage("MOBILE_LOG_COMMENT_FORMAT_DATE_YEAR")),
						"LAZYLOAD" => "Y",

						"NOTIFY_TAG" => ($arParams["bFromList"] ? "BLOG|COMMENT" : ""),
						"NOTIFY_TEXT" => ($arParams["bFromList"] ? TruncateText(str_replace(Array("\r\n", "\n"), " ", $arParams["POST_DATA"]["~TITLE"]), 100) : ""),
						"SHOW_MINIMIZED" => "Y",
						"SHOW_POST_FORM" => $arEvent["CAN_ADD_COMMENTS"],

						"IMAGE_SIZE" => $arParams["IMAGE_SIZE"],
						"mfi" => $arParams["mfi"],

						"FORM" => array(
							"ID" => $this->__component->__name,
							"URL" => $APPLICATION->GetCurPageParam("", array(
								"sessid", "comment_post_id", "act", "post", "comment",
								"decode", "ACTION", "ENTITY_TYPE_ID", "ENTITY_ID",
								"empty_get_form", "empty_get_comments")),
							"FIELDS" => array(
								"log_id" => $arParams["LOG_ID"]
							)
						),
						"IS_POSTS_LIST" => ($arParams["bFromList"] ? "Y" : "N"),
					),
					$this->__component
				);
				if ($eventHandlerID > 0 )
				{
					RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
				}

				ob_start();
				?><script>
					app.setPageID('LOG_ENTRY_<?=$arEvent["EVENT"]["ID"]?>');
					tmp_log_id = <?=intval($arEvent["EVENT"]["ID"])?>;

					var arEntryCommentID = [];
					BXMobileApp.onCustomEvent('onPullExtendWatch', {'id': 'UNICOMMENT<?=$arEvent["COMMENTS_PARAMS"]["ENTITY_XML_ID"]?>'}, true);

					BXMobileApp.onCustomEvent('onCommentsGet', { log_id: <?=$arEvent["EVENT"]["ID"]?>, ts: '<?=time()?>'}, true);
				</script><?
				if ($arEvent["CAN_ADD_COMMENTS"] == "Y")
				{
					?><form action="/<?=SITE_DIR.(SITE_DIR == '' ? '' : '/')?>mobile/ajax.php" <?
					?>id="<?=$this->__component->__name?>" <?
					?>name="<?=$this->__component->__name?>" <?
					?>method="POST" enctype="multipart/form-data" class="comments-form">
					<input type="hidden" name="action" value="add_comment" />
					<input type="hidden" name="mobile_action" value="add_comment" />
					<input type="hidden" name="site" value="<?=htmlspecialcharsbx(SITE_ID)?>" />
					<input type="hidden" name="lang" value="<?=htmlspecialcharsbx(LANGUAGE_ID)?>" />
					</form><?
					$arPostFields = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("SONET_COMMENT", 0, LANGUAGE_ID);
					$APPLICATION->IncludeComponent("bitrix:main.post.form",
						".default",
						array(
							"FORM_ID" => $this->__component->__name,
							"PARSER" => array(
								"Bold", "Italic", "Underline", "Strike", "ForeColor",
								"FontList", "FontSizeList", "RemoveFormat", "Quote",
								"Code", "Image", "Table", "Justify", "InsertOrderedList",
								"InsertUnorderedList", "MentionUser", "SmileList", "Source"),
							"TEXT" => array(
								"NAME" => "message",
								"VALUE" => "",
							),
							"DESTINATION" => array(
								"VALUE" => $arResult["FEED_DESTINATION"],
								"SHOW" => "N",
							),
							"UPLOADS" => array(
								$arPostFields["UF_SONET_COM_FILE"],
								$arPostFields["UF_SONET_COM_DOC"],
							),
							"SMILES" => array()
						),
						false,
						array("HIDE_ICONS" => "Y")
					);
				}

				$arResult["OUTPUT_LIST"]["HTML"] .= ob_get_clean();

				if ($_REQUEST["empty_get_comments"] == "Y")
				{
					$APPLICATION->RestartBuffer();
					while(ob_get_clean());
					echo CUtil::PhpToJSObject(array(
						"TEXT" => $arResult["OUTPUT_LIST"]["HTML"],
						"POST_NUM_COMMENTS" => intval($arParams["EVENT"]["COMMENTS_COUNT"])
					));
					\CMain::FinalActions();
					die();
				}

				?><div class="post-comments-wrap" id="post-comments-wrap"><?
					?><?=$arResult["OUTPUT_LIST"]["HTML"]?><?
					?><span id="post-comment-last-after"></span><?
				?></div><? // post-comments-wrap
			}
			else
			{
				?><script>
					app.setPageID('LOG_ENTRY_<?=$arEvent["EVENT"]["ID"]?>');
				</script><?

				if ($_REQUEST["empty_get_comments"] == "Y")
				{
					$APPLICATION->RestartBuffer();
					ob_start();
					?><script>
						app.setPageID('LOG_ENTRY_<?=$arEvent["EVENT"]["ID"]?>');
					</script><?
					$strCommentsText = ob_get_contents();

					echo CUtil::PhpToJSObject(array(
						"TEXT" => $strCommentsText
					));
					die();
				}
			}

		?></div><? // post-wrap / lenta-item
	}
}
?>