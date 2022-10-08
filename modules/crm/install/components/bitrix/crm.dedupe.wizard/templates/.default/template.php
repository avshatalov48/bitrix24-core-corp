<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityProgressBarComponent $component */

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass.' ' : '').'crm-dedupe-wizard-body-modifier');
\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
	'sidepanel',
	'ui.common',
	'ui.forms',
	'ui.hint',
	'ui.progressbar',
	'ui.fonts.opensans',
	'ui.icons.b24',
	'ui.buttons',
	'ui.notification',
	'crm_common',
	'ls',
]);

$configTitleID = 'configTitle';
$configTitleTextID = 'configTitleText';
$configEditButtonID = 'editConfig';
$configEditModeContainer = 'configEditModeContainer';
$configViewModeContainer = 'configViewModeContainer';
$scanButtonID = 'scanButton';
$stopButtonID = 'stopButton';
$returnToScanButtonID1 = 'rescanButton1';
$returnToScanButtonID2 = 'rescanButton2';
$returnToScanButtonID3 = 'rescanButton3';
$mergeButtonID = 'mergeButton';
$mergeStopButtonID = 'mergeStopButton';
$mergeSummaryButtonID = 'mergeSummaryButton';
$conflictResolvingButtonID = 'conflictResolvingButton';
$conflictResolvingAlternateButtonID = 'conflictResolvingAlternateButton';
$progressBarWrapperID = 'progressBar';
$mergeProgressBarWrapperID = 'mergeProgressBar';
$mergeListButtonID = 'mergeListButton';
$conflictResolvingListButtonID = 'conflictResolvingListButton';
$backToListLinkId = 'backToEntityList';
?>

<div id="scanning" class="crm-dedupe-wizard-start-container" style="display: none">
	<h1 id="scanningTitle" class="ui-title-1 crm-dedupe-wizard-start-title"><?=GetMessage('CRM_DEDUPE_WIZARD_STEP1_TITLE')?></h1>
	<div class="crm-dedupe-wizard-start-border-field-container">
		<div class="crm-dedupe-wizard-start-border-field" id="<?=$configEditModeContainer?>">
			<span class="crm-dedupe-wizard-start-text"><?=GetMessage('CRM_DEDUPE_WIZARD_CONFIGURATION_TITLE')?>:</span>
			<a href="#" id="<?=htmlspecialcharsbx($configTitleID)?>" class="crm-dedupe-wizard-start-link"></a>
			<a href="#" id="<?=htmlspecialcharsbx($configEditButtonID)?>" class="crm-dedupe-wizard-start-link crm-dedupe-wizard-start-link-grey"><?=GetMessage('CRM_DEDUPE_WIZARD_CHANGE_CONFIGURATION')?></a>
		</div>
		<div class="crm-dedupe-wizard-start-border-field" id="<?=$configViewModeContainer?>" style="display: none">
			<span class="crm-dedupe-wizard-start-text"><?=GetMessage('CRM_DEDUPE_WIZARD_CONFIGURATION_TITLE')?>:</span>
			<span id="<?=htmlspecialcharsbx($configTitleTextID)?>"></span>
		</div>
	</div>
	<div class="crm-dedupe-wizard-start-icon crm-dedupe-wizard-start-icon-scanning">
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-blue crm-dedupe-wizard-start-icon-cloud-left-top"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-left-bottom"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-blue crm-dedupe-wizard-start-icon-cloud-blue-right crm-dedupe-wizard-start-icon-cloud-right-bottom"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-right crm-dedupe-wizard-start-icon-cloud-right-top"></div>
		<div class="crm-dedupe-wizard-start-icon-main">
			<div class="crm-dedupe-wizard-start-icon-refresh"></div>
			<div class="crm-dedupe-wizard-start-icon-zoom"></div>
			<div class="crm-dedupe-wizard-start-icon-circle"></div>
		</div>
	</div>
	<div class="crm-dedupe-wizard-start-control-box">
		<div id="<?=htmlspecialcharsbx($scanButtonID)?>" class="ui-btn ui-btn-primary crm-dedupe-wizard-start-btn"><?=GetMessage('CRM_DEDUPE_WIZARD_START_SEARCH')?></div>
		<div id="<?=htmlspecialcharsbx($progressBarWrapperID)?>" class="crm-dedupe-wizard-status-bar"></div>
		<div id="<?=htmlspecialcharsbx($stopButtonID)?>" class="ui-btn ui-btn-primary crm-dedupe-wizard-start-btn"><?=GetMessage('CRM_DEDUPE_WIZARD_STOP_SEARCH')?></div>
	</div>
	<div class="crm-dedupe-wizard-start-description">
		<p><?=GetMessage('CRM_DEDUPE_WIZARD_REBUILD_DEDUPE_INDEX')?></p>
	</div>
