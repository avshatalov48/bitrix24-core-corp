<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_CREATE_ADS_NAME'),
	'DESCRIPTION' => GetMessage('CRM_CREATE_ADS_DESC'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'CrmCreateAdsActivityYa',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'FILTER' => array(
		'INCLUDE' => array(
			array('crm', 'CCrmDocumentDeal'),
			array('crm', 'CCrmDocumentLead')
		),
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'ads'
	),
	'EXCLUDED' => true,
);

if (CModule::IncludeModule('bitrix24'))
{
	$zone = \CBitrix24::getPortalZone();
	if ($zone === 'ua')
	{
		$arActivityDescription['FILTER'] = ['EXCLUDE' => [\CBPHelper::DISTR_B24]];
	}
}