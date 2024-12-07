<?php
if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @global CMain $APPLICATION */
/** @var array $arParams */
/** @var array $arResult */

$APPLICATION->AddHeadScript('/bitrix/components/bitrix/intranet.user.selector.new/templates/.default/users.js');

$namePrefix = CUtil::jsEscape($arResult['NAME']);
$selectorName = 'O_' . $namePrefix;
$bExtranet ??= null;
$ajaxUrl = $this->__component->GetPath() . '/ajax.php?' .
	http_build_query(
		array(
			'lang'                => LANGUAGE_ID,
			'SITE_ID'             => $arParams["SITE_ID"],
			'GROUP_SITE_ID'       => $GLOBALS["GROUP_SITE_ID"],
			'IS_EXTRANET'         => ($bExtranet ? "Y" : "N"),
			'SHOW_INACTIVE_USERS' => $arParams['SHOW_INACTIVE_USERS'],
			'SHOW_EXTRANET_USERS' => $arParams['SHOW_EXTRANET_USERS'],
			'EX_GROUP'            => !empty($arParams["EX_GROUP"]) ? $arParams["EX_GROUP"] : '',
			'nt'                  => $arParams["NAME_TEMPLATE"],
			'sl'                  => $arParams["SHOW_LOGIN"],
			'SHOW_USERS'          => ($arParams['SHOW_STRUCTURE_ONLY'] == 'Y' ? 'N' : 'Y'),
			'SHOW_USER_PROFILE_URL' => $arParams['SHOW_USER_PROFILE_URL'],
		)
	);
?>
<script>
	BX.message({
		INTRANET_EMP_HEAD : '<?=GetMessageJS('INTRANET_EMP_HEAD')?>',
		INTRANET_EMP_WAIT : '<?=GetMessageJS('INTRANET_EMP_WAIT')?>'
	});
	IntranetUsers.lastUsers = <?=CUtil::PhpToJSObject($arResult["LAST_USERS_IDS"]); ?>;

	window['<?= $selectorName; ?>'] = new IntranetUsers('<?= $namePrefix; ?>', <?=($arParams["MULTIPLE"] == "Y" ? "true" : "false"); ?>, <?=($arParams["SUBORDINATE_ONLY"] == "Y" ? "true" : "false"); ?>);
	window['<?= $selectorName; ?>'].ajaxUrl = '<?=$ajaxUrl; ?>';
	window['<?= $selectorName; ?>'].arFixed = <?=CUtil::PhpToJSObject($arResult['FIXED_USERS']); ?>;

	<?php
	foreach($arResult["CURRENT_USERS"] as $user):
		$userData = CUtil::PhpToJSObject(
			array(
				'id' => $user["ID"],
				'name' => $user["~NAME"],
				'sub' => $user["SUBORDINATE"] === "Y",
				'sup' => $user["SUPERORDINATE"] === "Y",
				'position' => $user["~WORK_POSITION"],
				'photo' => $user["PHOTO"],
				'url' => $user['~USER_PROFILE_URL'],
			),
			false,
			false,
			true
		);
	?>
		window['<?= $selectorName; ?>'].arSelected[<?php echo $user["ID"]?>] = <?= $userData; ?>;
		IntranetUsers.arEmployeesData[<?php echo $user["ID"]?>] = <?= $userData; ?>;
	<?php
	endforeach;

	foreach($arResult["LAST_USERS"] as $user):
		$userData = CUtil::PhpToJSObject(
			array(
				'id' => $user["ID"],
				'name' => $user["~NAME"],
				'sub' => $user["SUBORDINATE"] === "Y",
				'sup' => $user["SUPERORDINATE"] === "Y",
				'position' => $user["~WORK_POSITION"],
				'photo' => $user["PHOTO"],
				'url' => $user['~USER_PROFILE_URL'],
			),
			false,
			false,
			true
		);
	?>
		IntranetUsers.arEmployeesData[<?php echo $user["ID"]?>] = <?= $userData; ?>;
	<?php
	endforeach;
	?>
	BX.ready(function() {
		<?php if (isset($arParams["FORM_NAME"], $arParams["INPUT_NAME"]) && $arParams["FORM_NAME"] <> '' && $arParams["INPUT_NAME"] <> ''):?>
			window['<?= $selectorName; ?>'].searchInput = document.forms["<?php echo CUtil::JSEscape($arParams["FORM_NAME"])?>"].element["<?php echo CUtil::JSEscape($arParams["INPUT_NAME"])?>"];
		<?php elseif(isset($arParams["INPUT_NAME"]) && $arParams["INPUT_NAME"] <> ''):?>
			window['<?= $selectorName; ?>'].searchInput = BX("<?php echo CUtil::JSEscape($arParams["INPUT_NAME"])?>");
		<?php else:?>
			window['<?= $selectorName; ?>'].searchInput = BX('<?= $namePrefix; ?>_user_input');
		<?php endif?>

		<?php if (isset($arParams["ON_CHANGE"]) && $arParams["ON_CHANGE"] <> ''):?>
			window['<?= $selectorName; ?>'].onChange = <?php echo CUtil::JSEscape($arParams["ON_CHANGE"])?>;
			window['<?= $selectorName; ?>'].onChange(window['<?= $selectorName; ?>'].arSelected);
		<?php endif?>

		<?php if (isset($arParams["ON_SELECT"]) && $arParams["ON_SELECT"] <> ''):?>
			window['<?= $selectorName; ?>'].onSelect= <?php echo CUtil::JSEscape($arParams["ON_SELECT"])?>;
		<?php elseif (isset($arParams["ON_SECTION_SELECT"]) && $arParams["ON_SECTION_SELECT"] <> ''):?>
			window['<?= $selectorName; ?>'].onSectionSelect= <?php echo CUtil::JSEscape($arParams["ON_SECTION_SELECT"])?>;
		<?php endif?>

		BX.bind(window['<?= $selectorName; ?>'].searchInput, "keyup", BX.proxy(window['<?= $selectorName; ?>'].search, window['<?= $selectorName; ?>']));
		BX.bind(window['<?= $selectorName; ?>'].searchInput, "focus", BX.proxy(window['<?= $selectorName; ?>']._onFocus, window['<?= $selectorName; ?>']));
	});
