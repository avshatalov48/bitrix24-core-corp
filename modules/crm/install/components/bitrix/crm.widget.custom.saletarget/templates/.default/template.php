<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
use Bitrix\Main\Localization\Loc;

$messages = Loc::loadLanguageFile(__FILE__);

\Bitrix\Main\Loader::includeModule('socialnetwork');
CJSCore::Init('socnetlogdest');

$this->addExternalJs($this->getFolder() . '/user_selector.js');
$this->addExternalJs($this->getFolder() . '/config_popup.js');
?>
<script type="text/html" data-template="widget-saletarget-main" data-categories="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($arResult['DEAL_CATEGORIES']))?>">
	<div class="crm-start-row crm-start-row-margin-bottom">
		<div class="crm-start-target">
			<span class="crm-start-target-title"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_GENERAL_TARGET") ?></span>
			<span class="crm-start-target-total" data-role="total-target"></span>
		</div>
		<div class="crm-start-target crm-start-target-right">
			<span class="crm-start-target-title"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_TARGET_PERIOD") ?></span>
			<span class="crm-start-target-month">
				<span class="crm-start-target-month-swipe crm-start-target-month-swipe-left" data-role="previous-period"></span>
				<span class="crm-start-target-month-title" data-role="current-period"></span>
				<span class="crm-start-target-month-swipe crm-start-target-month-swipe-right" data-role="next-period"></span>
			</span>
		</div>
	</div>
	<div data-include="widget-saletarget-company" class="crm-start-widget-saletarget-company" data-if="view-type=COMPANY"></div>
	<div data-include="widget-saletarget-category" data-if="view-type=CATEGORY"></div>
	<div data-include="widget-saletarget-user" data-if="view-type=USER"></div>
	<div class="crm-start-row">
		<div class="crm-start-row-result">
			<div class="crm-start-row-result-item">
				<div class="crm-start-row-result-item-title"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_COMPLETED") ?></div>
				<div class="crm-start-row-result-item-total" data-role="total-complete"></div>
			</div>
			<div class="crm-start-row-result-item">
				<div class="crm-start-row-result-item-title"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_REMAINED") ?></div>
				<div class="crm-start-row-result-item-total" data-role="total-remaining"></div>
			</div>
			<div class="crm-start-row-result-item">
				<div class="crm-start-row-result-item-title"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_COMPLETING") ?></div>
				<div class="crm-start-row-result-item-total">
					<span class="crm-start-row-result-item-total-sum" data-role="total-progress-percent"></span>
					<span>%</span>
				</div>
			</div>
		</div>
	</div>
</script>

<script type="text/html" data-template="widget-saletarget-company">
	<div class="crm-start-row">
		<div class="crm-start-graph-month-progress" data-role="progress" data-more-class="crm-start-graph-progress-more">
			<div class="crm-start-graph-month-progress-line" style="width: 0" data-role="progress-line"></div>
			<div class="crm-start-graph-progress-total" data-role="progress-total"></div>
			<div class="crm-start-graph-month-progress-point" data-left-class="crm-start-graph-month-progress-point-left" data-right-class="crm-start-graph-month-progress-point-right" style="left: 0;" data-role="progress-point">
				<div class="crm-start-graph-month-progress-baloon">
					<span class="crm-start-graph-month-progress-baloon-day"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_TODAY") ?></span>
					<span class="crm-start-graph-month-progress-baloon-date" data-role="today"></span>
				</div>
			</div>
		</div>
		<div class="crm-start-graph-month-scale crm-start-graph-month-scale-28">
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item"></div>
			<div class="crm-start-graph-month-scale-item-first" data-role="first-day"></div>
			<div class="crm-start-graph-month-scale-item-middle"></div>
			<div class="crm-start-graph-month-scale-item-last" data-role="last-day"></div>
		</div>
	</div>
</script>

