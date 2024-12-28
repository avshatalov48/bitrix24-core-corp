<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

$companies = \Bitrix\Main\Web\Json::encode($arResult['COMPANIES'] ?? []);

\Bitrix\Main\UI\Extension::load([
	'humanresources.hcmlink.companies-manager',
	'ui.design-tokens',
	'ui.counter',
	'ui.hint',
]);
?>

<div class="hr-hcmlink-company-list-container"></div>

<script>
	const container = document.querySelector('.hr-hcmlink-company-list-container');
	(new BX.HumanResources.HcmLink.CompaniesManager({
		companies: <?= $companies ?>,
	})).renderTo(container);
</script>