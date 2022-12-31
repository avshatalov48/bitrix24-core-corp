<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$component = $this->getComponent();

include_once($_SERVER["DOCUMENT_ROOT"].getLocalPath('templates/'.$component->getSiteTemplateId(), BX_PERSONAL_ROOT)."/components/bitrix/socialnetwork.blog.post/mobile/functions.php");

$targetHtml = '';

if(!empty($arResult["Post"]))
{
	if ($_REQUEST["empty_get_comments"] === "Y")
	{
		$APPLICATION->IncludeComponent(
			"bitrix:socialnetwork.blog.post.comment",
			".default",
			Array(
				"PATH_TO_BLOG" => $arParams["PATH_TO_BLOG"],
				"PATH_TO_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
				"PATH_TO_POST_MOBILE" => $APPLICATION->GetCurPageParam("", array("LAST_LOG_TS", "empty_get_comments")),
				"PATH_TO_USER" => $arParams["PATH_TO_USER"],
				"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
				"PATH_TO_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
				"ID" => $arResult["Post"]["ID"],
				"LOG_ID" => $arParams["LOG_ID"],
				"CACHE_TIME" => $arParams["CACHE_TIME"],
				"CACHE_TYPE" => $arParams["CACHE_TYPE"],
				"COMMENTS_COUNT" => "5",
				"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
				"USER_ID" => $USER->getId(),
				"SONET_GROUP_ID" => $arParams["SONET_GROUP_ID"],
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
				"ALLOW_VIDEO"  => $arParams["ALLOW_VIDEO"],
				"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"],
				"ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"],
				"AJAX_POST" => "Y",
				"POST_DATA" => $arResult["PostSrc"],
				"BLOG_DATA" => $arResult["Blog"],
				"FROM_LOG" => ($arParams["IS_LIST"] ? "Y" : false),
				"bFromList" => ($arParams["IS_LIST"] ? true: false),
				"LAST_LOG_TS" => $arParams["LAST_LOG_TS"],
				"MARK_NEW_COMMENTS" => "Y",
				"AVATAR_SIZE" => $arParams["AVATAR_SIZE_COMMENT"],
				"MOBILE" => "Y",
				"ATTACHED_IMAGE_MAX_WIDTH_FULL" => 640,
				"ATTACHED_IMAGE_MAX_HEIGHT_FULL" => 832,
				"CAN_USER_COMMENT" => (!isset($arResult["CanComment"]) || $arResult["CanComment"] ? 'Y' : 'N'),
				"NAV_TYPE_NEW" => "Y",
				'UNREAD_BLOG_COMMENT_ID' => ($arParams['UNREAD_BLOG_COMMENT_ID'] ?? []),
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);
	}
	else
	{
		$itemClassList = [];
		if (!$arParams['IS_LIST'])
		{
			$itemClassList[] = 'post-wrap';
		}
		else
		{
			$itemClassList[] = 'lenta-item';
			if ($arParams['IS_UNREAD'])
			{
				$itemClassList[] = 'lenta-item-new';
			}
		}

		if (!empty($arParams['PINNED_PANEL_DATA']))
		{
			$itemClassList[] = 'lenta-item-pinned';
		}

		$pinned = (
			!empty($arParams['PINNED_PANEL_DATA'])
			|| (isset($arParams['PINNED']) && $arParams['PINNED'] === 'Y')
		);

		if ($pinned)
		{
			$itemClassList[] = 'lenta-item-pin-active';
		}

		if ($arParams["IS_LIST"])
		{
			?><script>
				if (window.arLogTs)
				{
					window.arLogTs.entry_<?= (int)$arParams['LOG_ID']?> = <?= ((int)$arParams['LAST_LOG_TS'] - (int)$arResult['TZ_OFFSET'])?>;
				}
			</script><?php
		}
		else
		{
			?><script>
				BX.ready(function()
				{
					BX.MobileImageViewer.viewImageBind(
						'lenta_item_<?=(int)$arParams["LOG_ID"]?>',
						'img[data-bx-image]'
					);

					oMSL.InitDetail({
						commentsType: 'blog',
						detailPageId: 'blog_' + <?=$arResult["Post"]["ID"]?>,
						logId: <?=(int)$arParams["LOG_ID"]?>,
						entityXMLId: 'BLOG_<?=(int)$arResult["Post"]["ID"]?>',
						bFollow: <?=($arParams["FOLLOW"] === 'N' ? 'false' : 'true')?>,
						feed_id: parseInt(Math.random() * 100000),
						entryParams: {
							destinations: <?=CUtil::PhpToJSObject($arResult["Post"]["SPERM"])?>,
							post_perm: '<?=CUtil::JSEscape($arResult["PostPerm"])?>',
							post_id: <?=(int)$arResult["Post"]["ID"]?>,
							post_content_type_id: 'BLOG_POST',
							post_content_id: <?=$arResult["Post"]["ID"]?>
						},
						readOnly: '<?=(!!$arResult["ReadOnly"] ? 'Y' : 'N')?>'
					});
				});	
			</script><?php
		}

		$topWrapClassList = [
			'post-item-top-wrap',
			'post-item-copyable'
		];
		if (
			$arParams["FOLLOW_DEFAULT"] === "N"
			&& $arParams["FOLLOW"] === "Y"
		)
		{
			$topWrapClassList[] = 'post-item-follow';
		}

		?><div
			 id="lenta_item_<?=(int)$arParams['LOG_ID']?>"
			 class="<?=implode(' ', $itemClassList)?>"
			 data-livefeed-id="<?=(int)$arParams['LOG_ID']?>"
			 data-livefeed-post-pinned="<?=($pinned ? 'Y' : 'N')?>"
			 data-security-entity-pin="<?= (int)$arParams['LOG_ID'] ?>"
			 data-security-token-pin="<?= htmlspecialcharsbx($arResult['LOG_ID_TOKEN']) ?>"
			 data-livefeed-post-entry-type="blog"
			 data-livefeed-post-use-follow="<?=($arParams['USE_FOLLOW'] === 'N' ? 'N' : 'Y')?>"
			 data-livefeed-post-use-tasks="<?=($arResult['bTasksAvailable'] ? 'Y' : 'N')?>"
			 data-livefeed-post-perm="<?=htmlspecialcharsbx($arResult['PostPerm'])?>"
			 data-livefeed-post-destinations="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($arResult['Post']['SPERMX']))?>"
			 data-livefeed-post-id="<?=(int)$arResult['Post']['ID']?>"
			 data-livefeed-post-url="<?=htmlspecialcharsbx(str_replace('#log_id#', $arParams['LOG_ID'], $arParams['PATH_TO_LOG_ENTRY']))?>"
			 data-livefeed-post-entity-xml-id="BLOG_<?=(int)$arResult['Post']['ID']?>"
			 data-livefeed-post-read-only="<?=((bool)$arResult['ReadOnly'] ? 'Y' : 'N')?>"
			 data-livefeed-post-content-type-id="BLOG_POST"
			 data-livefeed-post-content-id="<?=(int)$arResult['Post']['ID']?>"
			 data-livefeed-unread-log-comment-id-data=""
			 data-livefeed-unread-blog-comment-id-data="<?= \CUtil::JSEscape(implode(' ', (
				is_array($arParams['UNREAD_BLOG_COMMENT_ID'])
					? $arParams['UNREAD_BLOG_COMMENT_ID']
					: []
			 ))) ?>"
		><?php
			?><div id="post_item_top_wrap_<?=(int)$arParams["LOG_ID"]?>" class="<?=implode(' ', $topWrapClassList)?>"><?php
				?><div class="post-item-top" id="post_item_top_<?=(int)$arParams["LOG_ID"]?>"><?php

					$avatarId = 'post_item_avatar_' . (int)$arParams['LOG_ID'];
					$avatarDataSrc = (
						$arResult['arUser']['PERSONAL_PHOTO_resized']['src'] <> ''
							? ' data-src="' . $arResult['arUser']['PERSONAL_PHOTO_resized']['src'] . '"'
							: ''
					);

					?><div class="avatar" id="<?= $avatarId ?>" <?= $avatarDataSrc ?>></div><?php

					if($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"] <> '')
					{
						?><script>BitrixMobile.LazyLoad.registerImage("<?=$avatarId?>");</script><?php
					}

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

						$arTmpUser = array(
								"NAME" => $arResult["arUser"]["~NAME"],
								"LAST_NAME" => $arResult["arUser"]["~LAST_NAME"],
								"SECOND_NAME" => $arResult["arUser"]["~SECOND_NAME"],
								"LOGIN" => $arResult["arUser"]["~LOGIN"],
								"NAME_LIST_FORMATTED" => "",
							);
						?><a class="post-item-top-title<?=($arResult["arUser"]["isExtranet"] ? ' post-item-top-title-extranet' : '')?>" href="<?=$arResult["arUser"]["url"]?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false))?></a><?php

						$strTopic = "";

						if(!empty($arResult["Post"]["SPERM"]))
						{
							$cnt = (
								(!empty($arResult["Post"]["SPERM"]["U"]) ? count($arResult["Post"]["SPERM"]["U"]) : 0) +
								(!empty($arResult["Post"]["SPERM"]["SG"]) ? count($arResult["Post"]["SPERM"]["SG"]) : 0) +
								(!empty($arResult["Post"]["SPERM"]["DR"]) ? count($arResult["Post"]["SPERM"]["DR"]) : 0)
							);
							$i = 0;

							if(!empty($arResult["Post"]["SPERM"]["U"]))
							{
								foreach($arResult["Post"]["SPERM"]["U"] as $id => $val)
								{
									$i++;
									if ($i == 4)
									{
										$more_cnt = $cnt + (int)$arResult["Post"]["SPERM_HIDDEN"] - 3;
										$suffix = (
										($more_cnt % 100) > 10
										&& ($more_cnt % 100) < 20
											? 5
											: $more_cnt % 10
										);

										$moreClick = " onclick=\"showHiddenDestination('".$arResult["Post"]["ID"]."', this)\"";
										$strTopic .= "<span class=\"post-destination-more\"".$moreClick." ontouchstart=\"BX.toggleClass(this, 'post-destination-more-touch');\" ontouchend=\"BX.toggleClass(this, 'post-destination-more-touch');\">&nbsp;".GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))."</span><span id=\"blog-destination-hidden-".$arResult["Post"]["ID"]."\" style=\"display:none;\">";
									}

									$className = "post-item-destination ".(
										$val["NAME"] === "All"
											? "post-item-dest-all-users"
											: "post-item-dest-users".(
											(isset($val["IS_EXTRANET"]) && $val["IS_EXTRANET"] == "Y")
											|| (isset($val["IS_EMAIL"]) && $val["IS_EMAIL"] == "Y")
												? " post-item-dest-users-external"
												: ""
											)
										);

									$strTopic .= ($i != 1 ? ", " : " ").(
										$val["NAME"] !== "All"
											? '<a href="'.$val["URL"].'" class="'.$className.'">'.$val["NAME"].'</a>'
											: '<span class="'.$className.'">'.GetMessage("BLOG_DESTINATION_ALL").'</span>'
										);
								}
							}

							if(!empty($arResult["Post"]["SPERM"]["SG"]))
							{
								foreach($arResult["Post"]["SPERM"]["SG"] as $id => $val)
								{
									$i++;
									if ($i == 4)
									{
										$more_cnt = $cnt + intval($arResult["Post"]["SPERM_HIDDEN"]) - 3;
										$suffix = (
										($more_cnt % 100) > 10
										&& ($more_cnt % 100) < 20
											? 5
											: ($more_cnt % 10)
										);

										$moreClick = " onclick=\"showHiddenDestination('".$arResult["Post"]["ID"]."', this)\"";
										$strTopic .= "<span class=\"post-destination-more\"".$moreClick." ontouchstart=\"BX.toggleClass(this, 'post-destination-more-touch');\" ontouchend=\"BX.toggleClass(this, 'post-destination-more-touch');\">&nbsp;".GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))."</span><span id=\"blog-destination-hidden-".$arResult["Post"]["ID"]."\" style=\"display:none;\">";
									}

									$strTopic .= ($i != 1 ? ", " : " ").'<a href="'.$val["URL"].'" class="post-item-destination post-item-dest-sonetgroups">'.$val["NAME"].'</a>';
								}
							}

							if(!empty($arResult["Post"]["SPERM"]["DR"]))
							{
								foreach($arResult["Post"]["SPERM"]["DR"] as $id => $val)
								{
									$i++;
									if($i == 4)
									{
										$more_cnt = $cnt + intval($arResult["Post"]["SPERM_HIDDEN"]) - 3;
										$suffix = (
										($more_cnt % 100) > 10
										&& ($more_cnt % 100) < 20
											? 5
											: $more_cnt % 10
										);

										$moreClick = " onclick=\"showHiddenDestination('".$arResult["Post"]["ID"]."', this)\"";
										$strTopic .= "<span class=\"post-destination-more\"".$moreClick." ontouchstart=\"BX.toggleClass(this, 'post-destination-more-touch');\" ontouchend=\"BX.toggleClass(this, 'post-destination-more-touch');\">&nbsp;".GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))."</span><span id=\"blog-destination-hidden-".$arResult["Post"]["ID"]."\" style=\"display:none;\">";
									}

									$strTopic .= ($i != 1 ? ", " : " ").'<span class="post-item-destination post-item-dest-department">'.$val["NAME"].'</span>';
								}
							}

							if(!empty($arResult["Post"]["SPERM"]["CRMCONTACT"]))
							{
								foreach($arResult["Post"]["SPERM"]["CRMCONTACT"] as $id => $val)
								{
									$i++;
									if($i == 4)
									{
										$more_cnt = $cnt + intval($arResult["Post"]["SPERM_HIDDEN"]) - 3;
										$suffix = (
										($more_cnt % 100) > 10
										&& ($more_cnt % 100) < 20
											? 5
											: $more_cnt % 10
										);

										$moreClick = " onclick=\"showHiddenDestination('".$arResult["Post"]["ID"]."', this)\"";
										$strTopic .= "<span class=\"post-destination-more\"".$moreClick." ontouchstart=\"BX.toggleClass(this, 'post-destination-more-touch');\" ontouchend=\"BX.toggleClass(this, 'post-destination-more-touch');\">&nbsp;".GetMessage("BLOG_DESTINATION_MORE_".$suffix, Array("#NUM#" => $more_cnt))."</span><span id=\"blog-destination-hidden-".$arResult["Post"]["ID"]."\" style=\"display:none;\">";
									}

									$strTopic .= ($i != 1 ? ", " : " ").
										(
										!empty($val["CRM_PREFIX"])
											? ' <span class="post-item-dest-crm-prefix">'.$val["CRM_PREFIX"].':&nbsp;</span>'
											: ''
										).
										'<a href="'.$val["URL"].'" class="post-item-destination">'.$val["NAME"].'</a>';
								}
							}

							if (
								isset($arResult["Post"]["SPERM_HIDDEN"])
								&& intval($arResult["Post"]["SPERM_HIDDEN"]) > 0
							)
							{
								$suffix = (
								($arResult["Post"]["SPERM_HIDDEN"] % 100) > 10
								&& ($arResult["Post"]["SPERM_HIDDEN"] % 100) < 20
									? 5
									: ($arResult["Post"]["SPERM_HIDDEN"] % 10)
								);
								$strTopic .= '&nbsp;<span class="post-item-destination">'.GetMessage("BLOG_DESTINATION_HIDDEN_".$suffix, Array("#NUM#" => intval($arResult["Post"]["SPERM_HIDDEN"]))).'</span>';
							}
						}

						?><div class="post-item-top-topic"><?=$strTopic ?></div><?php

						?><div class="lenta-item-time" id="datetime_block_detail_<?=$arParams["LOG_ID"]?>"><?php
							echo \CComponentUtil::getDateTimeFormatted([
								'TIMESTAMP' => MakeTimeStamp($arResult["Post"]["DATE_PUBLISH"]),
								'TZ_OFFSET' => $arResult["TZ_OFFSET"]
							]);
						?></div><?php

					?></div><?php // post-item-top-cont

					$rightCornerNodeClassesList = [ 'lenta-item-right-corner' ];
					if ($arResult['MOBILE_API_VERSION'] >= 34)
					{
						$rightCornerNodeClassesList[] = 'lenta-item-right-corner-menu';
					}

					?><div class="<?=implode(' ', $rightCornerNodeClassesList)?>"><?php

						$useFavorites = (
							!$arResult["ReadOnly"]
							&& (
								!isset($arParams["USE_FAVORITES"])
								|| $arParams["USE_FAVORITES"] !== "N"
							)
						);

						$useFollow = (
							!$arResult["ReadOnly"]
							&& (
								!isset($arParams["USE_FOLLOW"])
								|| $arParams["USE_FOLLOW"] !== 'N'
							)
						);

						$usePinned = (
							!$arResult["ReadOnly"]
							&& $USER->isAuthorized()
						);

						$favoritesValue = (
							$useFavorites
							&& isset($arParams["FAVORITES"])
							&& $arParams["FAVORITES"] === "Y"
						);

						$followValue = (
							$useFollow
							&& (
								!isset($arParams["FOLLOW"])
								|| $arParams["FOLLOW"] !== 'N'
							)
						);

						$pinnedValue = (
							$usePinned
							&& isset($arParams["PINNED"])
							&& $arParams["PINNED"] === 'Y'
						);

						if ($arResult['MOBILE_API_VERSION'] >= 34)
						{
							$menuClasses = [
								'lenta-menu'
							];
							if ($usePinned)
							{
								$menuClasses[] = 'lenta-menu-use-pinned';
							}

							?><div
								id="log-entry-menu-<?=(int)$arParams["LOG_ID"]?>"
								data-menu-type="post"
								data-log-id="<?=(int)$arParams['LOG_ID']?>"
								data-post-id="<?=(int)$arResult['Post']['ID']?>"
								data-post-perm="<?=\CUtil::jSEscape($arResult["PostPerm"])?>"
								data-use-favorites="<?=($useFavorites ? "Y" : "N")?>"
								data-favorites="<?=($favoritesValue ? "Y" : "N")?>"
								data-use-pinned="<?=($usePinned ? "Y" : "N")?>"
								data-pinned="<?=($pinnedValue ? "Y" : "N")?>"
								data-use-follow="<?=($useFollow ? "Y" : "N")?>"
								data-follow="<?=($followValue ? "Y" : "N")?>"
								data-content-type-id="BLOG_POST"
								data-content-id="<?=(int)$arResult['Post']['ID']?>"
								class="<?=implode(' ', $menuClasses)?>"
								onclick="oMSL.showPostMenu(event)"
							>
								<div class="lenta-menu-item"></div>
							</div><?php

							if ($usePinned)
							{
								?><div class="lenta-item-pin"></div><?php
							}
						}
						else
						{
							if ($useFavorites)
							{
								?><div
									id="log_entry_favorites_<?=$arParams["LOG_ID"]?>"
									data-favorites="<?=($favoritesValue ? "Y" : "N")?>"
									class="lenta-item-fav<?=($favoritesValue ? " lenta-item-fav-active" : "")?>"
									onclick="__MSLSetFavorites(<?=$arParams["LOG_ID"]?>, this, event);"
								></div><?php
							}
							else
							{
								?><div class="lenta-item-fav-placeholder"></div><?php
							}
						}

					?></div><?php

				?></div><?php

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
					$postItemClassList[] = 'post-item-block-inner';
					$postItemClassList[] = 'post-item-contentview';
				}

				if (in_array($arParams["EVENT_ID"], [ 'blog_post_important', 'blog_post_grat']))
				{
					$postItemClassList[] = 'info-block-background';
					if ($arResult["Post"]["MICRO"] !== "Y")
					{
						$postItemClassList[] = 'info-block-background-with-title';
					}

					if ($arParams["EVENT_ID"] === 'blog_post_important')
					{
						$postItemClassList[] = 'info-block-important';
					}
					elseif (
						$arParams["EVENT_ID"] === 'blog_post_grat'
						&& isset($arResult['GRATITUDE'])
						&& isset($arResult['GRATITUDE']['TYPE'])
						&& !empty($arResult['GRATITUDE']['TYPE']['XML_ID'])
					)
					{
						$postItemClassList[] = 'info-block-gratitude';
						$postItemClassList[] = 'info-block-gratitude-'.$arResult['GRATITUDE']['TYPE']['XML_ID'];
					}
				}
				elseif ($arResult['Post']['BACKGROUND_CODE'] !== '')
				{
					$postItemClassList[] = 'ui-livefeed-background';
					$postItemClassList[] = 'post-item-post-block-full';
					$postItemClassList[] = 'ui-livefeed-background-'.preg_replace(['/(\d+)_/', '/_/'], ['', '-'], $arResult['Post']['BACKGROUND_CODE']);
				}

				?><div
				 class="<?=implode(' ', $postItemClassList)?>"
				 id="post_block_check_cont_<?=$arParams["LOG_ID"]?>"
				 bx-data-has-title="<?=$arResult['Post']['MICRO'] !== 'Y' ? 'Y' : 'N'?>"
				 bx-content-view-xml-id="<?= $arResult['CONTENT_ID'] ?>"
				 bx-content-view-key-signed="<?= htmlspecialcharsbx($arResult['CONTENT_VIEW_KEY_SIGNED']) ?>"><?php

					$importantIcon = ($arParams['EVENT_ID'] === 'blog_post_important' ? '<div class="info-block-important-icon"></div>' : '');

					if (
						isset($arParams['TARGET'])
						&& $arParams['TARGET'] === 'postContent'
					)
					{
						$targetHtml = '';
						ob_start();
					}

					if($arResult["Post"]["MICRO"] !== "Y")
					{
						$titleClassList = [ 'post-text-title' ];
						if ($arParams['EVENT_ID'] === 'blog_post_important')
						{
							$titleClassList[] = 'lenta-important-block-title';
						}

						?><div class="post-text-title-container"><?php
							?><div class="<?=implode(' ', $titleClassList)?>" id="post_text_title_<?=(int)$arParams["LOG_ID"]?>"><?=$arResult["Post"]["TITLE"]?></div><?php
							?><?=$importantIcon;?><?php
						?></div><?php
					}
					else
					{
						?><?=$importantIcon?><?php
					}

					$textClassList = [
						'post-item-full-content',
						'post-item-text',
						'post-item-copytext'
					];

					if ($arParams['EVENT_ID'] === 'blog_post_important')
					{
						$textClassList[] = 'lenta-important-block-text';
					}

					?><div class="<?= implode(' ', $textClassList) ?>" id="post_block_check_<?= (int)$arParams['LOG_ID'] ?>"><?= $arResult['Post']['textFormated'] ?><?php

						if (!empty($arResult['Post']['IMPORTANT']))
						{
							?><div class="post-item-important">
								<input id="important_post_<?= $arResult['Post']['ID'] ?>" bx-data-post-id="<?= $arResult['Post']['ID'] ?>" type="checkbox" onclick="return BX.MobileLivefeed.ImportantManagerInstance.setPostRead(this);" <?php if ($arResult['Post']['IMPORTANT']['IS_READ'] === 'Y') { ?>checked="checked" <?php } ?>/>
								<span class="checked webform-small-button"><?= Loc::getMessage('BLOG_ALREADY_READ') ?></span>
								<label for="important_post_<?= $arResult['Post']['ID'] ?>" class="unchecked ui-btn ui-btn-md ui-btn-success ui-btn-round"><?= Loc::getMessage(trim('BLOG_READ_'.$arResult['Post']['IMPORTANT']['USER']['PERSONAL_GENDER'])) ?></label><?php

								$readUsersCount = (int)$arResult['Post']['IMPORTANT']['COUNT'];

								?><div class="post-item-important-list"><?php
									?><div class="post-item-important-list-text post-item-important-list-text-current"><?= Loc::getMessage('BLOG_IMPORTANT_READ_LIST_' . ($readUsersCount % 10), [ '#NUM#' => $readUsersCount ]) ?></div><?php
									?><div class="post-item-important-list-text post-item-important-list-text-plus"><?= Loc::getMessage('BLOG_IMPORTANT_READ_LIST_' . (($readUsersCount+1) % 10), [ '#NUM#' => ($readUsersCount+1) ]) ?></div><?php
								?></div><?php
							?></div><?php
						}

					?></div><?php

					if (!empty($arResult["images"]))
					{
						?><div class="post-item-attached-img-wrap"><?php
							$jsIds = "";
							foreach($arResult["images"] as $val)
							{
								$id = "blog-post-attached-".mb_strtolower(randString(5));
								$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
								?><div class="post-item-attached-img-block"><img class="post-item-attached-img" id="<?=$id?>" src="<?=CMobileLazyLoad::getBase64Stub()?>" data-src="<?=$val["small"]?>" data-bx-image="<?=$val["full"]?>" alt="" border="0"></div><?php
							}
						?></div><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>], oMSL.checkVisibility);</script><?php
					}

					$showPostProperties = ($arResult['POST_PROPERTIES']['SHOW'] === "Y");
					if(
						$showPostProperties
						|| !empty($arResult["GRATITUDE"])
					)
					{
						?><div class="post-item-attached-file-wrap" id="post_block_check_files_<?=$arParams["LOG_ID"]?>"><?php

						if($showPostProperties)
						{
							$eventHandlerID = AddEventHandler('main', 'system.field.view.file', '__blogUFfileShowMobile');
							foreach ($arResult["POST_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField)
							{
								if(!empty($arPostField["VALUE"]))
								{
									$APPLICATION->IncludeComponent(
										"bitrix:system.field.view",
										$arPostField["USER_TYPE"]["USER_TYPE_ID"],
										array(
											"arUserField" => $arPostField,
											"ACTION_PAGE" => str_replace("#log_id#", $arParams["LOG_ID"], $arParams["PATH_TO_LOG_ENTRY"]),
											"MOBILE" => "Y",
											"VIEW_MODE" => ($arParams["IS_LIST"] ? "BRIEF" : "EXTENDED")
										),
										null,
										array("HIDE_ICONS"=>"Y")
									);
								}
							}
							if ((int)$eventHandlerID > 0)
							{
								RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
							}
						}

						if (!empty($arResult["URL_PREVIEW"]))
						{
							?><?=$arResult["URL_PREVIEW"]?><?php
						}

						if (!empty($arResult["GRATITUDE"]))
						{
							?><div class="lenta-info-block lenta-block-grat"><?php
								?><div class="lenta-block-grat-medal<?=($arResult["GRATITUDE"]["TYPE"]["XML_ID"] <> '' ? " lenta-block-grat-medal-".$arResult["GRATITUDE"]["TYPE"]["XML_ID"] : "")?>"></div><?php
								?><div class="lenta-block-grat-users"><?php
									$jsIds = "";

									$hiddenBlock = '';
									$smallGratUsersList = [];
									$counter = 0;
									$counterLimit = (count($arResult['GRATITUDE']['USERS_FULL']) === 3 ? 3 : 2);

									foreach($arResult["GRATITUDE"]["USERS_FULL"] as $arGratUser)
									{
										$avatarId = "lenta-block-grat-".randString(5);
										if($arGratUser["AVATAR_SRC"])
										{
											$jsIds .= $jsIds !== "" ? ', "'.$avatarId.'"' : '"'.$avatarId.'"';
										}

										if ($counter >= $counterLimit)
										{
											ob_start();
										}

										?><div class="lenta-block-grat-user">
											<div class="lenta-new-grat-avatar">
												<div class="avatar" id="<?=$avatarId?>"<?php if($arGratUser["AVATAR_SRC"]) { ?> data-src="<?=$arGratUser["AVATAR_SRC"]?>"<?php } ?>></div>
											</div>
											<div class="lenta-info-block-content">
												<div class="lenta-important-block-title"><a href="<?=($arGratUser['URL'] ? $arGratUser['URL'] : 'javascript:void(0);')?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arGratUser)?></a></div>
												<div class="lenta-important-block-text"><?=htmlspecialcharsbx($arGratUser["WORK_POSITION"])?></div>
											</div>
										</div><?php

										if ($counter >= $counterLimit)
										{
											$hiddenBlock .= ob_get_clean();
										}

										if ($counter >= $counterLimit)
										{
											$smallGratUsersList[] = $arGratUser;
										}

										$counter++;
									}

									if (!empty($smallGratUsersList))
									{
										$counterLimit = 6;
										$moreCount = count($smallGratUsersList) - $counterLimit;
										if ($moreCount > 0)
										{
											$smallGratUsersList = array_slice($smallGratUsersList, 0, $counterLimit);
										}
										$smallGratUsersList = array_reverse($smallGratUsersList);

										?><div class="lenta-block-grat-users-small-cont"><?php
											?><div class="lenta-block-grat-users-small"><?php
												$counter = 1;
												foreach($smallGratUsersList as $arGratUser)
												{
													$avatarId = "lenta-block-grat-small-".randString(5);

													if($arGratUser["AVATAR_SRC"])
													{
														$jsIds .= $jsIds !== "" ? ', "'.$avatarId.'"' : '"'.$avatarId.'"';
													}

													?><div class="avatar avatar-small" id="<?=$avatarId?>"<?php if($arGratUser["AVATAR_SRC"]) { ?> data-src="<?=$arGratUser["AVATAR_SRC"]?>"<?php } ?>></div><?php
													$counter++;
												}
											?></div><?php

											if ($moreCount > 0)
											{
												?><div class="lenta-block-grat-users-small-more">+<?=$moreCount?></div><?php
											}
										?></div><?php
										?><div class="lenta-block-grat-users-small-hidden" style="display: none"><?php
											?><?=$hiddenBlock?><?php
										?></div><?php
									}
								?></div><?php
							?></div><?php

							if($jsIds <> '')
							{
								?><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>]);</script><?php
							}
						}

						?></div><?php
					}

					$postMoreBlockStyle = (
						isset($arParams['TARGET'])
						&& $arParams['TARGET'] === 'postContent'
							? 'style="display: none;"'
							: ''
					);
					?><div class="post-more-block" id="post_more_block_<?=$arParams["LOG_ID"]?>"<?=$postMoreBlockStyle?>></div><?php

					if (
						isset($arParams['TARGET'])
						&& $arParams['TARGET'] === 'postContent'
					)
					{
						$targetHtml = ob_get_contents();
					}

				?></div><?php // post-item-post-block, post_block_check_cont_..

				if(!empty($arResult["UF_FILE"]))
				{
					?><div id="post_block_files_<?=$arParams["LOG_ID"]?>" class="post-item-attached-file-wrap post-item-attached-disk-file-wrap"><?php

					$eventHandlerID = AddEventHandler('main', 'system.field.view.file', '__blogUFfileShowMobile');
					$arPostField = $arResult["UF_FILE"];

					if (!empty($arPostField["VALUE"]))
					{
						$APPLICATION->IncludeComponent(
							"bitrix:system.field.view",
							$arPostField["USER_TYPE"]["USER_TYPE_ID"],
							array(
								"arUserField" => $arPostField,
								"ACTION_PAGE" => str_replace("#log_id#", $arParams["LOG_ID"], $arParams["PATH_TO_LOG_ENTRY"]),
								"MOBILE" => "Y",
								"GRID" => ($arResult['Post']['hasInlineDiskFile'] || !empty($arResult['Post']['BACKGROUND_CODE']) ? 'N' : 'Y'),
								"USE_TOGGLE_VIEW" => ($arResult["PostPerm"] >= 'W' ? 'Y' : 'N'),
								"VIEW_MODE" => ($arParams["IS_LIST"] ? "BRIEF" : "EXTENDED")
							),
							null,
							array("HIDE_ICONS"=>"Y")
						);
					}

					if (
						$eventHandlerID !== false
						&& ((int)$eventHandlerID > 0)
					)
					{
						RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
					}

					?></div><?php
				}

				if ($arResult["is_ajax_post"] !== "Y")
				{
					ob_start();
				}

				?><div class="post-item-inform-wrap-left"><?php

				if (
					$arParams["SHOW_RATING"] === "Y"
					&& (
						!isset($arParams['TARGET'])
						|| $arParams["TARGET"] !== 'postContent'
					)
				)
				{
					$voteId = 'BLOG_POST' . '_' . $arResult['Post']['ID'] . '-' . (time() + random_int(0, 1000));
					$emotion = (
						!empty($arResult['RATING'][$arResult['Post']['ID']]['USER_REACTION'])
							? mb_strtoupper($arResult['RATING'][$arResult['Post']['ID']]['USER_REACTION'])
							: 'LIKE'
					);

					?><span
					 class="post-item-informers bx-ilike-block"
					 id="rating_block_<?=$arParams['LOG_ID']?>"
					 data-counter="<?= (int)$arResult['RATING'][$arResult['Post']['ID']]['TOTAL_VOTES'] ?>"
					><?php
						?><span
						 data-rating-vote-id="<?= htmlspecialcharsbx($voteId) ?>"
						 data-rating-entity-type-id="BLOG_POST"
						 data-rating-entity-id="<?= (int)$arResult['Post']['ID'] ?>"
						 id="bx-ilike-button-<?= htmlspecialcharsbx($voteId) ?>"
						 class="post-item-informer-like feed-inform-ilike"
						><?php
							$likeClassList = [ 'bx-ilike-left-wrap' ];
							if (
								isset($arResult["RATING"])
								&& isset($arResult["RATING"][$arResult["Post"]["ID"]])
								&& isset($arResult["RATING"][$arResult["Post"]["ID"]]["USER_HAS_VOTED"])
								&& $arResult["RATING"][$arResult["Post"]["ID"]]["USER_HAS_VOTED"] == "Y"
							)
							{
								$likeClassList[] = 'bx-you-like-button';
								$likeClassList[] = 'bx-you-like-button-'.mb_strtolower($emotion);
							}
							?><span class="<?=implode(' ', $likeClassList)?>"><?php
								?><span class="bx-ilike-icon-new"></span><?php
								?><span class="bx-ilike-text"><?=\CRatingsComponentsMain::getRatingLikeMessage($emotion)?></span><?php
							?></span><?php
						?></span><?php
					?></span><?php
				}

				if (
					$arResult["Post"]["ENABLE_COMMENTS"] === "Y"
					&& (
						!isset($arParams['TARGET'])
						|| !in_array($arParams["TARGET"], [ 'postContent' ])
					)
				)
				{
					$bHasComments = true;

					if ($arResult["is_ajax_post"] !== "Y")
					{
						ob_start(); // inner buffer
					}

					if ($arResult["GetCommentsOnly"])
					{
						$APPLICATION->RestartBuffer();
					}

					$arCommentsResult = $APPLICATION->IncludeComponent(
						"bitrix:socialnetwork.blog.post.comment",
						".default",
						Array(
							"PATH_TO_BLOG" => $arParams["PATH_TO_BLOG"],
							"PATH_TO_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
							"PATH_TO_POST_MOBILE" => $APPLICATION->GetCurPageParam("", array("LAST_LOG_TS")),
							"PATH_TO_USER" => $arParams["PATH_TO_USER"],
							"PATH_TO_SMILE" => $arParams["PATH_TO_SMILE"],
							"PATH_TO_MESSAGES_CHAT" => $arParams["PATH_TO_MESSAGES_CHAT"],
							"ID" => $arResult["Post"]["ID"],
							"LOG_ID" => $arParams["LOG_ID"],
							"CACHE_TIME" => $arParams["CACHE_TIME"],
							"CACHE_TYPE" => $arParams["CACHE_TYPE"],
							"COMMENTS_COUNT" => "5",
							"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
							"USER_ID" => $GLOBALS["USER"]->GetID(),
							"SONET_GROUP_ID" => $arParams["SONET_GROUP_ID"],
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
							"ALLOW_VIDEO"  => $arParams["ALLOW_VIDEO"],
							"ALLOW_IMAGE_UPLOAD" => $arParams["BLOG_COMMENT_ALLOW_IMAGE_UPLOAD"],
							"ALLOW_POST_CODE" => $arParams["ALLOW_POST_CODE"],
							"AJAX_POST" => "Y",
							"POST_DATA" => $arResult["PostSrc"],
							"BLOG_DATA" => $arResult["Blog"],
							"FROM_LOG" => ($arParams["IS_LIST"] ? "Y" : false),
							"bFromList" => ($arParams["IS_LIST"] ? true: false),
							"LAST_LOG_TS" => $arParams["LAST_LOG_TS"],
							"AVATAR_SIZE" => $arParams["AVATAR_SIZE_COMMENT"],
							"MOBILE" => "Y",
							"ATTACHED_IMAGE_MAX_WIDTH_FULL" => 640,
							"ATTACHED_IMAGE_MAX_HEIGHT_FULL" => 832,
							"CAN_USER_COMMENT" => (!isset($arResult["CanComment"]) || $arResult["CanComment"] ? 'Y' : 'N'),
							"NAV_TYPE_NEW" => "Y",
							"SITE_TEMPLATE_ID" => (!empty($arParams["SITE_TEMPLATE_ID"]) ? $arParams["SITE_TEMPLATE_ID"] : ''),
							'UNREAD_BLOG_COMMENT_ID' => ($arParams['UNREAD_BLOG_COMMENT_ID'] ?? []),
							'MARK_NEW_COMMENTS' => 'Y',
						),
						$component,
						array("HIDE_ICONS" => "Y")
					);

//					$strCommentsBlock = ob_get_contents();
					$strCommentsBlock = (!$arParams["IS_LIST"] ? ob_get_contents() : "");

					ob_end_clean(); // inner buffer

					if ($arResult["GetCommentsOnly"])
					{
						?><?=$strCommentsBlock?><?php
						die();
					}

					if (!$arParams["IS_LIST"]) // detail, non-empty
					{
						$onClick = '';
						if ($arResult['CanComment'])
						{
							$onClick = 'onclick="BX.MobileLivefeed.CommentsInstance.setFocusOnCommentForm();"';
						}
						?><div id="comments_control_<?=$arParams["LOG_ID"]?>" class="post-item-informers post-item-inform-comments" <?= $onClick ?>"><?php
							?><div class="post-item-inform-comments-box"><?php
								?><span class="post-item-inform-icon"></span><?php
								?><div class="post-item-inform-left"><?=Loc::getMessage('BLOG_MOBILE_COMMENTS_ACTION')?></div><?php
							?></div><?php
						?></div><?php
					}
					else
					{
						$showNewComments = (
							(
								$arParams["USE_FOLLOW"] != "Y"
								|| $arParams["FOLLOW"] == "Y"
							)
							&& (int)$arCommentsResult["newCountWOMark"] > 0
						);

						$commentsBlockClassList = [
							'post-item-informers',
							'post-item-inform-comments'
						];
						if ($showNewComments)
						{
							$commentsBlockClassList[] = 'post-item-inform-likes-active';
						}

						?><div id="comments_control_<?=$arParams["LOG_ID"]?>" class="<?=implode(' ', $commentsBlockClassList)?>"><?php
							?><div class="post-item-inform-comments-box"><?php
								?><span class="post-item-inform-icon"></span><?php

								$num_comments = (isset($arParams["COMMENTS_COUNT"]) ? $arParams["COMMENTS_COUNT"] : intval($arResult["Post"]["NUM_COMMENTS"]));

								?><div class="post-item-inform-left" id="informer_comments_text2_<?=$arParams["LOG_ID"]?>" style="display: <?=($num_comments > 0 ? "inline-block" : "none")?>;"><?php
									?><?=Loc::getMessage('BLOG_MOBILE_COMMENTS')?><?php // BLOG_MOBILE_COMMENTS_2
								?></div><?php
								?><div class="post-item-inform-left" id="informer_comments_text_<?=$arParams["LOG_ID"]?>" style="display: <?=($num_comments <= 0 ? "inline-block" : "none")?>;"><?php
									?><?=Loc::getMessage('BLOG_MOBILE_COMMENTS')?><?php
								?></div><?php

								?><div class="post-item-inform-right" id="informer_comments_<?=$arParams["LOG_ID"]?>" style="display: <?=($num_comments > 0 ? 'inline-block' : 'none')?>;"><?php
									if ($showNewComments)
									{
										?><span id="informer_comments_all_<?=$arParams["LOG_ID"]?>"><?php
											$old_comments = (int)abs($num_comments - intval($arCommentsResult["newCountWOMark"]));
											?><?=($old_comments > 0 ? $old_comments : '')?><?php
										?></span><?php
										?><span id="informer_comments_new_<?=$arParams["LOG_ID"]?>" class="post-item-inform-right-new"><?php
											?><span class="post-item-inform-right-new-sign">+</span><?php
											?><span class="post-item-inform-right-new-value"><?= (int)$arCommentsResult["newCountWOMark"] ?></span><?php
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
				}
				else
				{
					$bHasComments = false;
				}

				if ($bHasComments)
				{
					?><div id="log_entry_follow_<?=(int)$arParams["LOG_ID"]?>" data-follow="<?=($arParams["FOLLOW"] === "Y" ? "Y" : "N")?>" style="display: none;"></div><?php
				}

				if ($arResult["bTasksAvailable"])
				{
					?><div id="log_entry_use_tasks_<?=(int)$arParams["LOG_ID"]?>" data-use-tasks="Y" style="display: none;"></div><?php
				}

				?></div><?php // class="post-item-inform-wrap-left"

				?><div class="post-item-inform-wrap-right"><?php
					?><a id="post_more_limiter_<?=(int)$arParams["LOG_ID"]?>" class="post-item-more" ontouchstart="this.classList.toggle('post-item-more-pressed')" ontouchend="this.classList.toggle('post-item-more-pressed')" style="visibility: hidden;"><?php
						?><?=GetMessage("BLOG_LOG_EXPAND")?><?php
					?></a><?php
				?></div><?php

				?><script>
					BX.ready(function() {
						if (window.arCanUserComment)
						{
							window.arCanUserComment[<?=$arParams["LOG_ID"]?>] = <?=($arCommentsResult && $arCommentsResult["CanUserComment"] ? "true" : "false")?>;
						}
					});
				</script><?php

				$strBottomBlock = ob_get_clean();

				if (
					$arParams["SHOW_RATING"] === "Y"
					&& (
						!isset($arParams['TARGET'])
						|| $arParams['TARGET'] !== 'postContent'
					)
				)
				{
					?><div class="post-item-inform-wrap-tree" id="<?=(!$arParams["IS_LIST"] ? 'rating-footer-wrap' : 'rating-footer-wrap_'. (int)$arParams["LOG_ID"]) ?>"><?php
						?><div class="feed-post-emoji-top-panel-outer"><?php
							?><div id="feed-post-emoji-top-panel-container-<?=htmlspecialcharsbx($voteId)?>" class="feed-post-emoji-top-panel-box <?= ((int)$arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_POSITIVE_VOTES"] > 0 ? 'feed-post-emoji-top-panel-container-active' : '') ?>"><?php
								$APPLICATION->IncludeComponent(
									"bitrix:rating.vote",
									"like_react",
									array(
										"MOBILE" => "Y",
										"ENTITY_TYPE_ID" => "BLOG_POST",
										"ENTITY_ID" => $arResult["Post"]["ID"],
										"OWNER_ID" => $arResult["Post"]["AUTHOR_ID"],
										"USER_VOTE" => $arResult["RATING"][$arResult["Post"]["ID"]]["USER_VOTE"],
										"USER_REACTION" => $arResult["RATING"][$arResult["Post"]["ID"]]["USER_REACTION"],
										"USER_HAS_VOTED" => $arResult["RATING"][$arResult["Post"]["ID"]]["USER_HAS_VOTED"],
										"TOTAL_VOTES" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_VOTES"],
										"TOTAL_POSITIVE_VOTES" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_POSITIVE_VOTES"],
										"TOTAL_NEGATIVE_VOTES" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_NEGATIVE_VOTES"],
										"TOTAL_VALUE" => $arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_VALUE"],
										"REACTIONS_LIST" => $arResult["RATING"][$arResult["Post"]["ID"]]["REACTIONS_LIST"],
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
					?><?=$strBottomBlock;?><?php
				?></div><?php
			}

			?></div><?php // post-item-top-wrap

			if (
				$arResult["Post"]["ENABLE_COMMENTS"] == "Y"
				&& $strCommentsBlock <> ''
			)
			{
				?><?=$strCommentsBlock?><?php
			}

		?></div><?php // post-wrap / lenta-item
	}
}
elseif(!$arResult["bFromList"])
{
	echo GetMessage("BLOG_BLOG_BLOG_NO_AVAIBLE_MES");
}

if ($targetHtml <> '')
{
	$APPLICATION->RestartBuffer();
	echo $targetHtml;
}