</div>

<div id="merging" class="crm-dedupe-wizard-start-container crm-dedupe-wizard-start-combination" style="display: none">
	<h1 id="mergingTitle" class="ui-title-1 crm-dedupe-wizard-start-title"></h1>
	<h2 id="mergingSubtitle" class="ui-title-2 crm-dedupe-wizard-start-title-light-text"></h2>
	<div class="crm-dedupe-wizard-start-description"></div>
	<div class="crm-dedupe-wizard-start-icon crm-dedupe-wizard-start-icon-merging">
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-blue crm-dedupe-wizard-start-icon-cloud-left-top"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-left-bottom"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-blue crm-dedupe-wizard-start-icon-cloud-blue-right crm-dedupe-wizard-start-icon-cloud-right-bottom"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-right crm-dedupe-wizard-start-icon-cloud-right-top"></div>
		<div class="crm-dedupe-wizard-start-icon-main">
			<div class="crm-dedupe-wizard-start-icon-refresh-noarrows"></div>
			<div class="crm-dedupe-wizard-start-icon-merge"></div>
			<div class="crm-dedupe-wizard-start-icon-circle"></div>
		</div>
	</div>
	<div class="crm-dedupe-wizard-start-control-box crm-dedupe-wizard-start-control-box-ready-to-merge-state">
		<div class="crm-dedupe-wizard-start-control-box-item">
			<div id="<?=htmlspecialcharsbx($returnToScanButtonID1)?>" class="ui-btn ui-btn-light crm-dedupe-wizard-start-btn"><?=GetMessage('CRM_DEDUPE_WIZARD_RETURN_TO_SCAN')?></div>
		</div>
		<div class="crm-dedupe-wizard-start-control-box-item">
			<div id="<?=htmlspecialcharsbx($mergeButtonID)?>" class="ui-btn ui-btn-primary crm-dedupe-wizard-merge-btn"><?=GetMessage('CRM_DEDUPE_WIZARD_MERGE_AUTO')?></div>
		</div>
		<div class="crm-dedupe-wizard-start-control-box-item">
			<div id="<?=htmlspecialcharsbx($conflictResolvingAlternateButtonID)?>" class="ui-btn ui-btn-light crm-dedupe-wizard-start-btn"><?=GetMessage('CRM_DEDUPE_WIZARD_MANUAL_MERGE')?></div>
		</div>
		<div id="<?=htmlspecialcharsbx($mergeProgressBarWrapperID)?>" class="crm-dedupe-wizard-status-bar"></div>
		<div id="<?=htmlspecialcharsbx($mergeStopButtonID)?>" class="ui-btn ui-btn-primary crm-dedupe-wizard-start-btn"><?=GetMessage('CRM_DEDUPE_WIZARD_STOP_MERGE')?></div>
	</div>
	<div class="crm-dedupe-wizard-start-description">
		<div class="crm-dedupe-wizard-merge-block-auto"><p><?=GetMessage('CRM_DEDUPE_WIZARD_MERGING_LEGEND')?></p></div>
		<div class="crm-dedupe-wizard-merge-block-manual"><p><?=GetMessage('CRM_DEDUPE_WIZARD_MANUAL_MERGING_LEGEND')?></p></div>
	</div>
	<div class="crm-dedupe-wizard-start-link-container">
		<a id="<?=htmlspecialcharsbx($mergeListButtonID)?>" href="#" class="crm-dedupe-wizard-start-link crm-dedupe-wizard-start-link-light-grey"><?=GetMessage('CRM_DEDUPE_WIZARD_SHOW_DEDUPE_LIST')?></a>
	</div>
</div>

