<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
{
	die();
}

use Bitrix\Main\UI\Extension;
use Bitrix\Bitrix24\Feature;

Extension::load(['ui.icons', 'ui.fonts.opensans']);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . ' no-background no-all-paddings pagetitle-toolbar-field-view ');
$APPLICATION->IncludeComponent('bitrix:ui.info.helper', '');

$sliderCode = Feature::isFeatureEnabled('crm_analytics_limit_reached')
	? 'limit_crm_analytics_max_number'
	: 'limit_crm_analytics_1000_number';

$isBitrix24Template = SITE_TEMPLATE_ID === 'bitrix24';
if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}
?>

<? if (!$isBitrix24Template): ?>
<div class="tasks-interface-filter-container">
	<? endif ?>
	<div class="pagetitle-container<? if (!$isBitrix24Template): ?> pagetitle-container-light<? endif ?> pagetitle-flexible-space">
		<div class="pagetitle-container pagetitle-align-right-container"></div>
	</div>
	<? if (!$isBitrix24Template): ?>
</div>
<? endif ?>

<?php
if ($isBitrix24Template)
{
	$this->EndViewTarget();
}
?>

<div class="crm-analytics-dashboard-mask">
	<div class="crm-analytics-dashboard-mask-img"></div>
	<div class="crm-analytics-dashboard-mask-content">
		<div class="crm-analytics-dashboard-mask-blur-box"></div>
		<div class="crm-analytics-dashboard-mask-text"></div>
	</div>
</div>
<script>
	BX.UI.InfoHelper.show('<?=$sliderCode?>');
</script>
