<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

\Bitrix\Main\UI\Extension::load("ui.icons");
\Bitrix\Main\UI\Extension::load("loader");
$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . ' no-background no-all-paddings pagetitle-toolbar-field-view ');
$isBitrix24Template = SITE_TEMPLATE_ID === "bitrix24";
if ($isBitrix24Template)
{
	$this->SetViewTarget('inside_pagetitle');
}

use Bitrix\Crm\Integration\Report\Limit;
use Bitrix\Main\Localization\Loc; ?>

<? if (!$isBitrix24Template): ?>
<div class="tasks-interface-filter-container">
	<? endif ?>

	<div class="pagetitle-container<? if (!$isBitrix24Template): ?> pagetitle-container-light<? endif ?> pagetitle-flexible-space">
		<div class="pagetitle-container pagetitle-align-right-container">
				dsadasd
		</div>
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
		<div class="crm-analytics-dashboard-mask-text"><?=Loc::getMessage('CRM_ANALYTICS_LIMIT_MASK_WITHOUT_REPEATED_TEXT')?></div>
	</div>
</div>

