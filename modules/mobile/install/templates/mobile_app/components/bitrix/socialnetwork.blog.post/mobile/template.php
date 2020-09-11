<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

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
	if ($_REQUEST["empty_get_comments"] == "Y")
	{
		$APPLICATION->IncludeComponent(
			"bitrix:socialnetwork.blog.post.comment",
			".default",
			Array(
				"PATH_TO_BLOG" => $arParams["PATH_TO_BLOG"],
				"PATH_TO_POST" => "/company/personal/user/#user_id#/blog/#post_id#/",
				"PATH_TO_POST_MOBILE" => $APPLICATION->GetCurPageParam("", array("LAST_LOG_TS", "empty_get_comments", "empty_get_form")),
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
				"MARK_NEW_COMMENTS" => "Y",
				"AVATAR_SIZE" => $arParams["AVATAR_SIZE_COMMENT"],
				"MOBILE" => "Y",
				"ATTACHED_IMAGE_MAX_WIDTH_FULL" => 640,
				"ATTACHED_IMAGE_MAX_HEIGHT_FULL" => 832,
				"CAN_USER_COMMENT" => (!isset($arResult["CanComment"]) || $arResult["CanComment"] ? 'Y' : 'N'),
				"NAV_TYPE_NEW" => "Y"
			),
			$component,
			array("HIDE_ICONS" => "Y")
		);
	}
	else
	{

		if (
			$_REQUEST["empty_get_form"] == "Y"
			|| !$arParams["IS_LIST"]
		)
		{
			$commentsFormBlock = "";
			ob_start();

			if(
				$_REQUEST["empty_get_form"] == "Y"
				|| intval($_REQUEST["comment_post_id"]) <= 0
			)
			{
				?><script>
					commentVarBlogPostID = <?=intval($arResult["Post"]["ID"])?>;
					commentVarURL = '<?=$GLOBALS["APPLICATION"]->GetCurPageParam("", array("sessid", "comment_post_id", "act", "post", "comment", "decode", "ACTION", "ENTITY_TYPE_ID", "ENTITY_ID", "empty_get_form", "empty_get_comments"))?>';
					commentVarLogID = <?=intval($arParams["LOG_ID"])?>;
					<?
					if (
						$_REQUEST["ACTION"] == "CONVERT"
						&& $_REQUEST["ENTITY_TYPE_ID"] <> ''
						&& intval($_REQUEST["ENTITY_ID"]) > 0
					)
					{
						?>
						commentVarAction = 'CONVERT';
						commentVarEntityTypeID = '<?=CUtil::JSEscape($_REQUEST["ENTITY_TYPE_ID"])?>';
						commentVarEntityID = <?=intval($_REQUEST["ENTITY_ID"])?>;
						<?
					}
					else
					{
						?>
						commentVarAction = false;
						commentVarEntityTypeID = false;
						commentVarEntityID = false;
						<?
					}
					?>

					entryType = 'blog';

					oMSL.createCommentInputForm({
						placeholder: "<?=GetMessageJS("BLOG_C_ADD_TITLE")?>",
						mentionDataSource: {outsection: false,
							url: (BX.message('MobileSiteDir')
								? BX.message('MobileSiteDir') : '/') + "mobile/index.php?mobile_action=get_user_list&use_name_format=Y"
						},
						button_name: "<?=GetMessageJS("BLOG_C_BUTTON_SEND")?>",
						useImageButton: true,
						action: function(text)
						{
							commonNativeInputCallback(text);
						}
					});
				</script><?
			}

			$commentsFormBlock = ob_get_contents();
			ob_end_clean();
		}

		if ($_REQUEST["empty_get_form"] == "Y")
		{
			$APPLICATION->RestartBuffer();
			echo $commentsFormBlock;
			die();
		}

		$item_class = (!$arParams["IS_LIST"] ? "post-wrap" : "lenta-item".($arParams["IS_UNREAD"] ? " lenta-item-new" : ""));

		if ($arParams["IS_LIST"])
		{
			?><script>
				if (window.arLogTs)
				{
					window.arLogTs.entry_<?=intval($arParams["LOG_ID"])?> = <?=intval($arParams["LAST_LOG_TS"])?>;
				}
			</script><?
		}
		else
		{
			?><script>
				BX.ready(function()
				{
					BX.MobileImageViewer.viewImageBind(
						'lenta_item_<?=intval($arParams["LOG_ID"])?>',
						'img[data-bx-image]'
					);

					oMSL.InitDetail({
						commentsType: 'blog',
						detailPageId: 'blog_' + <?=$arResult["Post"]["ID"]?>,
						logId: <?=intval($arParams["LOG_ID"])?>,
						entityXMLId: 'BLOG_<?=intval($arResult["Post"]["ID"])?>',
						bUseFollow: <?=($arParams["USE_FOLLOW"] == 'N' ? 'false' : 'true')?>,
						bFollow: <?=($arParams["FOLLOW"] == 'N' ? 'false' : 'true')?>,
						feed_id: parseInt(Math.random() * 100000),
						entryParams: {
							destinations: <?=CUtil::PhpToJSObject($arResult["Post"]["SPERM"])?>,
							post_perm: '<?=CUtil::JSEscape($arResult["PostPerm"])?>',
							post_id: <?=intval($arResult["Post"]["ID"])?>,
							post_content_type_id: 'BLOG_POST',
							post_content_id: <?=$arResult["Post"]["ID"]?>
						},
						readOnly: '<?=(!!$arResult["ReadOnly"] ? 'Y' : 'N')?>'
					});
				});	
			</script><?
		}

		?><div class="<?=($item_class)?>" id="lenta_item_<?=intval($arParams["LOG_ID"])?>"><?
			?><div 
				id="post_item_top_wrap_<?=intval($arParams["LOG_ID"])?>"
				class="post-item-top-wrap<?=($arParams["FOLLOW_DEFAULT"] == "N" && $arParams["FOLLOW"] == "Y" ? " post-item-follow" : "")?> post-item-copyable"
			><?
				?><div class="post-item-top" id="post_item_top_<?=intval($arParams["LOG_ID"])?>"><?
					$avatarId = "post_item_avatar_".intval($arParams["LOG_ID"]);
					?><div class="avatar" id="<?=$avatarId?>" <?if($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"] <> ''):?> data-src="<?=\CHTTP::urnEncode($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"])?>"<?endif?>></div><?

					if($arResult["arUser"]["PERSONAL_PHOTO_resized"]["src"] <> '')
					{
						?><script>BitrixMobile.LazyLoad.registerImage("<?=$avatarId?>");</script><?
					}

					?><div class="post-item-top-cont"><?

						$arTmpUser = array(
								"NAME" => $arResult["arUser"]["~NAME"],
								"LAST_NAME" => $arResult["arUser"]["~LAST_NAME"],
								"SECOND_NAME" => $arResult["arUser"]["~SECOND_NAME"],
								"LOGIN" => $arResult["arUser"]["~LOGIN"],
								"NAME_LIST_FORMATTED" => "",
							);
						?><a class="post-item-top-title<?=($arResult["arUser"]["isExtranet"] ? ' post-item-top-title-extranet' : '')?>" href="<?=$arResult["arUser"]["url"]?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arTmpUser, ($arParams["SHOW_LOGIN"] != "N" ? true : false))?></a><?

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

									$className = "post-item-destination ".(
										$val["NAME"] == "All"
											? "post-item-dest-all-users"
											: "post-item-dest-users".(
											(isset($val["IS_EXTRANET"]) && $val["IS_EXTRANET"] == "Y")
											|| (isset($val["IS_EMAIL"]) && $val["IS_EMAIL"] == "Y")
												? " post-item-dest-users-external"
												: ""
											)
										);

									$strTopic .= ($i != 1 ? ", " : " ").(
										$val["NAME"] != "All"
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

						?><div class="post-item-top-topic"><?=$strTopic ?></div><?

						?><div class="lenta-item-time" id="datetime_block_detail_<?=$arParams["LOG_ID"]?>"><?
							echo \CComponentUtil::getDateTimeFormatted([
								'TIMESTAMP' => MakeTimeStamp($arResult["Post"]["DATE_PUBLISH"]),
								'TZ_OFFSET' => $arResult["TZ_OFFSET"]
							]);
						?></div><?

					?></div><? // post-item-top-cont

					$rightCornerNodeClassesList = [ 'lenta-item-right-corner' ];
					if ($arResult['MOBILE_API_VERSION'] >= 34)
					{
						$rightCornerNodeClassesList[] = 'lenta-item-right-corner-menu';
					}

					?><div class="<?=implode(' ', $rightCornerNodeClassesList)?>"><?

						$useFavorites = (
							!$arResult["ReadOnly"]
							&& (
								!isset($arParams["USE_FAVORITES"])
								|| $arParams["USE_FAVORITES"] != "N"
							)
						);

						$useFollow = (
							!$arResult["ReadOnly"]
							&& (
								!isset($arParams["USE_FOLLOW"])
								|| $arParams["USE_FOLLOW"] != 'N'
							)
						);

						$favoritesValue = (
							$useFavorites
							&& isset($arParams["FAVORITES"])
							&& $arParams["FAVORITES"] == "Y"
						);

						$followValue = (
							$useFollow
							&& (
								!isset($arParams["FOLLOW"])
								|| $arParams["FOLLOW"] != 'N'
							)
						);

						if ($arResult['MOBILE_API_VERSION'] >= 34)
						{
							$menuClasses = [
								'lenta-menu'
							];

							if (
								!$useFavorites
							)
							{
								$menuClasses[] = 'lenta-menu-invisible';
							}

							?><div
								id="log-entry-menu-<?=intval($arParams["LOG_ID"])?>"
								data-menu-type="post"
								data-log-id="<?=intval($arParams['LOG_ID'])?>"
								data-post-id="<?=intval($arResult['Post']['ID'])?>"
								data-post-perm="<?=\CUtil::jSEscape($arResult["PostPerm"])?>"
								data-use-favorites="<?=($useFavorites ? "Y" : "N")?>"
								data-favorites="<?=($favoritesValue ? "Y" : "N")?>"
								data-use-follow="<?=($useFollow ? "Y" : "N")?>"
								data-follow="<?=($followValue ? "Y" : "N")?>"
								data-content-type-id="BLOG_POST"
								data-content-id="<?=intval($arResult['Post']['ID'])?>"
								class="<?=implode(' ', $menuClasses)?>"
								onclick="oMSL.showPostMenu(event)"
							>
								<div class="lenta-menu-item"></div>
							</div><?
						}
						else
						{
							if ($useFavorites)
							{
								?><div
									id="log_entry_favorites_<?=$arParams["LOG_ID"]?>"
									data-favorites="<?=($favoritesValue ? "Y" : "N")?>"
									class="lenta-item-fav<?=($favoritesValue ? " lenta-item-fav-active" : "")?>"
									onclick="__MSLSetFavorites(<?=$arParams["LOG_ID"]?>, this, '<?=($favoritesValue ? "N" : "Y")?>'); return BX.PreventDefault(this);"
								></div><?
							}
							else
							{
								?><div class="lenta-item-fav-placeholder"></div><?
							}
						}

					?></div><?

				?></div><?

				$arOnClickParams = array(
					"path" => $arParams["PATH_TO_LOG_ENTRY_EMPTY"],
					"log_id" => intval($arParams["LOG_ID"]),
					"entry_type" => "blog",
					"use_follow" => ($arParams["USE_FOLLOW"] == 'N' ? 'N' : 'Y'),
					"use_tasks" => ($arResult["bTasksAvailable"] == 'Y' ? 'Y' : 'N'),
					"post_perm" => $arResult["PostPerm"],
					"destinations" => $arResult["Post"]["SPERMX"],
					"post_id" => intval($arResult["Post"]["ID"]),
					"post_url" => str_replace("#log_id#", $arParams["LOG_ID"], $arParams["PATH_TO_LOG_ENTRY"]),
					"entity_xml_id" => "BLOG_".intval($arResult["Post"]["ID"]),
					"focus_comments" => false,
					"focus_form" => false,
					"show_full" => false,
					"read_only" => (!!$arResult["ReadOnly"] ? 'Y' : 'N'),
					"post_content_type_id" => "BLOG_POST",
					"post_content_id" => intval($arResult["Post"]["ID"])
				);

				$strOnClickParams = CUtil::PhpToJSObject($arOnClickParams);

				$post_item_style = (!$arParams["IS_LIST"] && $_REQUEST["show_full"] == "Y" ? "post-item-post-block-full" : "post-item-post-block post-item-block-inner post-item-contentview");
				$strOnClick = ($arParams["IS_LIST"] ? " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParams.", event);\"" : "");

				if ($arParams["EVENT_ID"] == "blog_post_important")
				{
					$post_item_style .= " info-block-important";
				}

				?><div class="<?=$post_item_style?>"<?=$strOnClick?> id="post_block_check_cont_<?=$arParams["LOG_ID"]?>" bx-content-view-xml-id="BLOG_POST-<?=intval($arResult["Post"]["ID"])?>"><?

					if (
						isset($arParams['TARGET'])
						&& $arParams['TARGET'] == 'postContent'
					)
					{
						$targetHtml = '';
						ob_start();
					}

					if($arResult["Post"]["MICRO"] != "Y")
					{
						?><div class="post-text-title<?if($arParams["EVENT_ID"]=="blog_post_important"){?> lenta-important-block-title<?}?>" id="post_text_title_<?=intval($arParams["LOG_ID"])?>"><?=$arResult["Post"]["TITLE"]?></div><?
					}

					?><div class="post-item-full-content post-item-text post-item-copytext<?if($arParams["EVENT_ID"]=="blog_post_important"){?> lenta-important-block-text<?}?>" id="post_block_check_<?=intval($arParams["LOG_ID"])?>"><?=$arResult["Post"]["textFormated"]?></div><?
					if (!empty($arResult["Post"]["IMPORTANT"]))
					{
						?><div class="post-item-important">
							<input id="important_post_<?=$arResult["Post"]["ID"]?>" bx-data-post-id="<?=$arResult["Post"]["ID"]?>" type="checkbox" onclick="return __MSLOnPostRead(this, event);" <?if ($arResult["Post"]["IMPORTANT"]["IS_READ"] == "Y"): ?>checked="checked" <? endif;?>/>
							<span class="checked webform-small-button"><?=GetMessage('BLOG_ALREADY_READ')?></span>
							<label for="important_post_<?=$arResult["Post"]["ID"]?>" class="unchecked webform-small-button"><?=GetMessage(trim("BLOG_READ_".$arResult["Post"]["IMPORTANT"]["USER"]["PERSONAL_GENDER"]))?></label>
						</div><?
					}
					if (!empty($arResult["images"]))
					{
						?><div class="post-item-attached-img-wrap"><?
							$jsIds = "";
							foreach($arResult["images"] as $val)
							{
								$id = "blog-post-attached-".mb_strtolower(randString(5));
								$jsIds .= $jsIds !== "" ? ', "'.$id.'"' : '"'.$id.'"';
								?><div class="post-item-attached-img-block"><img class="post-item-attached-img" id="<?=$id?>" src="<?=CMobileLazyLoad::getBase64Stub()?>" data-src="<?=$val["small"]?>" data-bx-image="<?=$val["full"]?>" alt="" border="0"></div><?
							}
						?></div><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>], oMSL.checkVisibility);</script><?
					}

					if(
						$arResult["POST_PROPERTIES"]["SHOW"] == "Y"
						|| !empty($arResult["GRATITUDE"])
					)
					{
						?><div class="post-item-attached-file-wrap" id="post_block_check_files_<?=$arParams["LOG_ID"]?>"><?

						if($arResult["POST_PROPERTIES"]["SHOW"] == "Y")
						{
							$eventHandlerID = false;
							$eventHandlerID = AddEventHandler('main', 'system.field.view.file', '__blogUFfileShowMobile');
							foreach ($arResult["POST_PROPERTIES"]["DATA"] as $FIELD_NAME => $arPostField)
							{
								if(!empty($arPostField["VALUE"]))
								{
									?><?$APPLICATION->IncludeComponent(
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
									);?><?
								}
							}
							if (
								$eventHandlerID !== false
								&& (intval($eventHandlerID) > 0)
							)
							{
								RemoveEventHandler('main', 'system.field.view.file', $eventHandlerID);
							}
						}

						if (!empty($arResult["URL_PREVIEW"]))
						{
							?><?=$arResult["URL_PREVIEW"]?><?
						}

						if (!empty($arResult["GRATITUDE"]))
						{
							?><div class="lenta-info-block lenta-block-grat"><?
								?><div class="lenta-block-grat-medal<?=($arResult["GRATITUDE"]["TYPE"]["XML_ID"] <> '' ? " lenta-block-grat-medal-".$arResult["GRATITUDE"]["TYPE"]["XML_ID"] : "")?>"></div><?
								?><div class="lenta-block-grat-arrow"></div><?
								?><div class="lenta-block-grat-users"><?
									$jsIds = "";
									foreach($arResult["GRATITUDE"]["USERS_FULL"] as $arGratUser)
									{
										$avatarId = "lenta-block-grat-".randString(5);
										if($arGratUser["AVATAR_SRC"])
										{
											$jsIds .= $jsIds !== "" ? ', "'.$avatarId.'"' : '"'.$avatarId.'"';
										}
										?><div class="lenta-block-grat-user">
											<div class="lenta-new-grat-avatar">
												<div class="avatar" id="<?=$avatarId?>"<?if($arGratUser["AVATAR_SRC"]):?> data-src="<?=$arGratUser["AVATAR_SRC"]?>"<?endif?>></div>
											</div>
											<div class="lenta-info-block-content">
												<div class="lenta-important-block-title"><a href="<?=($arGratUser['URL'] ? $arGratUser['URL'] : 'javascript:void(0);')?>"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arGratUser)?></a></div>
												<div class="lenta-important-block-text"><?=htmlspecialcharsbx($arGratUser["WORK_POSITION"])?></div>
											</div>
										</div><?
									}
								?></div><?
							?></div><?

							if($jsIds <> '')
							{
								?><script>BitrixMobile.LazyLoad.registerImages([<?=$jsIds?>]);</script><?
							}
						}

						?></div><?
					}

					$postMoreBlockStyle = (
						isset($arParams['TARGET'])
						&& $arParams['TARGET'] == 'postContent'
							? 'style="display: none;"'
							: ''
					);
					?><div class="post-more-block" id="post_more_block_<?=$arParams["LOG_ID"]?>"<?=$postMoreBlockStyle?> onclick="oMSL.expandText(<?=intval($arParams["LOG_ID"])?>);"></div><?

					if (
						isset($arParams['TARGET'])
						&& $arParams['TARGET'] == 'postContent'
					)
					{
						$targetHtml = ob_get_contents();
					}

				?></div><? // post-item-post-block, post_block_check_cont_..

				if(!empty($arResult["UF_FILE"]))
				{
					?><div id="post_block_files_<?=$arParams["LOG_ID"]?>" class="post-item-attached-file-wrap post-item-attached-disk-file-wrap"><?

					$eventHandlerID = false;
					$eventHandlerID = AddEventHandler('main', 'system.field.view.file', '__blogUFfileShowMobile');
					$arPostField = $arResult["UF_FILE"];

					if(!empty($arPostField["VALUE"]))
					{
						?><?$APPLICATION->IncludeComponent(
							"bitrix:system.field.view",
							$arPostField["USER_TYPE"]["USER_TYPE_ID"],
							array(
								"arUserField" => $arPostField,
								"ACTION_PAGE" => str_replace("#log_id#", $arParams["LOG_ID"], $arParams["PATH_TO_LOG_ENTRY"]),
								"MOBILE" => "Y",
								"GRID" => ($arResult['Post']['hasInlineDiskFile'] ? 'N' : 'Y'),
								"USE_TOGGLE_VIEW" => ($arResult["PostPerm"] >= 'W' ? 'Y' : 'N'),
								"VIEW_MODE" => ($arParams["IS_LIST"] ? "BRIEF" : "EXTENDED")
							),
							null,
							array("HIDE_ICONS"=>"Y")
						);?><?
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
				if ($arResult["is_ajax_post"] != "Y")
				{
					ob_start();
				}

				if (
					$arParams["SHOW_RATING"] == "Y"
					&& (
						!isset($arParams['TARGET'])
						|| !in_array($arParams["TARGET"], [ 'postContent' ])
					)
				)
				{
					$voteId = "BLOG_POST".'_'.$arResult["Post"]["ID"].'-'.(time()+rand(0, 1000));
					$emotion = (!empty($arResult["RATING"][$arResult["Post"]["ID"]]["USER_REACTION"])? mb_strtoupper($arResult["RATING"][$arResult["Post"]["ID"]]["USER_REACTION"]) : 'LIKE');

					?><span class="post-item-informers bx-ilike-block" id="rating_block_<?=$arParams["LOG_ID"]?>" data-counter="<?=intval($arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_VOTES"])?>"><?
						?><span data-rating-vote-id="<?=htmlspecialcharsbx($voteId)?>" id="bx-ilike-button-<?=htmlspecialcharsbx($voteId)?>" class="post-item-informer-like feed-inform-ilike"><?
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
							?><span class="<?=implode(' ', $likeClassList)?>"><?
								?><span class="bx-ilike-icon-new"></span><?
								?><span class="bx-ilike-text"><?=\CRatingsComponentsMain::getRatingLikeMessage($emotion)?></span><?
							?></span><?
						?></span><?
					?></span><?
				}

				if (
					$arResult["Post"]["ENABLE_COMMENTS"] == "Y"
					&& (
						!isset($arParams['TARGET'])
						|| !in_array($arParams["TARGET"], [ 'postContent' ])
					)
				)
				{
					$bHasComments = true;

					if ($arResult["is_ajax_post"] != "Y")
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
//							"MARK_NEW_COMMENTS" => "Y", // show new comments in the list
							"EMPTY_PAGE_PARAMS" => $arOnClickParams,
							"SITE_TEMPLATE_ID" => (!empty($arParams["SITE_TEMPLATE_ID"]) ? $arParams["SITE_TEMPLATE_ID"] : '')
						),
						$component,
						array("HIDE_ICONS" => "Y")
					);

