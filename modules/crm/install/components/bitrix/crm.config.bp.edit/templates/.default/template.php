<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

$APPLICATION->IncludeComponent(
	'bitrix:bizproc.workflow.edit',
	'',
	Array(
		'MODULE_ID' => 'crm',
		'ENTITY' => $arResult['ENTITY_TYPE'],
		'DOCUMENT_TYPE' => $arResult['DOCUMENT_TYPE'],
		'ID' => $arResult['BP_ID'],
		'EDIT_PAGE_TEMPLATE' => CComponentEngine::MakePathFromTemplate($arResult['~BP_EDIT_URL'],
			array(
				'bp_id' => '#ID#'
			)
		),
		'LIST_PAGE_URL' => $arResult['~BP_LIST_URL'],
		'SHOW_TOOLBAR' => 'Y',
		'SET_TITLE' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>