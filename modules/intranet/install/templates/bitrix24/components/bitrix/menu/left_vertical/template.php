<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$isCompositeMode = defined("USE_HTML_STATIC_CACHE") ? true : false;
$this->setFrameMode(true);

if (empty($arResult))
	return;

$sumHiddenCounters = 0;
$arHiddenItemsCounters = array();
$arAllItemsCounters = array();
$groupPopupExists = false;

?>
<div class="menu-items-block" id="bx-left-menu">
	<div class="menu-resize-container" id="left-menu-resizer">
		<div class="menu-resize-item" id="left-menu-resizer-button">
			<span class="menu-resize-btn"></span>
		</div>
	</div>
	<ul class="menu-items" id="left-menu-list">
		<li class="menu-items-empty-li" id="left-menu-empty-item" style="height: 3px;"></li>
	<?
	foreach(array("show", "hide") as $status)
	{
		if ($status=="hide")
		{
		?>
		<li class="menu-item-favorites-more" id="left-menu-hidden-items-block">
			<ul class="menu-items-fav-more-block" id="left-menu-hidden-items-list">
				<li class="menu-item-separator" id="left-menu-hidden-separator">
					<span class="menu-item-sepor-text"><?=GetMessage("MENU_HIDDEN_ITEMS")?></span>
				</li>
		<?
		}

		if (isset($arResult["ITEMS"][$status]) && is_array($arResult["ITEMS"][$status]))
		{
			foreach ($arResult["ITEMS"][$status] as $item)
			{
				if ($item["PERMISSION"] > "D")
				{
					$counterId = "";
					$counter = 0;
					if (array_key_exists("counter_id", $item["PARAMS"]) && strlen($item["PARAMS"]["counter_id"]) > 0)
					{
						$counterId = $item["PARAMS"]["counter_id"] == "live-feed" ? "**" : $item["PARAMS"]["counter_id"];
						$counter = array_key_exists($counterId, $arResult["COUNTERS"]) ? $arResult["COUNTERS"][$counterId] : 0;
					}

					if ($counterId)
					{
						$arAllItemsCounters[$counterId] = $isCompositeMode ? 0 : $counter;
						if ($status == "hide")
						{
							$sumHiddenCounters += $counter;
							$arHiddenItemsCounters[] = $counterId;
						}
					}

					$addLinks = "";
					if (isset($item["ADDITIONAL_LINKS"]) && is_array($item["ADDITIONAL_LINKS"]))
					{
						$addLinks = implode(",", $item["ADDITIONAL_LINKS"]);
					}

					if ($item["PARAMS"]["real_link"])
					{
						$addLinks .= ($addLinks === "" ? "" : ",").$item["LINK"];
					}

					$id = "";
					//if (in_array($item["PARAMS"]["menu_item_id"],$arResult["ALL_FAVOURITE_ITEMS_ID"]))
					//	$id.= "hidden_";
					$id .= $item["PARAMS"]["menu_item_id"];

					$curLink = isset($item["PARAMS"]["real_link"]) ? $item["PARAMS"]["real_link"] : $item["LINK"];
					if (preg_match("~^".SITE_DIR."index\\.php~i", $curLink))
					{
						$curLink = SITE_DIR;
					}
					elseif (isset($item["PARAMS"]["onclick"]) && !empty($item["PARAMS"]["onclick"]))
					{
						$curLink = "";
					}
					?>
					<li id="bx_left_menu_<?=$id?>"
						data-status="<?=$status?>"
						data-id="<?=$item["PARAMS"]["menu_item_id"]?>"
						data-counter-id="<?=$counterId?>"
						data-link="<?=$curLink?>"
						data-all-links="<?=$addLinks?>"
						data-type="<?=$item["ITEM_TYPE"]?>"
						data-delete-perm="<?=$item["DELETE_PERM"]?>"
						<? if (isset($item["PARAMS"]["is_application"])):?>
							data-app-id="<?=$item["PARAMS"]["app_id"]?>"
						<?endif ?>
						<? if (isset($item["PARAMS"]["top_menu_id"])):?>
							data-top-menu-id="<?=$item["PARAMS"]["top_menu_id"]?>"
						<?endif ?>
						data-new-page="<?=(isset($item["OPEN_IN_NEW_PAGE"]) && $item["OPEN_IN_NEW_PAGE"])? "Y" : "N"?>"
						class="menu-item-block<? if ($isCompositeMode === false && $counter > 0 && strlen($counterId) > 0):?> menu-item-with-index<?endif ?><? if (IsModuleInstalled("bitrix24") && $item["PARAMS"]["menu_item_id"] == "menu_live_feed"):?> menu-item-live-feed<?endif ?>"
					>
						<span
							class="menu-fav-editable-btn menu-favorites-btn menu-fav-editable-btn-js"
							onclick="BX.Bitrix24.LeftMenuClass.openMenuPopup(this, '<?=CUtil::JSEscape($item["PARAMS"]["menu_item_id"])?>')">
								<span class="menu-favorites-btn-icon"></span>
						</span>

						<span
							class="menu-favorites-btn menu-favorites-draggable"
							onmousedown="BX.addClass(this.parentNode, 'menu-item-draggable');"
							onmouseup="BX.removeClass(this.parentNode, 'menu-item-draggable');">
							<span class="menu-fav-draggable-icon"></span>
						</span>

						<? if (isset($item["PARAMS"]["sub_link"])):?>
							<span class="menu-item-plus">
								<a href="<?=htmlspecialcharsbx($item["PARAMS"]["sub_link"])?>" class="menu-item-plus-icon"></a>
							</span>
						<? endif?>
						<a class="menu-item-link"
							href="<?=(isset($item["PARAMS"]["onclick"])) ? "javascript:void(0)" : $curLink?>"
							title="<?=$item["TEXT"]?>"
							<?if (isset($item["OPEN_IN_NEW_PAGE"]) && $item["OPEN_IN_NEW_PAGE"]):?>
							target="_blank"
							<?endif?>
							onclick="if (BX.Bitrix24.LeftMenuClass.isEditMode()) return false;
							<?if (isset($item["PARAMS"]["onclick"])):?>
								<?=htmlspecialcharsbx($item["PARAMS"]["onclick"])?>
							<?endif?>">
							<span class="menu-item-link-text" data-role="item-text">
								<?=$item["TEXT"]?>
								<?if (isset($item["PARAMS"]["is_beta"])):?>
								<span class="menu-item-link-text-beta">beta</span>
								<?endif?>
							</span><?
							if (strlen($counterId) > 0):
								$itemCounter = "";
								$crmAttrs = "";
								if ($isCompositeMode === false)
								{
									$itemCounter = ($item["PARAMS"]["counter_id"] == "mail_unseen" ? ($counter > 99 ? "99+" : $counter) : ($counter > 50 ? "50+" : $counter));
								}
								?><span class="menu-item-index-wrap"><?
									?><span class="menu-item-index"
											id="menu-counter-<?=strtolower($item["PARAMS"]["counter_id"])?>"><?=$itemCounter?></span>
								</span>
								<? if (!empty($item["PARAMS"]["warning_link"])):?>
								<span
									onclick="window.location.replace('<?=$item["PARAMS"]["warning_link"];?>'); return false; "
									<? if (!empty($item["PARAMS"]["warning_title"])): ?>title="<?=$item["PARAMS"]["warning_title"];?>"<?endif ?>
									class="menu-post-warn-icon"
									id="menu-counter-warning-<?=strtolower($item["PARAMS"]["counter_id"]);?>"></span>
								<?endif ?>
							<?endif; ?>
						</a>
						<?
						if (
							$item["PARAMS"]["menu_item_id"] === "menu_all_groups" &&
							count($arResult["GROUPS"])
						):
							$groupPopupExists = true;
							?><span class="menu-item-show-link" id="menu-all-groups-link"><?=GetMessage("MENU_SHOW")?></span><?
						endif ?>
					</li>
					<?
				}
			}
		}

		if ($status=="hide"):?>
				<li class="menu-items-hidden-empty-li" id="left-menu-hidden-empty-item"></li>
			</ul>
		</li>
		<?endif;
	}
	?>
	</ul>

	<div class="menu-favorites-more-btn<?if (!is_array($arResult["ITEMS"]["hide"])):?> menu-favorites-more-btn-hidden<?endif?>" id="left-menu-more-btn">
		<span class="menu-favorites-more-text"><?=GetMessage("MENU_MORE_ITEMS_SHOW")?></span>
		<span class="menu-favorites-more-icon"></span>
		<span class="menu-item-index menu-item-index-more" id="menu-hidden-counter" <?if ($isCompositeMode || $sumHiddenCounters <= 0):?>style="display:none"<?endif?>><?= ($isCompositeMode ? "" : ($sumHiddenCounters > 50 ? "50+" : $sumHiddenCounters))?></span>
	</div>

	<div class="menu-favorites-settings-btn" id="menu-favorites-settings-btn">
		<span class="menu-items-title-text" id="left-menu-settings">
			<?=GetMessage("MENU_SETTINGS_TITLE")?>
		</span>
		<span class="menu-favorites-btn-done" onclick="BX.Bitrix24.LeftMenuClass.applyEditMode();"><?=GetMessage("MENU_EDIT_READY_FULL")?></span>
	</div>

	<?
	$showInviteButton = CModule::IncludeModule("bitrix24") && CBitrix24::isInvitingUsersAllowed();

	if ($showInviteButton && CModule::IncludeModule("intranet")):?>
	<div class="menu-invite-employees">
		<span
			class="menu-invite-employees-text"
			onclick="<?=CIntranetInviteDialog::ShowInviteDialogLink()?>"><?=GetMessage("BITRIX24_INVITE_ACTION")?></span>
	</div>
	<? endif ?>

	<?
	if ($arResult["SHOW_LICENSE_BUTTON"]):?>
		<?
		$arJsParams = array(
			"LICENSE_PATH" => $arResult["B24_LICENSE_PATH"],
			"COUNTER_URL" => $arResult["LICENSE_BUTTON_COUNTER_URL"],
			"HOST" => $arResult["HOST_NAME"]
		);
		?>
		<a class="menu-license-all<?if (!$showInviteButton) echo " menu-license-all-shift";?>" href="javascript:void(0)" onclick="if (BX.getClass('B24.upgradeButtonRedirect')) B24.upgradeButtonRedirect(<?=CUtil::PhpToJSObject($arJsParams)?>)" >
			<span class="menu-license-all-text"><?=GetMessage("MENU_LICENSE_ALL")?></span>
		</a>
	<?endif?>