</script>

<div id="<?php echo $arParams["NAME"]?>_selector_content" class="finder-box<?php if ($arParams["MULTIPLE"] == "Y"):?> finder-box-multiple<?php endif?>"<?php echo $arParams["POPUP"] == "Y" ? " style=\"display: none;\"" : ""?>>
	<table class="finder-box-layout" cellspacing="0">
		<tr>
			<td class="finder-box-left-column">
				<?php if (!isset($arParams["INPUT_NAME"]) || $arParams["INPUT_NAME"] == ''):?>
				<div class="finder-box-search"><input name="<?php echo $arResult["NAME"]?>_user_input" autocomplete="off" id="<?php echo $arResult["NAME"]?>_user_input" class="finder-box-search-textbox" /></div>
				<?php endif?>
				<?php if($arParams["DISPLAY_TABS"] == 'Y'): ?>
					<div class="finder-box-tabs">
						<span class="finder-box-tab finder-box-tab-selected" id="<?php echo $arResult["NAME"]?>_tab_last" onclick="window['<?= $selectorName; ?>'].displayTab('last');">
							<span class="finder-box-tab-left"></span>
							<span class="finder-box-tab-text"><?php echo GetMessage("INTRANET_LAST_SELECTED")?></span>
							<span class="finder-box-tab-right"></span>
						</span>
						<?php if($arParams["DISPLAY_TAB_STRUCTURE"] == 'Y'): ?>
						<span class="finder-box-tab" id="<?php echo $arResult["NAME"]?>_tab_structure" onclick="window['<?= $selectorName; ?>'].displayTab('structure');">
							<span class="finder-box-tab-left"></span>
							<span class="finder-box-tab-text"><?php echo GetMessage("INTRANET_TAB_USER_STRUCTURE")?></span>
							<span class="finder-box-tab-right"></span>
						</span>
						<?php endif; ?>
						<?php if($arParams["DISPLAY_TAB_GROUP"] == 'Y'): ?>
						<span class="finder-box-tab" id="<?php echo $arResult["NAME"]?>_tab_groups" onclick="window['<?= $selectorName; ?>'].displayTab('groups');">
							<span class="finder-box-tab-left"></span>
							<span class="finder-box-tab-text"><?php echo GetMessage('INTRANET_TAB_USER_GROUPS'); ?></span>
							<span class="finder-box-tab-right"></span>
						</span>
						<?php endif; ?>
						<span class="finder-box-tab" id="<?php echo $arResult["NAME"]?>_tab_search" onclick="window['<?= $selectorName; ?>'].displayTab('search'), window['<?= $selectorName; ?>'].searchInput.focus();">
							<span class="finder-box-tab-left"></span>
							<span class="finder-box-tab-text"><?php echo GetMessage("INTRANET_USER_SEARCH")?></span>
							<span class="finder-box-tab-right"></span>
						</span>
					</div>

					<div class="popup-window-hr popup-window-buttons-hr"><i></i></div>
				<?php endif?>
				<div class="finder-box-tabs-content">
					<?php if($arParams["SHOW_STRUCTURE_ONLY"] != 'Y'): ?>
						<div class="finder-box-tab-content finder-box-tab-content-selected" id="<?php echo $arResult["NAME"]?>_last">
							<table class="finder-box-tab-columns" cellspacing="0">
								<tr>
									<td>
										<?php $i = 0;?>
										<?php foreach($arResult["LAST_USERS"] as $key=>$user):?>
											<div class="finder-box-item<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " finder-box-item-selected" : "")?>" id="<?php echo $arResult["NAME"]?>_last_employee_<?php echo $user["ID"]?>" onclick="window['<?= $selectorName; ?>'].select(event)">
												<?php if ($arParams["MULTIPLE"] == "Y"):?>
													<input type="checkbox" name="<?php echo $arResult["NAME"]?>[]" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="intranet-hidden-input" />
												<?php else:?>
													<input type="radio" name="<?php echo $arResult["NAME"]?>" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="intranet-hidden-input" />
												<?php endif?>
												<div class="finder-box-item-text" bx-tooltip-user-id="<?=$user["ID"]; ?>" bx-tooltip-classname="intrantet-user-selector-tooltip"><?php echo $user["NAME"]?></div>
												<div class="finder-box-item-icon"></div>
											</div>
											<?php if ($i == ceil(sizeof($arResult["LAST_USERS"]) / 2) - 1):?>
											</td><td>
											<?php endif?>
											<?php $i++;?>
										<?php endforeach?>
										<?php foreach($arResult["CURRENT_USERS"] as $key=>$user):?>
											<?php if (!in_array($user, $arResult["LAST_USERS"])):?>
												<?php if ($arParams["MULTIPLE"] == "Y"):?>
													<input type="checkbox" name="<?php echo $arResult["NAME"]?>[]" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="intranet-hidden-input" />
												<?php else:?>
													<input type="radio" name="<?php echo $arResult["NAME"]?>" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="intranet-hidden-input" />
												<?php endif?>
											<?php endif?>
										<?php endforeach?>
									</td>
								</tr>
							</table>
						</div>
					<?php endif?>
					<?php if($arParams["DISPLAY_TAB_STRUCTURE"] == 'Y'): ?>
					<div class="finder-box-tab-content<?=($arParams["SHOW_STRUCTURE_ONLY"] == 'Y' ? ' finder-box-tab-content-selected' : '')?>" id="<?php echo $arResult["NAME"]?>_structure">
						<div class="company-structure">
							<?php if (sizeof($arResult["STRUCTURE"]) > 0) CIntranetUserSelectorHelper::drawEmployeeStructure($arResult["STRUCTURE"], $arResult["SECTIONS"], 0, $arResult["NAME"], ($arParams["SHOW_STRUCTURE_ONLY"] == "Y"));?>
						</div>
					</div>
					<?php endif; ?>
					<?php if($arParams["DISPLAY_TAB_GROUP"] == 'Y'): ?>
					<div class="finder-box-tab-content" id="<?php echo $arResult["NAME"]?>_groups">
						<?php
							CIntranetUserSelectorHelper::drawGroup($arResult["GROUPS"], $arResult["NAME"]);
						?>
					</div>
					<?php endif; ?>
					<div class="finder-box-tab-content" id="<?php echo $arResult["NAME"]?>_search"></div>
				</div>
			</td>
			<?php if ($arParams["MULTIPLE"] == "Y"):?>
			<td class="finder-box-right-column" id="<?=$arResult["NAME"]; ?>_selected_users">
				<div class="finder-box-selected-title"><?=GetMessage("INTRANET_EMP_CURRENT_COUNT"); ?> (<span id="<?=$arResult["NAME"]; ?>_current_count"><?=sizeof($arResult["CURRENT_USERS"]); ?></span>)</div>
				<div class="finder-box-selected-items">
					<?php
					foreach($arResult["CURRENT_USERS"] as $user) { ?>
						<div
							class="finder-box-selected-item"
							id="<?=$arResult["NAME"]; ?>_employee_selected_<?=$user["ID"]; ?>"><div
								class="finder-box-selected-item-icon"
								id="<?=$arResult['NAME']; ?>-user-selector-unselect-<?=$user["ID"]; ?>"
								onclick="window['<?= $selectorName; ?>'].unselect(<?=$user["ID"]; ?>, this);"
								<?php
								if (in_array($user['ID'], $arResult['FIXED_USERS']))
								{
									?>style="visibility: hidden; "<?php
								} ?>></div><span class="finder-box-selected-item-text"><?=$user["NAME"]; ?></span></div>
					<?php
					} ?>
				</div>
			</td>
			<?php endif?>
		</tr>
	</table>
</div>