<script type="text/html" data-template="widget-saletarget-category">
	<div data-loop="categories" class="crm-start-row">
		<div class="crm-start-graph-row">
			<div class="crm-start-graph-head crm-start-graph-head-dropdown" data-role="category-row" data-open-class="crm-start-graph-head-open">
				<div class="crm-start-graph-title">
					<span class="crm-start-graph-title-link" data-role="category-name"></span>
				</div>
				<div class="crm-start-graph-wrapper">
					<div class="crm-start-graph-progress" data-role="category-progress" data-more-class="crm-start-graph-progress-more">
						<div class="crm-start-graph-progress-line" style="width: 0" data-role="category-progress-line"></div>
						<div class="crm-start-graph-progress-total" data-role="category-progress-line-value"></div>
					</div>
					<div class="crm-start-graph-total-sum" data-role="category-target"></div>
					<div class="crm-start-graph-wrapper-line" style="left: 0" data-role="progress-point"></div>
				</div>
			</div>
			<div class="crm-start-graph-content" data-role="category-target-details" data-open-class="crm-start-graph-content-open">
				<div class="crm-start-graph-user-plan">
					<div class="crm-start-graph-user-plan-item">
						<span class="crm-start-graph-user-plan-title"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_COMPLETED") ?></span>
						<div class="crm-start-graph-user-plan-total" data-role="category-target-current"></div>
					</div>
					<div class="crm-start-graph-user-plan-item">
						<span class="crm-start-graph-user-plan-title"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_REMAINED") ?></span>
						<div class="crm-start-graph-user-plan-total" data-role="category-target-remaining"></div>
					</div>
					<div class="crm-start-graph-user-plan-item">
						<span class="crm-start-graph-user-plan-title" ><?= Loc::getMessage("CRM_WIDGET_SALETARGET_COMPLETING") ?></span>
						<div class="crm-start-graph-user-plan-total">
							<span class="crm-start-graph-user-plan-sum" data-role="category-target-effective"></span>
							<span class="crm-start-graph-user-plan-symbol">%</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="crm-start-saletarget-general-progress" data-include="widget-saletarget-general-progress"></div>
</script>

<script type="text/html" data-template="widget-saletarget-user">
	<div data-loop="users" class="crm-start-row">
		<div class="crm-start-graph-row">
			<div class="crm-start-graph-head crm-start-graph-head-dropdown" data-role="user-row" data-open-class="crm-start-graph-head-open">
				<div class="crm-start-graph-title">
					<div class="crm-start-graph-title-avatar" data-role="user-photo"></div>
					<div class="crm-start-graph-title-user">
						<span class="crm-start-graph-title-link" data-role="user-name"></span>
						<span class="crm-start-graph-title-position" data-role="user-title"></span>
					</div>
				</div>
				<div class="crm-start-graph-wrapper">
					<div class="crm-start-graph-progress" data-role="user-progress" data-more-class="crm-start-graph-progress-more">
						<div class="crm-start-graph-progress-line" style="width: 0" data-role="user-progress-line"></div>
						<div class="crm-start-graph-progress-total" data-role="user-progress-line-value"></div>
					</div>
					<div class="crm-start-graph-total-sum" data-role="user-target"></div>
					<div class="crm-start-graph-wrapper-line" style="left: 0" data-role="progress-point"></div>
				</div>
			</div>
			<div class="crm-start-graph-content" data-role="user-target-details" data-open-class="crm-start-graph-content-open">
				<div class="crm-start-graph-user-plan">
					<div class="crm-start-graph-user-plan-item">
						<span class="crm-start-graph-user-plan-title"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_COMPLETED") ?></span>
						<div class="crm-start-graph-user-plan-total" data-role="user-target-current"></div>
					</div>
					<div class="crm-start-graph-user-plan-item">
						<span class="crm-start-graph-user-plan-title"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_REMAINED") ?></span>
						<div class="crm-start-graph-user-plan-total" data-role="user-target-remaining"></div>
					</div>
					<div class="crm-start-graph-user-plan-item">
						<span class="crm-start-graph-user-plan-title" ><?= Loc::getMessage("CRM_WIDGET_SALETARGET_COMPLETING") ?></span>
						<div class="crm-start-graph-user-plan-total">
							<span class="crm-start-graph-user-plan-sum" data-role="user-target-effective"></span>
							<span class="crm-start-graph-user-plan-symbol">%</span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="crm-start-saletarget-general-progress" data-include="widget-saletarget-general-progress"></div>