</div>

<?
include($_SERVER["DOCUMENT_ROOT"].$this->GetFolder()."/menu_popup.php");

$arJSParams = array(
	"ajaxPath" => $this->GetFolder()."/ajax.php",
	"isAdmin" => $arResult["IS_ADMIN"],
	"hiddenCounters" => $arHiddenItemsCounters,
	"allCounters" => $arAllItemsCounters,
	"isBitrix24" => IsModuleInstalled("bitrix24") ? "Y" : "N",
	"siteId" => SITE_ID,
	"siteDir" => SITE_DIR,
	"isExtranet" => $arResult["IS_EXTRANET"] ? "Y" : "N",
	"isCompositeMode" => $isCompositeMode,
	"isCollapsedMode" => CUserOptions::GetOption("intranet", "left_menu_collapsed") === "Y",
	"showPresetPopup" => $arResult["SHOW_PRESET_POPUP"] ? "Y" : "N",
	"isPublicConverted" => $arResult["IS_PUBLIC_CONVERTED"] ? "Y" : "N",
	"isCustomPresetAvailable" => $arResult["IS_CUSTOM_PRESET_AVAILABLE"] ? "Y" : "N",
	"customPresetExists" => $arResult["CUSTOM_PRESET_EXISTS"] ? "Y" : "N"
);
?>

