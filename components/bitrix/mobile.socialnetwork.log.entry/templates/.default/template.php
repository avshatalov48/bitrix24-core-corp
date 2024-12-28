<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

$targetHtml = '';

if (!empty($arResult["FatalError"]))
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br /><br /><?php
}
else
{
	if (!empty($arResult["ErrorMessage"]))
	{
		?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br /><br /><?php
	}

	if (
		$arResult["Event"]
		&& is_array($arResult["Event"])
		&& !empty($arResult["Event"])
	)
	{
		$arEvent = $arResult["Event"];
		$isUnread = $arParams["EVENT"]["IS_UNREAD"];

		$strTopic = "";
		if (
			isset($arEvent["EVENT_FORMATTED"]["DESTINATION"])
			&& is_array($arEvent["EVENT_FORMATTED"]["DESTINATION"])
			&& count($arEvent["EVENT_FORMATTED"]["DESTINATION"]) > 0
		)
		{
			if (
				isset($arEvent['EVENT_FORMATTED']['TITLE_24'])
				&& $arEvent["EVENT_FORMATTED"]["TITLE_24"] <> ''
			)
			{
				$strTopic .= '<div class="post-item-top-text post-item-top-arrow'.($arEvent["EVENT_FORMATTED"]["STYLE"] <> '' ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE_24"].' </div>';
			}

			$i = 0;
			foreach($arEvent["EVENT_FORMATTED"]["DESTINATION"] as $arDestination)
			{
				$strTopic .= ($i > 0 ? ', ' : ' ');

				if (!empty($arDestination["CRM_PREFIX"]))
				{
					$strTopic .= ' <span class="post-item-dest-crm-prefix">'.$arDestination["CRM_PREFIX"].':&nbsp;</span>';
				}

				if ($arDestination["URL"] <> '')
				{
					$strTopic .= '<a href="'.$arDestination["URL"].'" class="post-item-destination'.($arDestination["STYLE"] <> '' ? ' post-item-dest-'.$arDestination["STYLE"] : '').'">'.$arDestination["TITLE"].'</a>';
				}
				else
				{
					$strTopic .= '<span class="post-item-destination'.($arDestination["STYLE"] <> '' ? ' post-item-dest-'.$arDestination["STYLE"] : '').'">'.$arDestination["TITLE"].'</span>';
				}

				$i++;
			}

			if ((int)$arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"] > 0)
			{
				$more_cnt = (int)$arEvent["EVENT_FORMATTED"]["DESTINATION_MORE"];
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
			$strTopic .= (!empty($arEvent["EVENT_FORMATTED"]["TITLE_24"])
					? '<div class="post-item-top-text'.(!empty($arEvent["EVENT_FORMATTED"]["STYLE"]) ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE_24"].'</div>'
					: '<div class="post-item-top-text'.(!empty($arEvent["EVENT_FORMATTED"]["STYLE"]) ? ' post-item-'.$arEvent["EVENT_FORMATTED"]["STYLE"] : '').'">'.$arEvent["EVENT_FORMATTED"]["TITLE"].'</div>'
			);
		}

		$strCreatedBy = "";
		if (
			isset($arEvent['CREATED_BY'])
			&& is_array($arEvent["CREATED_BY"])
		)
		{
			if (
				isset($arEvent['CREATED_BY']['TOOLTIP_FIELDS'])
				&& is_array($arEvent["CREATED_BY"]["TOOLTIP_FIELDS"])
			)
			{
				$strCreatedBy .= '<a class="post-item-top-title" href="'.str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["ID"], $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]).'">'.CUser::FormatName($arParams["NAME_TEMPLATE"], $arEvent["CREATED_BY"]["TOOLTIP_FIELDS"], ($arParams["SHOW_LOGIN"] !== "N" ? true : false)).'</a>';
			}
			elseif (
				isset($arEvent['CREATED_BY']['FORMATTED'])
				&& $arEvent["CREATED_BY"]["FORMATTED"] <> ''
			)
			{
				$strCreatedBy .= '<div class="post-item-top-title">'.$arEvent["CREATED_BY"]["FORMATTED"].'</div>';
			}
		}
		elseif (
			isset($arEvent['ENTITY'])
			&& in_array($arEvent["EVENT"]["EVENT_ID"], array("data", "news", "system"), true)
		)
		{
			if (
				isset($arEvent['ENTITY']['TOOLTIP_FIELDS'])
				&& is_array($arEvent["ENTITY"]["TOOLTIP_FIELDS"])
			)
			{
				$strCreatedBy .= '<a class="post-item-top-title" href="' . str_replace(array("#user_id#", "#USER_ID#", "#id#", "#ID#"), $arEvent["ENTITY"]["TOOLTIP_FIELDS"]["ID"], $arEvent["ENTITY"]["TOOLTIP_FIELDS"]["PATH_TO_SONET_USER_PROFILE"]).'">' .
					CUser::FormatName($arParams["NAME_TEMPLATE"], $arEvent["ENTITY"]["TOOLTIP_FIELDS"], $arParams["SHOW_LOGIN"] !== "N") .
				'</a>';
			}
			elseif (isset($arEvent['ENTITY']['FORMATTED']['NAME']))
			{
				$strCreatedBy .= '<div class="post-item-top-title">'.$arEvent["ENTITY"]["FORMATTED"]["NAME"].'</div>';
			}
		}

		$strDescription = "";
		if (
			isset($arEvent['EVENT_FORMATTED']['DESCRIPTION'])
			&& (
				(
					!is_array($arEvent['EVENT_FORMATTED']['DESCRIPTION'])
					&& $arEvent['EVENT_FORMATTED']['DESCRIPTION'] <> ''
				)
				|| (
					is_array($arEvent['EVENT_FORMATTED']['DESCRIPTION'])
					&& !empty($arEvent['EVENT_FORMATTED']['DESCRIPTION'])
				)
			)
		)
		{
			$descriptionClassList = [ 'post-item-description' ];

			if ($arEvent["EVENT_FORMATTED"]["DESCRIPTION_STYLE"] <> '')
			{
				$descriptionClassList[] = 'post-item-description-'.$arEvent["EVENT_FORMATTED"]["DESCRIPTION_STYLE"];
			}

			$strDescription = '<div class="'.implode(' ', $descriptionClassList).'">'.(is_array($arEvent["EVENT_FORMATTED"]["DESCRIPTION"]) ? '<span>'.implode('</span> <span>', $arEvent["EVENT_FORMATTED"]["DESCRIPTION"]).'</span>' : $arEvent["EVENT_FORMATTED"]["DESCRIPTION"]).'</div>';
		}

		if ($arParams["IS_LIST"])
		{
			?><script>
				arLogTs.entry_<?= (int)$arEvent["EVENT"]["ID"] ?> = <?= (int)$arResult["LAST_LOG_TS"] ?>;
			</script><?php
		}

		$taskId = false;
		$taskData = null;

		$calendarEventId = false;

		if (
			isset($arEvent["EVENT"]["EVENT_ID"])
			&& $arParams["IS_LIST"]
		)
		{
			if (
				isset($arEvent["EVENT"]["MODULE_ID"], $arEvent["EVENT"]["SOURCE_ID"])
				&& ($arEvent["EVENT"]["MODULE_ID"] === "tasks")
				&& ($arEvent["EVENT"]["EVENT_ID"] === "tasks")
				&& ($arEvent["EVENT"]["SOURCE_ID"] > 0)
			)
			{
				$taskId = (int)$arEvent["EVENT"]["SOURCE_ID"];
			}
			elseif (
				isset(
					$arEvent["EVENT"]["MODULE_ID"],
					$arEvent["EVENT"]["ENTITY_ID"],
					$arParams["CRM_ACTIVITY2TASK"][$arEvent["EVENT"]["ENTITY_ID"]]
				)
				&& ($arEvent["EVENT"]["MODULE_ID"] === "crm_shared")
				&& ($arEvent["EVENT"]["EVENT_ID"] === "crm_activity_add")
				&& ($arEvent["EVENT"]["ENTITY_ID"] > 0)
			)
			{
				$taskId = (int)$arParams["CRM_ACTIVITY2TASK"][$arEvent["EVENT"]["ENTITY_ID"]];
			}
			elseif (
				($arEvent["EVENT"]["EVENT_ID"] === "calendar")
				&& isset($arEvent["EVENT"]["SOURCE_ID"])
				&& ($arEvent["EVENT"]["SOURCE_ID"] > 0)
			)
			{
				$calendarEventId = (int)$arEvent["EVENT"]["SOURCE_ID"];
			}

			if (
				$taskId
				&& \Bitrix\Main\Loader::includeModule('tasks')
			)
			{
					try
					{
						$taskData = CTaskItem::getInstanceFromPool($taskId, \Bitrix\Tasks\Util\User::getId())->getData(false);
					}
					catch (TasksException $exception)
					{
					}
			}
		}

		$bHasNoCommentsOrLikes = (
			(
				!isset($arEvent['HAS_COMMENTS'])
				|| $arEvent["HAS_COMMENTS"] !== "Y"
			)
			&& (
				$arParams["SHOW_RATING"] !== "Y"
				|| $arEvent["RATING_TYPE_ID"] == ''
				|| (int)$arEvent["RATING_ENTITY_ID"] <= 0
			)
		);

		$itemClassList = [];
		if (!$arParams['IS_LIST'])
		{
			$itemClassList[] = 'post-wrap';
		}
		else
		{
			$itemClassList[] = 'lenta-item';
			if ($isUnread)
			{
				$itemClassList[] = 'lenta-item-new';
			}
			if ($bHasNoCommentsOrLikes)
			{
				$itemClassList[] = 'post-without-informers';
			}
		}

		if (!empty($arParams['PINNED_PANEL_DATA']))
		{
			$itemClassList[] = 'lenta-item-pinned';
		}

		$pinned = (
			!empty($arParams['PINNED_PANEL_DATA'])
			|| (
				isset($arParams['EVENT']['PINNED'])
				&& $arParams['EVENT']['PINNED'] === 'Y'
			)
		);

		if ($pinned)
		{
			$itemClassList[] = 'lenta-item-pin-active';
		}

		?><div
			 id="lenta_item_<?= $arEvent["EVENT"]["ID"] ?>"
			 class="<?=implode(' ', $itemClassList)?>"
			 data-livefeed-id="<?=(int)$arEvent["EVENT"]["ID"]?>"
			 data-livefeed-post-pinned="<?=($pinned ? 'Y' : 'N')?>"
			 data-security-entity-pin="<?= (int)$arParams['LOG_ID'] ?>"
			 data-security-token-pin="<?= htmlspecialcharsbx($arResult['LOG_ID_TOKEN']) ?>"
			 data-livefeed-post-entry-type="non-blog"
			 data-livefeed-post-use-follow="<?=($arParams['USE_FOLLOW'] === 'N' ? 'N' : 'Y')?>"
			 data-livefeed-post-use-tasks="<?=($arResult['bTasksAvailable'] && $arResult['canGetPostContent'] ? 'Y' : 'N')?>"
			 data-livefeed-post-entity-xml-id="<?=htmlspecialcharsbx($arEvent['COMMENTS_PARAMS']['ENTITY_XML_ID'])?>"
			 data-livefeed-post-content-type-id="<?=(!empty($arResult['POST_CONTENT_TYPE_ID']) ? htmlspecialcharsbx($arResult['POST_CONTENT_TYPE_ID']) : '')?>"
			 data-livefeed-post-content-id="<?=(!empty($arResult['POST_CONTENT_ID']) ? (int)$arResult['POST_CONTENT_ID'] : 0)?>"
			 data-livefeed-post-show-full="<?=(in_array($arEvent['EVENT']['EVENT_ID'], [ 'timeman_entry', 'report', 'calendar' ]) ? 'Y' : 'N')?>"

			 data-livefeed-task-id="<?=(int)$taskId?>"
			 data-livefeed-task-data="<?=($taskData ? htmlspecialcharsbx(Json::encode([
				 'creatorIcon' => \Bitrix\Tasks\UI\Avatar::getPerson($taskData['CREATED_BY_PHOTO']),
				 'responsibleIcon' => \Bitrix\Tasks\UI\Avatar::getPerson($taskData['RESPONSIBLE_PHOTO']),
				 'title' => addslashes(htmlspecialcharsbx($taskData['TITLE']))
			 ])) : '')?>"

			 data-livefeed-calendar-event-id="<?=(int)$calendarEventId?>"
		><?php
			$topWrapClassList = [
				'post-item-top-wrap',
				'post-item-copyable'
			];
			if (
				$arParams["FOLLOW_DEFAULT"] === "N"
				&& $arEvent["EVENT"]["FOLLOW"] === "Y"
			)
			{
				$topWrapClassList[] = 'post-item-follow';
			}

			?><div id="post_item_top_wrap_<?=$arEvent["EVENT"]["ID"]?>" class="<?=implode(' ', $topWrapClassList)?>"><?php
				?><div class="post-item-top" id="post_item_top_<?=$arEvent["EVENT"]["ID"]?>"><?php

					$avatarClassList = [ 'avatar' ];
					if (!empty($arEvent['EVENT_FORMATTED']['AVATAR_STYLE']))
					{
						$avatarClassList[] = $arEvent['EVENT_FORMATTED']['AVATAR_STYLE'];
					}

					$style = (
						$arEvent["AVATAR_SRC"] <> ''
							? "background-image: url('" . $arEvent['AVATAR_SRC'] . "')"
							: ''
					);

					?><div class="<?= implode(' ', $avatarClassList) ?>" style="<?= $style ?>"></div><?php

					?><div class="post-item-pinned-block"><?php

						if (
							!empty($arParams['PINNED_PANEL_DATA'])
							&& $arParams['PINNED_PANEL_DATA']['TITLE'] <> ''
						)
						{
							?><div class="post-item-pinned-title"><?=$arParams['PINNED_PANEL_DATA']['TITLE']?></div><?php
						}
						?><div class="post-item-pinned-text-box"><?php
							?><div class="post-item-pinned-desc"><?php
								if (
									!empty($arParams['PINNED_PANEL_DATA'])
									&& $arParams['PINNED_PANEL_DATA']['DESCRIPTION'] <> ''
								)
								{
									?><?=$arParams['PINNED_PANEL_DATA']['DESCRIPTION']?><?php
								}
							?></div><?php
						?></div><?php
					?></div><?php

					?><div class="post-item-top-cont"><?php
						?><?=$strCreatedBy?><?php
						?><div class="post-item-top-topic"><?=$strTopic ?></div><?php
						?><div class="lenta-item-time" id="datetime_block_detail_<?=$arEvent["EVENT"]["ID"]?>" ><?php
							echo CComponentUtil::getDateTimeFormatted([
								'TIMESTAMP' => $arEvent["LOG_DATE_TS"],
								'TZ_OFFSET' => $arResult["TZ_OFFSET"]
							]);
						?></div><?php
					?></div><?php

					$rightCornerNodeClassesList = [ 'lenta-item-right-corner' ];
					if ($arResult['MOBILE_API_VERSION'] >= 34)
					{
						$rightCornerNodeClassesList[] = 'lenta-item-right-corner-menu';
					}

					?><div class="<?=implode(' ', $rightCornerNodeClassesList)?>"><?php

						$useFavorites = (
							!isset($arParams["USE_FAVORITES"])
							|| $arParams["USE_FAVORITES"] !== "N"
						);

						$useFollow = ($arParams["USE_FOLLOW"] !== 'N');

						$favoritesValue = (
							$useFavorites
							&& isset($arParams['EVENT']['FAVORITES'])
							&& $arParams["EVENT"]["FAVORITES"] === "Y"
						);

						$followValue = (
							$useFollow
							&& $arEvent["EVENT"]["FOLLOW"] === "Y"
						);

						$pinnedValue = (
							isset($arParams['EVENT']['PINNED'])
							&& $arParams['EVENT']['PINNED'] === 'Y'
						);

						if ($arResult['MOBILE_API_VERSION'] >= 34)
						{
							$menuClasses = [
								'lenta-menu',
								'lenta-menu-use-pinned'
							];

							?><div
								 id="log-entry-menu-<?=(int)$arEvent["EVENT"]["ID"]?>"
								 data-menu-type="post"
								 data-log-id="<?=(int)$arEvent["EVENT"]["ID"]?>"
								 data-use-favorites="<?=($useFavorites ? "Y" : "N")?>"
								 data-favorites="<?=($favoritesValue ? "Y" : "N")?>"
								 data-use-pinned="Y"
								 data-pinned="<?=($pinnedValue ? "Y" : "N")?>"
								 data-use-follow="<?=($useFollow ? "Y" : "N")?>"
								 data-follow="<?=($followValue ? "Y" : "N")?>"
								 data-content-type-id="<?= (!empty($arResult["POST_CONTENT_TYPE_ID"]) ? $arResult["POST_CONTENT_TYPE_ID"] : '') ?>"
								 data-content-id="<?=(!empty($arResult["POST_CONTENT_ID"]) ? $arResult["POST_CONTENT_ID"] : 0)?>"
								 class="<?=implode(' ', $menuClasses)?>"
								 onclick="oMSL.showPostMenu(event)"
							>
								<div class="lenta-menu-item"></div>
							</div><?php

							?><div class="lenta-item-pin"></div><?php
						}
						elseif ($useFavorites)
						{
							?><div
							 id="log_entry_favorites_<?=$arEvent["EVENT"]["ID"]?>"
							 data-favorites="<?=($favoritesValue ? "Y" : "N")?>"
							 class="lenta-item-fav<?=($favoritesValue ? " lenta-item-fav-active" : "")?>"
							 onclick="__MSLSetFavorites(<?=$arEvent["EVENT"]["ID"]?>, this, event);"></div><?php
						}
						else
						{
							?><div class="lenta-item-fav-placeholder"></div><?php
						}

					?></div><?php

					if ($strDescription <> '')
					{
						echo $strDescription;
					}
				?></div><?php

				ob_start();

				?><div class="post-item-inform-wrap-left"><?php

				if (
					$arEvent["EVENT"]["RATING_TYPE_ID"] <> ''
					&& $arEvent["EVENT"]["RATING_ENTITY_ID"] > 0
					&& $arParams["SHOW_RATING"] === "Y"
				)
				{
					$voteId = $arEvent["EVENT"]["RATING_TYPE_ID"] . '_'.$arEvent["EVENT"]["RATING_ENTITY_ID"] . '-' . (time() + random_int(0, 1000));
					$emotion = (!empty($arEvent["RATING"]["USER_REACTION"])? mb_strtoupper($arEvent["RATING"]["USER_REACTION"]) : 'LIKE');

					?><span
					 class="post-item-informers bx-ilike-block"
					 id="rating_block_<?= (int)$arEvent['EVENT']['ID'] ?>"
					 data-counter="<?= (int)$arEvent['RATING']['TOTAL_VOTES'] ?>"
					><?php
						?><span
						 data-rating-vote-id="<?= htmlspecialcharsbx($voteId) ?>"
						 data-rating-entity-type-id="<?= htmlspecialcharsbx($arEvent['EVENT']['RATING_TYPE_ID']) ?>"
						 data-rating-entity-id="<?= (int)$arEvent['EVENT']['RATING_ENTITY_ID'] ?>"
						 id="bx-ilike-button-<?= htmlspecialcharsbx($voteId) ?>"
						 class="post-item-informer-like feed-inform-ilike"
						><?php
							$likeClassList = [ 'bx-ilike-left-wrap' ];
							if (
								isset($arEvent["RATING"]["USER_HAS_VOTED"])
								&& $arEvent["RATING"]["USER_HAS_VOTED"] === "Y" ? ' bx-you-like-button' : '')
							{
								$likeClassList[] = 'bx-you-like-button';
								$likeClassList[] = 'bx-you-like-button-'.mb_strtolower($emotion);
							}
							?><span class="<?=implode(' ', $likeClassList)?>"><?php
								?><span class="bx-ilike-icon-new"></span><?php
								?><span class="bx-ilike-text"><?= CRatingsComponentsMain::getRatingLikeMessage($emotion) ?></span><?php
							?></span><?php
						?></span><?php
					?></span><?php
				}

				if (
					isset($arEvent['HAS_COMMENTS'])
					&& $arEvent["HAS_COMMENTS"] === "Y"
				)
				{
					$bHasComments = true;

					$showNewComments = (
						(
							$arParams["USE_FOLLOW"] !== "Y"
							|| $arEvent["EVENT"]["FOLLOW"] === "Y"
						)
						&& (int)$arResult["NEW_COMMENTS"] > 0
					);

					$commentsBlockClassList = [
						'post-item-informers',
						'post-item-inform-comments'
					];
					if ($showNewComments)
					{
						$commentsBlockClassList[] = 'post-item-inform-likes-active';
					}

					?><div id="comments_control_<?= (int)$arEvent["EVENT"]["ID"] ?>" class="<?= implode(' ', $commentsBlockClassList) ?>"><?php
						?><div class="post-item-inform-comments-box"><?php
							?><span class="post-item-inform-icon"></span><?php

							$num_comments = (int)$arParams["EVENT"]["COMMENTS_COUNT"];
							?><div class="post-item-inform-left" id="informer_comments_text2_<?=$arEvent["EVENT"]["ID"]?>" style="display: <?=($num_comments > 0 ? "inline-block" : "none")?>;"><?php
								?><?=Loc::getMessage('MOBILE_LOG_COMMENTS')?><?php // MOBILE_LOG_COMMENTS_2
							?></div><?php
							?><div class="post-item-inform-left" id="informer_comments_text_<?=$arEvent["EVENT"]["ID"]?>" style="display: <?=($num_comments <= 0 ? "inline-block" : "none")?>;"><?php
								?><?=Loc::getMessage('MOBILE_LOG_COMMENTS')?><?php
							?></div><?php

							?><div class="post-item-inform-right" id="informer_comments_<?=$arEvent["EVENT"]["ID"]?>" style="display: <?=($num_comments > 0 ? 'inline-block' : 'none')?>;"><?php
								if (
									($arParams["USE_FOLLOW"] !== "Y" || $arEvent["EVENT"]["FOLLOW"] === "Y")
									&& (int)$arResult["NEW_COMMENTS"] > 0
								)
								{
									?><span id="informer_comments_all_<?=$arEvent["EVENT"]["ID"]?>"><?php
										$old_comments = (int)abs($num_comments - (int)$arResult["NEW_COMMENTS"]);
										?><?=($old_comments > 0 ? $old_comments : '')?><?php
									?></span><?php
									?><span id="informer_comments_new_<?=$arEvent["EVENT"]["ID"]?>" class="post-item-inform-right-new"><?php
										?><span class="post-item-inform-right-new-sign">+</span><?php
										?><span class="post-item-inform-right-new-value"><?= (int)$arResult["NEW_COMMENTS"] ?></span><?php
									?></span><?php
								}
								else
								{
									?><?=$num_comments?><?php
								}
							?></div><?php
						?></div><?php
					?></div><?php
				}
				else
				{
					$bHasComments = false;
				}

				if ($bHasComments)
				{
					?><div id="log_entry_follow_<?= (int)$arEvent["EVENT"]["ID"] ?>" data-follow="<?=($arEvent["EVENT"]["FOLLOW"] === "Y" ? "Y" : "N")?>" style="display: none;"></div><?php
				}

				?></div><?php // class="post-item-inform-wrap-left"

				if (
					!in_array(
						$arEvent["EVENT"]["EVENT_ID"], 
						array("photo", "photo_photo", "files", "commondocs", "timeman_entry", "report", "calendar", "crm_activity_add")
					)
				)
				{
					?><div class="post-item-inform-wrap-right"><?php
						?><a
						 id="post_more_limiter_<?=(int)$arEvent["EVENT"]["ID"]?>"
						 class="post-item-more"
						 ontouchstart="this.classList.toggle('post-item-more-pressed')"
						 ontouchend="this.classList.toggle('post-item-more-pressed')"
						 style="visibility: hidden;"><?php
							?><?=GetMessage("MOBILE_LOG_EXPAND")?><?php
						?></a><?php
					?></div><?php
				}

				$strBottomBlock = ob_get_clean();

				$postMoreBlockStyle = (
					isset($arParams['TARGET'])
					&& $arParams['TARGET'] === 'postContent'
						? 'style="display: none;"'
						: ''
				);

				if (in_array($arEvent["EVENT"]["EVENT_ID"], array("photo", "photo_photo")))
				{
					include($_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/mobile.socialnetwork.log.entry/templates/.default/photo.php");
				}
				elseif ($arEvent["EVENT_FORMATTED"]["MESSAGE"] <> '')
				{
					// body

					$contentXmlId = (!empty($arResult['CONTENT_ID']) ? $arResult['CONTENT_ID'] : '');

					if (
						isset($arEvent['EVENT_FORMATTED']['IS_IMPORTANT'])
						&& $arEvent["EVENT_FORMATTED"]["IS_IMPORTANT"]
					)
					{
						$postItemClassList = [];

						if (
							!$arParams["IS_LIST"]
							&& $_REQUEST["show_full"] === "Y"
						)
						{
							$postItemClassList[] = 'lenta-info-block-wrapp-full';
						}
						else
						{
							$postItemClassList[] = 'lenta-info-block-wrapp';
							$postItemClassList[] = 'post-item-block-inner';
							$postItemClassList[] = 'post-item-contentview';
						}

						?><div
						 class="<?=implode(' ', $postItemClassList)?>"
						 id="post_block_check_cont_<?=$arEvent["EVENT"]["ID"]?>"
						 bx-content-view-xml-id="<?= htmlspecialcharsBx($contentXmlId) ?>"
						 bx-content-view-key-signed="<?= htmlspecialcharsbx($arResult['CONTENT_VIEW_KEY_SIGNED']) ?>"><?php

							if (
								isset($arParams['TARGET'])
								&& $arParams['TARGET'] === 'postContent'
							)
							{
								$targetHtml = '';
								ob_start();
							}

							$classList = [
								'post-item-full-content',
								'post-item-copytext',
								'lenta-info-block',
								(
									in_array($arEvent["EVENT"]["EVENT_ID"], array("intranet_new_user", "bitrix24_new_user"))
										? 'lenta-block-new-employee'
										: 'info-block-important'
								),
							];

							?><div class="<?= implode(' ', $classList) ?>"><?php
								if (in_array($arEvent["EVENT"]["EVENT_ID"], array("intranet_new_user", "bitrix24_new_user")))
								{
									echo CSocNetTextParser::closetags($arEvent["EVENT_FORMATTED"]["MESSAGE"]);
								}
								else
								{
									if (
										isset($arEvent['EVENT_FORMATTED']['IS_IMPORTANT'], $arEvent['EVENT_FORMATTED']['TITLE_24_2'])
										&& $arEvent["EVENT_FORMATTED"]["IS_IMPORTANT"]
										&& $arEvent["EVENT_FORMATTED"]["TITLE_24_2"] <> ''
									)
									{
										?><div class="lenta-important-block-title" id="post_block_check_title_<?=$arEvent["EVENT"]["ID"]?>"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></div><?php
									}

									?><div class="lenta-important-block-text" id="post_block_check_<?=$arEvent["EVENT"]["ID"]?>"><?php
										?><?=CSocNetTextParser::closetags($arEvent["EVENT_FORMATTED"]["MESSAGE"])?><?php
										?><span class="lenta-block-angle"></span><?php
									?></div><?php
								}
							?></div><?php

							?><div class="post-more-block" id="post_more_block_<?=$arEvent["EVENT"]["ID"]?>"<?=$postMoreBlockStyle?>></div><?php

							if (
								isset($arParams['TARGET'])
								&& $arParams['TARGET'] === 'postContent'
							)
							{
								$targetHtml = ob_get_contents();
							}

						?></div><?php
					}
					elseif (in_array($arEvent["EVENT"]["EVENT_ID"], array("files", "commondocs")))
					{
						?><div class="post-item-post-block-full">
							<div class="post-item-attached-file-wrap">
								<div class="post-item-attached-file"><span><?=$arEvent["EVENT"]["TITLE"]?></span></div>
							</div><?php
						?></div><?php
					}
					elseif (in_array($arEvent["EVENT"]["EVENT_ID"], array("tasks", "timeman_entry", "report", "calendar", "crm_activity_add")))
					{
						$postItemClassList = [
							'lenta-info-block-wrapp-full',
							'post-item-block-inner',
							'post-item-contentview'
						];

						?><div
						 id="post_block_check_cont_<?=(int)$arEvent["EVENT"]["ID"]?>"
						 class="<?= implode(' ', $postItemClassList) ?>"
						 bx-content-view-xml-id="<?= htmlspecialcharsBx($contentXmlId) ?>"
						 bx-content-view-key-signed="<?= htmlspecialcharsbx($arResult['CONTENT_VIEW_KEY_SIGNED']) ?>"><?php

							if (
								isset($arParams['TARGET'])
								&& $arParams['TARGET'] === 'postContent'
							)
							{
								$targetHtml = $arEvent["EVENT_FORMATTED"]["MESSAGE"];
							}
							else
							{
								?><?=$arEvent["EVENT_FORMATTED"]["MESSAGE"]?><?php
							}

						?></div><?php
					}
					elseif ($arEvent["EVENT_FORMATTED"]["MESSAGE"] <> '') // all other events
					{
						$postItemClassList = [];
						if (
							!$arParams["IS_LIST"]
							&& $_REQUEST["show_full"] === "Y"
						)
						{
							$postItemClassList[] = 'post-item-post-block-full';
						}
						else
						{
							$postItemClassList[] = 'post-item-post-block';
							$postItemClassList[] = 'post-item-contentview';
						}

						?><div
						 class="<?= implode(' ', $postItemClassList) ?>"
						 id="post_block_check_cont_<?= $arEvent["EVENT"]["ID"] ?>"
						 bx-content-view-xml-id="<?= htmlspecialcharsBx($contentXmlId) ?>"
						 bx-content-view-key-signed="<?= htmlspecialcharsbx($arResult['CONTENT_VIEW_KEY_SIGNED']) ?>"><?php

							if (
								isset($arParams['TARGET'])
								&& $arParams['TARGET'] === 'postContent'
							)
							{
								$targetHtml = '';
								ob_start();
							}

							if (
								isset($arEvent['EVENT_FORMATTED']['TITLE_24_2'])
								&& $arEvent["EVENT_FORMATTED"]["TITLE_24_2"] <> ''
							)
							{
								?><div class="post-text-title" id="post_text_title_<?=$arEvent["EVENT"]["ID"]?>"><?=$arEvent["EVENT_FORMATTED"]["TITLE_24_2"]?></div><?php
							}

							?><div class="post-item-text post-item-copytext" id="post_block_check_<?=$arEvent["EVENT"]["ID"]?>"><?=$arEvent["EVENT_FORMATTED"]["MESSAGE"]?></div><?php

							if (
								isset($arEvent["EVENT_FORMATTED"])
								&& is_array($arEvent["EVENT_FORMATTED"]["UF"])
								&& !empty($arEvent['EVENT_FORMATTED']['UF'])
							)
							{
								?><div class="post-item-attached-file-wrap" id="post_block_check_files_<?=$arEvent["EVENT"]["ID"]?>"><?php

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

									if ($eventHandlerID > 0)
									{
										RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
									}
								?></div><?php
							}

							?><div class="post-more-block" id="post_more_block_<?=$arEvent["EVENT"]["ID"]?>"<?=$postMoreBlockStyle?>></div><?php

							if (
								isset($arParams['TARGET'])
								&& $arParams['TARGET'] === 'postContent'
							)
							{
								$targetHtml = ob_get_contents();
							}

						?></div><?php
					}

					if(!empty($arResult['UF_FILE']))
					{
						?><div id="post_block_files_<?=$arEvent["EVENT"]["ID"]?>" class="post-item-attached-file-wrap post-item-attached-disk-file-wrap"><?php

							$eventHandlerID = AddEventHandler('main', 'system.field.view.file', '__logUFfileShowMobile');
							$arPostField = $arResult['UF_FILE'];

							if(!empty($arPostField["VALUE"]))
							{
								?><?php
									$APPLICATION->IncludeComponent(
									"bitrix:system.field.view",
									$arPostField["USER_TYPE"]["USER_TYPE_ID"],
									[
										"arUserField" => $arPostField,
										"ACTION_PAGE" => str_replace("#log_id#", $arEvent["EVENT"]["ID"], $arParams["PATH_TO_LOG_ENTRY"]),
										"MOBILE" => "Y",
										"GRID" => "Y",
										"USE_TOGGLE_VIEW" => ($arResult["isCurrentUserEventOwner"] ? 'Y' : 'N'),
									],
									null,
									[ 'HIDE_ICONS' => 'Y' ]
								);?><?php
							}

							if ($eventHandlerID > 0)
							{
								RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
							}

						?></div><?php
					}
				}

				if (
					$arParams["SHOW_RATING"] === "Y"
					&& (
						!isset($arParams['TARGET'])
						|| $arParams["TARGET"] !== 'postContent'
					)
					&& !empty($voteId)
				)
				{
					?><div class="post-item-inform-wrap-tree" id="<?=(!$arParams["IS_LIST"] ? 'rating-footer-wrap' : 'rating-footer-wrap_'. (int)$arEvent["EVENT"]["ID"])?>"><?php
						?><div class="feed-post-emoji-top-panel-outer"><?php

							$classList = [ 'feed-post-emoji-top-panel-box' ];
							if ((int)$arEvent["RATING"]["TOTAL_POSITIVE_VOTES"] > 0)
							{
								$classList[] = 'feed-post-emoji-top-panel-container-active';
							}

							?><div
							 id="feed-post-emoji-top-panel-container-<?=htmlspecialcharsbx($voteId)?>"
							 class="<?= implode(' ', $classList) ?>"><?php
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
										'VOTE_ID' => $voteId,
										'TYPE' => 'POST'
									),
									$component,
									array("HIDE_ICONS" => "Y")
								);

							?></div><?php
						?></div><?php
					?></div><?php
				}

				if ($strBottomBlock <> '')
				{
					?><div id="post_item_inform_wrap" class="post-item-inform-wrap"><?php
						?><?= $strBottomBlock ?><?php
					?></div><?php
				}

			?></div><?php // post-item-top-wrap

			?><script>
				<?php
				if (!$arParams["IS_LIST"])
				{
					$arEntityXMLID = array(
						"tasks" => "TASK",
						"forum" => "FORUM",
						"photo_photo" => "PHOTO",
						"sonet" => "SOCNET",
					);

					$entity_xml_id = (
						isset($arEntityXMLID[$arEvent['EVENT']['EVENT_ID']])
						&& $arEvent["EVENT"]["SOURCE_ID"] > 0
							? $arEntityXMLID[$arEvent["EVENT"]["EVENT_ID"]]."_".$arEvent["EVENT"]["SOURCE_ID"]
							: mb_strtoupper($arEvent["EVENT"]["EVENT_ID"])."_".$arEvent["EVENT"]["ID"]
					);

					?>
					BX.ready(function()
					{
						oMSL.InitDetail({
							commentsType: 'log',
							detailPageId: 'log_' + <?= $arEvent['EVENT']['ID'] ?>,
							logId: <?= $arEvent['EVENT']['ID'] ?>,
							entityXMLId: '<?= CUtil::JSEscape($entity_xml_id) ?>',
							bFollow: <?= ($arParams['FOLLOW'] === 'N' ? 'false' : 'true') ?>,
							entryParams: {
								post_content_type_id: '<?= (!empty($arResult['POST_CONTENT_TYPE_ID']) ? $arResult['POST_CONTENT_TYPE_ID'] : '') ?>',
								post_content_id: <?= (!empty($arResult['POST_CONTENT_ID']) ? $arResult['POST_CONTENT_ID'] : 0) ?>,
							},
						});
					});
					<?php
				}
			?></script><?php

			if (
				!$arParams["IS_LIST"] // not in list
				&& (
					!isset($arParams['TARGET'])
					|| $arParams["TARGET"] !== 'postContent'
				)
			)
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
					(int)$arParams["EVENT"]["COMMENTS_COUNT"] > 0
						? array_fill(0, (int)$arParams["EVENT"]["COMMENTS_COUNT"], null)
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
							$arParams["SHOW_RATING"] === "Y"
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
						"VIEW_URL" => str_replace("#log_id#", $arEvent["EVENT"]["ID"], $arParams["PATH_TO_LOG_ENTRY"]).(mb_strpos($arParams["PATH_TO_LOG_ENTRY"], "?") === false ? "?" : "&")."empty_get_comments=Y",
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
						"SHOW_POST_FORM" => (!$arParams["IS_LIST"] ? $arEvent["CAN_ADD_COMMENTS"] : 'N'),
						"USE_LIVE" => !$arParams["IS_LIST"],
						"SHOW_MENU" => !$arParams["IS_LIST"],
						"REPLY_ACTION" => '',
						"IMAGE_SIZE" => $arParams["IMAGE_SIZE"],
						"mfi" => $arParams["mfi"],

						"FORM" => array(
							"ID" => $this->__component->__name,
							"URL" => $APPLICATION->GetCurPageParam("", array(
								"sessid", "comment_post_id", "act", "post", "comment",
								"decode", "ACTION", "ENTITY_TYPE_ID", "ENTITY_ID",
								"empty_get_comments")),
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

				if ($arParams["IS_LIST"])
				{
					if (!empty($records))
					{
						ob_start();
						$APPLICATION->IncludeComponent(
							"bitrix:mobile.comments.pseudoform",
							"",
							[
								'REPLY_ACTION' => ''
							]
						);
						$arResult["OUTPUT_LIST"]["HTML"] .= ob_get_clean();
					}
				}
				else
				{
					ob_start();

					?><script>
						app.setPageID('LOG_ENTRY_<?=$arEvent["EVENT"]["ID"]?>');
						tmp_log_id = <?= (int)$arEvent["EVENT"]["ID"] ?>;

						var arEntryCommentID = [];
						BXMobileApp.onCustomEvent('onPullExtendWatch', {'id': 'UNICOMMENT<?=$arEvent["COMMENTS_PARAMS"]["ENTITY_XML_ID"]?>'}, true);

						BXMobileApp.onCustomEvent('onCommentsGet', {
							log_id: <?=$arEvent["EVENT"]["ID"]?>,
							ts: '<?=time()?>',
						}, true);
					</script><?php

					if ($arEvent["CAN_ADD_COMMENTS"] === "Y")
					{
						?><form action="/<?=SITE_DIR.(SITE_DIR == '' ? '' : '/')?>mobile/ajax.php" <?php
								?>id="<?=$this->__component->__name?>" <?php
								?>name="<?=$this->__component->__name?>" <?php
								?>method="POST" enctype="multipart/form-data" class="comments-form">
							<input type="hidden" name="action" value="add_comment" />
							<input type="hidden" name="mobile_action" value="add_comment" />
							<input type="hidden" name="site" value="<?=htmlspecialcharsbx(SITE_ID)?>" />
							<input type="hidden" name="lang" value="<?=htmlspecialcharsbx(LANGUAGE_ID)?>" />
						</form><?php
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
				}

				if ($_REQUEST['empty_get_comments'] === 'Y')
				{
					$APPLICATION->RestartBuffer();
					while (ob_get_clean()) {}
					CMain::FinalActions(CUtil::PhpToJSObject([
						'TEXT' => $arResult['OUTPUT_LIST']['HTML'],
						'POST_NUM_COMMENTS' => (int)$arParams['EVENT']['COMMENTS_COUNT'],
						'TS' => time(),
					]));
					die();
				}

				?><div class="post-comments-wrap" id="post-comments-wrap"><?php
					?><?=$arResult["OUTPUT_LIST"]["HTML"]?><?php
					?><span id="post-comment-last-after"></span><?php
				?></div><?php // post-comments-wrap
			}
			else
			{
				?><script>
					app.setPageID('LOG_ENTRY_<?=$arEvent["EVENT"]["ID"]?>');
				</script><?php

				if (isset($_REQUEST["empty_get_comments"]) && $_REQUEST["empty_get_comments"] === "Y")
				{
					$APPLICATION->RestartBuffer();
					ob_start();
					?><script>
						app.setPageID('LOG_ENTRY_<?=$arEvent["EVENT"]["ID"]?>');
					</script><?php
					$strCommentsText = ob_get_contents();
					CMain::finalActions(Json::encode(array(
						"TEXT" => $strCommentsText
					)));
					die();
				}
			}

		?></div><?php // post-wrap / lenta-item
	}
}

if ($targetHtml <> '')
{
	$APPLICATION->RestartBuffer();
	echo $targetHtml;
}