<div id="mergingSummary" class="crm-dedupe-wizard-start-container crm-dedupe-wizard-start-next-step" style="display: none">
	<h1 id="mergingSummaryTitle" class="ui-title-1 crm-dedupe-wizard-start-title"></h1>
	<h2 id="mergingSummarySubtitle" class="ui-title-2 crm-dedupe-wizard-start-title-light-text"></h2>
	<div class="crm-dedupe-wizard-start-icon">
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-blue crm-dedupe-wizard-start-icon-cloud-left-top"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-left-bottom"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-blue crm-dedupe-wizard-start-icon-cloud-blue-right crm-dedupe-wizard-start-icon-cloud-right-bottom"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-right crm-dedupe-wizard-start-icon-cloud-right-top"></div>
		<div class="crm-dedupe-wizard-start-icon-main">
			<div class="crm-dedupe-wizard-start-icon-refresh-noarrows"></div>
			<div class="crm-dedupe-wizard-start-icon-like"></div>
			<div class="crm-dedupe-wizard-start-icon-circle"></div>
		</div>
	</div>
	<div id="<?=htmlspecialcharsbx($mergeSummaryButtonID)?>" class="ui-btn ui-btn-primary crm-dedupe-wizard-start-btn"><?=GetMessage('CRM_DEDUPE_WIZARD_NEXT')?></div>
</div>

<div id="conflictResolving" class="crm-dedupe-wizard-start-container crm-dedupe-wizard-start-warning" style="display: none">
	<h1 id="conflictResolvingTitle" class="ui-title-1 crm-dedupe-wizard-start-title"></h1>
	<h2 id="conflictResolvingSubtitle" class="ui-title-2 crm-dedupe-wizard-start-title-light-text"></h2>
	<div class="crm-dedupe-wizard-start-description crm-dedupe-wizard-merge-block-auto"><?=GetMessage('CRM_DEDUPE_WIZARD_CONFLICT_RESOLVING_LEGEND')?></div>
	<div class="crm-dedupe-wizard-start-icon">
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-blue crm-dedupe-wizard-start-icon-cloud-left-top"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-left-bottom"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-blue crm-dedupe-wizard-start-icon-cloud-blue-right crm-dedupe-wizard-start-icon-cloud-right-bottom"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-right crm-dedupe-wizard-start-icon-cloud-right-top"></div>
		<div class="crm-dedupe-wizard-start-icon-main crm-dedupe-wizard-merge-block-auto">
			<div class="crm-dedupe-wizard-start-icon-refresh-noarrows crm-dedupe-wizard-start-icon-refresh-noarrows-yellow"></div>
			<div class="crm-dedupe-wizard-start-icon-alert"></div>
			<div class="crm-dedupe-wizard-start-icon-circle crm-dedupe-wizard-start-icon-circle-yellow"></div>
		</div>
		<div class="crm-dedupe-wizard-start-icon-main crm-dedupe-wizard-merge-block-manual">
			<div class="crm-dedupe-wizard-start-icon-refresh-noarrows"></div>
			<div class="crm-dedupe-wizard-start-icon-like"></div>
			<div class="crm-dedupe-wizard-start-icon-circle"></div>
		</div>
	</div>
	<div id="<?=htmlspecialcharsbx($returnToScanButtonID3)?>" class="ui-btn ui-btn-light crm-dedupe-wizard-restart-btn"><?=GetMessage('CRM_DEDUPE_WIZARD_RETURN_TO_SCAN')?></div>
	<div id="<?=htmlspecialcharsbx($conflictResolvingButtonID)?>" class="ui-btn ui-btn-primary crm-dedupe-wizard-start-btn"><?=GetMessage('CRM_DEDUPE_WIZARD_MANUAL_MERGE')?></div>
	<div class="crm-dedupe-wizard-start-link-container">
		<a id="<?=htmlspecialcharsbx($conflictResolvingListButtonID)?>" href="#" class="crm-dedupe-wizard-start-link crm-dedupe-wizard-start-link-light-grey"><?=GetMessage('CRM_DEDUPE_WIZARD_SHOW_DEDUPE_LIST')?></a>
	</div>
</div>

