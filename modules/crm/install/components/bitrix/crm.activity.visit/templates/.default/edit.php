<?php
/**
 * @global $APPLICATION
 * @global $arResult
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
?>

<div class="crm-activity-visit-wrapper">
	<div class="crm-activity-visit-container" data-role="visit-form">
		<input type="hidden" name="OWNER_ENTITY_TYPE" value="<?=htmlspecialcharsbx($arResult['OWNER_ENTITY_TYPE'])?>" data-role="field-owner-entity-type">
		<input type="hidden" name="OWNER_ENTITY_ID" value="<?=intval($arResult['OWNER_ENTITY_ID'])?>" data-role="field-owner-entity-id">
		<input type="hidden" name="OWNER_ENTITY_TITLE" value="" data-role="field-owner-entity-title">
		<input type="hidden" value="<?=intval($arResult['DEAL'])?>" data-role="field-owner-entity-deal">
		<input type="hidden" name="CREATE_TIMESTAMP" value="<?= time()?>" data-role="field-create-timestamp">

		<? if($arResult['FACEID_ENABLED']): ?>
			<div class="crm-activity-visit-faceid-container" data-role="faceid-container">
				<? /*<div class="crm-activity-visit-faceid-close">
					<div class="crm-activity-visit-faceid-close-item"></div>
				</div> */?><!--crm-activity-visit-faceid-close-->
				<div class="crm-activity-visit-faceid-tab">
					<span class="crm-activity-visit-faceid-tab-item crm-activity-visit-faceid-active-tab"><?=GetMessage('CRM_ACTIVITY_VISIT_TAB_VISIT')?></span>
				</div>
				<div class="crm-activity-visit-faceid-inner">
					<div class="crm-activity-visit-sidebar-photo">
						<div class="crm-activity-visit-sidebar-photo-settings">
							<span class="crm-activity-visit-sidebar-photo-settings-item" data-role="faceid-button-settings"></span>
						</div><!--crm-activity-visit-sidebar-photo-settings-->
						<div class="crm-activity-visit-faceid-video-container" data-role="faceid-video-container">
							<video class="crm-activity-visit-faceid-video" data-role="faceid-video"></video>
						</div>
						<div class="crm-activity-visit-faceid-picture-container crm-activity-visit-hidden" data-role="faceid-picture-container">
							<div class="crm-activity-visit-faceid-checkbox-container crm-activity-visit-hidden" data-role="faceid-button-save-photo">
								<div class="crm-activity-visit-faceid-checkbox-item">
									<span class="crm-activity-visit-faceid-checkbox-text"><?=GetMessage('CRM_ACTIVITY_VISIT_TAB_SAVE_PHOTO')?></span>
								</div>
							</div>
							<img class="crm-activity-visit-faceid-picture" src="" data-role="faceid-picture">
						</div>
						<div class="crm-activity-visit-hidden">
							<canvas data-role="faceid-canvas"></canvas>
						</div>
					</div><!--crm-activity-visit-sidebar-photo-->
					<div class="crm-activity-visit-sidebar-photo-button">
						<span class="crm-activity-visit-sidebar-photo-button-item" id="faceid-startbutton" data-role="faceid-button-picture"></span>
						<div class="crm-activity-visit-user-loader crm-activity-visit-hidden" data-role="faceid-button-picture-loader">
							<div class="crm-activity-visit-user-loader-item">
								<div class="crm-activity-visit-loader">
									<svg class="crm-activity-visit-circular" viewBox="25 25 50 50">
										<circle class="crm-activity-visit-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/>
									</svg>
								</div>
							</div>
						</div>
					</div><!--crm-activity-visit-sidebar-photo-button-->
					<div class="crm-activity-visit-sidebar-social crm-activity-visit-block-disable" data-role="faceid-social">
						<div class="crm-activity-visit-sidebar-social-inner">
							<div class="crm-activity-visit-sidebar-social-item">
								<div class="crm-activity-visit-main-user-info-control-social">
									<div class="crm-activity-visit-hidden" data-role="faceid-vk-profile">
										<span class="crm-activity-visit-main-user-info-control-social-name"><?=GetMessage('CRM_ACTIVITY_VISIT_VK')?></span>
										<a href="#" class="crm-activity-visit-main-user-info-control-social-item" target="_blank" data-role="faceid-vk-profile-link">VK.com/userprofile</a>
									</div>
									<span class="crm-activity-visit-main-user-info-control-social-item" data-role="faceid-button-search-social">
										<?=GetMessage('CRM_ACTIVITY_VISIT_SEARCH_VK')?>
									</span>
								</div>
							</div>
						</div>
					</div><!--crm-activity-visit-sidebar-social-->
				</div><!--crm-activity-visit-faceid-inner-->
			</div><!--crm-activity-visit-faceid-container-->
		<? endif ?>


		<div class="crm-activity-visit-card" data-role="activity-owner-card">
			<? $APPLICATION->IncludeComponent(
				"bitrix:crm.card.show",
				"",
				array(
					'ENTITY_TYPE' => $arResult['OWNER_ENTITY_TYPE'],
					'ENTITY_ID' => $arResult['OWNER_ENTITY_ID']
				)
			) ?>
		</div>

		<div class="<?=($arResult['SHOW_ENTITY_SELECTOR'] ? '' : 'crm-activity-visit-hidden')?>" data-role="owner-selector">
			<div class="crm-activity-visit-user-settings-container" >
				<? if ($arResult['CAN_CREATE_CONTACT'] || $arResult['CAN_CREATE_LEAD']): ?>
					<div class="crm-activity-visit-user-settings">
						<div class="crm-activity-visit-user-settings-title">
							<?=GetMessage('CRM_ACTIVITY_VISIT_CREATE')?>
						</div>
						<? if ($arResult['CAN_CREATE_CONTACT']): ?>
							<div class="crm-activity-visit-user-settings-item"
								 data-role="create-contact-button"
								 data-url="<?=htmlspecialcharsbx($arResult['CREATE_CONTACT_URL'])?>"
								 data-context="<?=htmlspecialcharsbx($arResult['CREATE_CONTACT_CONTEXT'])?>"
							>
								<?=GetMessage('CRM_ACTIVITY_VISIT_CONTACT')?>
							</div>
						<? endif ?>
						<? if ($arResult['CAN_CREATE_LEAD']): ?>
							<div class="crm-activity-visit-user-settings-item"
								 data-role="create-lead-button"
								 data-url="<?=htmlspecialcharsbx($arResult['CREATE_LEAD_URL'])?>"
								 data-context="<?=htmlspecialcharsbx($arResult['CREATE_LEAD_CONTEXT'])?>"
							>
								<?=GetMessage('CRM_ACTIVITY_VISIT_LEAD')?>
							</div>
						<? endif ?>
					</div><!--crm-activity-visit-user-settings-create-->
				<? endif ?>
				<div class="crm-activity-visit-user-settings">
					<div class="crm-activity-visit-user-settings-title"><?=GetMessage('CRM_ACTIVITY_VISIT_SELECT')?></div>
					<div class="crm-activity-visit-user-settings-item" data-role="select-owner-button"><?=GetMessage('CRM_ACTIVITY_VISIT_CONTACT_OR_COMPANY')?></div>
				</div><!--crm-activity-visit-user-settings-select-->

			</div><!--crm-activity-visit-user-settings-->
			<div class="crm-activity-visit-border"></div>
		</div>
		<div class="crm-activity-visit-recorder-container">
			<div class="crm-activity-visit-recorder" data-role="activity-recorder"></div>
			<div class="crm-activity-visit-recorder-settings">
				<div class="crm-activity-visit-recorder-settings-item" data-role="record-timer">
					<?=GetMessage('CRM_ACTIVITY_VISIT_RECORDING')?>
					<span data-role="record-length">00:00</span>
					<?=GetMessage('CRM_ACTIVITY_VISIT_MINUTES')?>
				</div>
				<div class="crm-activity-visit-recorder-settings-item crm-activity-visit-hidden" data-role="recorder-error">
					<?=GetMessage('CRM_ACTIVITY_BROWSER_ERROR')?>
				</div>
				<?/*
				<div class="crm-activity-visit-recorder-settings-button">
					<div class="crm-activity-visit-recorder-settings-button-item"></div>
					<div class="crm-activity-visit-popup-settings">
						<div class="crm-activity-visit-popup-settings-inner" id="faceid-settings-container">
							<div class="crm-activity-visit-popup-settings-inner-container">
								<div class="crm-activity-visit-popup-settings-inner-title">
									<span class="crm-activity-visit-popup-settings-inner-title-item">Действия:</span>
								</div>
								<div class="crm-activity-visit-popup-settings-inner-list" id="faceid-cameralist">
									<div class="crm-activity-visit-popup-settings-inner-list-item crm-activity-visit-popup-settings-record">Запись</div>
								</div>
								<div class="crm-activity-visit-popup-settings-inner-list" id="faceid-cameralist">
									<div class="crm-activity-visit-popup-settings-inner-list-item crm-activity-visit-popup-settings-pause">Пауза</div>
								</div>
							</div><!--crm-activity-visit-popup-settings-inner-container-->
							<div class="crm-activity-visit-popup-settings-inner-container">
								<div class="crm-activity-visit-popup-settings-inner-title">
									<span class="crm-activity-visit-popup-settings-inner-title-item">Микрофон:</span>
								</div>
								<div class="crm-activity-visit-popup-settings-inner-list" id="faceid-cameralist">
									<div class="crm-activity-visit-popup-settings-inner-list-item crm-activity-visit-popup-settings-checked">Lorem Ipsum</div>
								</div>
								<div class="crm-activity-visit-popup-settings-inner-list" id="faceid-cameralist">
									<div class="crm-activity-visit-popup-settings-inner-list-item">Lorem Ipsum</div>
								</div>
							</div><!--crm-activity-visit-popup-settings-inner-container-->
						</div>
					</div>
				</div>
				*/?>
			</div>
		</div>
		<div class="crm-activity-visit-hidden" data-role="entity-links">
			<div class="crm-activity-visit-border"></div>
			<div class="crm-activity-visit-badges">
				<?//<div class="crm-activity-visit-badges-comment">Комментарий</div>?>
				<?/*
			<div class="crm-activity-visit-badges-item"
				 data-role="create-activity-button"
				 data-url="<?=htmlspecialcharsbx($arResult['CREATE_ACTIVITY_URL'])?>"
			>
				<?=GetMessage('CRM_ACTIVITY_VISIT_ACTIVITY')?>
			</div>*/?>
				<? if($arResult['CAN_CREATE_DEAL']): ?>
					<div class="crm-activity-visit-badges-item"
						 data-role="add-deal-button"
						 data-url="<?=htmlspecialcharsbx($arResult['CREATE_DEAL_URL'])?>"
						 data-context="<?=htmlspecialcharsbx($arResult['CREATE_DEAL_CONTEXT'])?>"
					>
						<?=GetMessage('CRM_ACTIVITY_VISIT_DEAL')?>
					</div>
				<? endif ?>
				<? if($arResult['CAN_CREATE_INVOICE']): ?>
					<div class="crm-activity-visit-badges-item"
						 data-role="add-invoice-button"
						 data-url="<?=htmlspecialcharsbx($arResult['CREATE_INVOICE_URL'])?>"
						 data-context="<?=htmlspecialcharsbx($arResult['CREATE_INVOICE_CONTEXT'])?>"
					>
						<?=GetMessage('CRM_ACTIVITY_VISIT_INVOICE')?>
					</div>
				<? endif ?>
				<?//<div class="crm-activity-visit-badges-item">Связи</div>?>
				<?//<div class="crm-activity-visit-badges-item">Еще...</div>?>
			</div>

		</div>
	</div><!-- crm-activity-visit-container -->

	<div class="crm-activity-visit-button">
		<span class="crm-activity-button crm-activity-visit-button-item" data-role="button-finish"><?= GetMessage('CRM_ACTIVITY_VISIT_FINISH')?></span>
		<div class="crm-activity-visit-user-loader crm-activity-visit-hidden" data-role="loader-finish">
			<div class="crm-activity-visit-user-loader-item">
				<div class="crm-activity-visit-loader">
					<svg class="crm-activity-visit-circular" viewBox="25 25 50 50">
						<circle class="crm-activity-visit-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"/>
					</svg>
				</div>
			</div>
		</div>
	</div><!--crm-activity-visit-button-->
</div>

<!-- templates -->
<script type="text/html" data-role="template-social-search">
	<div class="crm-activity-visit-facesearch-profile-search-main">
		<div class="crm-activity-visit-facesearch-profile-search-loading crm-activity-visit-facesearch-animate-visible">
			<div class="crm-activity-visit-facesearch-profile-search-loading-block">
				<div class="crm-activity-visit-facesearch-user-loader-item">
					<div class="crm-activity-visit-loader" style="width: 100px">
						<svg class="crm-activity-visit-circular" viewBox="25 25 50 50">
							<circle class="crm-activity-visit-path" cx="50" cy="50" r="20" fill="none" stroke-width="1" stroke-miterlimit="10"></circle>
						</svg>
					</div>
				</div>
				<div class="crm-activity-visit-facesearch-profile-search-loading-desc"><?=GetMessage('CRM_ACTIVITY_VISIT_SEARCH_IN_PROGRESS')?></div>
			</div>
		</div>
	</div>
</script>