</script>

<script type="text/html" data-template="widget-saletarget-general-progress">
	<div class="crm-start-row crm-start-row-flex">
		<div class="crm-start-graph-title" data-if="view-type=CATEGORY">
			<span class="crm-start-graph-title-text"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_GENERAL_CATEGORY_PROGRESS") ?></span>
		</div>
		<div class="crm-start-graph-title" data-if="view-type=USER">
			<span class="crm-start-graph-title-text"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_GENERAL_PROGRESS") ?></span>
		</div>
		<div class="crm-start-graph-wrapper">
			<div class="crm-start-graph-month-progress" data-role="progress" data-more-class="crm-start-graph-progress-more">
				<div class="crm-start-graph-month-progress-line" style="width: 0" data-role="progress-line"></div>
				<div class="crm-start-graph-progress-total" data-role="progress-total"></div>
				<div class="crm-start-graph-month-progress-point" data-left-class="crm-start-graph-month-progress-point-left" data-right-class="crm-start-graph-month-progress-point-right" style="left: 0;" data-role="progress-point">
					<div class="crm-start-graph-month-progress-baloon">
						<span class="crm-start-graph-month-progress-baloon-day"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_TODAY") ?></span>
						<span class="crm-start-graph-month-progress-baloon-date" data-role="today"></span>
					</div>
				</div>
			</div>
			<div class="crm-start-graph-month-scale crm-start-graph-month-scale-28">
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item"></div>
				<div class="crm-start-graph-month-scale-item-first" data-role="first-day"></div>
				<div class="crm-start-graph-month-scale-item-middle"></div>
				<div class="crm-start-graph-month-scale-item-last" data-role="last-day"></div>
			</div>
		</div>
	</div>
</script>

<script type="text/html" data-template="widget-saletarget-config">
	<form data-role="form">
		<div class="crm-start-popup-row crm-start-popup-row-border">
			<div class="crm-start-popup-row-title">
				<span class="crm-start-popup-text"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_CONFIG_PERIOD_TYPE") ?>:</span>
			</div>
			<div class="crm-start-popup-row-content">
				<table>
					<tr>
						<td><span class="crm-start-popup-link" data-role="period-type"></span></td>
						<td>
							<div class="crm-start-dropdown" data-role="period-month">
								<span class="crm-start-dropdown-text" data-role="period-month-value"></span>
							</div>
							<div class="crm-start-dropdown" data-role="period-quarter">
								<span class="crm-start-dropdown-text" data-role="period-quarter-value"></span>
							</div>
							<div class="crm-start-dropdown" data-role="period-half">
								<span class="crm-start-dropdown-text" data-role="period-half-value"></span>
							</div>
						</td>
						<td>
							<div class="crm-start-dropdown" data-role="period-year">
								<span class="crm-start-dropdown-text" data-role="period-year-value"></span>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<div class="crm-start-popup-row crm-start-popup-row-border">
			<div class="crm-start-popup-row-title">
				<span class="crm-start-popup-text"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_CONFIG_TARGET_TYPE") ?>:</span>
			</div>
			<div class="crm-start-popup-row-content">
				<div class="crm-start-dropdown" data-role="target-type">
					<span class="crm-start-dropdown-text" data-role="target-type-value"></span>
				</div>
			</div>
		</div>
		<div class="crm-start-popup-row crm-start-popup-row-border crm-start-popup-row-margin-bottom">
			<div class="crm-start-popup-row-title">
				<span class="crm-start-popup-text"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_CONFIG_VIEW_TYPE") ?>:</span>
			</div>
			<div class="crm-start-popup-row-content">
				<div class="crm-start-dropdown" data-role="view-type">
					<span class="crm-start-dropdown-text" data-role="view-type-value"></span>
				</div>
			</div>
		</div>
		<div data-role="view-type-content"></div>
		<div class="crm-start-popup-row crm-start-popup-row-block">
			<a target="_blank" href="<?=htmlspecialcharsbx($arResult['PATH_TO_DEAL_CATEGORY_LIST'])?>" class="crm-start-popup-config" data-role="categories-link" style="display: none"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_CONFIG_CATEGORIES_LINK") ?></a>
			<span class="crm-start-popup-copy" data-role="copy-configuration"></span>
		</div>
	</form>