<div id="finish" class="crm-dedupe-wizard-start-container crm-dedupe-wizard-start-done" style="display: none">
	<h1 id="finishTitle" class="ui-title-1 crm-dedupe-wizard-start-title crm-dedupe-wizard-start-title-light-text"><?=GetMessage('CRM_DEDUPE_WIZARD_FINISH_TITLE')?></h1>
	<h2 id="finishSubtitle" class="ui-title-1 crm-dedupe-wizard-start-title crm-dedupe-wizard-start-title-dark-text"></h2>
	<div class="crm-dedupe-wizard-start-icon">
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-blue crm-dedupe-wizard-start-icon-cloud-left-top"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-left-bottom"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-blue crm-dedupe-wizard-start-icon-cloud-blue-right crm-dedupe-wizard-start-icon-cloud-right-bottom"></div>
		<div class="crm-dedupe-wizard-start-icon-cloud crm-dedupe-wizard-start-icon-cloud-right crm-dedupe-wizard-start-icon-cloud-right-top"></div>
		<div class="crm-dedupe-wizard-start-icon-main">
			<div class="crm-dedupe-wizard-start-icon-refresh-noarrows"></div>
			<div class="crm-dedupe-wizard-start-icon-like"></div>
			<div class="crm-dedupe-wizard-start-icon-circle"></div>
		</div>
	</div>
	<div class="crm-dedupe-wizard-start-control-box-item">
		<div id="<?=htmlspecialcharsbx($returnToScanButtonID2)?>" class="ui-btn ui-btn-light crm-dedupe-wizard-restart-btn"><?=GetMessage('CRM_DEDUPE_WIZARD_RETURN_TO_SCAN')?></div>
	</div>
	<?if ($arResult['PATH_TO_ENTITY_LIST']):?>
	<a href="<?=$arResult['PATH_TO_ENTITY_LIST']?>" id="<?=htmlspecialcharsbx($backToListLinkId)?>" class="ui-btn ui-btn-primary"><?=GetMessage('CRM_DEDUPE_WIZARD_BACK_TO_LIST')?></a>
	<?endif?>
</div>

