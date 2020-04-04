<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCUMENT_NAME'),
	'DESCRIPTION' => GetMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCUMENT_DESC'),
	'TYPE' => ['activity', 'robot_activity'],
	'CLASS' => 'CrmGenerateEntityDocumentActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'FILTER' => array(
		'INCLUDE' => array(
			array('crm'),
		),
	),
	'RETURN' => array(
		'DocumentId' => array(
			'NAME' => GetMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCUMENT_ID'),
			'TYPE' => 'int',
		),
		'DocumentUrl' => array(
			'NAME' => GetMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCUMENT_URL'),
			'TYPE' => 'string',
		),
		'DocumentPdf' => array(
			'NAME' => GetMessage('CRM_ACTIVITY_GENERATE_ENTITY_PDF_FILE'),
			'TYPE' => 'file',
		),
		'DocumentDocx' => array(
			'NAME' => GetMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCX_FILE'),
			'TYPE' => 'file',
		),
		'DocumentNumber' => array(
			'NAME' => GetMessage('CRM_ACTIVITY_GENERATE_ENTITY_DOCUMENT_NUMBER'),
			'TYPE' => 'string',
		),
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'employee'
	),
);
if(\Bitrix\Main\Loader::includeModule('crm') && !\Bitrix\Crm\Integration\DocumentGeneratorManager::getInstance()->isEnabled())
{
	$arActivityDescription['EXCLUDED'] = true;
}