</script>

<script type="text/html" data-template="widget-saletarget-config-user">
	<div data-loop="users" class="crm-start-popup-users">
		<div class="crm-start-popup-row" data-new-class="crm-start-popup-row-new" data-remove-class="crm-start-popup-row-remove">
			<div class="crm-start-popup-personal">
				<div class="crm-start-popup-personal-title">
					<div class="crm-start-popup-personal-title-wrapper">
						<div class="crm-start-popup-personal-title-avatar" data-role="user-photo"></div>
						<div class="crm-start-popup-personal-title-info">
							<span class="crm-start-popup-personal-title-name" data-role="user-name"></span>
							<span class="crm-start-popup-personal-title-position" data-role="user-title"></span>
						</div>
					</div>
				</div>
				<div class="crm-start-popup-personal-content">
					<input type="text" class="crm-start-popup-input" data-role="user-target" placeholder="<?= Loc::getMessage("CRM_WIDGET_SALETARGET_CONFIG_TARGET_EXAMPLE") ?>">
				</div>
			</div>
			<div class="crm-start-popup-personal-remove" data-role="user-remove"></div>
		</div>
	</div>
	<div class="crm-start-popup-row crm-start-popup-row-border">
		<span class="crm-start-popup-link crm-start-popup-link-plus" data-role="user-add"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_CONFIG_VIEW_TYPE_USER_ADD") ?></span>
	</div>
</script>

<script type="text/html" data-template="widget-saletarget-config-category">
	<div data-loop="categories" class="crm-start-popup-row-border">
		<div class="crm-start-popup-row">
			<div class="crm-start-popup-personal">
				<div class="crm-start-popup-personal-title">
					<span class="crm-start-popup-personal-title-name" data-role="category-name"></span>
				</div>
				<div class="crm-start-popup-personal-content">
					<input type="text" class="crm-start-popup-input" data-role="category-target" placeholder="<?= Loc::getMessage("CRM_WIDGET_SALETARGET_CONFIG_TARGET_EXAMPLE") ?>">
				</div>
			</div>
		</div>
	</div>
</script>
<script type="text/html" data-template="widget-saletarget-config-company">
	<div class="crm-start-popup-row crm-start-popup-row-border">
		<div class="crm-start-popup-row-title">
			<span class="crm-start-popup-text"><?= Loc::getMessage("CRM_WIDGET_SALETARGET_CONFIG_TARGET_LABEL") ?>:</span>
		</div>
		<div class="crm-start-popup-row-content">
			<input type="text" class="crm-start-popup-input" data-role="company-target" placeholder="<?= Loc::getMessage("CRM_WIDGET_SALETARGET_CONFIG_TARGET_EXAMPLE") ?>">
		</div>
	</div>
</script>

<script>
	BX.ready(function()
	{
		BX.message(<?=\Bitrix\Main\Web\Json::encode($messages)?>);
		var userSelector = BX.getClass('BX.Crm.Widget.Custom.SaleTarget.UserSelector');
		if (userSelector)
		{
			userSelector.data = <?=\Bitrix\Main\Web\Json::encode($arResult['USER_SELECTOR_DATA'])?>;
		}
	});
</script>