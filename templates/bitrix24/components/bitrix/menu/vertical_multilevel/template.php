<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$isCompositeMode = defined("USE_HTML_STATIC_CACHE") ? true : false;
$this->setFrameMode(true);

if(!function_exists("IsSubItemSelected"))
{
	function IsSubItemSelected($ITEMS)
	{
		if (is_array($ITEMS))
		{
			foreach($ITEMS as $arItem)
			{
				if ($arItem["SELECTED"])
					return true;
			}
		}
		return false;
	}
}
if (empty($arResult))
	return;

$arHiddenItemsSelected = array();
$sumHiddenCounters = 0;
$arHiddenItemsCounters = array();
$arAllItemsCounters = array();
?>
<div id="bx_b24_menu">
<?
foreach($arResult["TITLE_ITEMS"] as $title => $arTitleItem)
{
	if (is_array($arResult["SORT_ITEMS"][$title]["show"]) || is_array($arResult["SORT_ITEMS"][$title]["hide"]))
	{
		$hideOption = CUserOptions::GetOption("bitrix24", $arTitleItem["PARAMS"]["class"]);
		$SubItemSelected = false;
		if (!is_array($hideOption) || $hideOption["hide"] == "Y")
			$SubItemSelected = IsSubItemSelected($arResult["SORT_ITEMS"][$title]["show"]) || IsSubItemSelected($arResult["SORT_ITEMS"][$title]["hide"]) ? true : false;

		if (IsModuleInstalled("bitrix24"))
			$disabled = (!is_array($hideOption) && $arTitleItem["PARAMS"]["class"]=="menu-crm" && !$SubItemSelected) || (is_array($hideOption) && $hideOption["hide"] == "Y" && !$SubItemSelected);
		else
			$disabled = (!is_array($hideOption) && $arTitleItem["PARAMS"]["class"]!="menu-favorites" && !$SubItemSelected) || (is_array($hideOption) && $hideOption["hide"] == "Y" && !$SubItemSelected);?>

		<div class="menu-items-block <?=$arTitleItem["PARAMS"]["class"]?> " <?if ($arTitleItem["PARAMS"]["is_empty"] == "Y"):?>style="display:none"<?endif?> id="div_<?=$arTitleItem["PARAMS"]["menu_item_id"]?>">
			<div id="<?=$arTitleItem["PARAMS"]["menu_item_id"]?>" class="menu-items-title <?=$arTitleItem["PARAMS"]["class"]?>"><?
				if ($arTitleItem["PARAMS"]["class"] == "menu-favorites"):?>
					<span class="menu-items-title-text"><?echo $arTitleItem["TEXT"]?></span>
					<span class="menu-favorites-settings" id="menu_favorites_settings" onclick="B24menuItemsObj.applyEditMode();" title="<?=GetMessage("MENU_SETTINGS_TITLE")?>"><span class="menu-fav-settings-icon"></span></span>
					<span class="menu-favorites-btn-done" onclick="B24menuItemsObj.applyEditMode();"><?=GetMessage("MENU_EDIT_READY")?></span><?
				else:
					echo $arTitleItem["TEXT"];
					?><span class="menu-toggle-text"><?=($disabled ? GetMessage("MENU_SHOW") : GetMessage("MENU_HIDE"))?></span><?
				endif
			?></div>
			<ul  class="menu-items<?if ($disabled):?> menu-items-close<?endif;?>" id="ul_<?=$arTitleItem["PARAMS"]["menu_item_id"]?>">
				<li class="menu-items-empty-li" id="empty_li_<?=$arTitleItem["PARAMS"]["menu_item_id"]?>" style="height: 3px;"></li>
				<?
				$arTmp = array("show", "hide");
				foreach($arTmp as $status)
				{
					if ($status=="hide"):?>
					<li class="menu-item-separator" id="separator_<?=$arTitleItem["PARAMS"]["menu_item_id"]?>">
						<span class="menu-item-sepor-text"><?=GetMessage("MENU_HIDDEN_ITEMS")?></span>
						<span class="menu-item-sepor-line"></span>
					</li>
					<li class="menu-item-block menu-item-favorites-more" id="hidden_items_li_<?=$arTitleItem["PARAMS"]["menu_item_id"]?>">
						<ul class="menu-items-fav-more-block" id="hidden_items_ul_<?=$arTitleItem["PARAMS"]["menu_item_id"]?>">
					<?endif;
					if (is_array($arResult["SORT_ITEMS"][$title][$status]))
					{
						foreach($arResult["SORT_ITEMS"][$title][$status] as $arItem)
						{
							if ($arItem["PERMISSION"] > "D")
							{
								$couterId = "";
								$counter = 0;
								if (array_key_exists("counter_id", $arItem["PARAMS"]) && $arItem["PARAMS"]["counter_id"] <> '')
								{
									$couterId = $arItem["PARAMS"]["counter_id"] == "live-feed" ? "**" : $arItem["PARAMS"]["counter_id"];
									$counter = isset($GLOBALS["LEFT_MENU_COUNTERS"]) && array_key_exists($couterId, $GLOBALS["LEFT_MENU_COUNTERS"]) ? $GLOBALS["LEFT_MENU_COUNTERS"][$couterId] : 0;
									if ($couterId == "crm_cur_act")
									{
										$counterCrm = (isset($GLOBALS["LEFT_MENU_COUNTERS"]) && array_key_exists("CRM_**", $GLOBALS["LEFT_MENU_COUNTERS"]) ? intval($GLOBALS["LEFT_MENU_COUNTERS"]["CRM_**"]) : 0);
										$counterAct = $counter;
										$counter += $counterCrm;
									}
								}

								if ($couterId == "bp_tasks" && IsModuleInstalled("bitrix24"))
								{
									$showMenuItem = CUserOptions::GetOption("bitrix24", "show_bp_in_menu", false);
									if ($showMenuItem === false && $counter > 0)
									{
										CUserOptions::SetOption("bitrix24", "show_bp_in_menu", true);
										$showMenuItem = true;
									}

									if ($showMenuItem === false)
										continue;
								}

								if ($couterId)
								{
									$arAllItemsCounters[$couterId] = $isCompositeMode ? 0 : $counter;
									if ($status=="hide")
									{
										$sumHiddenCounters+= $counter;
										$arHiddenItemsCounters[] = $couterId;
									}
								}
								?>
								<li <?if ($title!= "menu-favorites" && in_array($arItem["PARAMS"]["menu_item_id"],$arResult["ALL_FAVOURITE_ITEMS_ID"])):?>style="display:none; " <?endif?>
									id="<?if ($title!= "menu-favorites" && in_array($arItem["PARAMS"]["menu_item_id"],$arResult["ALL_FAVOURITE_ITEMS_ID"])) echo "hidden_"; echo $arItem["PARAMS"]["menu_item_id"]?>"
									data-status="<?=$status?>"
									data-title-item="<?=$arTitleItem["PARAMS"]["menu_item_id"]?>"
									data-counter-id="<?=$couterId?>"
									data-can-delete-from-favorite="<?=$arItem["PARAMS"]["can_delete_from_favourite"]?>"
									<?if (isset($arItem["PARAMS"]["is_application"])):?>
										data-app-id="<?=$arItem["PARAMS"]["app_id"]?>"
									<?endif?>
									class="menu-item-block <?if ($isCompositeMode === false && $arItem["SELECTED"]):?> menu-item-active<?endif?><?if($isCompositeMode === false && $counter > 0 && $couterId <> '' && (!$arItem["SELECTED"] || ($arItem["SELECTED"] && $couterId == "bp_tasks"))):?> menu-item-with-index<?endif?><?if ((IsModuleInstalled("bitrix24") && $arItem["PARAMS"]["menu_item_id"] == "menu_live_feed") || $arItem["PARAMS"]["menu_item_id"] == "menu_all_groups"):?> menu-item-live-feed<?endif?>">
									<?if (!((IsModuleInstalled("bitrix24") && $arItem["PARAMS"]["menu_item_id"] == "menu_live_feed") || $arItem["PARAMS"]["menu_item_id"] == "menu_all_groups")):?>
										<span class="menu-fav-editable-btn menu-favorites-btn" onclick="B24menuItemsObj.openMenuPopup(this, '<?=CUtil::JSEscape($arItem["PARAMS"]["menu_item_id"])?>')"><span class="menu-favorites-btn-icon"></span></span>
										<span class="menu-favorites-btn menu-favorites-draggable" onmousedown="BX.addClass(this.parentNode, 'menu-item-draggable');" onmouseup="BX.removeClass(this.parentNode, 'menu-item-draggable');"><span class="menu-fav-draggable-icon"></span></span>
									<?endif?>
									<?
									$curLink = "";
									if (preg_match("~^".SITE_DIR."index\.php~i", $arItem["LINK"]))
										$curLink = SITE_DIR;
									elseif (isset($arItem["PARAMS"]["onclick"]) && !empty($arItem["PARAMS"]["onclick"]))
										$curLink = "javascript:void(0)";
									else
										$curLink = $arItem["LINK"];
									?>
									<a class="menu-item-link" href="<?=$curLink?>" title="<?=$arItem["TEXT"]?>" onclick="
										if (B24menuItemsObj.isEditMode())
											return false;

										<?if (isset($arItem["PARAMS"]["onclick"])):?>
											<?=CUtil::JSEscape($arItem["PARAMS"]["onclick"])?>
										<?endif?>
									">
										<span class="menu-item-link-text"><?=$arItem["TEXT"]?></span>
										<?if ($couterId <> ''):
											$itemCounter = "";
											$crmAttrs = "";
											if ($isCompositeMode === false)
											{
												$itemCounter = ($arItem["PARAMS"]["counter_id"] == "mail_unseen" ? ($counter > 99 ? "99+" : $counter) : ($counter > 50 ? "50+" : $counter));
												$crmAttrs = ($arItem["PARAMS"]["counter_id"] == "crm_cur_act" ? ' data-counter-crmstream="'.intval($counterCrm).'" data-counter-crmact="'.intval($counterAct).'"' : "");
											}
										?><span class="menu-item-index-wrap"><span class="menu-item-index" <?=$crmAttrs?> id="menu-counter-<?= mb_strtolower($arItem["PARAMS"]["counter_id"])?>"><?=$itemCounter?></span></span>
											<?if (!empty($arItem["PARAMS"]["warning_link"])):?>
												<span onclick="window.location.replace('<?=$arItem["PARAMS"]["warning_link"]; ?>'); return false; "
													<? if (!empty($arItem["PARAMS"]["warning_title"])):?>title="<?=$arItem["PARAMS"]["warning_title"]; ?>"<?endif?>
													class="menu-post-warn-icon"
													id="menu-counter-warning-<?= mb_strtolower($arItem["PARAMS"]["counter_id"]); ?>"></span>
											<?endif?>
										<?endif;?>
									</a>
								</li>
							<?
							}
						}
					}
					if ($status=="hide"):?>
						</ul>
					</li>
					<?endif;
				}
				?>
			</ul>
			<div class="menu-favorites-more-btn<?if ($disabled):?> menu-items-close<?endif;?>" id="more_btn_<?=$arTitleItem["PARAMS"]["menu_item_id"]?>" <?if (!is_array($arResult["SORT_ITEMS"][$title]["hide"])):?>style="display:none;"<?endif?> onclick="B24menuItemsObj.showHideMoreItems(this, '<?=CUtil::JSEscape($arTitleItem["PARAMS"]["menu_item_id"])?>');">
				<span class="menu-favorites-more-text"><?=GetMessage("MENU_MORE_ITEMS_SHOW")?></span>
				<?if ($title == "menu-favorites"):?>
					<span class="menu-item-index menu-item-index-more" id="menu-hidden-counter" <?if ($isCompositeMode || $sumHiddenCounters <= 0):?>style="display:none"<?endif?>><?= ($isCompositeMode ? "" : ($sumHiddenCounters > 50 ? "50+" : $sumHiddenCounters))?></span>
				<?endif?>
				<span class="menu-favorites-more-icon"></span>
			</div>
			<?
			if (IsSubItemSelected($arResult["SORT_ITEMS"][$title]["hide"]))
				$arHiddenItemsSelected[] = $arTitleItem["PARAMS"]["menu_item_id"];
			?>
		</div>
	<?
	}
}
?>
</div>

