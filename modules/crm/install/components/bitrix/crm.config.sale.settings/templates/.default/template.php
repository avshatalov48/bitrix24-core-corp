<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

CUtil::InitJSCore(array("amcharts", "amcharts_funnel", "amcharts_serial", "ui.fonts.opensans"));
Bitrix\Main\Page\Asset::getInstance()->addCss("/bitrix/js/crm/css/crm.css");
Bitrix\Main\Page\Asset::getInstance()->addJs($this->GetFolder()."/drag_and_drop.js");

\Bitrix\Main\UI\Extension::load("popup");

$settingsId = $arResult["SETTINGS_ID"];
$settings = $arResult["SETTINGS"];
$pageSettings = $arResult["PAGE_SETTINGS"];
$semanticInfo = $settings["SEMANTIC_INFO"];

$jsClass = "CrmSaleSettings_".$pageSettings["RAND_STRING"];
$isSidePanel = (isset($_REQUEST['IFRAME']) && $_REQUEST['IFRAME'] === 'Y');

$initialFields = $settings["SORTED_FIELDS"]["INITIAL_FIELDS"];
$extraFields = $settings["SORTED_FIELDS"]["EXTRA_FIELDS"];
$finalFields = $settings["SORTED_FIELDS"]["FINAL_FIELDS"];
$extraFinalFields = $settings["SORTED_FIELDS"]["EXTRA_FINAL_FIELDS"];
$successFields = $settings["SORTED_FIELDS"]["SUCCESS_FIELDS"];
$unSuccessFields = $settings["SORTED_FIELDS"]["UNSUCCESS_FIELDS"];
?>
<form name="crmStatusForm" action="<?=$APPLICATION->GetCurPageParam()?>" method="POST" onsubmit="BX['<?=$jsClass?>'].confirmSubmit();">
<input type="hidden" name="ACTION" value="save" id="ACTION">
<input type="hidden" name="type" value="<?=$arResult["TYPE_SETTINGS"]?>">
<?=bitrix_sessid_post()?>

