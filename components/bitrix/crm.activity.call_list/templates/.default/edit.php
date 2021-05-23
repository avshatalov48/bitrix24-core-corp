<?php
/**
 * @global $APPLICATION
 * @global $arResult
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

\Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/components/bitrix/main.ui.grid/templates/.default/style.css');
?>

<div id="activity-call-list" class="activity-call-list-main" data-role="call-list-container">
	<input type="hidden" name="callListId" value="<?=(int)$arResult['CALL_LIST']['ID']?>">
	<div class="activity-call-list-container">
		<div class="crm-activity-popup-info-location-container">
			<span class="crm-activity-popup-info-location-text"><?=GetMessage('CRM_ACTIVITY_CALL_LIST_SUBJECT')?>:</span>
			<input type="text" name="callListSubject" value="<?=htmlspecialcharsbx($arResult['CALL_LIST']['SUBJECT'])?>" class="crm-activity-popup-info-location" placeholder="<?=GetMessage('CRM_ACTIVITY_CALL_LIST_SUBJECT_PLACEHOLDER')?>" data-role="call-list-subject">
		</div>
		<div class="crm-activity-popup-info-person-detail-description">
			<label class="crm-activity-popup-info-person-detail-description-name"><?=GetMessage('CRM_ACTIVITY_CALL_LIST_DESCRIPTION')?>:</label>
			<textarea name="callListDescription" class="crm-activity-popup-info-person-detail-description-input" placeholder="<?=GetMessage('CRM_ACTIVITY_CALL_LIST_DESCRIPTION_PLACEHOLDER')?>" data-role="call-list-description"><?=htmlspecialcharsbx($arResult['CALL_LIST']['DESCRIPTION'])?></textarea>
		</div>
		<? if($arResult['CALL_LIST']['NEW']): ?>
			<div class="crm-activity-popup-edit-section">
				<div class="crm-activity-popup-edit-section-title"><?=GetMessage('CRM_ACTIVITY_CALL_LIST_LABEL')?>:</div>
				<div class="crm-activity-popup-edit-section-content crm-activity-popup-edit-include-lead">
					<div class="crm-activity-popup-edit-include-lead-container">
						<div class="crm-activity-popup-edit-include-lead-row">
							<div class="crm-activity-popup-edit-include-lead-title"><?=GetMessage('CRM_ACTIVITY_CALL_LIST_CREATE_FROM')?>:</div>
							<div class="crm-activity-popup-edit-include-lead-list">
								<span class="crm-activity-popup-edit-include-lead-list-item"
									  data-role="create-from-leads"
									  data-url="<?=htmlspecialcharsbx($arResult['URLS']['LEAD_LIST'])?>"
								>
									<?= htmlspecialcharsbx(CCrmOwnerType::GetCategoryCaption(CCrmOwnerType::Lead))?>
								</span>
								<span class="crm-activity-popup-edit-include-lead-list-item"
									  data-role="create-from-contacts"
									  data-url="<?=htmlspecialcharsbx($arResult['URLS']['CONTACT_LIST'])?>"
								>
									<?= htmlspecialcharsbx(CCrmOwnerType::GetCategoryCaption(CCrmOwnerType::Contact))?>
								</span>
								<span class="crm-activity-popup-edit-include-lead-list-item"
									  data-role="create-from-companies"
									  data-url="<?=htmlspecialcharsbx($arResult['URLS']['COMPANY_LIST'])?>"
								>
									<?= htmlspecialcharsbx(CCrmOwnerType::GetCategoryCaption(CCrmOwnerType::Company))?>
								</span>
								<span class="crm-activity-popup-edit-include-lead-list-item"
									  data-role="create-from-deals"
									  data-url="<?=htmlspecialcharsbx($arResult['URLS']['DEAL_LIST'])?>"
								>
									<?= htmlspecialcharsbx(CCrmOwnerType::GetCategoryCaption(CCrmOwnerType::Deal))?>
								</span>
								<span class="crm-activity-popup-edit-include-lead-list-item"
									  data-role="create-from-quotes"
									  data-url="<?=htmlspecialcharsbx($arResult['URLS']['QUOTE_LIST'])?>"
								>
									<?= htmlspecialcharsbx(CCrmOwnerType::GetCategoryCaption(CCrmOwnerType::Quote))?>
								</span>
								<span class="crm-activity-popup-edit-include-lead-list-item"
									  data-role="create-from-invoices"
									  data-url="<?=htmlspecialcharsbx($arResult['URLS']['INVOICE_LIST'])?>"
								>
									<?= htmlspecialcharsbx(CCrmOwnerType::GetCategoryCaption(CCrmOwnerType::Invoice))?>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		<? else: ?>
			<div data-role="call-list-display">
				<div class="crm-activity-popup-edit-section-title">
					<?=GetMessage('CRM_ACTIVITY_CALL_LIST_LABEL')?>
					<span class="crm-activity-popup-edit-add-more" data-role="add-more" data-url="<?=htmlspecialcharsbx($arResult['URLS']['ADD_MORE_URL'])?>">
						(<?=GetMessage('CRM_CALL_LIST_ADD_MORE')?>)
					</span>:
				</div>
				<div class="activity-call-list-display-tabs" data-role="call-list-tab-header">
					<div class="activity-call-list-display-tab activity-call-list-display-tab-active" data-tab-header="params">
						<?=GetMessage('CRM_ACTIVITY_CALL_LIST_CREATE_TAB_FILTER_PARAMS')?>
					</div>
					<div class="activity-call-list-display-tab activity-call-list-display-tab-inactive" data-tab-header="grid">
						<?=htmlspecialcharsbx($arResult['CALL_LIST']['ENTITY_CAPTION'])?>
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
				</div>
			</div>
		<? endif ?>
	</div>
</div>

<? if($arResult['INITIALIZE_EDITOR']): ?>
	<script>
		(function()
		{
			BX.message({
				'CRM_ACTIVITY_CALL_LIST_ACTIVITY_CREATED': '<?=GetMessageJS('CRM_ACTIVITY_CALL_LIST_ACTIVITY_CREATED')?>',
				'CRM_ACTIVITY_CALL_LIST_ACTIVITY_CREATED_TEXT': '<?=GetMessageJS('CRM_ACTIVITY_CALL_LIST_ACTIVITY_CREATED_TEXT')?>',
				'CRM_ACTIVITY_CALL_LIST_ACTIVITY_GOTO': '<?=GetMessageJS('CRM_ACTIVITY_CALL_LIST_ACTIVITY_GOTO')?>'

			});

			BX.CallListActivity.create({
				node: BX('activity-call-list'),
				callListId: <?= (int)$arResult['CALL_LIST']['ID']?>,
				allowEdit: true,
				gridId: '<?=$arResult['GRID_ID']?>',
				enableSavePopup: <?=($arResult['CALL_LIST']['NEW'] ? 'false' : 'true')?>
			})
		})();
	</script>
<? endif ?>