<script>
	BX.message({
		add_to_favorite: '<?=CUtil::JSEscape(GetMessage('MENU_ADD_TO_FAVORITE'))?>',
		delete_from_favorite: '<?=CUtil::JSEscape(GetMessage('MENU_DELETE_FROM_FAVORITE'))?>',
		hide_item: '<?=CUtil::JSEscape(GetMessage('MENU_HIDE_ITEM'))?>',
		show_item: '<?=CUtil::JSEscape(GetMessage('MENU_SHOW_ITEM'))?>',
		delete_from_favorite_all: '<?=CUtil::JSEscape(GetMessage('MENU_DELETE_FROM_FAVORITE_ALL'))?>',
		MENU_SET_MAIN_PAGE: '<?=GetMessageJS("MENU_SET_MAIN_PAGE")?>',
		more_items_hide: '<?=CUtil::JSEscape(GetMessage('MENU_MORE_ITEMS_HIDE'))?>',
		more_items_show: '<?=CUtil::JSEscape(GetMessage('MENU_MORE_ITEMS_SHOW'))?>',
		edit_error: '<?=CUtil::JSEscape(GetMessage('MENU_ITEM_EDIT_ERROR'))?>',
		set_rights: '<?=CUtil::JSEscape(GetMessage('MENU_ITEM_SET_RIGHTS'))?>',
		menu_show: '<?=CUtil::JSEscape(GetMessage('MENU_SHOW'))?>',
		menu_hide: '<?=CUtil::JSEscape(GetMessage('MENU_HIDE'))?>',
		SORT_ITEMS: '<?=GetMessageJS("MENU_SORT_ITEMS")?>',
		MENU_ADD_SELF_PAGE: '<?=GetMessageJS("MENU_ADD_SELF_PAGE")?>',
		MENU_EDIT_SELF_PAGE: '<?=GetMessageJS("MENU_EDIT_SELF_PAGE")?>',
		MENU_SET_DEFAULT: '<?=GetMessageJS("MENU_SET_DEFAULT")?>',
		MENU_SET_DEFAULT2: '<?=GetMessageJS("MENU_SET_DEFAULT2")?>',
		MENU_ADD_BUTTON: '<?=GetMessageJS("MENU_ADD_BUTTON")?>',
		MENU_ITEM_NAME: '<?=GetMessageJS("MENU_ITEM_NAME")?>',
		MENU_ITEM_LINK: '<?=GetMessageJS("MENU_ITEM_LINK")?>',
		MENU_SET_DEFAULT_CONFIRM: '<?=GetMessageJS("MENU_SET_DEFAULT_CONFIRM")?>',
		MENU_SET_DEFAULT_CONFIRM_BUTTON: '<?=GetMessageJS("MENU_SET_DEFAULT_CONFIRM_BUTTON")?>',
		MENU_DELETE_SELF_ITEM: '<?=GetMessageJS("MENU_DELETE_SELF_ITEM")?>',
		MENU_DELETE_SELF_ITEM_CONFIRM: '<?=GetMessageJS("MENU_DELETE_SELF_ITEM_CONFIRM")?>',
		MENU_ADD_ITEM_TO_ALL: '<?=GetMessageJS("MENU_ADD_ITEM_TO_ALL")?>',
		MENU_DELETE_ITEM_FROM_ALL: '<?=GetMessageJS("MENU_DELETE_ITEM_FROM_ALL")?>',
		MENU_REMOVE_STANDARD_ITEM: '<?=GetMessageJS("MENU_REMOVE_STANDARD_ITEM")?>',
		MENU_OPEN_IN_NEW_PAGE: '<?=GetMessageJS("MENU_OPEN_IN_NEW_PAGE")?>',
		MENU_ADD_PAGE_TO_LEFT_MENU: '<?=GetMessageJS("MENU_ADD_PAGE_TO_LEFT_MENU")?>',
		MENU_DELETE_PAGE_FROM_LEFT_MENU: '<?=GetMessageJS("MENU_DELETE_PAGE_FROM_LEFT_MENU")?>',
		MENU_CANCEL: '<?=GetMessageJS("MENU_CANCEL")?>',
		MENU_DELETE: '<?=GetMessageJS("MENU_DELETE")?>',
		MENU_ERROR_OCCURRED: '<?=GetMessageJS("MENU_ERROR_OCCURRED")?>',
		MENU_ITEM_WAS_ADDED_TO_LEFT: '<?=GetMessageJS("MENU_ITEM_WAS_ADDED_TO_LEFT")?>',
		MENU_ITEM_WAS_DELETED_FROM_LEFT: '<?=GetMessageJS("MENU_ITEM_WAS_DELETED_FROM_LEFT")?>',
		MENU_ITEM_WAS_ADDED_TO_ALL: '<?=GetMessageJS("MENU_ITEM_WAS_ADDED_TO_ALL")?>',
		MENU_ITEM_WAS_DELETED_FROM_ALL: '<?=GetMessageJS("MENU_ITEM_WAS_DELETED_FROM_ALL")?>',
		MENU_ITEM_MAIN_PAGE: '<?=GetMessageJS("MENU_ITEM_MAIN_PAGE")?>',
		MENU_EDIT_ITEM: '<?=GetMessageJS("MENU_EDIT_ITEM")?>',
		MENU_RENAME_ITEM: '<?=GetMessageJS("MENU_RENAME_ITEM")?>',
		MENU_SAVE_BUTTON: '<?=GetMessageJS("MENU_SAVE_BUTTON")?>',
		MENU_EMPTY_FORM_ERROR: '<?=GetMessageJS("MENU_EMPTY_FORM_ERROR")?>',
		MENU_SELF_ITEM_FIRST_ERROR: '<?=GetMessageJS("MENU_SELF_ITEM_FIRST_ERROR")?>',
		MENU_COLLAPSE: '<?=GetMessageJS("MENU_COLLAPSE")?>',
		MENU_CONFIRM_BUTTON: '<?=GetMessageJS("MENU_CONFIRM_BUTTON")?>',
		MENU_DELAY_BUTTON: '<?=GetMessageJS("MENU_DELAY_BUTTON")?>',
		MENU_STAR_TITLE_DEFAULT_PAGE: '<?=GetMessageJS("MENU_STAR_TITLE_DEFAULT_PAGE")?>',
		MENU_STAR_TITLE_DEFAULT_PAGE_DELETE_ERROR: '<?=GetMessageJS("MENU_STAR_TITLE_DEFAULT_PAGE_DELETE_ERROR")?>',
		MENU_ADD_TO_LEFT_MENU: '<?=GetMessageJS("MENU_ADD_TO_LEFT_MENU")?>',
		MENU_DELETE_FROM_LEFT_MENU: '<?=GetMessageJS("MENU_DELETE_FROM_LEFT_MENU")?>',
		MENU_ITEM_MAIN_SECTION_PAGE: '<?=GetMessageJS("MENU_ITEM_MAIN_SECTION_PAGE")?>',
		MENU_TOP_ITEM_LAST_HIDDEN: '<?=GetMessageJS("MENU_TOP_ITEM_LAST_HIDDEN")?>',
		MENU_SAVE_CUSTOM_PRESET: '<?=GetMessageJS("MENU_SAVE_CUSTOM_PRESET2")?>',
		MENU_DELETE_CUSTOM_PRESET: '<?=GetMessageJS("MENU_DELETE_CUSTOM_PRESET")?>',
		MENU_CUSTOM_PRESET_POPUP_TITLE: '<?=GetMessageJS("MENU_CUSTOM_PRESET_POPUP_TITLE2")?>',
		MENU_CUSTOM_PRESET_CURRENT_USER: '<?=GetMessageJS("MENU_CUSTOM_PRESET_CURRENT_USER")?>',
		MENU_CUSTOM_PRESET_NEW_USER: '<?=GetMessageJS("MENU_CUSTOM_PRESET_NEW_USER")?>',
		MENU_SET_CUSTOM_PRESET: '<?=GetMessageJS("MENU_SET_CUSTOM_PRESET")?>',
		MENU_CUSTOM_PRESET_SEPARATOR: '<?=GetMessageJS("MENU_CUSTOM_PRESET_SEPARATOR")?>',
		MENU_DELETE_CUSTOM_PRESET_CONFIRM: '<?=GetMessageJS("MENU_DELETE_CUSTOM_PRESET_CONFIRM")?>',
		MENU_CUSTOM_PRESET_SUCCESS: '<?=GetMessageJS("MENU_CUSTOM_PRESET_SUCCESS2")?>',
		MENU_DELETE_CUSTOM_ITEM_FROM_ALL: '<?=GetMessageJS("MENU_DELETE_CUSTOM_ITEM_FROM_ALL")?>',
		MENU_SETTINGS_MODE: '<?=GetMessageJS("MENU_SETTINGS_MODE")?>',
		MENU_EDIT_READY_FULL: '<?=GetMessageJS("MENU_EDIT_READY_FULL")?>'
	});

	BX.Bitrix24.LeftMenuClass.init(<?=CUtil::PhpToJSObject($arJSParams)?>);
