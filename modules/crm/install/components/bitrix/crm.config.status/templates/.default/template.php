<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use \Bitrix\Main\Page\Asset;

Asset::getInstance()->addJs($this->GetFolder().'/drag_and_drop.js');
CUtil::InitJSCore(array("amcharts", "amcharts_funnel", "amcharts_serial"));

\Bitrix\Main\UI\Extension::load("popup");

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/themes/.default/crm-entity-show.css');

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'CONFIG',
			'ACTIVE_ITEM_ID' => '',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
			'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
			'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
			'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

if($arResult['NEED_FOR_FIX_STATUSES']): ?>
	<div id="fixStatusesMsg" class="crm-view-message">
		<?=GetMessage('CRM_STATUS_FIX_STATUSES', array('#ID#' => 'fixStatusesLink', '#URL#' => '#'))?>
	</div>
<? endif;
$tabActive = 'status_tab_active';
$jsClass = 'CrmConfigStatusClass_'.$arResult['RAND_STRING'];

$initialFields = $arResult['INITIAL_FIELDS'];
$extraFields = $arResult['EXTRA_FIELDS'];
$finalFields = $arResult['FINAL_FIELDS'];
$extraFinalFields = $arResult['EXTRA_FINAL_FIELDS'];
$successFields = $arResult['SUCCESS_FIELDS'];
$unSuccessFields = $arResult['UNSUCCESS_FIELDS'];
$totalNumberFields = 0;
foreach($arResult["ROWS"] as $rows)
{
	$totalNumberFields = $totalNumberFields + count($rows);
}

$footerOption = current(CUserOptions::GetOption('crm', 'crm_config_status', array('fix_footer' => 'on')));
$blockFixed = $footerOption == 'on' ? true : false;

if($blockFixed)
	$titleFooterPin = GetMessage('CRM_STATUS_FOOTER_PIN_OFF');
else
	$titleFooterPin = GetMessage('CRM_STATUS_FOOTER_PIN_ON');
?>

<form name="crmStatusForm" method="POST" onsubmit="BX['<?=$jsClass?>'].confirmSubmit();">
<input type="hidden" name="ACTION" value="save" id="ACTION">
<input type="hidden" name="ACTIVE_TAB" value="<?=htmlspecialcharsbx($arResult['ACTIVE_TAB'])?>" id="ACTIVE_TAB">
<?=bitrix_sessid_post()?>