<div id="crm-container" class="crm-container">
<div class="crm-transaction-stage">

	<? if ($settings["TYPE"] == "SEPARATED"): ?>

	<div id="content_<?=$settingsId?>" class="crm-status-content">

		<!-- Initial stage -->
		<div class="transaction-stage transaction-initial-stage">
			<?
				$iconClass = "";
				$blockClass = "";
				$colorValue = $initialFields[$settingsId]["COLOR"] ? $initialFields[$settingsId]["COLOR"] : "#ACE9FB";
				$style = "background:".$colorValue.";";
				$style .= "color:".getColorText($colorValue, $iconClass, $blockClass).";";
			?>
			<div class="transaction-stage-title">
				<?=GetMessage("CRM_STATUS_TITLE_INITIAL_".$settingsId)?>
			</div>

			<div class="transaction-stage-phase" data-sort="<?=$initialFields[$settingsId]["SORT"]?>"
			     id="field-phase-<?=$initialFields[$settingsId]["ID"]?>" data-calculate="1" data-success="1"
			     ondblclick="BX['<?=$jsClass?>'].editField('<?=$initialFields[$settingsId]["ID"]?>');"
			     style="<?=htmlspecialcharsbx($style)?>">
				<div id="phase-panel" data-class="transaction-stage-phase-panel" class="<?=$blockClass?>
				    transaction-stage-phase-panel">
					<?if($initialFields[$settingsId]["SYSTEM"] == "Y" &&
						!empty($initialFields[$settingsId]["NAME_INIT"])):?>
						<div onclick="BX['<?=$jsClass?>'].recoveryName('<?=$initialFields[$settingsId]["ID"]?>',
							'<?=htmlspecialcharsbx($initialFields[$settingsId]["NAME_INIT"])?>')" class="
						transaction-stage-phase-panel-button transaction-stage-phase-panel-button-refresh" title="
						<?=GetMessage("CRM_STATUS_LIST_RECOVERY_NAME")?>"></div>
					<?endif?>
					<div class="transaction-stage-phase-panel-button"
					     title="<?=GetMessage("CRM_STATUS_EDIT_COLOR")?>"
					     onclick="BX['<?=$jsClass?>'].correctionColorPicker(event, '<?=$initialFields[$settingsId]["ID"]?>')">
					</div>
				</div>
				<span id="transaction-stage-phase-icon" class="<?=$iconClass?>
				    transaction-stage-phase-icon" data-class="transaction-stage-phase-icon">
					<span class="transaction-stage-phase-icon-arrow"></span>
				</span>
				<span id="phase-panel" data-class="transaction-stage-phase-title" class="<?=$blockClass?>
				    transaction-stage-phase-title">
					<span id="field-title-inner-<?=$initialFields[$settingsId]["ID"]?>" class="transaction-stage-phase-title-inner">
						<span id="field-name-<?=$initialFields[$settingsId]["ID"]?>" class="transaction-stage-phase-name">
							<?=$initialFields[$settingsId]["NUMBER"]?>.
							<?=htmlspecialcharsbx($initialFields[$settingsId]["NAME"])?>
						</span>
						<span onclick="BX['<?=$jsClass?>'].editField('<?=$initialFields[$settingsId]["ID"]?>');"
						      title="<?=GetMessage("CRM_STATUS_EDIT_NAME")?>"
						      class="transaction-stage-phase-icon-edit"></span>
					</span>
				</span>

				<input type="hidden" id="field-number-<?=$initialFields[$settingsId]["ID"]?>"
				       value="<?=$initialFields[$settingsId]["NUMBER"]?>">
				<input type="hidden" name="LIST[<?=$settingsId?>][<?=$initialFields[$settingsId]["ID"]?>][SORT]"
				       id="field-sort-<?=$initialFields[$settingsId]["ID"]?>"
				       value="<?=$initialFields[$settingsId]["SORT"]?>">
				<input type="hidden" name="LIST[<?=$settingsId?>][<?=$initialFields[$settingsId]["ID"]?>][VALUE]"
				       id="field-hidden-name-<?=$initialFields[$settingsId]["ID"]?>"
				       value="<?=htmlspecialcharsbx($initialFields[$settingsId]["NAME"])?>">
				<input type="hidden" name="LIST[<?=$settingsId?>][<?=$initialFields[$settingsId]["ID"]?>][COLOR]"
				       id="stage-color-<?=$initialFields[$settingsId]["ID"]?>"
				       value="<?=htmlspecialcharsbx($colorValue)?>">
				<input type="hidden" name="LIST[<?=$settingsId?>][<?=$initialFields[$settingsId]["ID"]?>][STATUS_ID]"
				       id="stage-status-id-<?=$initialFields[$settingsId]["ID"]?>" data-status-id="1"
				       value="<?=htmlspecialcharsbx($initialFields[$settingsId]["STATUS_ID"])?>">
			</div>
		</div>

		<!-- Extra stage -->
		<div id="extra-storage-<?=$settingsId?>" class="transaction-stage droppable">
			<div class="transaction-stage-title"><?=GetMessage("CRM_STATUS_TITLE_EXTRA_".$settingsId)?></div>
			<? foreach($extraFields[$settingsId] as $field):
				$blockClass = "";
				$iconClass = "";
				$colorValue = $field["COLOR"] ? $field["COLOR"] : "#ACE9FB";
				$style = "background:".$colorValue.";";
				$style .= "color:".getColorText($colorValue, $iconClass, $blockClass).";";
			?>
				<div class="transaction-stage-phase draghandle" data-calculate="1" data-success="1"
				     style="<?=htmlspecialcharsbx($style)?>" id="field-phase-<?=$field["ID"]?>" data-space="<?=$field["ID"]?>"
				     data-sort="<?=$field["SORT"]?>" ondblclick="BX['<?=$jsClass?>'].editField('<?=$field["ID"]?>');">
					<div id="phase-panel" data-class="transaction-stage-phase-panel"
					     class="<?=$blockClass?> transaction-stage-phase-panel">
						<?if(!empty($field["NAME_INIT"])):?>
							<div onclick="BX['<?=$jsClass?>'].recoveryName('<?=$field["ID"]?>',
									'<?=htmlspecialcharsbx($field["NAME_INIT"])?>')"
							     title="<?=GetMessage("CRM_STATUS_LIST_RECOVERY_NAME")?>"
							     class="transaction-stage-phase-panel-button
						transaction-stage-phase-panel-button-refresh"></div>
						<?endif?>
						<div class="transaction-stage-phase-panel-button"
						     title="<?=GetMessage("CRM_STATUS_EDIT_COLOR")?>"
						     onclick="BX['<?=$jsClass?>'].correctionColorPicker(event, '<?=$field["ID"]?>')">
						</div>
						<div onclick="BX['<?=$jsClass?>'].openPopupBeforeDeleteField('<?=$field["ID"]?>')"
						     title="<?=GetMessage("CRM_STATUS_DELETE_FIELD")?>"
						     class="transaction-stage-phase-panel-button transaction-stage-phase-panel-button-close">
						</div>
					</div>
					<span id="transaction-stage-phase-icon" class="<?=$iconClass?> transaction-stage-phase-icon
						transaction-stage-phase-icon-move draggable" data-class="transaction-stage-phase-icon
						transaction-stage-phase-icon-move draggable">
						<span class="transaction-stage-phase-icon-burger"></span>
					</span>
					<span id="phase-panel" data-class="transaction-stage-phase-title"
					      class="<?=$blockClass?> transaction-stage-phase-title">
						<span id="field-title-inner-<?=$field["ID"]?>" class="transaction-stage-phase-title-inner">
							<span id="field-name-<?=$field["ID"]?>" class="transaction-stage-phase-name">
								<?=$field["NUMBER"]?>. <?=htmlspecialcharsbx($field["NAME"])?>
							</span>
							<span onclick="BX['<?=$jsClass?>'].editField('<?=$field["ID"]?>');"
							      title="<?=GetMessage("CRM_STATUS_EDIT_NAME")?>"
							      class="transaction-stage-phase-icon-edit"></span>
						</span>
					</span>
					<input type="hidden" id="field-number-<?=$field["ID"]?>"
					       value="<?=$field["NUMBER"]?>">
					<input type="hidden" name="LIST[<?=$settingsId?>][<?=$field["ID"]?>][SORT]"
					       id="field-sort-<?=$field["ID"]?>"
					       value="<?=$field["SORT"]?>">
					<input type="hidden" name="LIST[<?=$settingsId?>][<?=$field["ID"]?>][VALUE]"
					       id="field-hidden-name-<?=$field["ID"]?>"
					       value="<?=htmlspecialcharsbx($field["NAME"])?>">
					<input type="hidden" name="LIST[<?=$settingsId?>][<?=$field["ID"]?>][COLOR]"
					       id="stage-color-<?=$field["ID"]?>"
					       value="<?=htmlspecialcharsbx($colorValue)?>">
					<input type="hidden" name="LIST[<?=$settingsId?>][<?=$field["ID"]?>][STATUS_ID]"
					       id="stage-status-id-<?=$field["ID"]?>" data-status-id="1"
					       value="<?=htmlspecialcharsbx($field["STATUS_ID"])?>">
				</div>
			<? endforeach; ?>
			<a href="javascript:void(0)" onclick="BX['<?=$jsClass?>'].addField(this);"
			   class="transaction-stage-addphase draghandle" data-space="main">+
				<span><?=isset($semanticInfo["ADD_CAPTION"]) ? $semanticInfo["ADD_CAPTION"] : GetMessage("CRM_STATUS_ADD")?></span>
			</a>
		</div>

		<!-- Final stage -->
		<div class="transaction-stage-final">
			<div class="transaction-stage-final-title">
				<span class="transaction-stage-final-title-sub"><?=GetMessage("CRM_STATUS_FINAL_TITLE")?></span>
			</div>
			<div class="transaction-stage-final-result">
				<div class="transaction-stage-final-success"><?=GetMessage("CRM_STATUS_SUCCESSFUL")?></div>
				<div class="transaction-stage-final-failure"><?=GetMessage("CRM_STATUS_UNSUCCESSFUL")?></div>
			</div>
			<div class="transaction-stage-final-column">

				<?
				$blockClass = "";
				$iconClass = "";
				$colorValue = $finalFields[$settingsId]["SUCCESSFUL"]["COLOR"] ?
					$finalFields[$settingsId]["SUCCESSFUL"]["COLOR"] : "#DBF199";
				$style = "background:".$colorValue.";";
				$style .= "color:".getColorText($colorValue, $iconClass, $blockClass).";";
				?>

				<div id="final-success-storage-<?=$settingsId?>" class="transaction-stage transaction-stage-success">
					<div class="transaction-stage-title"><?=GetMessage("CRM_STATUS_SUCCESSFUL_".$settingsId)?></div>
					<div ondblclick="BX['<?=$jsClass?>'].editField('<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>');"
					     class="transaction-stage-phase" data-sort="<?=$finalFields[$settingsId]["SUCCESSFUL"]["SORT"]?>"
					     id="field-phase-<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>" data-calculate="1"
					     style="<?=htmlspecialcharsbx($style)?>" data-success="1">
						<div id="phase-panel" data-class="transaction-stage-phase-panel"
						     class="<?=$blockClass?> transaction-stage-phase-panel">
							<?if($finalFields[$settingsId]["SUCCESSFUL"]["SYSTEM"] == "Y" &&
								!empty($finalFields[$settingsId]["SUCCESSFUL"]["NAME_INIT"])):?>
								<div onclick="BX['<?=$jsClass?>'].recoveryName(
										'<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>',
										'<?=htmlspecialcharsbx($finalFields[$settingsId]["SUCCESSFUL"]["NAME_INIT"])?>')"
								     title="<?=GetMessage("CRM_STATUS_LIST_RECOVERY_NAME")?>"
								     class="transaction-stage-phase-panel-button
									transaction-stage-phase-panel-button-refresh"></div>
							<?endif?>
							<div class="transaction-stage-phase-panel-button" title="
								<?=GetMessage("CRM_STATUS_EDIT_COLOR")?>" onclick="
									BX['<?=$jsClass?>'].correctionColorPicker(event,
									'<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>')">
							</div>
						</div>
						<span id="transaction-stage-phase-icon" class="<?=$iconClass?> transaction-stage-phase-icon"
						      data-class="transaction-stage-phase-icon">
						<span class="transaction-stage-phase-icon-arrow"></span>
					</span>
						<span id="phase-panel" data-class="transaction-stage-phase-title"
						      class="<?=$blockClass?> transaction-stage-phase-title">
						<span id="field-title-inner-<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>"
						      class="transaction-stage-phase-title-inner">
							<span id="field-name-<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>"
							      class="transaction-stage-phase-name">
								<?=$finalFields[$settingsId]["SUCCESSFUL"]["NUMBER"]?>.
								<?=htmlspecialcharsbx($finalFields[$settingsId]["SUCCESSFUL"]["NAME"])?>
							</span>
							<span onclick="BX['<?=$jsClass?>'].editField('<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>');"
							      class="transaction-stage-phase-icon-edit"
							      title="<?=GetMessage("CRM_STATUS_EDIT_NAME")?>"></span>
						</span>
					</span>
						<input type="hidden" id="field-number-<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>"
						       value="<?=$finalFields[$settingsId]["SUCCESSFUL"]["NUMBER"]?>">
						<input type="hidden" name="LIST[<?=$settingsId?>][<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>][SORT]"
						       id="field-sort-<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>"
						       value="<?=$finalFields[$settingsId]["SUCCESSFUL"]["SORT"]?>">
						<input type="hidden" name="LIST[<?=$settingsId?>][<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>][VALUE]"
						       id="field-hidden-name-<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>"
						       value="<?=htmlspecialcharsbx($finalFields[$settingsId]["SUCCESSFUL"]["NAME"])?>">
						<input type="hidden" name="LIST[<?=$settingsId?>][<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>][COLOR]"
						       id="stage-color-<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>"
						       value="<?=htmlspecialcharsbx($colorValue)?>">
						<input type="hidden" name="LIST[<?=$settingsId?>][<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>][STATUS_ID]"
						       id="stage-status-id-<?=$finalFields[$settingsId]["SUCCESSFUL"]["ID"]?>" data-status-id="1"
						       value="<?=htmlspecialcharsbx($finalFields[$settingsId]["SUCCESSFUL"]["STATUS_ID"])?>">
					</div>
				</div>
			</div>
			<div class="transaction-stage-final-column">

				<?
				$blockClass = "";
				$iconClass = "";
				$colorValue = $finalFields[$settingsId]["UNSUCCESSFUL"]["COLOR"] ?
					$finalFields[$settingsId]["UNSUCCESSFUL"]["COLOR"] : "#FFBEBD";
				$style = "background:".$colorValue.";";
				$style .= "color:".getColorText($colorValue, $iconClass, $blockClass).";";
				?>

				<div id="final-storage-<?=$settingsId?>" class="transaction-stage transaction-stage-failure droppable">
					<div class="transaction-stage-title"><?=GetMessage("CRM_STATUS_UNSUCCESSFUL_".$settingsId)?></div>
					<div ondblclick="BX['<?=$jsClass?>'].editField('<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>');"
					     class="transaction-stage-phase" data-sort="<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["SORT"]?>"
					     id="field-phase-<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>" data-calculate="1"
					     style="<?=htmlspecialcharsbx($style)?>" data-success="0">
						<div id="phase-panel" data-class="transaction-stage-phase-panel"
						     class="<?=$blockClass?> transaction-stage-phase-panel">
							<?if($finalFields[$settingsId]["UNSUCCESSFUL"]["SYSTEM"] == "Y" &&
								!empty($finalFields[$settingsId]["UNSUCCESSFUL"]["NAME_INIT"])):?>
								<div onclick="BX['<?=$jsClass?>'].recoveryName('<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>',
										'<?=htmlspecialcharsbx($finalFields[$settingsId]["UNSUCCESSFUL"]["NAME_INIT"])?>')"
								     title="<?=GetMessage("CRM_STATUS_LIST_RECOVERY_NAME")?>"
								     class="transaction-stage-phase-panel-button
									transaction-stage-phase-panel-button-refresh"></div>
							<?endif?>
							<div class="transaction-stage-phase-panel-button" title="
								<?=GetMessage("CRM_STATUS_EDIT_COLOR")?>" onclick="
									BX['<?=$jsClass?>'].correctionColorPicker(event,
									'<?= $finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>')">
							</div>
						</div>
						<span id="transaction-stage-phase-icon" class="<?=$iconClass?> transaction-stage-phase-icon"
						      data-class="transaction-stage-phase-icon">
						<span class="transaction-stage-phase-icon-arrow"></span>
					</span>
						<span id="phase-panel" data-class="transaction-stage-phase-title"
						      class="<?=$blockClass?> transaction-stage-phase-title">
						<span id="field-title-inner-<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>" class="transaction-stage-phase-title-inner">
							<span id="field-name-<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>" class="transaction-stage-phase-name">
								<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["NUMBER"]?>.
								<?=htmlspecialcharsbx($finalFields[$settingsId]["UNSUCCESSFUL"]["NAME"])?>
							</span>
							<span onclick="BX['<?=$jsClass?>'].editField('<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>');"
							      title="<?=GetMessage("CRM_STATUS_EDIT_NAME")?>"
							      class="transaction-stage-phase-icon-edit"></span>
						</span>
					</span>
						<input type="hidden" id="field-number-<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>"
						       value="<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["NUMBER"]?>">
						<input type="hidden" name="LIST[<?=$settingsId?>][<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>][SORT]"
						       id="field-sort-<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>"
						       value="<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["SORT"]?>">
						<input type="hidden" name="LIST[<?=$settingsId?>][<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>][VALUE]"
						       id="field-hidden-name-<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>"
						       value="<?=htmlspecialcharsbx($finalFields[$settingsId]["UNSUCCESSFUL"]["NAME"])?>">
						<input type="hidden" name="LIST[<?=$settingsId?>][<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>][COLOR]"
						       id="stage-color-<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>"
						       value="<?=htmlspecialcharsbx($colorValue)?>">
						<input type="hidden" name="LIST[<?=$settingsId?>][<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>][STATUS_ID]"
						       id="stage-status-id-<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["ID"]?>" data-status-id="1"
						       value="<?=htmlspecialcharsbx($finalFields[$settingsId]["UNSUCCESSFUL"]["STATUS_ID"])?>">
					</div>
					<? foreach($extraFinalFields[$settingsId] as $field): ?>

						<?
						$blockClass = "";
						$iconClass = "";
						$colorValue = $field["COLOR"] ? $field["COLOR"] : "#FFBEBD";
						$style = "background:".$colorValue.";";
						$style .= "color:".getColorText($colorValue, $iconClass, $blockClass).";";
						?>

						<div class="transaction-stage-phase draghandle" data-calculate="1"
						     data-space="<?=$field["ID"]?>" id="field-phase-<?=$field["ID"]?>"
						     data-sort="<?=$field["SORT"]?>" data-success="0"
						     ondblclick="BX['<?=$jsClass?>'].editField('<?=$field["ID"]?>');"
						     style="<?=htmlspecialcharsbx($style)?>">
							<div id="phase-panel" data-class="transaction-stage-phase-panel"
							     class="<?=$blockClass?> transaction-stage-phase-panel">
								<?if(!empty($field["NAME_INIT"])):?>
									<div onclick="BX['<?=$jsClass?>'].recoveryName('<?=$field["ID"]?>',
											'<?=htmlspecialcharsbx($field["NAME_INIT"])?>')"
									     title="<?=GetMessage("CRM_STATUS_LIST_RECOVERY_NAME")?>"
									     class="transaction-stage-phase-panel-button
										transaction-stage-phase-panel-button-refresh"></div>
								<?endif?>
								<div class="transaction-stage-phase-panel-button"
								     title="<?=GetMessage("CRM_STATUS_EDIT_COLOR")?>"
								     onclick="BX['<?=$jsClass?>'].correctionColorPicker(event, '<?=$field["ID"]?>')">
								</div>
								<div onclick="BX['<?=$jsClass?>'].openPopupBeforeDeleteField('<?=$field["ID"]?>')"
								     title="<?=GetMessage("CRM_STATUS_DELETE_FIELD")?>"
								     class="transaction-stage-phase-panel-button transaction-stage-phase-panel-button-close">
								</div>
							</div>
							<span id="transaction-stage-phase-icon" class="<?=$iconClass?>
							transaction-stage-phase-icon transaction-stage-phase-icon-move draggable"
							      data-class="transaction-stage-phase-icon transaction-stage-phase-icon-move draggable">
							<span class="transaction-stage-phase-icon-burger"></span>
						</span>
						<span id="phase-panel" data-class="transaction-stage-phase-title" class="<?=$blockClass?>
						    transaction-stage-phase-title">
						<span id="field-title-inner-<?=$field["ID"]?>" class="transaction-stage-phase-title-inner">
							<span id="field-name-<?=$field["ID"]?>" class="transaction-stage-phase-name">
								<?=$field["NUMBER"]?>.
								<?=htmlspecialcharsbx($field["NAME"])?>
							</span>
							<span onclick="BX['<?=$jsClass?>'].editField('<?=$field["ID"]?>');"
							title="<?=GetMessage("CRM_STATUS_EDIT_NAME")?>"class="transaction-stage-phase-icon-edit">
							</span>
						</span>
                        </span>
							<input type="hidden" id="field-number-<?=$field["ID"]?>"
							       value="<?=$field["NUMBER"]?>">
							<input type="hidden" name="LIST[<?=$settingsId?>][<?=$field["ID"]?>][SORT]"
							       id="field-sort-<?=$field["ID"]?>"
							       value="<?=$field["SORT"]?>">
							<input type="hidden" name="LIST[<?=$settingsId?>][<?=$field["ID"]?>][VALUE]"
							       id="field-hidden-name-<?=$field["ID"]?>"
							       value="<?=htmlspecialcharsbx($field["NAME"])?>">
							<input type="hidden" name="LIST[<?=$settingsId?>][<?=$field["ID"]?>][COLOR]"
							       id="stage-color-<?=$field["ID"]?>"
							       value="<?=htmlspecialcharsbx($colorValue)?>">
							<input type="hidden" name="LIST[<?=$settingsId?>][<?=$field["ID"]?>][STATUS_ID]"
							       id="stage-status-id-<?=$field["ID"]?>" data-status-id="1"
							       value="<?=htmlspecialcharsbx($field["STATUS_ID"])?>">
						</div>
					<? endforeach; ?>
					<a href="javascript:void(0)" onclick="BX['<?=$jsClass?>'].addField(this);"
					   data-space="final" class="transaction-stage-addphase draghandle">+
						<span><?=isset($semanticInfo["ADD_CAPTION"]) ? $semanticInfo["ADD_CAPTION"] : GetMessage("CRM_STATUS_ADD")?></span>
					</a>
				</div>
			</div>
		</div>

		<!-- Chart -->
		<div class="crm-previously">
			<div class="crm-previously-scale">
			<span class="crm-previously-title">
				<?=GetMessage("CRM_STATUS_VIEW_SCALE")?>
			</span>
				<table style="width: 100%;">
					<tbody>
					<tr>
						<td></td>
						<td class="crm-previously-line-top"><span>&nbsp;</span></td>
						<td>
							<table class="crm-previously-table">
								<tr>
									<td><span class="stage-name">&nbsp;</span></td>
								</tr>
								<tr id="previously-scale-final-success-<?=$settingsId?>">
									<td style="background:<?= (isset($finalFields[$settingsId]["SUCCESSFUL"]["COLOR"])) ?
										htmlspecialcharsbx($finalFields[$settingsId]["SUCCESSFUL"]["COLOR"]) : "#DBF199"?>">&nbsp;</td>
								</tr>
								<tr id="previously-scale-number-final-success-<?=$settingsId?>">
									<td>
									<span class="stage-name">
										<?=$finalFields[$settingsId]["SUCCESSFUL"]["NUMBER"]?>
									</span>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td>
							<table class="crm-previously-table">
								<tr></tr>
								<tr id="previously-scale-<?=$settingsId?>">
									<td data-scale-type="main"
									    style="background:<?=(isset($initialFields["COLOR"])) ?
											htmlspecialcharsbx($initialFields["COLOR"]) : "#ACE9FB"?>">&nbsp;</td>

									<?foreach($extraFields[$settingsId] as $field):?>
										<td data-scale-type="main"
										    style="background:<?= (isset($field["COLOR"])) ?
												htmlspecialcharsbx($field["COLOR"]) : "#ACE9FB"?>">&nbsp;</td>
									<?endforeach;?>
									<td id="previously-scale-final-cell-<?=$settingsId?>">
										<span class="stage-name"><?=GetMessage("CRM_STATUS_FINAL_TITLE")?></span>
									</td>
								</tr>
								<tr id="previously-scale-number-<?=$settingsId?>">
									<td data-scale-type="main">
									<span class="stage-name">
										<?=$initialFields["NUMBER"]?>
									</span>
									</td>
									<?foreach($extraFields[$settingsId] as $field):?>
										<td data-scale-type="main">
											<span class="stage-name"><?=$field["NUMBER"]?></span>
										</td>
									<?endforeach;?>
									<td id="previously-scale-number-final-cell-<?=$settingsId?>">
										<span class="stage-name">&nbsp;</span>
									</td>
								</tr>
							</table>
						</td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td></td>
						<td class="crm-previously-line-bottom"><span>&nbsp;</span></td>
						<td>
							<table class="crm-previously-table">
								<td><span class="stage-name">&nbsp;</span></td>
								<tr id="previously-scale-final-un-success-<?=$settingsId?>">
									<td style="background:<?=(isset($finalFields[$settingsId]["UNSUCCESSFUL"]["COLOR"])) ?
										htmlspecialcharsbx($finalFields[$settingsId]["UNSUCCESSFUL"]["COLOR"]) : "#FFBEBD"?>">&nbsp;</td>
									<?foreach($extraFinalFields[$settingsId] as $field):?>
										<td style="background:<?= (isset($field["COLOR"])) ?
											htmlspecialcharsbx($field["COLOR"]) : "#FFBEBD"?>">&nbsp;</td>
									<?endforeach;?>
								</tr>
								<tr id="previously-scale-number-final-un-success-<?=$settingsId?>">
									<td>
									<span class="stage-name">
										<?=$finalFields[$settingsId]["UNSUCCESSFUL"]["NUMBER"]?>
									</span>
									</td>
									<?foreach($extraFinalFields[$settingsId] as $field):?>
										<td><span class="stage-name"><?=$field["NUMBER"]?></span></td>
									<?endforeach;?>
								</tr>
							</table>
						</td>
					</tr>
					</tbody>
				</table>
			</div>

			<div class="crm-previously-funnels">
			<span class="crm-previously-title">
				<?=GetMessage("CRM_STATUS_VIEW_FUNNEL")?>
			</span>
				<div class="crm-previously-funnel">
				<span class="crm-previously-funnel-subtitle">
					<?=GetMessage("CRM_STATUS_FUNNEL_SUCCESSFUL_".$settingsId)?>
				</span>
					<div class="crm-previously-funnel-inner">
						<div id="config-funnel-success-<?=$settingsId?>" style="width: 100%; min-height: 300px;"></div>
					</div>
				</div>
				<div class="crm-previously-funnel crm-previously-funnel-failure">
				<span class="crm-previously-funnel-subtitle">
					<?=GetMessage("CRM_STATUS_FUNNEL_UNSUCCESSFUL_".$settingsId)?>
				</span>
					<div class="crm-previously-funnel-inner">
						<div id="config-funnel-unsuccess-<?=$settingsId?>" style="width: 100%; min-height: 300px;"></div>
					</div>
				</div>
				<script></script>
			</div>
		</div>
	</div>

	<? else : ?>

	<div id="content_<?=$settingsId?>" class="crm-status-content">
		<div id="extra-storage-<?=$settingsId?>" class="transaction-stage droppable">
			<? $number = 1; ?>
			<? foreach($settings["DATA"][$settingsId] as $field): ?>
				<? $field["NUMBER"] = $number; ?>
				<div class="transaction-stage-phase draghandle" data-calculate="1" data-success="1"
				     style="background: #d3eef9"
				     id="field-phase-<?=$field["ID"]?>" data-space="<?=$field["ID"]?>" data-sort="<?=$field["SORT"]?>"
				     ondblclick="BX['<?=$jsClass?>'].editField('<?=$field["ID"]?>');">

					<? if($field["SYSTEM"] == "Y"): ?>
						<div class="transaction-stage-phase-panel">
							<?if(!empty($field["NAME_INIT"])):?>
								<div onclick="BX['<?=$jsClass?>'].recoveryName('<?=$field["ID"]?>',
										'<?=htmlspecialcharsbx($field["NAME_INIT"])?>')"
								     title="<?=GetMessage("CRM_STATUS_LIST_RECOVERY_NAME")?>"
								     class="transaction-stage-phase-panel-button
												transaction-stage-phase-panel-button-refresh"></div>
							<?endif?>
						</div>
						<span class="transaction-stage-phase-icon transaction-stage-phase-icon-move draggable">
									<span class="transaction-stage-phase-icon-burger"></span>
								</span>
					<? else: ?>
						<div class="transaction-stage-phase-panel">
							<div onclick="BX['<?=$jsClass?>'].openPopupBeforeDeleteField('<?=$field["ID"]?>')"
							     title="<?=GetMessage("CRM_STATUS_DELETE_FIELD")?>"
							     class="transaction-stage-phase-panel-button transaction-stage-phase-panel-button-close">
							</div>
						</div>
						<span class="transaction-stage-phase-icon transaction-stage-phase-icon-move draggable">
									<span class="transaction-stage-phase-icon-burger"></span>
								</span>
					<? endif; ?>

					<span class="transaction-stage-phase-title">
						<span id="field-title-inner-<?=$field["ID"]?>" class="transaction-stage-phase-title-inner">
							<span id="field-name-<?=$field["ID"]?>" class="transaction-stage-phase-name">
								<?=$field["NUMBER"]?>.
								<?=htmlspecialcharsbx($field["NAME"])?>
							</span>
							<span onclick="BX['<?=$jsClass?>'].editField('<?=$field["ID"]?>');"
							      title="<?=GetMessage("CRM_STATUS_EDIT_NAME")?>"
							      class="transaction-stage-phase-icon-edit"></span>
						</span>
							</span>
					<input type="hidden" id="field-number-<?=$field["ID"]?>"
					       value="<?=$field["NUMBER"]?>">
					<input type="hidden" name="LIST[<?=$entityId?>][<?=$field["ID"]?>][SORT]"
					       id="field-sort-<?=$field["ID"]?>"
					       value="<?=$field["SORT"]?>">
					<input type="hidden" name="LIST[<?=$entityId?>][<?=$field["ID"]?>][VALUE]"
					       id="field-hidden-name-<?=$field["ID"]?>"
					       value="<?=htmlspecialcharsbx($field["NAME"])?>">
					<input type="hidden" name="LIST[<?=$entityId?>][<?=$field["ID"]?>][STATUS_ID]"
					       id="stage-status-id-<?=$field["ID"]?>" data-status-id="1"
					       value="<?=htmlspecialcharsbx($field["STATUS_ID"])?>">
				</div>
				<? $number++ ?>
			<? endforeach; ?>
			<a href="javascript:void(0)" onclick="BX['<?=$jsClass?>'].addField(this);"
			   class="transaction-stage-addphase draghandle" data-space="main">+
				<span><?=GetMessage("CRM_STATUS_ADD")?></span>
			</a>
		</div>
	</div>

	<? endif; ?>

	<!-- Footer buttons -->
	<div id="crm-configs-footer" class="webform-buttons webform-buttons-fixed">
		<span class="crm-fixedbtn <?if($pageSettings["BLOCK_FIXED"]):?>crm-fixedbtn-pin<?endif?>" onclick="
			BX['<?=$jsClass?>'].fixFooter(this);" title="<?=$pageSettings["TITLE_FOOTER_PIN"]?>"></span>
		<input type="submit" value="<?=GetMessage("CRM_STATUS_BUTTONS_SAVE");?>" class="
			webform-small-button webform-small-button-accept">
		<? $cancelOnclick = ($isSidePanel ? "BX['".$jsClass."'].statusReset();" : "BX['".$jsClass."'].statusReset();"); ?>
		<input type="button" value="<?=GetMessage("CRM_STATUS_BUTTONS_CANCEL");?>" class="
			webform-small-button webform-small-button-cancel" onclick="<?=$cancelOnclick?>">
	</div>

