<?php

use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityProgressBarComponent $component */

//\CJSCore::Init(array('date'));

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'ui.notification',
	'main.date',
	'main.loader',
	'ui.progressround',
	'ls',
]);

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/common.js');
$guid = $arResult['GUID'];
$prefix = mb_strtolower($guid);
$primaryEditorWrapperID = "{$prefix}_primary_wrapper";
$primaryEditorSwitchName = "{$prefix}_primary_switch";
$secondaryEditorContainerID = "{$prefix}_secondary_container";
$secondaryEditorHeaderContainerID = "{$prefix}_secondary_header_container";
$resultTitle = $arResult['RESULT_TITLE'] ?? Loc::getMessage("CRM_ENTITY_MERGER_RESULT_TITLE");

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'no-background');

?>
<div class="crm-entity-merger-wrapper">
	<div class="crm-entity-merger-sidebar">
		<div class="crm-entity-merger-sidebar-result"><?=GetMessage("CRM_ENTITY_MERGER_RESULT_TITLE")?></div>
		<div class="crm-entity-merger-sidebar-inner">
			<div class="crm-entity-merger-sidebar-warning-box">
				<div class="crm-entity-merger-sidebar-warning-icon"></div>
				<h5 class="crm-entity-merger-sidebar-warning-title">
					<?= htmlspecialcharsbx($resultTitle) ?>
				</h5>
				<div class="crm-entity-merger-sidebar-warning-text">
					<?=htmlspecialcharsbx($arResult['RESULT_LEGEND'])?>
				</div>
			</div>
			<div id="<?=htmlspecialcharsbx($primaryEditorWrapperID)?>"></div>
			<div class="crm-entity-merger-sidebar-skeleton">
			</div>
		</div>
	</div>
	<div class="crm-entity-merger-column">
		<div class="crm-entity-merger-column-inner">
			<div id="<?=htmlspecialcharsbx($secondaryEditorHeaderContainerID)?>" class="crm-entity-merger-column-head">
			</div>
			<div id="<?=htmlspecialcharsbx($secondaryEditorContainerID)?>" class="crm-entity-merger-column-container">
			</div>
			<script>
				BX.ready(
					function ()
					{
						BX.Crm.EntityMergerHeader.messages =
							{
								markAsNonDuplicate: "<?=GetMessageJS('CRM_ENTITY_MERGER_MARK_AS_NON_DUPLICATE')?>",
								open: "<?=GetMessageJS('CRM_ENTITY_MERGER_OPEN_ENTITY')?>"
							};

						BX.Crm.EntityMerger.messages =
							{
								unresolvedConflictsFound: "<?=GetMessageJS('CRM_ENTITY_MERGER_UNRESOLVED_CONFLICTS_FOUND')?>",
								primaryEntityNotFound: "<?=GetMessageJS('CRM_ENTITY_MERGER_PRIMARY_ENTITY_NOT_FOUND')?>",
								entitiesNotFound: "<?=GetMessageJS('CRM_ENTITY_MERGER_ENTITIES_NOT_FOUND')?>"
							};

						BX.Crm.EntityMerger.create(
							"<?=CUtil::JSEscape($arResult['GUID'])?>",
							{
								primaryEditorWrapperId: "<?=CUtil::JSEscape($primaryEditorWrapperID)?>",
								primaryEditorSwitchName: "<?=CUtil::JSEscape($primaryEditorSwitchName)?>",
								secondaryEditorContainerId: "<?=CUtil::JSEscape($secondaryEditorContainerID)?>",
								secondaryEditorHeaderContainerId: "<?=CUtil::JSEscape($secondaryEditorHeaderContainerID)?>",
								editorConfigId: "<?=CUtil::JSEscape($arResult['ENTITY_EDITOR_CONFIGURATION_ID'])?>",
								entityTypeId: <?=$arResult['ENTITY_TYPE_ID']?>,
								entityIds: <?=CUtil::PhpToJSObject($arResult['ENTITY_IDS'])?>,
								entityInfos: <?=CUtil::PhpToJSObject($arResult['ENTITY_INFOS'])?>,
								dedupeQueueInfo: <?=CUtil::PhpToJSObject($arResult['DEDUPE_QUEUE_INFO'])?>,
								dedupeConfig: <?=CUtil::PhpToJSObject($arResult['DEDUPE_CONFIG'])?>,
								dedupeCriterionData: <?=CUtil::PhpToJSObject($arResult['DEDUPE_CRITERION_DATA'])?>,
								dedupeListUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_DEDUPE_LIST'])?>",
								entityListUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_ENTITY_LIST'])?>",
								isAutomatic: <?=($arResult['IS_AUTOMATIC'] ? 'true' : 'false')?>,
								previouslyProcessedCount: <?=(isset($arResult['PROCESSED_COUNT']) ? (int)$arResult['PROCESSED_COUNT'] : 0)?>,
								entityEditorUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_EDITOR'])?>",
								externalContextId: "<?=CUtil::JSEscape($arResult['EXTERNAL_CONTEXT_ID'])?>",
								headerTitleTemplate: "<?=CUtil::JSEscape($arResult['HEADER_TEMPLATE'])?>",
								isReceiveEntityEditorFromController: <?= $arResult['IS_RECEIVE_ENTITY_EDITOR_FROM_CONTROLLER'] ? 'true' : 'false' ?>,
							}
						);
					}
				);
			</script>
		</div>
	</div>
