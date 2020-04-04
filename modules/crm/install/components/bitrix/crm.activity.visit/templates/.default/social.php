<?php
/**
 * @global $APPLICATION
 * @global $arResult
 */
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
?>
<div class="crm-activity-visit-facesearch-profile-search-main">
	<? if ($arResult['SUCCESS']): ?>
		<div class="crm-activity-visit-facesearch-main-user-item crm-activity-visit-facesearch-profile-search-found crm-activity-visit-facesearch-photo crm-activity-visit-facesearch-found-user">
			<div class="crm-activity-visit-facesearch-main-user-photo">
				<div class="crm-activity-visit-facesearch-main-user-photo-platform">
					<span class="crm-activity-visit-facesearch-main-user-photo-platform-item">CRM</span>
				</div>
				<div class="crm-activity-visit-facesearch-main-user-photo-item" style="background-image: url('<?= htmlspecialcharsbx($arResult["VK_PROFILES"][0]['photo'])?>')"></div>
				<div class="crm-activity-visit-facesearch-main-user-photo-count">
					<span class="crm-activity-visit-facesearch-main-user-photo-count-item"><?= intval($arResult["VK_PROFILES"][0]['confidence']*100)?>%</span>
				</div>
			</div>
			<div class="crm-activity-visit-facesearch-main-user-info">
				<div class="crm-activity-visit-facesearch-main-user-info-name">
					<div class="crm-activity-visit-facesearch-main-user-info-name-item"><?=htmlspecialcharsbx($arResult["VK_PROFILES"][0]['name'])?></div>
					<div class="crm-activity-visit-facesearch-main-user-date">
						<span class="crm-activity-visit-facesearch-main-user-date-item"><?=htmlspecialcharsbx($arResult["VK_PROFILES"][0]['personal'])?></span>
					</div>
					<div class="crm-activity-visit-facesearch-main-user-social-link">
						<a href="http://vk.com/<?=htmlspecialcharsbx($arResult["VK_PROFILES"][0]['id'])?>" class="crm-activity-visit-facesearch-main-user-social-link-item" target="_blank">
							vk.com/<?= htmlspecialcharsbx($arResult["VK_PROFILES"][0]['id'])?>
						</a>
					</div>
				</div>
				<div class="crm-activity-visit-facesearch-main-user-info-control">
					<div class="webform-small-button webform-small-button-blue crm-activity-visit-facesearch-main-user-info-control-button"
						 data-role="faceid-social-button-select"
						 data-profile="<?=htmlspecialcharsbx($arResult["VK_PROFILES"][0]['id'])?>"
					>
						<?=GetMessage('CRM_ACTIVITY_VISIT_SOCIAL_SELECT')?>
					</div>
				</div>
			</div>
		</div>
		<div class="crm-activity-visit-facesearch-profile-search-found-more">
			<div class="crm-activity-visit-facesearch-profile-search-found-more-header">
				<div class="crm-activity-visit-facesearch-profile-search-found-more-item"><?=GetMessage('CRM_ACTIVITY_VISIT_SOCIAL_FOUND_MORE')?>:</div>
				<div class="crm-activity-visit-facesearch-profile-search-found-more-count"><?=GetMessage('CRM_ACTIVITY_VISIT_SOCIAL_FOUND_TOTAL', array('#COUNT#' => count($arResult["VK_PROFILES"]) - 1))?></div>
			</div>
			<div class="crm-activity-visit-facesearch-profile-search-found-container">
				<? for($i = 1; $i < count($arResult["VK_PROFILES"]); $i++): ?>
					<div class="crm-activity-visit-facesearch-main-user-item crm-activity-visit-facesearch-profile-search-found crm-activity-visit-facesearch-photo">
						<div class="crm-activity-visit-facesearch-main-user-photo">
							<div class="crm-activity-visit-facesearch-main-user-photo-item" style="background-image: url('<?= htmlspecialcharsbx($arResult["VK_PROFILES"][$i]['photo'])?>')"></div>
							<div class="crm-activity-visit-facesearch-main-user-photo-count">
								<span class="crm-activity-visit-facesearch-main-user-photo-count-item"><?= intval($arResult["VK_PROFILES"][$i]['confidence']*100)?>%</span>
							</div>
						</div>
						<div class="crm-activity-visit-facesearch-main-user-info">
							<div class="crm-activity-visit-facesearch-main-user-info-name">
								<div class="crm-activity-visit-facesearch-main-user-info-name-item"><?=htmlspecialcharsbx($arResult["VK_PROFILES"][$i]['name'])?></div>
								<div class="crm-activity-visit-facesearch-main-user-date">
									<span class="crm-activity-visit-facesearch-main-user-date-item"><?=htmlspecialcharsbx($arResult["VK_PROFILES"][$i]['personal'])?></span>
								</div>
								<div class="crm-activity-visit-facesearch-main-user-social-link">
									<a href="http://vk.com/<?=htmlspecialcharsbx($arResult["VK_PROFILES"][$i]['id'])?>" class="crm-activity-visit-facesearch-main-user-social-link-item" target="_blank">
										vk.com/<?= htmlspecialcharsbx($arResult["VK_PROFILES"][$i]['id'])?>
									</a>
								</div>
							</div>
							<div class="crm-activity-visit-facesearch-main-user-info-control">
								<div class="webform-small-button webform-small-button-blue crm-activity-visit-facesearch-main-user-info-control-button crm-activity-visit-facesearch-search-button"
									 data-role="faceid-social-button-select"
									 data-profile="<?=htmlspecialcharsbx($arResult["VK_PROFILES"][$i]['id'])?>"
								>
									<?=GetMessage('CRM_ACTIVITY_VISIT_SOCIAL_SELECT')?>
								</div>
							</div>
						</div>
					</div>
				<? endfor ?>
			</div>
		</div>

	<? else: ?>
		<? ShowError($arResult['ERROR']) ?>
	<? endif ?>
</div>
