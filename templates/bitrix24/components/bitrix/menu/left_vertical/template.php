<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Counter\CounterDictionary as TasksCounterDictionary;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}
CJSCore::Init([
	'main.core',
	'ui.draganddrop.draggable',
	'ui.dialogs.messagebox']);
$isCompositeMode = defined("USE_HTML_STATIC_CACHE") ? true : false;
$this->setFrameMode(true);

if (empty($arResult))
{
	return;
}

$sumHiddenCounters = 0;
$arHiddenItemsCounters = array();
$arAllItemsCounters = array();
$groupPopupExists = false;

?>
<div class="menu-items-block menu-items-view-mode" id="menu-items-block">
	<div class="menu-items-header"><?
		include($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/bitrix24/logo.php");
		?><div class="menu-items-header-title"><?=Loc::getMessage("MENU_EXPAND")?></div>
	</div>
	<div class="menu-items-body">
		<div class="menu-items-body-inner"><?
		foreach (array("show", "hide") as $status)
		{
			if ($status === "hide")
			{
				?><div class="menu-item-favorites-more" id="left-menu-hidden-items-block"><?
					?><ul class="menu-items-fav-more-block" id="left-menu-hidden-items-list"><?
						?><li class="menu-item-separator" id="left-menu-hidden-separator">
							<span class="menu-item-sepor-text-line"></span><?
							?><span class="menu-item-sepor-text"><?=Loc::getMessage("MENU_HIDDEN_ITEMS")?></span>
							<span class="menu-item-sepor-text-line"></span><?
						?></li><?
			}
			else
			{
				?><ul class="menu-items"><?
					?><li class="menu-items-empty-li" id="left-menu-empty-item"></li><?
			}

			if (isset($arResult["ITEMS"][$status]) && is_array($arResult["ITEMS"][$status]))
			{
				$chain = [];
				foreach ($arResult["ITEMS"][$status] as $item)
				{
					if ($item["PERMISSION"] <= "D")
					{
						continue;
					}

					$counterId = "";
					$counter = 0;
					if (array_key_exists("counter_id", $item["PARAMS"]) && $item["PARAMS"]["counter_id"] <> '')
					{
						switch ($item['PARAMS']['counter_id'])
						{
							case 'live-feed':
								$counterId = \CUserCounter::LIVEFEED_CODE;
								break;
							default:
								$counterId = $item['PARAMS']['counter_id'];
						}

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

					if (isset($item["PARAMS"]["real_link"]))
					{
						$addLinks .= ($addLinks === "" ? "" : ",").$item["LINK"];
					}

					$curLink = '';
					if (isset($item["PARAMS"]["real_link"]) && is_string($item["PARAMS"]["real_link"]))
					{
						$curLink = $item["PARAMS"]["real_link"];
					}
					else
					{
						$curLink = isset($item["LINK"]) && is_string($item["LINK"]) ? $item["LINK"] : '';
					}

					if (preg_match("~^".SITE_DIR."index\\.php~i", $curLink))
					{
						$curLink = SITE_DIR;
					}
					elseif (isset($item["PARAMS"]["onclick"]) && !empty($item["PARAMS"]["onclick"]))
					{
						$curLink = "";
					}

					$itemId = $item["PARAMS"]["menu_item_id"];
					$isCustomItem = preg_match("/^[0-9]+$/", $itemId) === 1;
					$isCustomSection =
						isset($item['PARAMS']['is_custom_section'])
							? (bool)$item['PARAMS']['is_custom_section']
							: false
					;

					$itemClass = "menu-item-block";
					if (!$isCustomItem)
					{
						$itemClass .= " ".str_replace("_", "-", $itemId);
					}

					if ($isCompositeMode === false && $counter > 0 && $counterId <> '')
					{
						$itemClass .= " menu-item-with-index";
					}

					if (IsModuleInstalled("bitrix24") && $item["PARAMS"]["menu_item_id"] == "menu_live_feed")
					{
						$itemClass .= " menu-item-live-feed";
					}

					if (isset($item['IS_GROUP']) && $item['IS_GROUP'] === 'Y')
					{
						$itemClass .= " menu-item-group";
					}
					else if ($item["ITEM_TYPE"] !== "default" || $isCustomItem || $isCustomSection)
					{
						$itemClass .= " menu-item-no-icon-state";
					}

					while ($lastParent = end($chain))
					{
						if (isset($item['GROUP_ID'])
							&& $item['GROUP_ID'] === $lastParent)
						{
							break;
						}
							array_shift($chain);
						?></ul><?
					?></li><?
					}
					?><li id="bx_left_menu_<?=$itemId?>"
						data-status="<?=$status?>"
						data-id="<?=$item["PARAMS"]["menu_item_id"]?>"
						data-role="<?= isset($item['IS_GROUP']) && $item['IS_GROUP'] === 'Y' ? 'group' : 'item' ?>"
						<? if (isset($item['IS_GROUP']) && $item['IS_GROUP'] === 'Y'):?>
							data-collapse-mode="<?=$item['PARAMS']['collapse_mode']?>"
						<?endif ?>
						data-storage="<?= $item['PARAMS']['storage'] ?? '' ?>"
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
						data-new-page="<?=(isset($item["OPEN_IN_NEW_PAGE"]) && $item["OPEN_IN_NEW_PAGE"] === "Y" ? "Y" : "N")?>"
						<? if (array_key_exists("can_be_first_item", $item["PARAMS"]) && !$item["PARAMS"]["can_be_first_item"]) :?>
						data-disable-first-item="Y"
						<? endif ?>
						class="<?=$itemClass?>"
					><?
						?><span
							class="menu-favorites-btn menu-favorites-draggable"
						><?
							?><span class="menu-fav-draggable-icon"></span>
						</span><?

						if (isset($item["PARAMS"]["sub_link"])):
							?><a href="<?=htmlspecialcharsbx($item["PARAMS"]["sub_link"])?>" class="menu-item-plus">
								<span class="menu-item-plus-icon"></span>
							</a><?
						endif

						?><a
							class="menu-item-link"
							href="<?=(isset($item["PARAMS"]["onclick"])) ? "javascript:void(0)" : $curLink?>"
							<?if (isset($item["OPEN_IN_NEW_PAGE"]) && ($item["OPEN_IN_NEW_PAGE"] === "Y")):?>
								target="_blank"
							<?endif?>
							<?= (mb_strpos($curLink, SITE_DIR . 'workgroups/group/') === 0 ? 'data-slider-ignore-autobinding="true"' : '') ?>
							onclick="<?if (isset($item["PARAMS"]["onclick"])):?>
								<?=htmlspecialcharsbx($item["PARAMS"]["onclick"])?>
							<?endif?>">
							<span class="menu-item-icon-box"><span class="menu-item-icon"></span></span><?
							?><span class="menu-item-link-text <? echo isset($item["PARAMS"]["is_beta"]) ? ' menu-item-link-beta' : ''?>" data-role="item-text"><?
							echo $item["TEXT"];
							?></span><?
							if (isset($item["PARAMS"]["is_beta"]))
							{
								?><span class="menu-item-beta">beta</span><?
							}
							if ($counterId <> '')
							{
								$valueCounter = "";
								$badgeCounter = "";
								if ($isCompositeMode === false)
								{
									$valueCounter = intval($counter);
									$badgeCounter =  $counter > 99 ? "99+" : $counter;
								}
								?>
								<span class="menu-item-index-wrap">
									<span
										data-role="counter"
										data-counter-value="<?=$valueCounter?>"
										class="menu-item-index"
										id="menu-counter-<?= mb_strtolower($item["PARAMS"]["counter_id"])?>"><?=$badgeCounter?></span>
									</span>
							<?
							}
							if (isset($item['IS_GROUP']) && $item['IS_GROUP'] === 'Y'):?>
								<span class="menu-item-link-arrow"></span>
							<?endif ?>
						</a><?
						$editBtnHideClass = "";
						if ($item["PARAMS"]["menu_item_id"] === "menu_all_groups" && count($arResult["GROUPS"])):
							$groupPopupExists = true;
							$editBtnHideClass = " menu-fav-editable-btn-hide";
							?><span class="menu-item-show-link" id="menu-all-groups-link"><?=Loc::getMessage("MENU_SHOW")?></span><?
						endif
						?><span data-role="item-edit-control" class="menu-fav-editable-btn menu-favorites-btn<?=$editBtnHideClass?>"><?
							?><span class="menu-favorites-btn-icon"></span><?
						?></span><?
					?></li><?

					if (isset($item['IS_GROUP']) && $item['IS_GROUP'] === 'Y')
					{
						$chain[] = $item['ID'];

					?><li class="menu-item-group-more" id="bx_left_menu_<?=$itemId?>_parent" data-group-id="<?=$itemId?>" data-role="group-content"><?
						?><ul class="menu-item-group-more-ul"><?
					}
				}
				while ($lastParent = array_shift($chain))
				{
						?></ul><?
					?></li><?
				}
			}

			if ($status === "hide")
			{
						?><li class="menu-items-hidden-empty-li" id="left-menu-hidden-empty-item"></li><?
					?></ul><?
				?></div><?
			}
			else
			{
			?>
				</ul>
			<?
			}
		}
		?>

		<div class="menu-favorites-more-btn<?if (empty($arResult["ITEMS"]["hide"])):?> menu-favorites-more-btn-hidden<?endif?>">
			<div class="menu-collapsed-more-btn">
				<span class="menu-favorites-more-icon"></span>
			</div>
			<div class="menu-default-more-btn">
				<span
					class="menu-favorites-more-text"
					id="menu-more-btn-text"
				><?=Loc::getMessage("MENU_MORE_ITEMS_SHOW")?></span>
				<span class="menu-favorites-more-icon"></span>
			</div>
			<?if ($isCompositeMode || $sumHiddenCounters <= 0):?>
			<span id="menu-hidden-counter" class="menu-item-index menu-item-index-more menu-hidden-counter" data-counter-value="0"></span>
			<?else:?>
			<span id="menu-hidden-counter" class="menu-item-index menu-item-index-more" data-counter-value="<?=$sumHiddenCounters?>"><?
				?><?=($sumHiddenCounters > 99 ? "99+" : $sumHiddenCounters)
			?></span>
			<?endif;?>
		</div>

		<div class="menu-extra-btn-box">
			<div class="menu-settings-save-btn"><?=Loc::getMessage("MENU_EDIT_READY_FULL")?></div>

			<div class="menu-help-btn">
				<span class="menu-help-icon-box">
					<span class="menu-help-icon"></span>
				</span>
				<span class="menu-help-btn-text"><?=Loc::getMessage("MENU_HELP")?></span>
			</div>

			<? if ($arResult["SHOW_SITEMAP_BUTTON"]): ?>
			<div class="menu-sitemap-btn">
				<span class="menu-sitemap-icon-box">
					<span class="menu-sitemap-icon"></span>
				</span>
				<span class="menu-sitemap-btn-text"><?=Loc::getMessage("MENU_SITE_MAP")?></span>
			</div>
			<? endif ?>

			<div data-bx-role="settings-container" class="menu-settings-btn">
				<span class="menu-settings-icon-box">
					<span class="menu-settings-icon"></span>
				</span>
				<span class="menu-settings-btn-text"><?=Loc::getMessage("MENU_SETTINGS_TITLE")?></span>
			</div>

			<?
			if (CModule::IncludeModule("bitrix24") && CBitrix24::isInvitingUsersAllowed()):?>
				<div class="menu-invite-employees" onclick="<?=CIntranetInviteDialog::showInviteDialogLink(
						[
							'analyticsLabel' => [
								'analyticsLabel[source]' => 'leftMenu',
							]
						]
				)?>">
					<span class="menu-invite-icon-box"><span class="menu-invite-icon"></span></span>
					<span class="menu-invite-employees-text"><?=Loc::getMessage("BITRIX24_INVITE_ACTION")?></span>
				</div>
			<? endif ?>

			<?
			if ($arResult["SHOW_LICENSE_BUTTON"]):

				$arJsParams = array(
					"LICENSE_PATH" => $arResult["B24_LICENSE_PATH"],
					"COUNTER_URL" => $arResult["LICENSE_BUTTON_COUNTER_URL"],
					"HOST" => $arResult["HOST_NAME"]
				);
				?>
				<div class="menu-license-all-container">
					<span
						class="menu-license-all menu-license-all-collapsed"
						onclick="
							if (BX.getClass('B24.upgradeButtonRedirect'))
								B24.upgradeButtonRedirect(<?=CUtil::PhpToJSObject($arJsParams)?>)"
					>
						<span class="menu-license-all-icon"></span>
						<span class="menu-license-all-text"></span>
					</span>
					<?if ($arResult["IS_DEMO_LICENSE"] && !empty($arResult["DEMO_DAYS"])):?>
						<span
							class="menu-license-all menu-license-all-default"
							onclick="
								if (BX.getClass('B24.upgradeButtonRedirect'))
								B24.upgradeButtonRedirect(<?=CUtil::PhpToJSObject($arJsParams)?>)"
						>
							<span class="menu-license-all-icon"></span>
							<span class="menu-license-all-text menu-license-demo-text">
								<?=Loc::getMessage("MENU_LICENSE_DEMO", [
									"#NUM_DAYS#" => '<span class="menu-license-all-days">'.$arResult["DEMO_DAYS"].'</span>'
								])?>
							</span>
							<span class="ui-btn ui-btn-xs ui-btn-light-border ui-btn-round ui-btn-themes menu-license-demo-button">
								<?=Loc::getMessage("MENU_LICENSE_BUY")?>
							</span>
						</span>
					<?else:?>
						<span
							class="menu-license-all menu-license-all-default"
							onclick="
								if (BX.getClass('B24.upgradeButtonRedirect'))
									B24.upgradeButtonRedirect(<?=CUtil::PhpToJSObject($arJsParams)?>)"
						>
							<span class="menu-license-all-icon"></span>
							<span class="menu-license-all-text"><?=Loc::getMessage("MENU_LICENSE_ALL")?></span>
						</span>
					<?endif?>
				</div>
			<?endif?>
		</div>
	</div>
	<div class="menu-btn-arrow-up">
		<span class="menu-btn-arrow-up-icon"></span>
	</div>
	</div>
</div>

<?
include($_SERVER["DOCUMENT_ROOT"].$this->GetFolder()."/menu_popup.php");

$arJSParams = array(
	"ajaxPath" => $this->GetFolder()."/ajax.php",
	"isAdmin" => $arResult["IS_ADMIN"],
	"hiddenCounters" => $arHiddenItemsCounters,
	"allCounters" => $arAllItemsCounters,
	"isExtranet" => $arResult["IS_EXTRANET"] ? "Y" : "N",
	"isCollapsedMode" => CUserOptions::GetOption("intranet", "left_menu_collapsed") === "Y",
	"isCustomPresetAvailable" => $arResult["IS_CUSTOM_PRESET_AVAILABLE"] ? "Y" : "N",
	"customPresetExists" => !empty($arResult["CUSTOM_PRESET_EXISTS"]) ? "Y" : "N",
	'workgroupsCounterData' => $arResult["WORKGROUP_COUNTER_DATA"],
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
	MENU_FIRST_ITEM_ERROR: '<?=GetMessageJS("MENU_FIRST_ITEM_ERROR")?>',
	MENU_COLLAPSE: '<?=GetMessageJS("MENU_COLLAPSE")?>',
	MENU_EXPAND: '<?=GetMessageJS("MENU_EXPAND")?>',
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
	MENU_EDIT_READY_FULL: '<?=GetMessageJS("MENU_EDIT_READY_FULL")?>',
	COUNTER_PROJECTS_MAJOR: '<?= \Bitrix\Main\Loader::includeModule('tasks') ? CUtil::JSEscape(TasksCounterDictionary::COUNTER_PROJECTS_MAJOR) : "" ?>',
	COUNTER_SCRUM_TOTAL_COMMENTS: '<?= \Bitrix\Main\Loader::includeModule('tasks') ? CUtil::JSEscape(TasksCounterDictionary::COUNTER_SCRUM_TOTAL_COMMENTS) : "" ?>',
});
BX.Intranet.LeftMenu = new BX.Intranet.Menu(<?=CUtil::PhpToJSObject($arJSParams)?>);
<?