<script type="text/javascript">
	BX.ready(
		function()
		{
			BX.Crm.DedupeWizard.messages = {
				closeConfirmationTitle: "<?=GetMessageJS("CRM_DEDUPE_WIZARD_SLIDER_CLOSE_CONFIRMATION_TITLE")?>",
				closeConfirmationText: "<?=GetMessageJS("CRM_DEDUPE_WIZARD_SLIDER_CLOSE_CONFIRMATION_TEXT")?>"
			};
			var wizard = BX.Crm.DedupeWizard.create(
				"<?=$arResult['GUID']?>",
				{
					entityTypeId: <?=$arResult['ENTITY_TYPE_ID']?>,
					currentScope: "<?=CUtil::JSEscape($arResult['CURRENT_SCOPE'])?>",
					config: <?=CUtil::PhpToJSObject($arResult['CONFIG'])?>,
					typeInfos: <?=CUtil::PhpToJSObject($arResult['TYPE_INFOS'])?>,
					scopeInfos: <?=CUtil::PhpToJSObject($arResult['SCOPE_LIST_ITEMS'])?>,
					mergerUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_MERGER'])?>",
					dedupeListUrl: "<?=CUtil::JSEscape($arResult['PATH_TO_DEDUPE_LIST'])?>",
					contextId: "<?=CUtil::JSEscape($arResult['CONTEXT_ID'])?>",
					dedupeSettingsPath: "<?=CUtil::JSEscape($arResult['PATH_TO_DEDUPE_SETTINGS'])?>",
					steps: {
						scanning: BX.Crm.DedupeWizardScanning.create(
							"scanning",
							{
								wrapperId: "scanning",
								buttonId: "<?=CUtil::JSEscape($scanButtonID)?>",
								stopButtonId: "<?=CUtil::JSEscape($stopButtonID)?>",
								titleWrapperId: "scanningTitle",
								configTitleId: "<?=CUtil::JSEscape($configTitleID)?>",
								configTitleTextId: "<?=CUtil::JSEscape($configTitleTextID)?>",
								configEditButtonId: "<?=CUtil::JSEscape($configEditButtonID)?>",
								configEditModeContainer: "<?=CUtil::JSEscape($configEditModeContainer)?>",
								configViewModeContainer: "<?=CUtil::JSEscape($configViewModeContainer)?>",
								progressBarWrapperId: "<?=CUtil::JSEscape($progressBarWrapperID)?>",
								nextStepId: "merging",
								messages:
									{
										emptyConfig: "<?=GetMessageJS('CRM_DEDUPE_WIZARD_EMPTY_CONFIGURATION')?>",
									}
							}
						),
						merging: BX.Crm.DedupeWizardMerging.create(
							"merging",
							{
								wrapperId: "merging",
								returnToScanButtonId: "<?=CUtil::JSEscape($returnToScanButtonID1)?>",
								buttonId: "<?=CUtil::JSEscape($mergeButtonID)?>",
								alternateButtonId: "<?=CUtil::JSEscape($conflictResolvingAlternateButtonID)?>",
								listButtonId: "<?=CUtil::JSEscape($mergeListButtonID)?>",
								stopButtonId: "<?=CUtil::JSEscape($mergeStopButtonID)?>",
								titleWrapperId: "mergingTitle",
								subtitleWrapperId: "mergingSubtitle",
								progressBarWrapperId: "<?=CUtil::JSEscape($mergeProgressBarWrapperID)?>",
								messages:
									{
										duplicatesFound: "<?=GetMessageJS('CRM_DEDUPE_WIZARD_DUPLICATES_FOUND')?>",
										matchesFound: "<?=GetMessageJS('CRM_DEDUPE_WIZARD_MATCHES_FOUND_NEW')?>"
									}
							}
						),
						mergingSummary: BX.Crm.DedupeWizardMergingSummary.create(
							"mergingSummary",
							{
								wrapperId: "mergingSummary",
								buttonId: "<?=CUtil::JSEscape($mergeSummaryButtonID)?>",
								titleWrapperId: "mergingSummaryTitle",
								subtitleWrapperId: "mergingSummarySubtitle",
								messages:
									{
										duplicatesProcessed: "<?=GetMessageJS('CRM_DEDUPE_WIZARD_DUPLICATES_PROCESSED')?>",
										matchesProcessed: "<?=GetMessageJS('CRM_DEDUPE_WIZARD_MATCHES_PROCESSED_NEW')?>"
									}
							}
						),
						conflictResolving: BX.Crm.DedupeWizardConflictResolving.create(
							"conflictResolving",
							{
								wrapperId: "conflictResolving",
								buttonId: "<?=CUtil::JSEscape($conflictResolvingButtonID)?>",
								alternateButtonId: "<?=CUtil::JSEscape($conflictResolvingAlternateButtonID)?>",
								listButtonId: "<?=CUtil::JSEscape($conflictResolvingListButtonID)?>",
								titleWrapperId: "conflictResolvingTitle",
								subtitleWrapperId: "conflictResolvingSubtitle",
								returnToScanButtonId: "<?=CUtil::JSEscape($returnToScanButtonID3)?>",
								messages:
									{
										duplicatesConflicted: "<?=GetMessageJS('CRM_DEDUPE_WIZARD_DUPLICATES_CONFLICTED')?>",
										matchesConflicted: "<?=GetMessageJS('CRM_DEDUPE_WIZARD_MATCHES_CONFLICTED_NEW')?>"
									}
							}
						),
						finish: BX.Crm.DedupeWizardMergingFinish.create(
							"finish",
							{
								wrapperId: "finish",
								titleWrapperId: "finishTitle",
								subtitleWrapperId: "finishSubtitle",
								returnToScanButtonId: "<?=CUtil::JSEscape($returnToScanButtonID2)?>",
								backToListLinkId: "<?=CUtil::JSEscape($backToListLinkId)?>",
								messages:
									{
										duplicatesComplete: "<?=GetMessageJS('CRM_DEDUPE_WIZARD_DUPLICATES_COMPLETE')?>",
										duplicatesCompleteEmpty: "<?=GetMessageJS('CRM_DEDUPE_WIZARD_DUPLICATES_COMPLETE_EMPTY')?>"
									}
							}
						)
					},
					indexAgentState: <?=CUtil::PhpToJSObject($arResult['INDEX_AGENT_STATE'])?>,
					mergeAgentState: <?=CUtil::PhpToJSObject($arResult['MERGE_AGENT_STATE'])?>
				}
			);
			wizard.layout();
		}
	);
</script>
