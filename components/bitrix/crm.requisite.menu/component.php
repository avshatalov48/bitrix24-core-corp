<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$entityTypeId = isset($arParams['ENTITY_TYPE_ID']) ? intval($arParams['ENTITY_TYPE_ID']) : 0;
$entityId = isset($arParams['ENTITY_ID']) ? intval($arParams['ENTITY_ID']) : 0;

$arParams['PATH_TO_REQUISITE_LIST'] = CrmCheckPath('PATH_TO_REQUISITE_LIST', $arParams['PATH_TO_REQUISITE_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_REQUISITE_EDIT'] = CrmCheckPath('PATH_TO_REQUISITE_EDIT', $arParams['PATH_TO_REQUISITE_EDIT'], $APPLICATION->GetCurPage().'?id=#id#&edit');

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_requisite_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();

$bRead = true;
$bAdd = true;
$bWrite = true;
$bDelete = ($arParams['TYPE'] === 'list' ? false : true);

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('REQUISITE_ADD'),
			'TITLE' => GetMessage('REQUISITE_ADD_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_REQUISITE_EDIT'], array('id' => 0)),
				array('etype' => $entityTypeId, 'eid' => $entityId)
			),
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
	}
}
else
{
	if ($arParams['TYPE'] == 'edit' && $bAdd && !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('REQUISITE_COPY'),
			'TITLE' => GetMessage('REQUISITE_COPY_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_REQUISITE_EDIT'],
					array('id' => $arParams['ELEMENT_ID'])
				),
				array('copy' => 1)
			),
			'ICON' => 'btn-copy'
		);
	}

	$qty = count($arResult['BUTTONS']);

	if ($qty > 0 && $arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))
		$arResult['BUTTONS'][] = array('SEPARATOR' => true);
	elseif ($qty > 2)
		$arResult['BUTTONS'][] = array('NEWBAR' => true);

	if ($arParams['TYPE'] == 'edit' && $bDelete && !empty($arParams['ELEMENT_ID']))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('REQUISITE_DELETE'),
			'TITLE' => GetMessage('REQUISITE_DELETE_TITLE'),
			'LINK' => "javascript:requisite_delete('".GetMessage('REQUISITE_DELETE_DLG_TITLE')."', '".
				GetMessage('REQUISITE_DELETE_DLG_MESSAGE')."', '".GetMessage('REQUISITE_DELETE_DLG_BTNTITLE')."', '".
				CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_REQUISITE_EDIT'],
						array('id' => $arParams['ELEMENT_ID'])
					),
					array(
						'delete' => '', 'sessid' => bitrix_sessid(),
						'back_url' => isset($arParams['BACK_URL']) ? urlencode(strval($arParams['BACK_URL'])) : ''
					)
				)."')",
			'ICON' => 'btn-delete'
		);
	}

	if ($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('REQUISITE_ADD'),
			'TITLE' => GetMessage('REQUISITE_ADD_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_REQUISITE_EDIT'], array('id' => 0)),
				array('etype' => $entityTypeId, 'eid' => $entityId)
			),
			'ICON' => 'btn-new'
		);
	}
}

$this->IncludeComponentTemplate();
