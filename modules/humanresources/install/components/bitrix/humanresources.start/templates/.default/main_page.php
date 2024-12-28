<?php

global $APPLICATION;
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$APPLICATION->SetPageProperty('BodyClass', 'ui-page-slider-wrapper-hr');
$APPLICATION->IncludeComponent(
	'bitrix:ui.toolbar',
	'',
);

\Bitrix\Main\UI\Extension::load(['humanresources.company-structure.org-chart']);
?>

<div class="humanresources-company-structure" id="humanresources-company-structure"></div>
<style>
	#humanresources-company-structure {
		height: 100%;
	}
</style>
<script>
	(() => {
		const { App } = BX.Humanresources.CompanyStructure;
		App.mount('humanresources-company-structure');
	})();
</script>
