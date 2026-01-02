<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load([
	'ui.icons',
	'loader',
]);

$bodyClass = $APPLICATION->GetPageProperty('BodyClass');
$APPLICATION->SetPageProperty('BodyClass', ($bodyClass ? $bodyClass . ' ' : '') . ' no-background no-all-paddings pagetitle-toolbar-field-view ');

?>

<div class="crm-analytics-dashboard-mask">
	<div class="crm-analytics-dashboard-mask-img"></div>
	<div class="crm-analytics-dashboard-mask-content">
		<div class="crm-analytics-dashboard-mask-blur-box"></div>
		<div class="crm-analytics-dashboard-mask-text"><?=Loc::getMessage('CRM_ANALYTICS_LIMIT_MASK_WITHOUT_REPEATED_TEXT')?></div>
	</div>
</div>
