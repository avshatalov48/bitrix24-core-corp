<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

return array(
	'js'  => array(
		'/bitrix/js/crm/report/salestarget/contenttype.js',
	),
	'css'  => array(
		'/bitrix/js/crm/report/salestarget/contenttype.css',
	),
	'rel' => array('report', 'report.js.dashboard', 'report_visual_constructor'),
	'bundle_js' => 'crmreportsalestarget',
	'bundle_css' => 'crmreportsalestarget'
);