</script>

<?
// for composite
$js = <<<HTML

<script>
if (!BX.Bitrix24.LeftMenuClass.initPagetitleStar())
{
	BX.ready(function() {
		BX.Bitrix24.LeftMenuClass.initPagetitleStar()
	});
}

</script>
HTML;


$APPLICATION->AddViewContent("below_pagetitle", $js, 10);

// item map for header
if (count($arResult["MAP_ITEMS"]))
{
?>
	<div class="sitemap-content" id="sitemap-content">
		<? $previousDepthLevel = 0; ?>
		<? foreach ($arResult["MAP_ITEMS"] as $index => $item):

			if ($item["PERMISSION"] <= "D")
			{
				continue;
			}

			$link = isset($item["PARAMS"]["real_link"]) ? $item["PARAMS"]["real_link"] : $item["LINK"];

			$link = htmlspecialcharsbx($link, ENT_COMPAT, false);
			$item["TEXT"] = htmlspecialcharsbx($item["TEXT"], ENT_COMPAT, false);
		?>
			<? if ($item["DEPTH_LEVEL"] === 1): ?>
				<? if ($previousDepthLevel):?>
					</div>
				</div>
				<? endif ?>
			<div class="sitemap-section">
				<a class="sitemap-section-title" href="<?=$link?>"><?=$item["TEXT"]?></a>
				<div class="sitemap-section-items">
			<? else: ?>
				<a class="sitemap-section-item" href="<?=$link?>"><?=$item["TEXT"]?></a>
			<? endif ?>
			<? $previousDepthLevel = $item["DEPTH_LEVEL"] ?>
		<? endforeach ?>

		<? if ($previousDepthLevel): ?>
				</div>
			</div>
		<? endif ?>
		<div class="sitemap-close-link" id="sitemap-close-link" title="<?=GetMessage("MAP_CLOSE_LINK")?>"></div>
	</div>
	<script>
		BX.ready(function() {
			new BX.Bitrix24.Map();
		});
	</script>

	<?
	$this->SetViewTarget("sitemap");
		?><div class="sitemap-menu" id="sitemap-menu"><span class="sitemap-menu-lines"></span></div><?
	$this->EndViewTarget();
}