<?
$arJSParams = array(
	"arFavouriteAll" => $arResult["ALL_FAVOURITE_ITEMS_ID"],
	"arFavouriteShowAll" => $arResult["ALL_SHOW_FAVOURITE_ITEMS_ID"],
	"arTitles" => array_keys($arResult["TITLE_ITEMS"]),
	"ajaxPath" => $this->GetFolder()."/ajax.php",
	"isAdmin" => (IsModuleInstalled("bitrix24") && $GLOBALS['USER']->CanDoOperation('bitrix24_config') || !IsModuleInstalled("bitrix24") && $GLOBALS['USER']->IsAdmin()) ? "Y" : "N",
	"hiddenCounters" => $arHiddenItemsCounters,
	"allCounters" => $arAllItemsCounters,
	"isBitrix24" => IsModuleInstalled("bitrix24") ? "Y" : "N",
	"siteId" => SITE_ID,
	"arHiddenItemsSelected" => $isCompositeMode ? array() : $arHiddenItemsSelected,
	"isCompositeMode" => $isCompositeMode
);
?>

<script>
	BX.message({
		add_to_favorite: '<?=CUtil::JSEscape(GetMessage('MENU_ADD_TO_FAVORITE'))?>',
		delete_from_favorite: '<?=CUtil::JSEscape(GetMessage('MENU_DELETE_FROM_FAVORITE'))?>',
		hide_item: '<?=CUtil::JSEscape(GetMessage('MENU_HIDE_ITEM'))?>',
		show_item: '<?=CUtil::JSEscape(GetMessage('MENU_SHOW_ITEM'))?>',
		add_to_favorite_all: '<?=CUtil::JSEscape(GetMessage('MENU_ADD_TO_FAVORITE_ALL'))?>',
		delete_from_favorite_all: '<?=CUtil::JSEscape(GetMessage('MENU_DELETE_FROM_FAVORITE_ALL'))?>',
		more_items_hide: '<?=CUtil::JSEscape(GetMessage('MENU_MORE_ITEMS_HIDE'))?>',
		more_items_show: '<?=CUtil::JSEscape(GetMessage('MENU_MORE_ITEMS_SHOW'))?>',
		edit_error: '<?=CUtil::JSEscape(GetMessage('MENU_ITEM_EDIT_ERROR'))?>',
		set_rights: '<?=CUtil::JSEscape(GetMessage('MENU_ITEM_SET_RIGHTS'))?>',
		menu_show: '<?=CUtil::JSEscape(GetMessage('MENU_SHOW'))?>',
		menu_hide: '<?=CUtil::JSEscape(GetMessage('MENU_HIDE'))?>'
	});

	<?if ($isCompositeMode):?>
	var path = document.location.pathname;

	if (document.location.pathname !== '<?=SITE_DIR?>')
		path += document.location.search;

	if (!BX.Bitrix24.MenuClass.highlight(path))
	{
		BX.ready(function() {
			BX.Bitrix24.MenuClass.highlight(path);
		});
	}
	<?endif?>

	BX.ready(function() {
		window.B24menuItemsObj = new BX.Bitrix24.MenuClass(<?=CUtil::PhpToJSObject($arJSParams)?>);
	});
</script>