</div>
<?

$APPLICATION->IncludeComponent('bitrix:ui.button.panel', '', [
	'CLASS_NAME' => 'crm-entity-merger-panel',
	'BUTTONS' => [
		['TYPE' => 'custom', 'LAYOUT' =>
			'<div id="queueStatisticsWrapper" class="crm-entity-merger-panel-box crm-entity-merger-panel-border">
						<ul class="crm-entity-merger-panel-list">
							<li class="crm-entity-merger-panel-list-item">
								<span class="crm-entity-merger-panel-list-name">'.GetMessage('CRM_ENTITY_MERGER_PROCESSED_AMOUNT').':</span>
								<span id="processedAmount" class="crm-entity-merger-panel-list-value"></span>
							</li>
							<li class="crm-entity-merger-panel-list-item">
								<span class="crm-entity-merger-panel-list-name">'.GetMessage('CRM_ENTITY_MERGER_REMAINING_AMOUNT').':</span>
								<span id="remainingAmount" class="crm-entity-merger-panel-list-value"></span>
							</li>
						</ul>
					</div>'],
		['TYPE' => 'custom', 'LAYOUT' =>
			'<div class="crm-entity-merger-panel-box">
					<div class="crm-entity-merger-panel-label-container">
						<div class="crm-entity-merger-panel-label-inner">
							<!--<label class="crm-entity-merger-panel-label">
									<input type="checkbox" class="crm-entity-merger-panel-checkbox">
									select primary column automatically
							</label>-->
						</div>
					</div>
					<div class="ui-btn-container ui-btn-container-center">
					  <input type="submit" class="ui-btn ui-btn-success ui-btn-disabled" name="merge" value="'.GetMessage('CRM_ENTITY_MERGER_PROCESS').'" id="mergeButton" >
					  <input type="button" class="ui-btn ui-btn-light-border ui-btn-disabled" name="merge_with_edit" value="'.GetMessage('CRM_ENTITY_MERGER_MERGE_AND_EDIT').'" id="mergeWithEditButton" title="" >
					  <input type="button" class="ui-btn ui-btn-link" name="postpone" value="'.GetMessage('CRM_ENTITY_MERGER_POSTPONE').'" id="postponeButton" title="" >
					</div>
					<div class="crm-entity-merger-panel-toggler-container">
						<div class="crm-entity-merger-panel-toggler-inner">
							<span id="duplicateListButton" class="ui-btn ui-btn-link crm-entity-merger-panel-toggler-name">'.GetMessage('CRM_ENTITY_MERGER_GO_TO_DUPLICATE_LIST').'</span>
							<button id="queueNavigationButton" class="crm-entity-merger-panel-toggler">
								<span id="previousButton" class="crm-entity-merger-panel-toggler-arrow"></span>
								<span id="nextButton" class="crm-entity-merger-panel-toggler-arrow"></span>
							</button>
						</div>
					</div>
				</div>']
	],
]);
?>