<div id="crm-container" class="crm-container">

	<div id="status_box" class="crm-transaction-menu">
		<? foreach($arResult['HEADERS'] as $entityId => $headerName): ?>
			<?$headerName = htmlspecialcharsbx($headerName);?>
			<? $tabActive = ('status_tab_'.$entityId == $arResult['ACTIVE_TAB']) ? 'status_tab_active':'' ?>
			<a href="javascript:void(0)" id="status_tab_<?=$entityId?>" class="status_tab <?=$tabActive?>"
			   onclick="BX['<?=$jsClass?>'].selectTab('<?=$entityId ?>');" title="<?=$headerName?>">
				<span><?=$headerName?></span>
			</a>
		<? endforeach; ?>
	</div>

	<div class="crm-transaction-stage">
		<? foreach($arResult['HEADERS'] as $entityId => $headerName): ?>
			<? $isActive = $entityId === $arResult['ACTIVE_ENTITY_ID']; ?>
			<? $maxSort = 0; ?>
			<? if(isset($arResult['ENTITY'][$entityId])): ?>
			<? $entitySettings = $arResult['ENTITY'][$entityId];?>
			<div id="content_<?=$entityId?>"
				 class="crm-status-content<?= $isActive ? ' active' : ''?>">

				<div class="transaction-stage transaction-initial-stage">

					<?
						$iconClass = '';
						$blockClass = '';
						$colorValue = $initialFields[$entityId]['COLOR'] ? $initialFields[$entityId]['COLOR'] : '#ACE9FB';
						$style = 'background:'.$colorValue.';';
						$style .= 'color:'.getColorText($colorValue, $iconClass, $blockClass).';';
					?>

					<div class="transaction-stage-title"><?
						$stageTitle = GetMessage('CRM_STATUS_TITLE_INITIAL_' . $entityId);
						if ((string)$stageTitle === '')
						{
							$stageTitle = GetMessage('CRM_STATUS_TITLE_INITIAL_' . $entityId . '_MSGVER_1');
						}
						echo $stageTitle;
						?></div>
					<div class="transaction-stage-phase" data-sort="<?=$initialFields[$entityId]['SORT']?>"
						 id="field-phase-<?=$initialFields[$entityId]['ID']?>" data-calculate="1" data-success="1"
						 ondblclick="BX['<?=$jsClass?>'].editField('<?=$initialFields[$entityId]['ID']?>');"
						 style="<?=htmlspecialcharsbx($style)?>">
						<div id="phase-panel" data-class="transaction-stage-phase-panel"
							 class="<?=$blockClass?> transaction-stage-phase-panel">
							<?if($initialFields[$entityId]['SYSTEM'] == 'Y' &&
								!empty($initialFields[$entityId]['NAME_INIT'])):?>
								<div onclick="BX['<?=$jsClass?>'].recoveryName('<?=$initialFields[$entityId]['ID']?>',
									'<?=htmlspecialcharsbx($initialFields[$entityId]['NAME_INIT'])?>')"
									class="transaction-stage-phase-panel-button
									transaction-stage-phase-panel-button-refresh"
									title="<?=GetMessage('CRM_STATUS_LIST_RECOVERY_NAME')?>"></div>
							<?endif?>
							<div class="transaction-stage-phase-panel-button"
								 title="<?=GetMessage('CRM_STATUS_EDIT_COLOR')?>"
								 onclick="BX['<?=$jsClass?>'].correctionColorPicker(event,
									 '<?=$initialFields[$entityId]['ID']?>')">
							</div>
						</div>
						<span id="transaction-stage-phase-icon" class="<?=$iconClass?> transaction-stage-phase-icon"
							  data-class="transaction-stage-phase-icon">
							<span class="transaction-stage-phase-icon-arrow"></span>
						</span>
						<span id="phase-panel" data-class="transaction-stage-phase-title"
							  class="<?=$blockClass?> transaction-stage-phase-title">
							<span id="field-title-inner-<?=$initialFields[$entityId]['ID']?>" class="transaction-stage-phase-title-inner">
								<span id="field-name-<?=$initialFields[$entityId]['ID']?>" class="transaction-stage-phase-name">
									<?=$initialFields[$entityId]['NUMBER']?>.
									<?=htmlspecialcharsbx($initialFields[$entityId]['NAME'])?>
								</span>
								<span onclick="BX['<?=$jsClass?>'].editField('<?=$initialFields[$entityId]['ID']?>');"
									  title="<?=GetMessage('CRM_STATUS_EDIT_NAME')?>"
									  class="transaction-stage-phase-icon-edit"></span>
							</span>
						</span>

						<input type="hidden" id="field-number-<?=$initialFields[$entityId]['ID']?>"
							   value="<?=$initialFields[$entityId]['NUMBER']?>">
						<input type="hidden" name="LIST[<?=$entityId?>][<?=$initialFields[$entityId]['ID']?>][SORT]"
							   id="field-sort-<?=$initialFields[$entityId]['ID']?>"
							   value="<?=$initialFields[$entityId]['SORT']?>">
						<input type="hidden" name="LIST[<?=$entityId?>][<?=$initialFields[$entityId]['ID']?>][VALUE]"
							   id="field-hidden-name-<?=$initialFields[$entityId]['ID']?>"
							   value="<?=htmlspecialcharsbx($initialFields[$entityId]['NAME'])?>">
						<input type="hidden" name="LIST[<?=$entityId?>][<?=$initialFields[$entityId]['ID']?>][COLOR]"
							   id="stage-color-<?=$initialFields[$entityId]['ID']?>"
							   value="<?=htmlspecialcharsbx($colorValue)?>">
						<input type="hidden" name="LIST[<?=$entityId?>][<?=$initialFields[$entityId]['ID']?>][STATUS_ID]"
							   id="stage-status-id-<?=$initialFields[$entityId]['ID']?>" data-status-id="1"
							   value="<?=htmlspecialcharsbx($initialFields[$entityId]['STATUS_ID'])?>">
					</div>
				</div>

				<div id="extra-storage-<?=$entityId?>" class="transaction-stage droppable">
					<div class="transaction-stage-title"><?
						$title = GetMessage('CRM_STATUS_TITLE_EXTRA_' . $entityId);
						if ((string)$title === '')
						{
							$title = GetMessage('CRM_STATUS_TITLE_EXTRA_' . $entityId . '_MSGVER_1');
						}
						echo $title;
						?></div>
					<? foreach($extraFields[$entityId] as $field): ?>

						<?
							$blockClass = '';
							$iconClass = '';
							$colorValue = $field['COLOR'] ? $field['COLOR'] : '#ACE9FB';
							$style = 'background:'.$colorValue.';';
							$style .= 'color:'.getColorText($colorValue, $iconClass, $blockClass).';';
						?>

						<div class="transaction-stage-phase draghandle" data-calculate="1" data-success="1"
							 style="<?=htmlspecialcharsbx($style)?>" id="field-phase-<?=$field['ID']?>" data-space="<?=$field['ID']?>"
							 data-sort="<?=$field['SORT']?>" ondblclick="BX['<?=$jsClass?>'].editField('<?=$field['ID']?>');">
							<div id="phase-panel" data-class="transaction-stage-phase-panel"
								 class="<?=$blockClass?> transaction-stage-phase-panel">
								<?if(!empty($field['NAME_INIT'])):?>
									<div onclick="BX['<?=$jsClass?>'].recoveryName('<?=$field['ID']?>',
										'<?=htmlspecialcharsbx($field['NAME_INIT'])?>')"
										 title="<?=GetMessage('CRM_STATUS_LIST_RECOVERY_NAME')?>"
										 class="transaction-stage-phase-panel-button
									transaction-stage-phase-panel-button-refresh"></div>
								<?endif?>
								<div class="transaction-stage-phase-panel-button"
									 title="<?=GetMessage('CRM_STATUS_EDIT_COLOR')?>"
									 onclick="BX['<?=$jsClass?>'].correctionColorPicker(event, '<?=$field['ID']?>')">
								</div>
								<div onclick="BX['<?=$jsClass?>'].openPopupBeforeDeleteField('<?=$field['ID']?>')"
									 title="<?=GetMessage('CRM_STATUS_DELETE_FIELD')?>"
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
								<span id="field-title-inner-<?=$field['ID']?>" class="transaction-stage-phase-title-inner">
									<span id="field-name-<?=$field['ID']?>" class="transaction-stage-phase-name">
										<?=$field['NUMBER']?>. <?=htmlspecialcharsbx($field['NAME'])?>
									</span>
									<span onclick="BX['<?=$jsClass?>'].editField('<?=$field['ID']?>');"
										  title="<?=GetMessage('CRM_STATUS_EDIT_NAME')?>"
										  class="transaction-stage-phase-icon-edit"></span>
								</span>
							</span>
							<input type="hidden" id="field-number-<?=$field['ID']?>"
								   value="<?=$field['NUMBER']?>">
							<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][SORT]"
								   id="field-sort-<?=$field['ID']?>"
								   value="<?=$field['SORT']?>">
							<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][VALUE]"
								   id="field-hidden-name-<?=$field['ID']?>"
								   value="<?=htmlspecialcharsbx($field['NAME'])?>">
							<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][COLOR]"
								   id="stage-color-<?=$field['ID']?>"
								   value="<?=htmlspecialcharsbx($colorValue)?>">
							<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][STATUS_ID]"
								   id="stage-status-id-<?=$field['ID']?>" data-status-id="1"
								   value="<?=htmlspecialcharsbx($field['STATUS_ID'])?>">
						</div>
					<? endforeach; ?>
					<a href="javascript:void(0)" onclick="BX['<?=$jsClass?>'].addField(this);"
					   class="transaction-stage-addphase draghandle" data-space="main">+
						<span><?=isset($entitySettings['ADD_CAPTION']) ? $entitySettings['ADD_CAPTION'] : GetMessage('CRM_STATUS_ADD')?></span>
					</a>
				</div>

				<div class="transaction-stage-final">
					<div class="transaction-stage-final-title">
						<span class="transaction-stage-final-title-sub"><?=GetMessage('CRM_STATUS_FINAL_TITLE')?></span>
					</div>
					<div class="transaction-stage-final-result">
						<div class="transaction-stage-final-success"><?=GetMessage('CRM_STATUS_SUCCESSFUL')?></div>
						<div class="transaction-stage-final-failure"><?=GetMessage('CRM_STATUS_UNSUCCESSFUL')?></div>
					</div>
					<div class="transaction-stage-final-column">

						<?
							$blockClass = '';
							$iconClass = '';
							$colorValue = $finalFields[$entityId]['SUCCESSFUL']['COLOR'] ?
								$finalFields[$entityId]['SUCCESSFUL']['COLOR'] : '#DBF199';
							$style = 'background:'.$colorValue.';';
							$style .= 'color:'.getColorText($colorValue, $iconClass, $blockClass).';';
						?>

						<div id="final-success-storage-<?=$entityId?>" class="transaction-stage transaction-stage-success">
							<div class="transaction-stage-title"><?
								$title = GetMessage('CRM_STATUS_SUCCESSFUL_' . $entityId);
								if ((string)$title === '')
								{
									$title = GetMessage('CRM_STATUS_SUCCESSFUL_' . $entityId . '_MSGVER_1');
								}
								echo $title;
							?></div>
							<div ondblclick="BX['<?=$jsClass?>'].editField('<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>');"
								 class="transaction-stage-phase" data-sort="<?=$finalFields[$entityId]['SUCCESSFUL']['SORT']?>"
								 id="field-phase-<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>" data-calculate="1"
								 style="<?=htmlspecialcharsbx($style)?>" data-success="1">
								<div id="phase-panel" data-class="transaction-stage-phase-panel"
									 class="<?=$blockClass?> transaction-stage-phase-panel">
									<?if($finalFields[$entityId]['SUCCESSFUL']['SYSTEM'] == 'Y' &&
										!empty($finalFields[$entityId]['SUCCESSFUL']['NAME_INIT'])):?>
										<div onclick="BX['<?=$jsClass?>'].recoveryName(
											'<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>',
											'<?=htmlspecialcharsbx($finalFields[$entityId]['SUCCESSFUL']['NAME_INIT'])?>')"
											 title="<?=GetMessage('CRM_STATUS_LIST_RECOVERY_NAME')?>"
											 class="transaction-stage-phase-panel-button
												transaction-stage-phase-panel-button-refresh"></div>
									<?endif?>
									<div class="transaction-stage-phase-panel-button"
										 title="<?=GetMessage('CRM_STATUS_EDIT_COLOR')?>"
										 onclick="BX['<?=$jsClass?>'].correctionColorPicker(event,
											 '<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>')">
									</div>
								</div>
								<span id="transaction-stage-phase-icon" class="<?=$iconClass?> transaction-stage-phase-icon"
									  data-class="transaction-stage-phase-icon">
									<span class="transaction-stage-phase-icon-arrow"></span>
								</span>
								<span id="phase-panel" data-class="transaction-stage-phase-title"
									  class="<?=$blockClass?> transaction-stage-phase-title">
									<span id="field-title-inner-<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>"
										  class="transaction-stage-phase-title-inner">
										<span id="field-name-<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>"
											  class="transaction-stage-phase-name">
											<?=$finalFields[$entityId]['SUCCESSFUL']['NUMBER']?>.
											<?=htmlspecialcharsbx($finalFields[$entityId]['SUCCESSFUL']['NAME'])?>
										</span>
										<span onclick="BX['<?=$jsClass?>'].editField('<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>');"
											  class="transaction-stage-phase-icon-edit"
											  title="<?=GetMessage('CRM_STATUS_EDIT_NAME')?>"></span>
									</span>
								</span>
								<input type="hidden" id="field-number-<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>"
									   value="<?=$finalFields[$entityId]['SUCCESSFUL']['NUMBER']?>">
								<input type="hidden" name="LIST[<?=$entityId?>][<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>][SORT]"
								   id="field-sort-<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>"
								   value="<?=$finalFields[$entityId]['SUCCESSFUL']['SORT']?>">
								<input type="hidden" name="LIST[<?=$entityId?>][<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>][VALUE]"
								   id="field-hidden-name-<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>"
								   value="<?=htmlspecialcharsbx($finalFields[$entityId]['SUCCESSFUL']['NAME'])?>">
								<input type="hidden" name="LIST[<?=$entityId?>][<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>][COLOR]"
									   id="stage-color-<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>"
									   value="<?=htmlspecialcharsbx($colorValue)?>">
								<input type="hidden" name="LIST[<?=$entityId?>][<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>][STATUS_ID]"
									   id="stage-status-id-<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>" data-status-id="1"
									   value="<?=htmlspecialcharsbx($finalFields[$entityId]['SUCCESSFUL']['STATUS_ID'])?>">
                                <input type="hidden" name="LIST[<?=$entityId?>][<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>][SEMANTICS]"
                                       id="stage-semantics-<?=$finalFields[$entityId]['SUCCESSFUL']['ID']?>"
                                       value="<?=htmlspecialcharsbx($finalFields[$entityId]['SUCCESSFUL']['SEMANTICS'])?>">
							</div>
						</div>
					</div>
					<div class="transaction-stage-final-column">

						<?
							$blockClass = '';
							$iconClass = '';
							$colorValue = $finalFields[$entityId]['UNSUCCESSFUL']['COLOR'] ?
								$finalFields[$entityId]['UNSUCCESSFUL']['COLOR'] : '#FFBEBD';
							$style = 'background:'.$colorValue.';';
							$style .= 'color:'.getColorText($colorValue, $iconClass, $blockClass).';';
						?>

						<div id="final-storage-<?=$entityId?>" class="transaction-stage transaction-stage-failure droppable">
							<div class="transaction-stage-title"><?
							 	$title = GetMessage('CRM_STATUS_UNSUCCESSFUL_' . $entityId);
								if ((string)$title === '')
								{
									$title = GetMessage('CRM_STATUS_UNSUCCESSFUL_' . $entityId . '_MSGVER_1');
								}
								echo $title;
							?></div>
							<div ondblclick="BX['<?=$jsClass?>'].editField('<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>');"
								 class="transaction-stage-phase" data-sort="<?=$finalFields[$entityId]['UNSUCCESSFUL']['SORT']?>"
								 id="field-phase-<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>" data-calculate="1"
								 style="<?=htmlspecialcharsbx($style)?>" data-success="0">
								<div id="phase-panel" data-class="transaction-stage-phase-panel"
									 class="<?=$blockClass?> transaction-stage-phase-panel">
									<?if($finalFields[$entityId]['UNSUCCESSFUL']['SYSTEM'] == 'Y' &&
										!empty($finalFields[$entityId]['UNSUCCESSFUL']['NAME_INIT'])):?>
										<div onclick="BX['<?=$jsClass?>'].recoveryName(
											'<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>',
											'<?=htmlspecialcharsbx($finalFields[$entityId]['UNSUCCESSFUL']['NAME_INIT'])?>')"
											 title="<?=GetMessage('CRM_STATUS_LIST_RECOVERY_NAME')?>"
											 class="transaction-stage-phase-panel-button
												transaction-stage-phase-panel-button-refresh"></div>
									<?endif?>
									<div class="transaction-stage-phase-panel-button"
										 title="<?=GetMessage('CRM_STATUS_EDIT_COLOR')?>"
										 onclick="BX['<?=$jsClass?>'].correctionColorPicker(event,
											 '<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>')">
									</div>
								</div>
								<span id="transaction-stage-phase-icon" class="<?=$iconClass?> transaction-stage-phase-icon"
									  data-class="transaction-stage-phase-icon">
									<span class="transaction-stage-phase-icon-arrow"></span>
								</span>
								<span id="phase-panel" data-class="transaction-stage-phase-title"
									  class="<?=$blockClass?> transaction-stage-phase-title">
									<span id="field-title-inner-<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>" class="transaction-stage-phase-title-inner">
										<span id="field-name-<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>" class="transaction-stage-phase-name">
											<?=$finalFields[$entityId]['UNSUCCESSFUL']['NUMBER']?>.
											<?=htmlspecialcharsbx($finalFields[$entityId]['UNSUCCESSFUL']['NAME'])?>
										</span>
										<span onclick="BX['<?=$jsClass?>'].editField('<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>');"
											  title="<?=GetMessage('CRM_STATUS_EDIT_NAME')?>"
											  class="transaction-stage-phase-icon-edit"></span>
									</span>
								</span>
								<input type="hidden" id="field-number-<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>"
									   value="<?=$finalFields[$entityId]['UNSUCCESSFUL']['NUMBER']?>">
								<input type="hidden" name="LIST[<?=$entityId?>][<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>][SORT]"
								   id="field-sort-<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>"
								   value="<?=$finalFields[$entityId]['UNSUCCESSFUL']['SORT']?>">
								<input type="hidden" name="LIST[<?=$entityId?>][<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>][VALUE]"
									   id="field-hidden-name-<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>"
								   value="<?=htmlspecialcharsbx($finalFields[$entityId]['UNSUCCESSFUL']['NAME'])?>">
								<input type="hidden" name="LIST[<?=$entityId?>][<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>][COLOR]"
									   id="stage-color-<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>"
									   value="<?=htmlspecialcharsbx($colorValue)?>">
								<input type="hidden" name="LIST[<?=$entityId?>][<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>][STATUS_ID]"
									   id="stage-status-id-<?=$finalFields[$entityId]['UNSUCCESSFUL']['ID']?>" data-status-id="1"
									   value="<?=htmlspecialcharsbx($finalFields[$entityId]['UNSUCCESSFUL']['STATUS_ID'])?>">
							</div>
							<? foreach($extraFinalFields[$entityId] as $field): ?>

								<?
									$blockClass = '';
									$iconClass = '';
									$colorValue = $field['COLOR'] ? $field['COLOR'] : '#FFBEBD';
									$style = 'background:'.$colorValue.';';
									$style .= 'color:'.getColorText($colorValue, $iconClass, $blockClass).';';
								?>

								<div class="transaction-stage-phase draghandle" data-calculate="1"
									 data-space="<?=$field['ID']?>" id="field-phase-<?=$field['ID']?>"
									 data-sort="<?=$field['SORT']?>" data-success="0"
									 ondblclick="BX['<?=$jsClass?>'].editField('<?=$field['ID']?>');"
									 style="<?=htmlspecialcharsbx($style)?>">
									<div id="phase-panel" data-class="transaction-stage-phase-panel"
										 class="<?=$blockClass?> transaction-stage-phase-panel">
										<?if(!empty($field['NAME_INIT'])):?>
											<div onclick="BX['<?=$jsClass?>'].recoveryName('<?=$field['ID']?>',
												'<?=htmlspecialcharsbx($field['NAME_INIT'])?>')"
												 title="<?=GetMessage('CRM_STATUS_LIST_RECOVERY_NAME')?>"
												 class="transaction-stage-phase-panel-button
													transaction-stage-phase-panel-button-refresh"></div>
										<?endif?>
										<div class="transaction-stage-phase-panel-button"
											 title="<?=GetMessage('CRM_STATUS_EDIT_COLOR')?>"
											 onclick="BX['<?=$jsClass?>'].correctionColorPicker(event,
												 '<?=$field['ID']?>')">
										</div>
										<div onclick="BX['<?=$jsClass?>'].openPopupBeforeDeleteField('<?=$field['ID']?>')"
											 title="<?=GetMessage('CRM_STATUS_DELETE_FIELD')?>"
											class="transaction-stage-phase-panel-button transaction-stage-phase-panel-button-close">
										</div>
									</div>
									<span id="transaction-stage-phase-icon" class="<?=$iconClass?>
										transaction-stage-phase-icon transaction-stage-phase-icon-move draggable"
										  data-class="transaction-stage-phase-icon transaction-stage-phase-icon-move draggable">
										<span class="transaction-stage-phase-icon-burger"></span>
									</span>
                                    <span id="phase-panel" data-class="transaction-stage-phase-title"
										  class="<?=$blockClass?> transaction-stage-phase-title">
                                        <span id="field-title-inner-<?=$field['ID']?>" class="transaction-stage-phase-title-inner">
                                            <span id="field-name-<?=$field['ID']?>" class="transaction-stage-phase-name">
                                                <?=$field['NUMBER']?>.
												<?=htmlspecialcharsbx($field['NAME'])?>
                                            </span>
                                            <span onclick="BX['<?=$jsClass?>'].editField('<?=$field['ID']?>');"
												  title="<?=GetMessage('CRM_STATUS_EDIT_NAME')?>"
												  class="transaction-stage-phase-icon-edit"></span>
                                        </span>
                                    </span>
									<input type="hidden" id="field-number-<?=$field['ID']?>"
										   value="<?=$field['NUMBER']?>">
									<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][SORT]"
										   id="field-sort-<?=$field['ID']?>"
										   value="<?=$field['SORT']?>">
									<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][VALUE]"
										   id="field-hidden-name-<?=$field['ID']?>"
										   value="<?=htmlspecialcharsbx($field['NAME'])?>">
									<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][COLOR]"
										   id="stage-color-<?=$field['ID']?>"
										   value="<?=htmlspecialcharsbx($colorValue)?>">
									<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][STATUS_ID]"
										   id="stage-status-id-<?=$field['ID']?>" data-status-id="1"
										   value="<?=htmlspecialcharsbx($field['STATUS_ID'])?>">
								</div>
							<? endforeach; ?>
							<a href="javascript:void(0)" onclick="BX['<?=$jsClass?>'].addField(this);"
							   data-space="final" class="transaction-stage-addphase draghandle">+
								<span><?=isset($entitySettings['ADD_CAPTION']) ? $entitySettings['ADD_CAPTION'] : GetMessage('CRM_STATUS_ADD')?></span>
							</a>
						</div>
					</div>
				</div>

				<div class="crm-previously">

					<div class="crm-previously-scale">
						<span class="crm-previously-title">
							<?=GetMessage('CRM_STATUS_VIEW_SCALE')?>
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
										<tr id="previously-scale-final-success-<?=$entityId?>">
											<td style="background:<?= (isset($finalFields[$entityId]['SUCCESSFUL']['COLOR'])) ?
												htmlspecialcharsbx($finalFields[$entityId]['SUCCESSFUL']['COLOR']) : '#DBF199'?>">&nbsp;</td>
										</tr>
										<tr id="previously-scale-number-final-success-<?=$entityId?>">
											<td>
												<span class="stage-name">
													<?=$finalFields[$entityId]['SUCCESSFUL']['NUMBER']?>
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
										<tr id="previously-scale-<?=$entityId?>">
											<td data-scale-type="main"
												style="background:<?=(isset($initialFields[$entityId]['COLOR'])) ?
													htmlspecialcharsbx($initialFields[$entityId]['COLOR']) : '#ACE9FB'?>">&nbsp;</td>

											<?foreach($extraFields[$entityId] as $field):?>
												<td data-scale-type="main"
													style="background:<?= (isset($field['COLOR'])) ?
														htmlspecialcharsbx($field['COLOR']) : '#ACE9FB'?>">&nbsp;</td>
											<?endforeach;?>
											<td id="previously-scale-final-cell-<?=$entityId?>">
												<span class="stage-name"><?=GetMessage('CRM_STATUS_FINAL_TITLE')?></span>
											</td>
										</tr>
										<tr id="previously-scale-number-<?=$entityId?>">
											<td data-scale-type="main">
												<span class="stage-name">
													<?=$initialFields[$entityId]['NUMBER']?>
												</span>
											</td>
											<?foreach($extraFields[$entityId] as $field):?>
												<td data-scale-type="main">
													<span class="stage-name"><?=$field['NUMBER']?></span>
												</td>
											<?endforeach;?>
											<td id="previously-scale-number-final-cell-<?=$entityId?>">
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
										<tr id="previously-scale-final-un-success-<?=$entityId?>">
											<td style="background:<?=(isset($finalFields[$entityId]['UNSUCCESSFUL']['COLOR'])) ?
												htmlspecialcharsbx($finalFields[$entityId]['UNSUCCESSFUL']['COLOR']) : '#FFBEBD'?>">&nbsp;</td>
											<?foreach($extraFinalFields[$entityId] as $field):?>
												<td style="background:<?= (isset($field['COLOR'])) ?
													htmlspecialcharsbx($field['COLOR']) : '#FFBEBD'?>">&nbsp;</td>
											<?endforeach;?>
										</tr>
										<tr id="previously-scale-number-final-un-success-<?=$entityId?>">
											<td>
												<span class="stage-name">
													<?=$finalFields[$entityId]['UNSUCCESSFUL']['NUMBER']?>
												</span>
											</td>
											<?foreach($extraFinalFields[$entityId] as $field):?>
												<td><span class="stage-name"><?=$field['NUMBER']?></span></td>
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
							<?=GetMessage('CRM_STATUS_VIEW_FUNNEL2_MSGVER_1')?>
						</span>
						<div class="crm-previously-funnel">
							<span class="crm-previously-funnel-subtitle">
								<?
								$title = GetMessage('CRM_STATUS_FUNNEL_SUCCESSFUL_' . $entityId);
								if ((string)$title === '')
								{
									$title = GetMessage('CRM_STATUS_FUNNEL_SUCCESSFUL_' . $entityId . '_MSGVER_1');
								}
								echo $title;
								?>
							</span>
							<div class="crm-previously-funnel-inner">
								<div id="config-funnel-success-<?=$entityId?>" style="width: 100%; min-height: 300px;"></div>
							</div>
						</div>
						<div class="crm-previously-funnel crm-previously-funnel-failure">
							<span class="crm-previously-funnel-subtitle">
								<?
								$title = GetMessage('CRM_STATUS_FUNNEL_UNSUCCESSFUL_' . $entityId);
								if ((string)$title === '')
								{
									$title = GetMessage('CRM_STATUS_FUNNEL_UNSUCCESSFUL_' . $entityId . '_MSGVER_1');
								}
								echo $title;
								?>
							</span>
							<div class="crm-previously-funnel-inner">
								<div id="config-funnel-unsuccess-<?=$entityId?>" style="width: 100%; min-height: 300px;"></div>
							</div>
						</div>
						<script></script>
					</div>

				</div>

			</div>

			<? else: ?>

			<div id="content_<?=$entityId?>"
				 class="crm-status-content<?=('status_tab_'.$entityId == $arResult['ACTIVE_TAB'])? ' active' : ''?>">

				<div id="extra-storage-<?=$entityId?>" class="transaction-stage droppable">
					<? $number = 1; ?>
					<? foreach($arResult["ROWS"][$entityId] as $field): ?>
						<? $field['NUMBER'] = $number; ?>
						<div class="transaction-stage-phase draghandle" data-calculate="1" data-success="1"
							 style="background: #d3eef9"
							 id="field-phase-<?=$field['ID']?>" data-space="<?=$field['ID']?>" data-sort="<?=$field['SORT']?>"
							 ondblclick="BX['<?=$jsClass?>'].editField('<?=$field['ID']?>');">

							<? if($field['SYSTEM'] == 'Y'): ?>
								<div class="transaction-stage-phase-panel">
									<?if(!empty($field['NAME_INIT'])):?>
										<div onclick="BX['<?=$jsClass?>'].recoveryName('<?=$field['ID']?>',
											'<?=htmlspecialcharsbx($field['NAME_INIT'])?>')"
											 title="<?=GetMessage('CRM_STATUS_LIST_RECOVERY_NAME')?>"
											 class="transaction-stage-phase-panel-button
												transaction-stage-phase-panel-button-refresh"></div>
									<?endif?>
								</div>
								<span class="transaction-stage-phase-icon transaction-stage-phase-icon-move draggable">
									<span class="transaction-stage-phase-icon-burger"></span>
								</span>
							<? else: ?>
								<div class="transaction-stage-phase-panel">
									<div onclick="BX['<?=$jsClass?>'].openPopupBeforeDeleteField('<?=$field['ID']?>')"
										 title="<?=GetMessage('CRM_STATUS_DELETE_FIELD')?>"
										 class="transaction-stage-phase-panel-button transaction-stage-phase-panel-button-close">
									</div>
								</div>
								<span class="transaction-stage-phase-icon transaction-stage-phase-icon-move draggable">
									<span class="transaction-stage-phase-icon-burger"></span>
								</span>
							<? endif; ?>

							<span class="transaction-stage-phase-title">
								<span id="field-title-inner-<?=$field['ID']?>" class="transaction-stage-phase-title-inner">
									<span id="field-name-<?=$field['ID']?>" class="transaction-stage-phase-name">
										<?=$field['NUMBER']?>.
										<?=htmlspecialcharsbx($field['NAME'])?>
									</span>
									<span onclick="BX['<?=$jsClass?>'].editField('<?=$field['ID']?>');"
										  title="<?=GetMessage('CRM_STATUS_EDIT_NAME')?>"
										  class="transaction-stage-phase-icon-edit"></span>
								</span>
							</span>
							<input type="hidden" id="field-number-<?=$field['ID']?>"
								   value="<?=$field['NUMBER']?>">
							<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][SORT]"
								   id="field-sort-<?=$field['ID']?>"
								   value="<?=$field['SORT']?>">
							<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][VALUE]"
								   id="field-hidden-name-<?=$field['ID']?>"
								   value="<?=htmlspecialcharsbx($field['NAME'])?>">
							<input type="hidden" name="LIST[<?=$entityId?>][<?=$field['ID']?>][STATUS_ID]"
								   id="stage-status-id-<?=$field['ID']?>" data-status-id="1"
								   value="<?=htmlspecialcharsbx($field['STATUS_ID'])?>">
						</div>
						<? $number++ ?>
					<? endforeach; ?>
					<a href="javascript:void(0)" onclick="BX['<?=$jsClass?>'].addField(this);"
					   class="transaction-stage-addphase draghandle" data-space="main">+
						<span><?=GetMessage('CRM_STATUS_ADD')?></span>
					</a>
				</div>

			</div>

			<? endif; ?>
		<? endforeach; ?>
		<div id="crm-configs-footer" class="webform-buttons webform-buttons-fixed">
			<span class="crm-fixedbtn <?if($blockFixed):?>crm-fixedbtn-pin<?endif?>"
				  onclick="BX['<?=$jsClass?>'].fixFooter(this);" title="<?=$titleFooterPin?>"></span>
			<input type="submit" value="<?=GetMessage('CRM_STATUS_BUTTONS_SAVE');?>"
				   class="webform-small-button webform-small-button-accept">
			<input type="button" value="<?=GetMessage('CRM_STATUS_BUTTONS_CANCEL');?>"
				   class="webform-small-button webform-small-button-cancel" onclick="BX['<?=$jsClass?>'].statusReset()">
		</div>
	</div>

