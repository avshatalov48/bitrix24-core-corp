<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/main.ui.grid/templates/.default/style.css');
?>

<div class="crm-task-list-call">
	<div class="crm-task-list-call-info">
		<div class="crm-task-list-call-info-container">
			<span class="crm-task-list-call-info-name"><?=GetMessage('CRM_CALL_LIST_SUBJECT')?>:</span>
		</div>
		<span><?=htmlspecialcharsbx($arResult['ACTIVITY']['SUBJECT'])?></span>
	</div>
	<div class="crm-task-list-call-info">
		<div class="crm-task-list-call-info-container">
			<span class="crm-task-list-call-info-name"><?=GetMessage('CRM_CALL_LIST_DESCRIPTION')?>:</span>
		</div>
		<span><?=htmlspecialcharsbx($arResult['ACTIVITY']['DESCRIPTION'])?></span>
	</div>

	<div id="activity-call-list" style="margin-top: 14px">
		<div class="crm-activity-popup-edit-section-title"><?=GetMessage('CRM_CALL_LIST_TITLE')?>:</div>
		<div data-role="call-list-display">
			<div class="activity-call-list-display-tabs" data-role="call-list-tab-header">
				<div class="activity-call-list-display-tab activity-call-list-display-tab-active" data-tab-header="params">
					<?=GetMessage('CRM_CALL_LIST_SELECTION_PARAMS')?>
				</div>
				<div class="activity-call-list-display-tab activity-call-list-display-tab-inactive" data-tab-header="grid">
					<?=htmlspecialcharsbx($arResult['CALL_LIST']['ENTITY_CAPTION'])?>
				</div>
				<div class="activity-call-list-display-tab activity-call-list-display-tab-inactive" data-tab-header="stats">
					<?=GetMessage('CRM_CALL_LIST_STATISTICS')?>
				</div>
			</div>

			<div data-role="call-list-tab-body">
				<div class="call-list-tab-content" data-tab="params">
					<div class="crm-activity-popup-edit-section">
						<div class="crm-activity-popup-edit-section-content crm-activity-popup-edit-lead-list">
							<? if($arResult['CALL_LIST']['FILTERED'] == 'Y'): ?>
								<?=$arResult['CALL_LIST']['FILTER_TEXT']?>
								<span class="crm-activity-call-list-filter-link" data-role="open-filter">
									<?= GetMessage('CRM_CALL_LIST_FILTER')?>
								</span>
							<? else: ?>
								<?=$arResult['CALL_LIST']['FILTER_TEXT']?>
							<? endif ?>
						</div>
					</div>
				</div>
				<div class="call-list-tab-content activity-call-list-display-hidden" data-tab="grid">
					<div class="" data-role="grid-container">
						<?/* grid loads via ajax to avoid php buffer corruption */?>
					</div>
				</div>
				<div class="call-list-tab-content activity-call-list-display-hidden" data-tab="stats">
					<div class="crm-activity-popup-edit-section">
						<div class="crm-activity-popup-edit-section-content crm-activity-popup-edit-lead-list">
							<? foreach ($arResult['CALL_LIST']['STATUS_STATS'] as $statRecord): ?>
								<?= htmlspecialcharsbx($statRecord['NAME'])?>: <?= (int)$statRecord['COUNT']?><br>
							<? endforeach ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<? if($arResult['CALL_LIST']['WEBFORM_ID'] > 0): ?>
		<div class="crm-activity-popup-edit-section">
			<span class="crm-activity-popup-edit-section-title"><?=GetMessage('CRM_CALL_LIST_USE_FORM')?>:</span>
			<span class="crm-activity-call-list-webform-name">
				<a href="<?=htmlspecialcharsbx($arResult['CALL_LIST']['WEBFORM_URL'])?>" target="_blank">
					<?=htmlspecialcharsbx($arResult['CALL_LIST']['WEBFORM_NAME'])?>
				</a>
			</span>
		</div>
	<? endif ?>
	<hr class="crm-activity-popup-edit-separator">
	<div class="crm-activity-popup-edit-section">
		<span class="webform-small-button webform-small-button-blue" data-role="invoke-call-interface">
			<?= ($arResult['CALL_LIST']['COMPLETE_COUNT'] > 0 ? GetMessage('CRM_CALL_LIST_BUTTON_CONTINUE') : GetMessage('CRM_CALL_LIST_BUTTON_START'));?>
		</span>
		<span class="crm-activity-popup-edit-call-count">
			<?= GetMessage(
				'CRM_CALL_LIST_COMPLETE',
				array(
					'#COMPLETE#' => $arResult['CALL_LIST']['COMPLETE_COUNT'],
					'#TOTAL#' => $arResult['CALL_LIST']['TOTAL_COUNT']
				)); ?>
		</span>
	</div>

</div>

<script>
	(function()
	{
		BX.CallListActivity.create({
			node: BX('activity-call-list'),
			callListId: <?= (int)$arResult['CALL_LIST']['ID']?>,
			webformId: <?= (int)$arResult['CALL_LIST']['WEBFORM_ID']?>,
			webformSecCode: '<?= CUtil::JSEscape($arResult['CALL_LIST']['WEBFORM_SECURITY_CODE'])?>',
			allowEdit: false,
			gridId: '<?=$arResult['GRID_ID']?>'
		})
	})();
</script>