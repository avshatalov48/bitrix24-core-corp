<?php
use Bitrix\Main\Localization\Loc;
$x = 1;
if($x)
{
	echo $arResult['FINAL_RESULT'];
}
$APPLICATION->IncludeComponent(
	'bitrix:crm.widget.custom.saletarget',
	'',
	array(),
	$component,
	array('HIDE_ICONS' => 'Y')
);


?>

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



