<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
/** CMain $APPLICATION */
global $APPLICATION;
define('CRM_REPORT_UPDATE_14_5_2_MESSAGE', 'Y');
$APPLICATION->ShowViewContent('REPORT_UPDATE_14_5_2_MESSAGE');
?>
<? $APPLICATION->IncludeComponent(
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
);?>
<?$APPLICATION->IncludeComponent(
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
);?>
<? $APPLICATION->IncludeComponent(
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
);?>
<? $APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'REPORT_TITLE' => GetMessage('CRM_REPORT_LIST_INVOICE'),
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmInvoiceReportHelper'
	),
	false
);?>
<? $APPLICATION->IncludeComponent(
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
);?>