</div>

</form>

<?
function getColorText($color, &$iconClass, &$blockClass)
{
	$r = ord(pack("H*", mb_substr($color, 1, 2)));
	$g = ord(pack("H*", mb_substr($color, 3, 2)));
	$b = ord(pack("H*", mb_substr($color, 5, 2)));
	$y = 0.21 * $r + 0.72 * $g + 0.07 * $b;

	if ($y < 145)
	{
		$iconClass = 'light-icon';
		$blockClass = 'transaction-stage-phase-dark';
		return '#FFFFFF';
	}
	else
	{
		$blockClass = '';
		$iconClass = 'dark-icon';
		return '#545C69';
	}
}
?>

<script>
	function OnSelectBGColor(color, objColorPicker)
	{
		BX['<?=$jsClass?>'].paintElement(color, objColorPicker);
	}
</script>
<div id="block-color-picker" class="crm-config-status-block-color-picker">
	<? $APPLICATION->includeComponent(
		"bitrix:main.colorpicker",
		"",
		array(
			"SHOW_BUTTON" =>"Y",
			"ONSELECT" => "OnSelectBGColor"
		)
	); ?>
</div>

<script>
	BX.ready(function(){

		BX.message(
			{
				CRM_STATUS_NEW: '<?= GetMessageJS('CRM_STATUS_NEW') ?>',
				CRM_STATUS_FINAL_TITLE: '<?= GetMessageJS('CRM_STATUS_FINAL_TITLE') ?>',
				CRM_STATUS_CONFIRMATION_DELETE_TITLE: '<?= GetMessageJS('CRM_STATUS_CONFIRMATION_DELETE_TITLE') ?>',
				CRM_STATUS_CONFIRMATION_DELETE_SAVE_BUTTON: '<?= GetMessageJS('CRM_STATUS_CONFIRMATION_DELETE_SAVE_BUTTON') ?>',
				CRM_STATUS_CONFIRMATION_DELETE_CANCEL_BUTTON: '<?= GetMessageJS('CRM_STATUS_CONFIRMATION_DELETE_CANCEL_BUTTON') ?>',
				CRM_STATUS_DELETE_FIELD_QUESTION: '<?= GetMessageJS('CRM_STATUS_DELETE_FIELD_QUESTION') ?>',
				CRM_STATUS_CHECK_CHANGES: '<?= GetMessageJS('CRM_STATUS_CHECK_CHANGES') ?>',
				CRM_STATUS_REMOVE_ERROR: '<?= GetMessageJS('CRM_STATUS_REMOVE_ERROR') ?>',
				CRM_STATUS_CLOSE_POPUP_REMOVE_ERROR: '<?= GetMessageJS('CRM_STATUS_CLOSE_POPUP_REMOVE_ERROR') ?>',
				CRM_STATUS_FOOTER_PIN_ON: '<?= GetMessageJS('CRM_STATUS_FOOTER_PIN_ON') ?>',
				CRM_STATUS_FOOTER_PIN_OFF: '<?= GetMessageJS('CRM_STATUS_FOOTER_PIN_OFF') ?>'
			}
		);

<?foreach($arResult['ENTITY'] as $entityId => $entitySettings):?>
	<?if(isset($entitySettings['DEFAULT_NAME'])):?>
		BX.message({ CRM_STATUS_NEW_<?=$entityId?>: "<?=CUtil::JSEscape($entitySettings['DEFAULT_NAME'])?>" });
	<?endif;?>
<?endforeach;?>

		BX.CrmConfigStatusClass.semanticEntityTypes = <?=CUtil::PhpToJsObject(array_keys($arResult["ENTITY"]))?>;
		BX.CrmConfigStatusClass.entityInfos = <?=CUtil::PhpToJsObject($arResult["ENTITY"])?>;

		BX['<?=$jsClass?>'] = new BX.CrmConfigStatusClass({
			randomString: '<?= $arResult['RAND_STRING'] ?>',
			tabs: <?=CUtil::PhpToJsObject(array_keys($arResult['HEADERS']))?>,
			ajaxUrl: "<?=SITE_DIR?>bitrix/components/bitrix/crm.config.status/ajax.php?&<?=bitrix_sessid_get()?>",
			entityId: "<?=CUtil::JSEscape($arResult['ACTIVE_ENTITY_ID'])?>",
			hasSemantics: <?=isset($arResult['ENTITY'][$arResult['ACTIVE_ENTITY_ID']]) ? 'true' : 'false'?>,
			data: <?=CUtil::PhpToJsObject($arResult["ROWS"])?>,
			totalNumberFields: <?=$totalNumberFields?>,
			successFields: <?=CUtil::PhpToJSObject($successFields)?>,
			unSuccessFields: <?=CUtil::PhpToJSObject($unSuccessFields)?>,
			initialFields: <?=CUtil::PhpToJSObject($initialFields)?>,
			extraFields: <?=CUtil::PhpToJSObject($extraFields)?>,
			finalFields: <?=CUtil::PhpToJSObject($finalFields)?>,
			extraFinalFields: <?=CUtil::PhpToJSObject($extraFinalFields)?>,
			blockFixed: '<?=$blockFixed?>'
		});

		<? if($arResult['NEED_FOR_FIX_STATUSES']): ?>
			var fixStatusesLink = BX("fixStatusesLink");
			if(fixStatusesLink)
			{
				BX.bind(fixStatusesLink, "click", function(e) {
					BX['<?=$jsClass?>'].fixStatuses();
					return BX.PreventDefault(e);
				});
			}
		<? endif; ?>

		DragManager.onDragStart = function(dragObject) {
			BX['<?=$jsClass?>'].setDragStartParentElement(dragObject.avatar.parentElement);
		};

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
