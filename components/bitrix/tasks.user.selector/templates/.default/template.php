<?
if(!Defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\UI;

UI\Extension::load("ui.tooltip");

$ajaxUrl = $this->__component->GetPath() 
	. "/ajax.php?lang=" . LANGUAGE_ID 
	. "&SITE_ID=" . $arParams["SITE_ID"]
	. "&GROUP_SITE_ID=" . $GLOBALS["GROUP_SITE_ID"]
	. "&IS_EXTRANET=".(($bExtranet ?? null) ? "Y" : "N")
	. "&SHOW_INACTIVE_USERS=" . $arParams['SHOW_INACTIVE_USERS']
	. "&nt=".urlencode($arParams["NAME_TEMPLATE"])
?>
<script>
	BX.message({
		TASKS_EMP_HEAD : '<?php echo GetMessage("TASKS_EMP_HEAD")?>'
	});
	TasksUsers.lastUsers = <?php echo CUtil::PhpToJSObject($arResult["LAST_USERS_IDS"])?>;


	var O_<?php echo $arResult["NAME"]?> = new TasksUsers("<?php echo $arResult["NAME"]?>", <?php echo $arParams["MULTIPLE"] == "Y" ? "true" : "false"?>, <?php echo ($arParams["SUBORDINATE_ONLY"] ?? null) == "Y" ? "true" : "false"?>);

	O_<?php echo $arResult["NAME"]?>.ajaxUrl = '<?php echo $ajaxUrl; ?>';

	<?php foreach($arResult["CURRENT_USERS"] as $user):?>
		O_<?php echo $arResult["NAME"]?>.arSelected[<?php echo $user["ID"]?>] = {id : <?php echo CUtil::JSEscape($user["ID"])?>, name : "<?php echo CUtil::JSEscape($user["~NAME"])?>", sub : <?php echo $user["SUBORDINATE"] == "Y" ? "true" : "false"?>, sup : <?php echo $user["SUPERORDINATE"] == "Y" ? "true" : "false"?>, position : "<?php echo CUtil::JSEscape($user["WORK_POSITION"])?>", photo : "<?php echo CUtil::JSEscape($user["PHOTO"])?>"};
		TasksUsers.arEmployeesData[<?php echo $user["ID"]?>] = {id : <?php echo CUtil::JSEscape($user["ID"])?>, name : "<?php echo CUtil::JSEscape($user["~NAME"])?>", sub : <?php echo $user["SUBORDINATE"] == "Y" ? "true" : "false"?>, sup : <?php echo $user["SUPERORDINATE"] == "Y" ? "true" : "false"?>, position : "<?php echo CUtil::JSEscape($user["WORK_POSITION"])?>", photo : "<?php echo CUtil::JSEscape($user["PHOTO"])?>"};
	<?php endforeach?>

	<?php foreach($arResult["LAST_USERS"] as $user):?>
		TasksUsers.arEmployeesData[<?php echo $user["ID"]?>] = {id : <?php echo CUtil::JSEscape($user["ID"])?>, name : "<?php echo CUtil::JSEscape($user["NAME"])?>", sub : <?php echo $user["SUBORDINATE"] == "Y" ? "true" : "false"?>, position : "<?php echo CUtil::JSEscape($user["WORK_POSITION"])?>", sup : <?php echo $user["SUPERORDINATE"] == "Y" ? "true" : "false"?>, photo : "<?php echo CUtil::JSEscape($user["PHOTO"])?>"};
	<?php endforeach?>

	BX.ready(function() {
		<?php if ($arParams["FORM_NAME"] <> '' && $arParams["INPUT_NAME"] <> ''):?>
			O_<?php echo $arResult["NAME"]?>.searchInput = document.forms["<?php echo CUtil::JSEscape($arParams["FORM_NAME"])?>"].element["<?php echo CUtil::JSEscape($arParams["INPUT_NAME"])?>"];
		<?php elseif($arParams["INPUT_NAME"] <> ''):?>
			O_<?php echo $arResult["NAME"]?>.searchInput = BX("<?php echo CUtil::JSEscape($arParams["INPUT_NAME"])?>");
		<?php else:?>
			O_<?php echo $arResult["NAME"]?>.searchInput = BX("<?php echo $arResult["NAME"]?>_user_input");
		<?php endif?>

		<?php if (($arParams["ON_CHANGE"] ?? null) <> ''):?>
			O_<?php echo $arResult["NAME"]?>.onChange = <?php echo CUtil::JSEscape($arParams["ON_CHANGE"])?>;
		<?php endif?>

		<?php if ($arParams["ON_SELECT"] <> ''):?>
			O_<?php echo $arResult["NAME"]?>.onSelect= <?php echo CUtil::JSEscape($arParams["ON_SELECT"])?>;
		<?php endif?>

		BX.bind(O_<?php echo $arResult["NAME"]?>.searchInput, "keyup", BX.proxy(O_<?php echo $arResult["NAME"]?>.search, O_<?php echo $arResult["NAME"]?>));
		BX.bind(O_<?php echo $arResult["NAME"]?>.searchInput, "focus", BX.proxy(O_<?php echo $arResult["NAME"]?>._onFocus, O_<?php echo $arResult["NAME"]?>));
	});
</script>

<div id="<?php echo $arParams["NAME"]?>_selector_content" class="finder-box<?php if ($arParams["MULTIPLE"] == "Y"):?> finder-box-multiple<?php endif?>"<?php echo $arParams["POPUP"] == "Y" ? " style=\"display: none;\"" : ""?>>
	<table class="finder-box-layout" cellspacing="0">
		<tr>
			<td class="finder-box-left-column">
				<?php if (!isset($arParams["INPUT_NAME"]) || $arParams["INPUT_NAME"] == ''):?>
				<div class="finder-box-search"><input name="<?php echo $arResult["NAME"]?>_user_input" id="<?php echo $arResult["NAME"]?>_user_input" class="finder-box-search-textbox" /></div>
				<?php endif?>

				<div class="finder-box-tabs">
					<span class="finder-box-tab finder-box-tab-selected" id="<?php echo $arResult["NAME"]?>_tab_last" onclick="O_<?php echo $arResult["NAME"]?>.displayTab('last');"><span class="finder-box-tab-left"></span><span class="finder-box-tab-text"><?php echo GetMessage("TASKS_LAST_SELECTED")?></span><span class="finder-box-tab-right"></span></span><span class="finder-box-tab" id="<?php echo $arResult["NAME"]?>_tab_structure" onclick="O_<?php echo $arResult["NAME"]?>.displayTab('structure');"><span class="finder-box-tab-left"></span><span class="finder-box-tab-text"><?php echo GetMessage("TASKS_USER_STRUCTURE")?></span><span class="finder-box-tab-right"></span></span><span class="finder-box-tab" id="<?php echo $arResult["NAME"]?>_tab_search" onclick="O_<?php echo $arResult["NAME"]?>.displayTab('search');"><span class="finder-box-tab-left"></span><span class="finder-box-tab-text"><?php echo GetMessage("TASKS_USER_SEARCH")?></span><span class="finder-box-tab-right"></span></span>
				</div>

				<div class="popup-window-hr popup-window-buttons-hr"><i></i></div>

				<div class="finder-box-tabs-content">
					<div class="finder-box-tab-content finder-box-tab-content-selected" id="<?php echo $arResult["NAME"]?>_last">
						<table class="finder-box-tab-columns" cellspacing="0">
							<tr>
								<td>
									<?php
									foreach($arResult["LAST_USERS"] as $key=>$user)
									{
										$anchor_id = RandString(16);
										?>
										<div class="finder-box-item<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " finder-box-item-selected" : "")?>" 
											id="<?php echo $arResult["NAME"]?>_last_employee_<?php echo $user["ID"]?>" 
											onclick="O_<?php echo $arResult["NAME"]?>.select(event)">
											<?php if ($arParams["MULTIPLE"] == "Y"):?>
												<input type="checkbox" name="<?php echo $arResult["NAME"]?>[]" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php else:?>
												<input type="radio" name="<?php echo $arResult["NAME"]?>" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php endif?>
											<div class="finder-box-item-text" id="anchor_created_<?php echo $anchor_id; ?>" bx-tooltip-user-id="<?=$user["ID"]?>"><?php
												echo $user["NAME"];
												/*
												TODO: good look and feel
												if (strlen($user['WORK_POSITION']) > 0)
													echo ', ' . $user['WORK_POSITION'] . ''; */
											?></div>
											<div class="<?php if ($arParams['MULTIPLE'] === 'Y') echo ' finder-box-item-icon '; ?>"></div>
										</div>
										<?php if ($key == ceil(sizeof($arResult["LAST_USERS"]) / 2) - 1):?>
										</td><td>
										<?php endif;
									}
									
									foreach($arResult["CURRENT_USERS"] as $key=>$user)
									{
										if (!in_array($user, $arResult["LAST_USERS"])):?>
											<?php if ($arParams["MULTIPLE"] == "Y"):?>
												<input type="checkbox" name="<?php echo $arResult["NAME"]?>[]" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php else:?>
												<input type="radio" name="<?php echo $arResult["NAME"]?>" value="<?php echo $user["ID"]?>"<?php echo (in_array($user["ID"], $arParams["VALUE"]) ? " checked" : "")?> class="tasks-hidden-input" />
											<?php endif?>
										<?php endif;
									}
									?>
								</td>
							</tr>
						</table>
					</div>
					<div class="finder-box-tab-content" id="<?php echo $arResult["NAME"]?>_structure">
						<div class="company-structure">
							<?php TasksEmployeeDrawStructure($arResult["STRUCTURE"], $arResult["SECTIONS"], 0, $arResult["NAME"]);?>
							<script>
								TasksUsers.arEmployees['<?php echo CUtil::JSEscape($arResult["STRUCTURE"][0][0]); ?>'] = <?=$arResult['ROOT_DEP_USER'];?>;
								O_<?php echo $arResult["NAME"]?>.load('<?php echo CUtil::JSEscape($arResult["STRUCTURE"][0][0]); ?>');
							</script>
						</div>
					</div>
					<div class="finder-box-tab-content" id="<?php echo $arResult["NAME"]?>_search"></div>
				</div>
			</td>
			<?php if ($arParams["MULTIPLE"] == "Y"):?>
			<td class="finder-box-right-column" id="<?php echo $arResult["NAME"]?>_selected_users">
				<div class="finder-box-selected-title"><?php echo GetMessage("TASKS_EMP_CURRENT_COUNT")?> (<span id="<?php echo $arResult["NAME"]?>_current_count"><?php echo sizeof($arResult["CURRENT_USERS"])?></span>)</div>
				<div class="finder-box-selected-items">
					<?php foreach($arResult["CURRENT_USERS"] as $user):?>
						<div class="finder-box-selected-item" id="<?php echo $arResult["NAME"]?>_employee_selected_<?php echo $user["ID"]?>"><div class="finder-box-selected-item-icon" onclick="O_<?php echo $arResult["NAME"]?>.unselect(<?php echo $user["ID"]?>, this);"></div><span class="finder-box-selected-item-text"><?php echo $user["NAME"]?></span></div>
					<?php endforeach?>
				</div>
			</td>
			<?php endif?>
		</tr>
	</table>
</div>