if ($groupPopupExists):
?>

<script>
	BX.ready(function() {
		new BX.Bitrix24.GroupPanel({
			ajaxPath: "<?=$this->getFolder()?>/ajax.php",
			siteId: "<?=SITE_ID?>"
		});
	});
</script>

<?
$filter = CUserOptions::GetOption("intranet", "left_menu_group_filter_".SITE_ID, "all");;
?>
<div class="group-panel-content group-panel-content-<?=$filter?>" data-filter="<?=$filter?>" id="group-panel-content">
	<div class="group-panel-header">
		<span class="group-panel-header-filters">
			<span
				class="group-panel-header-filter group-panel-header-filter-all"
				data-filter="all"><?=GetMessage("MENU_MY_WORKGROUPS")?></span>
			<? if (isModuleInstalled("extranet")):?>
			<span
				class="group-panel-header-filter group-panel-header-filter-extranet"
				data-filter="extranet"><?=GetMessage("MENU_MY_WORKGROUPS_EXTRANET")?></span>
			<? endif ?>
			<span
				class="group-panel-header-filter group-panel-header-filter-favorites"
				data-filter="favorites"><?=GetMessage("MENU_MY_WORKGROUPS_FAVORITES")?>
					<span class="group-panel-header-filter-counter" id="group-panel-header-filter-counter"></span>
			</span>
		</span>
	</div>
	<div class="group-panel-items" id="group-panel-items"><?
		foreach ($arResult["GROUPS"] as $index => $group):
			$className = "group-panel-item";
			$className .= $group["EXTRANET"] ? " group-panel-item-extranet" : " group-panel-item-intranet";
			$className .= $group["FAVORITE"] ? " group-panel-item-favorite" : "";
			?><a href="/workgroups/group/<?=$group["ID"]?>/" class="<?=$className?>" data-id="<?=$group["ID"]?>"><?
				?><span
					class="group-panel-item-text"
					title="<?=htmlspecialcharsbx($group["NAME"])?>"><?=htmlspecialcharsbx($group["NAME"])?></span><?
				?><span class="group-panel-item-star"></span><?
			?></a><?
		endforeach
	?></div>
	<div class="sitemap-close-link group-panel-close-link" id="group-panel-close-link"></div>
</div>
<? endif ?>