//					$strCommentsBlock = ob_get_contents();
					$strCommentsBlock = (!$arParams["IS_LIST"] ? ob_get_contents() : "");

					ob_end_clean(); // inner buffer

					if ($arResult["GetCommentsOnly"])
					{
						?><?=$strCommentsBlock?><?
						die();
					}

					if (!$arParams["IS_LIST"]) // detail, non-empty
					{
						?><div id="comments_control_<?=$arParams["LOG_ID"]?>" class="post-item-informers post-item-inform-comments"><?
							?><div class="post-item-inform-comments-box"><?
								?><span class="post-item-inform-icon"></span><?
								?><div class="post-item-inform-left"><?=Loc::getMessage('BLOG_MOBILE_COMMENTS_ACTION')?></div><?
							?></div><?
						?></div><?
					}
					else
					{
						$arOnClickParamsCommentsTop = $arOnClickParams;
						$arOnClickParamsCommentsTop["focus_comments"] = true;
						$arOnClickParamsCommentsTop["show_full"] = false;
						$strOnClickParamsCommentsTop = CUtil::PhpToJSObject($arOnClickParamsCommentsTop);
						$strOnClickCommentsTop = ($arParams["IS_LIST"] ? " onclick=\"__MSLOpenLogEntryNew(".$strOnClickParamsCommentsTop.", event);\"" : "");

						$showNewComments = (
							(
								$arParams["USE_FOLLOW"] != "Y"
								|| $arParams["FOLLOW"] == "Y"
							)
							&& intval($arCommentsResult["newCountWOMark"]) > 0
						);

						$commentsBlockClassList = [
							'post-item-informers',
							'post-item-inform-comments'
						];
						if ($showNewComments)
						{
							$commentsBlockClassList[] = 'post-item-inform-likes-active';
						}

						?><div id="comments_control_<?=$arParams["LOG_ID"]?>" class="<?=implode(' ', $commentsBlockClassList)?>"<?=$strOnClickCommentsTop?>><?
							?><div class="post-item-inform-comments-box"><?
								?><span class="post-item-inform-icon"></span><?

								$num_comments = (isset($arParams["COMMENTS_COUNT"]) ? $arParams["COMMENTS_COUNT"] : intval($arResult["Post"]["NUM_COMMENTS"]));

								?><div class="post-item-inform-left" id="informer_comments_text2_<?=$arParams["LOG_ID"]?>" style="display: <?=($num_comments > 0 ? "inline-block" : "none")?>;"><?
									?><?=Loc::getMessage('BLOG_MOBILE_COMMENTS')?><? // BLOG_MOBILE_COMMENTS_2
								?></div><?
								?><div class="post-item-inform-left" id="informer_comments_text_<?=$arParams["LOG_ID"]?>" style="display: <?=($num_comments <= 0 ? "inline-block" : "none")?>;"><?
									?><?=Loc::getMessage('BLOG_MOBILE_COMMENTS')?><?
								?></div><?

								?><div class="post-item-inform-right" id="informer_comments_<?=$arParams["LOG_ID"]?>" style="display: <?=($num_comments > 0 ? 'inline-block' : 'none')?>;"><?
									if ($showNewComments)
									{
										?><span id="informer_comments_all_<?=$arParams["LOG_ID"]?>"><?
											$old_comments = intval(abs($num_comments - intval($arCommentsResult["newCountWOMark"])));
											?><?=($old_comments > 0 ? $old_comments : '')?><?
										?></span><?
										?><span id="informer_comments_new_<?=$arParams["LOG_ID"]?>" class="post-item-inform-right-new"><?
											?><span class="post-item-inform-right-new-sign">+</span><?
											?><span class="post-item-inform-right-new-value"><?=intval($arCommentsResult["newCountWOMark"])?></span><?
										?></span><?
									}
									else
									{
										?><?=$num_comments?><?
									}
								?></div><?

							?></div><?


						?></div><?
					}
				}
				else
				{
					$bHasComments = false;
				}

				if ($bHasComments)
				{
					?><div id="log_entry_follow_<?=intval($arParams["LOG_ID"])?>" data-follow="<?=($arParams["FOLLOW"] == "Y" ? "Y" : "N")?>" style="display: none;"></div><?
				}

				if ($arResult["bTasksAvailable"])
				{
					?><div id="log_entry_use_tasks_<?=intval($arParams["LOG_ID"])?>" data-use-tasks="Y" style="display: none;"></div><?
				}

				if ($arParams["IS_LIST"])
				{
					$arOnClickParams["focus_comments"] = false;
					$arOnClickParams["show_full"] = true;
					$strOnClickParams = CUtil::PhpToJSObject($arOnClickParams);
					$strOnClickMore = ' onclick="__MSLOpenLogEntryNew('.$strOnClickParams.', event);"';
				}
				else
				{
					$strOnClickMore = " onclick=\"oMSL.expandText(".intval($arParams["LOG_ID"]).");\"";
				}

				?><a id="post_more_limiter_<?=intval($arParams["LOG_ID"])?>" <?=$strOnClickMore?> class="post-item-more" ontouchstart="this.classList.toggle('post-item-more-pressed')" ontouchend="this.classList.toggle('post-item-more-pressed')" style="visibility: hidden;"><?
					?><?=GetMessage("BLOG_LOG_EXPAND")?><?
				?></a><?

				?><script>
					BX.ready(function() {
						if (window.arCanUserComment)
						{
							window.arCanUserComment[<?=$arParams["LOG_ID"]?>] = <?=($arCommentsResult && $arCommentsResult["CanUserComment"] ? "true" : "false")?>;
						}
					});
				</script><?

				$strBottomBlock = ob_get_contents();
				ob_end_clean(); // outer buffer

				if (
					$arParams["SHOW_RATING"] == "Y"
					&& (
						!isset($arParams['TARGET'])
						|| !in_array($arParams['TARGET'], [ 'postContent' ])
					)
				)
				{
					?><div class="post-item-inform-wrap-tree" id="<?=(!$arParams["IS_LIST"] ? 'rating-footer-wrap' : 'rating-footer-wrap_'.intval($arParams["LOG_ID"]))?>"><?
						?><div class="feed-post-emoji-top-panel-outer"><?
							?><div id="feed-post-emoji-top-panel-container-<?=htmlspecialcharsbx($voteId)?>" class="feed-post-emoji-top-panel-box <?=(intval($arResult["RATING"][$arResult["Post"]["ID"]]["TOTAL_POSITIVE_VOTES"]) > 0 ? 'feed-post-emoji-top-panel-container-active' : '')?>"><?
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
							?></div><?
						?></div><?
					?></div><?
				}

			if ($strBottomBlock <> '')
			{
				?><div id="post_item_inform_wrap" class="post-item-inform-wrap"><?
					?><?=$strBottomBlock;?><?
				?></div><?
			}

			?></div><? // post-item-top-wrap

			if (
				$arResult["Post"]["ENABLE_COMMENTS"] == "Y"
				&& $strCommentsBlock <> ''
			)
			{
				?><?=$strCommentsBlock?><?
			}

		?></div><? // post-wrap / lenta-item

		if (!$arParams["IS_LIST"])
		{
			// comments form block

			if ($arResult["GetCommentsFormOnly"])
			{
				$APPLICATION->RestartBuffer();
			}

			if ($arCommentsResult["CanUserComment"])
			{
				echo $commentsFormBlock;
			}

			if ($arResult["GetCommentsFormOnly"])
			{
				die();
			}
		}
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
?>