</div>
</div>

</form>

<script>
	BX.ready(function() {
		BX.message({
			CRM_STATUS_NEW: "<?= GetMessageJS("CRM_STATUS_NEW") ?>",
			CRM_STATUS_FINAL_TITLE: "<?= GetMessageJS("CRM_STATUS_FINAL_TITLE") ?>",
			CRM_STATUS_CONFIRMATION_DELETE_TITLE: "<?= GetMessageJS("CRM_STATUS_CONFIRMATION_DELETE_TITLE") ?>",
			CRM_STATUS_CONFIRMATION_DELETE_SAVE_BUTTON: "<?= GetMessageJS("CRM_STATUS_CONFIRMATION_DELETE_SAVE_BUTTON") ?>",
			CRM_STATUS_CONFIRMATION_DELETE_CANCEL_BUTTON: "<?= GetMessageJS("CRM_STATUS_CONFIRMATION_DELETE_CANCEL_BUTTON") ?>",
			CRM_STATUS_DELETE_FIELD_QUESTION: "<?= GetMessageJS("CRM_STATUS_DELETE_FIELD_QUESTION") ?>",
			CRM_STATUS_CHECK_CHANGES: "<?= GetMessageJS("CRM_STATUS_CHECK_CHANGES") ?>",
			CRM_STATUS_REMOVE_ERROR: "<?= GetMessageJS("CRM_STATUS_REMOVE_ERROR") ?>",
			CRM_STATUS_SAVE_SUCCESS: "<?= GetMessageJS("CRM_STATUS_SAVE_SUCCESS") ?>",
			CRM_STATUS_CLOSE_POPUP_REMOVE_ERROR: "<?= GetMessageJS("CRM_STATUS_CLOSE_POPUP_REMOVE_ERROR") ?>",
			CRM_STATUS_FOOTER_PIN_ON: "<?= GetMessageJS("CRM_STATUS_FOOTER_PIN_ON") ?>",
			CRM_STATUS_FOOTER_PIN_OFF: "<?= GetMessageJS("CRM_STATUS_FOOTER_PIN_OFF") ?>"
		});
		<?if (isset($semanticInfo["DEFAULT_NAME"])):?>
			BX.message({ CRM_STATUS_NEW_<?=$settingsId?>: "<?=CUtil::JSEscape($semanticInfo["DEFAULT_NAME"])?>" });
		<?endif;?>

		BX['<?=$jsClass?>'] = new BX.CrmSaleSettings({
			randomString: "<?= $pageSettings["RAND_STRING"] ?>",
			ajaxUrl: "<?= $pageSettings["AJAX_URL"] ?>",
			entityId: "<?=CUtil::JSEscape($settingsId)?>",
			hasSemantics: "<?=($settings["TYPE"] == "SEPARATED")?>",
			data: <?=CUtil::PhpToJsObject($settings["DATA"])?>,
			totalNumberFields: <?=count($settings["DATA"][$settingsId])?>,
			successFields: <?=CUtil::PhpToJSObject($successFields)?>,
			unSuccessFields: <?=CUtil::PhpToJSObject($unSuccessFields)?>,
			initialFields: <?=CUtil::PhpToJSObject($initialFields)?>,
			extraFields: <?=CUtil::PhpToJSObject($extraFields)?>,
			finalFields: <?=CUtil::PhpToJSObject($finalFields)?>,
			extraFinalFields: <?=CUtil::PhpToJSObject($extraFinalFields)?>,
			blockFixed: "<?=$pageSettings["BLOCK_FIXED"]?>",
			isDestroySidePanel: "<?=($_GET["sidePanelAction"] == "destroy")?>"
		});

		DragManager.onDragMove = function(dragObject, overElem, e) {
			BX['<?=$jsClass?>'].showPlaceToInsert(overElem, e);
		};
		DragManager.onDragCancel = function(dragObject) {
			BX['<?=$jsClass?>'].deleteSpaceToInsert();
			dragObject.avatar.rollback();
		};
		DragManager.onDragEnd = function(dragObject, dropElem, overElem) {
			var element = dragObject.avatar;
			dragObject.avatar.styleback();
			var result = BX['<?=$jsClass?>'].putDomElement(element, dropElem, overElem);
			if(result)
			{
				BX['<?=$jsClass?>'].recalculateSort();
			}
			else
			{
				dragObject.avatar.rollback();
			}

			BX['<?=$jsClass?>'].deleteSpaceToInsert();
		};

		window.onbeforeunload = function() {
			return BX['<?=$jsClass?>'].checkChanges();
		};
	});
</script>

<div id="block-color-picker" class="crm-config-status-block-color-picker">
	<script>
		function OnSelectBGColor(color, objColorPicker)
		{
			BX['<?=$jsClass?>'].paintElement(color, objColorPicker);
		}
	</script>
	<? $APPLICATION->includeComponent(
		"bitrix:main.colorpicker",
		"",
		array(
			"SHOW_BUTTON" =>"Y",
			"ONSELECT" => "OnSelectBGColor"
		)
	); ?>
</div>

<?
function getColorText($color, &$iconClass, &$blockClass)
{
	$r = ord(pack("H*", mb_substr($color, 1, 2)));
	$g = ord(pack("H*", mb_substr($color, 3, 2)));
	$b = ord(pack("H*", mb_substr($color, 5, 2)));
	$y = 0.21 * $r + 0.72 * $g + 0.07 * $b;

	if ($y < 145)
	{
		$iconClass = "light-icon";
		$blockClass = "transaction-stage-phase-dark";
		return "#FFFFFF";
	}
	else
	{
		$blockClass = "";
		$iconClass = "dark-icon";
		return "#545C69";
	}
}