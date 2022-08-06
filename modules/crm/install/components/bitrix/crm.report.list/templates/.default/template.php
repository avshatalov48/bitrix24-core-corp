<?php

use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?php
/** CMain $APPLICATION */
global $APPLICATION;
define('CRM_REPORT_UPDATE_14_5_2_MESSAGE', 'Y');
$APPLICATION->ShowViewContent('REPORT_UPDATE_14_5_2_MESSAGE');
?>
<?php

$isReportsRestricted = false;
if (
	Loader::includeModule('bitrix24')
	&& !Bitrix\Bitrix24\Feature::isFeatureEnabled('report')
)
{
	$isReportsRestricted = true;
}


$APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'REPORT_TITLE' => GetMessage('CRM_REPORT_LIST_DEAL'),
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmReportHelper'
	),
	false
);

// This is a temporary code section.
// The old funnel is an unsupported report. It should be removed in future releases.
$showOldFunnel = function() {
	if (\COption::getOptionString('intranet', 'new_portal_structure', 'N') === 'Y')
	{
		return false;
	}

	if (Loader::includeModule('bitrix24'))
	{
		$targetTime = strtotime('2022-06-24');
		$createTime = \CBitrix24::getCreateTime();
		if ($createTime && $createTime > $targetTime)
		{
			return false;
		}
	}

	$isAdmin = CCrmPerms::isAdmin();
	$userPermissions = CCrmPerms::getCurrentUserPermissions();

	return $isAdmin || CCrmDeal::checkReadPermission(0, $userPermissions);
};

if ($showOldFunnel()):
?>
<script>
(function() {
	const table = document.querySelector('#reports_list_table_crm .reports-list-table:last-child > tbody');
	if (!table)
	{
		return;
	}

	const lastRow = table.querySelector('tr:last-child');
	if (!lastRow)
	{
		return;
	}

	const newRow = lastRow.cloneNode(true);
	newRow.querySelector('.reports-first-column').innerHTML = `
		<a href="<?=SITE_DIR?>crm/reports/" class="reports-title-link"><?=GetMessage('CRM_REPORT_LIST_OLD_FUNNEL')?></a>
	`;
	newRow.querySelector('.reports-menu-column').innerHTML = '';
	newRow.querySelector('.reports-second-column').innerHTML = '';
	newRow.querySelector('.reports-date-column').innerHTML = '';

	table.appendChild(newRow);

})();
</script>
<?
endif;

if ($isReportsRestricted)
{
	return;
}

$APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'REPORT_TITLE' => GetMessage('CRM_REPORT_LIST_PRODUCT'),
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmProductReportHelper'
	),
	false
);
$APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'REPORT_TITLE' => GetMessage('CRM_REPORT_LIST_LEAD'),
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmLeadReportHelper'
	),
	false
);
if (\Bitrix\Crm\Settings\InvoiceSettings::getCurrent()->isOldInvoicesEnabled())
{
	$APPLICATION->IncludeComponent(
		'bitrix:report.list',
		'',
		array(
			'REPORT_TITLE' => \Bitrix\Crm\Service\Container::getInstance()->getLocalization()->appendOldVersionSuffix(GetMessage('CRM_REPORT_LIST_INVOICE')),
			'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
			'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
			'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
			'REPORT_HELPER_CLASS' => 'CCrmInvoiceReportHelper'
		),
		false
	);
}
$APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'REPORT_TITLE' => GetMessage('CRM_REPORT_LIST_ACTIVITY'),
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmActivityReportHelper'
	),
	false
);
?>