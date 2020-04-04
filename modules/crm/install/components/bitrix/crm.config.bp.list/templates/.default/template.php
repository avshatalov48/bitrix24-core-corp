<?
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.toolbar',
	'',
	array(
		'BUTTONS'=>array(
			array(
				'TEXT' => GetMessage('CRM_BP_TOOLBAR_TYPES'),
				'TITLE' => GetMessage('CRM_BP_TOOLBAR_TYPES_TITLE'),
				'LINK' => $arResult['ENTITY_LIST_URL'],
				'ICON' => 'btn-view-elements',
			),
			array(
				'SEPARATOR' => 'Y',
			),
			array(
				'TEXT' => GetMessage('CRM_BP_TOOLBAR_ADD'),
				'TITLE' => GetMessage('CRM_BP_TOOLBAR_ADD_TITLE'),
				'LINK' => CComponentEngine::MakePathFromTemplate($arResult['BP_EDIT_URL'],
					array(
						'bp_id' => '0'
					)
				),
				'ICON' => 'btn-add-field',
			)
		)
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);

$APPLICATION->IncludeComponent(
	'bitrix:bizproc.workflow.list',
	'',
	Array(
		'MODULE_ID' => 'crm',
		'ENTITY' => $arResult['ENTITY_TYPE'],
		'DOCUMENT_ID' => $arResult['DOCUMENT_TYPE'],
		'EDIT_URL' => CComponentEngine::MakePathFromTemplate($arResult['~BP_EDIT_URL'],
			array(
				'bp_id' => '#ID#'
			)
		),
		'SET_TITLE'	=>	'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>