if ($arResult["SHOW_PRESET_POPUP"] === true)
{
?>BX.Intranet.LeftMenu.showGlobalPreset();
<?
	if (isset($arResult["SHOW_IMPORT_CONFIGURATION"]))
	{
	?>
BX.addCustomEvent(BX.Intranet.LeftMenu, 'BX.Intranet.LeftMenu::onPresetIsPostponed', function() {
	BX.SidePanel.Instance.open('<?=\CUtil::JSEscape($arResult["URL_IMPORT_CONFIGURATION"])?>');
});<?
	}
}
else if (isset($arResult["SHOW_IMPORT_CONFIGURATION"]))
{
?>BX.SidePanel.Instance.open('<?=\CUtil::JSEscape($arResult["URL_IMPORT_CONFIGURATION"])?>');<?
}
?>
</script>
<?php
// for a composite
$js = <<<HTML

<script>
if (
	BX.Intranet
	&& BX.Intranet.LeftMenu
	&& !BX.Intranet.LeftMenu.initPagetitleStar()
)
{
	BX.ready(function() {
		BX.Intranet.LeftMenu.initPagetitleStar()
	});
}

</script>
HTML;


$APPLICATION->AddViewContent("below_pagetitle", $js, 10);

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
			?><a href="<?=SITE_DIR?>workgroups/group/<?=$group["ID"]?>/" class="<?=$className?>" data-id="<?=$group["ID"]?>" data-slider-ignore-autobinding="true"><?
				?><span
					class="group-panel-item-text"
					title="<?=htmlspecialcharsbx($group["NAME"])?>"><?=htmlspecialcharsbx($group["NAME"])?></span><?
				?><span class="group-panel-item-star"></span><?
			?></a><?
		endforeach
	?></div>
	<div class="sitemap-close-link group-panel-close-link" id="group-panel-close-link"></div>
</div><?
endif;

if ($arResult['SHOW_WHATS_NEW'])
{
	include(__DIR__ . '/whats-new/left-menu-new-structure.php');
}
