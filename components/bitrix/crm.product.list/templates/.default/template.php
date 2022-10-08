<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @var \CBitrixComponent $component */
/** @global CMain $APPLICATION */
global $APPLICATION;

$extMgrId = $arParams['PRODUCT_SECTION_MANAGER_ID'] ?? '';
$mgrId = isset($extMgrId[0]) ? $extMgrId : 'CrmProductSectionManager';

CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/activity.js');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/interface_grid.js');

\Bitrix\Main\UI\Extension::load([
	'ui.design-tokens',
	'ui.fonts.opensans',
]);

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if (SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

?>
<script type="text/javascript">
function crm_product_delete_grid(title, message, btnTitle, path)
{
	var d =
		new BX.CDialog(
		{
			title: title,
			head: '',
			content: message,
			resizable: false,
			draggable: true,
			height: 70,
			width: 300
		}
	);

	var _BTN = [
		{
			title: btnTitle,
			id: 'crmOk',
			'action': function ()
			{
				window.location.href = path;
				BX.WindowManager.Get().Close();
			}
		},
		BX.CDialog.btnCancel
	];
	d.ClearButtons();
	d.SetButtons(_BTN);
	d.Show();
}
</script>
<?php
$bVatMode = $arResult['VAT_MODE'] === true;
$arResult['GRID_DATA'] = $arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
{
	$arColumns[$arHead['id']] = false;
}
$arSections = array();
if (is_array($arResult['SECTIONS']))
	$arSections = $arResult['SECTIONS'];
foreach($arSections as $sKey =>  $arSection)
{
	$arActions = array();
	if($arResult['CAN_EDIT'])
	{
		$arActions[] = array(
			'ICONCLASS' => 'edit',
			'TEXT' => GetMessage('CRM_PRODUCT_SECTION_ACTION_RENAME1'),
			'ONCLICK' => 'BX.CrmProductSectionManager.items[\''.$mgrId.'\'].renameSection('.$arSection['ID'].', \''.CUtil::JSEscape($arSection['~NAME']).'\');',
			'DEFAULT' => true,
		);
	}

	if($arResult['CAN_DELETE'])
	{
		$arActions[] = array(
			'ICONCLASS' => 'delete',
			'TEXT' => GetMessage('CRM_PRODUCT_SECTION_ACTION_DELETE1'),
			'ONCLICK' => 'bxGrid_'.$arResult['GRID_ID'].'.DeleteItem(\''.$arSection['TYPE'].$arSection['ID'].'\', \''.GetMessage('CRM_PRODUCT_SECTION_ACTION_DELETE_PROPMT').'\')',
		);
	}

	$gridDataRecord = array(
		'id' => $arSection['TYPE'].$arSection['ID'],
		'actions' => $arActions,
		'data' => $arSection,
		'editable' => $arSection['EDIT'] ? true : $arColumns,
		'columns' => array(
			/*'NAME' => '<a target="_self" href="'.$arResult['SECTION_LIST'][$arSection['ID']]['LIST_URL'].'">'.$arSection['NAME'].'</a>'*/
			'NAME' => '<table class="bx-crm-object-name">'.PHP_EOL.
				"\t".'<tbody>'.PHP_EOL.
				"\t".'<tr>'.
				"\t\t".'<td style="width: 45px;">'.
				"\t\t\t".'<div class="bx-crm-folder-icon-container-small bx-crm-folder-icon"></div>'.
				"\t\t".'</td>'.
				"\t\t".'<td>'.
				"\t\t\t".'<a target="_self" href="'.$arResult['SECTION_LIST'][$arSection['ID']]['LIST_URL'].'">'.$arSection['NAME'].'</a>'.
				"\t\t".'</td>'.
				"\t".'</tr>'.PHP_EOL.
				"\t".'</tbody>'.PHP_EOL.
				'</table>'.PHP_EOL
		)
	);

	$arResult['GRID_DATA'][] = $gridDataRecord;
	unset($gridDataRecord);
}


$arProducts = array();
if (is_array($arResult['PRODUCTS']))
	$arProducts = $arResult['PRODUCTS'];
foreach($arProducts as $sKey =>  $arProduct)
{
	$arActions = array();
	$arActions[] =  array(
		'ICONCLASS' => 'view',
		'TITLE' => GetMessage('CRM_PRODUCT_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_PRODUCT_SHOW'),
		'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape(
				CHTTP::urlAddParams(
					$arProduct['PATH_TO_PRODUCT_SHOW'],
					array('list_section_id' => $arResult['BACK_URL_SECTION_ID'])
				)
			).'\');',
		'DEFAULT' => true
	);

	if ($arProduct['EDIT'])
	{
		$arActions[] =  array(
			'ICONCLASS' => 'edit',
			'TITLE' => GetMessage('CRM_PRODUCT_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_PRODUCT_EDIT'),
			'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape(
					CHTTP::urlAddParams(
						$arProduct['PATH_TO_PRODUCT_EDIT'],
						array('list_section_id' => $arResult['BACK_URL_SECTION_ID'])
					)
				).'\');'
		);

		if ($arResult['CAN_ADD_PRODUCT'])
		{
			$arActions[] = array(
				'ICONCLASS' => 'copy',
				'TITLE' => GetMessage('CRM_PRODUCT_COPY_TITLE'),
				'TEXT' => GetMessage('CRM_PRODUCT_COPY'),
				'ONCLICK' => 'jsUtils.Redirect([], \''.CUtil::JSEscape(
						CHTTP::urlAddParams(
							$arProduct['PATH_TO_PRODUCT_EDIT'],
							array('list_section_id' => $arResult['BACK_URL_SECTION_ID'], 'copy' => 1)
						)
					).'\');'
			);
		}
	}

	if ($arProduct['DELETE'] && !$arResult['INTERNAL'])
	{
		$arActions[] = array('SEPARATOR' => true);
		$arActions[] =  array(
			'ICONCLASS' => 'delete',
			'TITLE' => GetMessage('CRM_PRODUCT_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_PRODUCT_DELETE'),
			'ONCLICK' =>
				'crm_product_delete_grid(\''.CUtil::JSEscape(GetMessage('CRM_PRODUCT_DELETE_TITLE')).'\', \''.
				CUtil::JSEscape(
					sprintf(GetMessage('CRM_PRODUCT_DELETE_CONFIRM'), htmlspecialcharsbx($arProduct['NAME']))
				).
				'\', \''.CUtil::JSEscape(GetMessage('CRM_PRODUCT_DELETE')).'\', \''.
				CUtil::JSEscape(
					CHTTP::urlAddParams(
						$arProduct['PATH_TO_PRODUCT_DELETE'],
						array('list_section_id' => $arResult['BACK_URL_SECTION_ID'])
					)
				).'\')'
		);
	}

	$sectionLink = '';
	if(isset($arProduct['SECTION_ID'])
		&&  array_key_exists($arProduct['SECTION_ID'], $arResult['SECTION_LIST']))
	{
		$sectionData = $arResult['SECTION_LIST'][$arProduct['SECTION_ID']];
		$sectionLink = '<a href="'.htmlspecialcharsbx($sectionData['LIST_URL']).'">'.htmlspecialcharsbx($sectionData['NAME']).'</a>';
	}

	$obPreviewPictureFile = null;
	if (isset($arProduct['~PREVIEW_PICTURE']))
	{
		$obPreviewPictureFile = new CCrmProductFile(
			$arProduct['ID'],
			'PREVIEW_PICTURE',
			$arProduct['~PREVIEW_PICTURE']
		);
	}


	$gridDataRecord = array(
		'id' => $arProduct['TYPE'].$arProduct['ID'],
		'actions' => $arActions,
		'data' => $arProduct,
		'editable' => $arProduct['EDIT'] ? true : $arColumns,
		'columns' => array(
			/*'NAME' => '<a target="_self" href="'.
				CHTTP::urlAddParams(
					$arProduct['PATH_TO_PRODUCT_SHOW'],
					array('list_section_id' => $arResult['BACK_URL_SECTION_ID'])
				).
				'">'.$arProduct['NAME'].'</a>',*/
			'NAME' => '<table class="bx-crm-object-name">'.PHP_EOL.
				"\t".'<tbody>'.PHP_EOL.
				"\t".'<tr>'.
				"\t\t".'<td style="width: 45px;">'.
				"\t\t\t".'<div class="bx-crm-item-icon-container-small'.
				(!$obPreviewPictureFile ? ' bx-crm-item-icon icon-img' : '').'">'.
				($obPreviewPictureFile
					? $obPreviewPictureFile->GetImgHtml(array('max_width' => 27,'max_height' => 35)) : '').
				'</div>'.
				"\t\t".'</td>'.
				"\t\t".'<td>'.
				"\t\t\t".'<a target="_self" href="'.
				CHTTP::urlAddParams(
					$arProduct['PATH_TO_PRODUCT_SHOW'],
					array('list_section_id' => $arResult['BACK_URL_SECTION_ID'])
				).
				'">'.$arProduct['NAME'].'</a>'.
				"\t\t".'</td>'.
				"\t".'</tr>'.PHP_EOL.
				"\t".'</tbody>'.PHP_EOL.
				'</table>'.PHP_EOL,
			'PRICE' => CCrmProduct::FormatPrice($arProduct),
			'MEASURE' => htmlspecialcharsbx(
				(isset($arProduct['MEASURE']) && intval($arProduct['MEASURE']) > 0) ?
					$arResult['MEASURE_LIST_ITEMS'][$arProduct['MEASURE']] : ''
			),
			'SECTION_ID' => $sectionLink
		)
	);
	if ($bVatMode)
	{
		$gridDataRecord['columns']['VAT_ID'] =
			htmlspecialcharsbx(
				isset($arProduct['VAT_ID']) ?
					$arResult['VAT_RATE_LIST_ITEMS'][$arProduct['VAT_ID']] : $arResult['VAT_RATE_LIST_ITEMS']['']
			);
	}

	// Pictures
	$arFields = array('PREVIEW_PICTURE', 'DETAIL_PICTURE');
	$html = '';
	$obFileControl = $obFile = null;
	foreach ($arFields as $fieldID)
	{
		if (isset($arProduct['~'.$fieldID]))
		{
			if ($fieldID === 'PREVIEW_PICTURE' && $obPreviewPictureFile)
			{
				$obFile = &$obPreviewPictureFile;
			}
			else
			{
				$obFile = new CCrmProductFile(
					$arProduct['ID'],
					$fieldID,
					$arProduct['~'.$fieldID]
				);
			}

			$obFileControl = new CCrmProductFileControl($obFile, $fieldID);

			$htmlValue = '<nobr>'.$obFileControl->GetHTML(array(
					'show_input' => false,
					'max_size' => 102400,
					'max_width' => 50,
					'max_height' => 50,
					'url_template' => $arParams['PATH_TO_PRODUCT_FILE'],
					'a_title' => GetMessage('CRM_PRODUCT_PROP_ENLARGE'),
					'download_text' => GetMessage("CRM_PRODUCT_PROP_DOWNLOAD"),
				)).'</nobr>';

			$gridDataRecord['columns'][$fieldID] = $htmlValue;
		}
	}
	unset($arFields, $fieldID, $obFile, $obPreviewPictureFile, $obFileControl, $htmlValue);

	// Product properties
	if (isset($arResult['PROPERTY_VALUES'][$sKey])
		&& is_array($arResult['PROPERTY_VALUES'][$sKey]))
	{
		foreach ($arResult['PROPERTY_VALUES'][$sKey] as $propID => $propValue)
			$gridDataRecord['columns'][$propID] = $propValue;
	}

	$arResult['GRID_DATA'][] = $gridDataRecord;
	unset($gridDataRecord);
}

/*$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.toolbar',
	'',
	array(
		'TOOLBAR_ID' => $arResult['GRID_ID'].'_toolbar',
		'BUTTONS' => array(
			array(
				'TEXT' => GetMessage('CRM_PRODUCT_LIST_SHOW_FILTER_SHORT'),
				'TITLE' => GetMessage('CRM_PRODUCT_LIST_SHOW_FILTER'),
				'ICON' => 'crm-filter-light-btn',
				'ALIGNMENT' => 'right',
				'ONCLICK' => "BX.InterfaceGridFilterPopup.toggle('{$arResult['GRID_ID']}', this)"
			)
		)
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);*/

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'PRODUCT',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => '',
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => array()
);

// Prepare filter
if (is_array($arResult['FILTER']))
{
	foreach ($arResult['FILTER'] as &$filterItem)
	{
		if (isset($filterItem['id']) && isset($filterItem['type']) && $filterItem['type'] === 'propertyE'
			&& is_array($arResult['PROPS'][$filterItem['id']]))
		{
			$propID = $filterItem['id'];
			$arProp = $arResult['PROPS'][$propID];

			$filterItem['type'] = 'custom';
			$items = array();

			if (is_array($arResult['CUSTOM_FILTER_PROPERTY_VALUES'])
				&& is_array($arResult['CUSTOM_FILTER_PROPERTY_VALUES'][$propID])
				&& is_array($arResult['CUSTOM_FILTER_PROPERTY_VALUES'][$propID]['items']))
			{
				$items = $arResult['CUSTOM_FILTER_PROPERTY_VALUES'][$propID]['items'];
			}

			ob_start();

			$values = array();
			foreach($items as $elementId => $elementName)
			{
				$values[] = $elementName.' ['.$elementId.']';
			}
			?><input type="hidden" name="<?php echo $propID?>[]" value=""><?php //This will emulate empty input
			$lookupInputId = $APPLICATION->IncludeComponent(
				'bitrix:main.lookup.input',
				'elements',
				array(
					'INPUT_NAME' => $propID,
					'INPUT_NAME_STRING' => 'inp_'.$propID,
					'INPUT_VALUE_STRING' => implode("\n", $values),
					'START_TEXT' => GetMessage('CRM_PRODUCT_PROP_START_TEXT'),
					'MULTIPLE' => $arProp['MULTIPLE'],
					//These params will go throught ajax call to ajax.php in template
					'IBLOCK_TYPE_ID' => $arResult['CATALOG_TYPE_ID'],
					'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
					'SOCNET_GROUP_ID' => '',
				), $component, array('HIDE_ICONS' => 'Y')
			);

			$treeSelectorId = $APPLICATION->IncludeComponent(
				'bitrix:main.tree.selector',
				'elements',
				array(
					'INPUT_NAME' => $propID,
					'ONSELECT' => 'jsMLI_'.$lookupInputId.'.SetValue',
					'MULTIPLE' => $arProp['MULTIPLE'],
					'SHOW_INPUT' => 'N',
					'SHOW_BUTTON' => 'N',
					'GET_FULL_INFO' => 'Y',
					'START_TEXT' => GetMessage('CRM_PRODUCT_PROP_START_TEXT'),
					'NO_SEARCH_RESULT_TEXT' => GetMessage('CRM_PRODUCT_PROP_NO_SEARCH_RESULT_TEXT'),
					//These params will go throught ajax call to ajax.php in template
					'IBLOCK_TYPE_ID' => $arResult['CATALOG_TYPE_ID'],
					'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
					'SOCNET_GROUP_ID' => '',
				), $component, array('HIDE_ICONS' => 'Y')
			);
			?><a href="javascript:void(0)" onclick="<?=$treeSelectorId?>.SetValue([]); <?=$treeSelectorId?>.Show()"><?php echo GetMessage('CRM_PRODUCT_PROP_CHOOSE_ELEMENT')?></a><?php

			$html = ob_get_contents();
			ob_end_clean();

			$filterItem['value'] = $html;
			unset($html);
		}
	}
	unset($filterItem);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'flat',
	array
	(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'CUSTOM_EDITABLE_COLUMNS' => array(
			'S' => array('NAME', 'SORT')
		),
		'FOOTER' =>
			array
			(
				array
				(
					'title' => GetMessage('CRM_ALL'),
					'value' => $arResult['ROWS_COUNT']
				)
			),
		'EDITABLE' => !$arResult['PERMS']['WRITE'] || $arResult['INTERNAL'] ? 'N' : 'Y',
		'ACTIONS' =>
			array
			(
				'delete' => $arResult['PERMS']['DELETE'],
				'list' => array()
			),
		'ACTION_ALL_ROWS' => true,
		'NAV_OBJECT' => $arResult['NAV_OBJECT'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_MODE' => $arResult['INTERNAL'] ? 'N' : 'Y',
		'AJAX_OPTION_JUMP' => 'N',
		'AJAX_OPTION_HISTORY' => 'N',
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'FILTER_TEMPLATE' => 'flat',
		'MANAGER' => array(
			'ID' => $gridManagerID,
			'CONFIG' => $gridManagerCfg
		)
	),
	$component
);

if(!isset($extMgrId[0]))
{?>
	<form name="form_section_add" id="form_section_add" action="<?= POST_FORM_ACTION_URI ?>" method="POST" enctype="multipart/form-data">
		<?= bitrix_sessid_post(); ?>
		<input type="hidden" id="sectionName" name="sectionName" value="">
		<input type="hidden" id="sectionID" name="sectionID" value="">
		<input type="hidden" id="action" name="action" value="">
	</form>
	<?php
}?>
<script type="text/javascript">
	<?php
	if(!isset($extMgrId[0]))
	{?>
	BX.CrmProductSectionManager.create(
		'<?= $mgrId?>',
		{
			formID: 'form_section_add',
			actionField: 'action',
			nameField: 'sectionName',
			IDField: 'sectionID'
		}
	);
	<?php
}?>

	BX.CrmProductSectionManager.messages =
	{
		addDialogTitle: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_ADD_DIALOG_TITLE')) ?>',
		renameDialogTitle: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_RENAME_DIALOG_TITLE')) ?>',
		nameFieldTitle: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_NAME_FIELD_TITLE')) ?>',
		defaultName: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_DEFAULT_NAME')) ?>',
		addBtnText: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_ADD_BTN_TEXT')) ?>',
		renameBtnText: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_RENAME_BTN_TEXT')) ?>',
		cancelBtnText: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_CANCEL_BTN_TEXT')) ?>',
		emptyNameError: '<?= CUtil::addslashes(GetMessage('CRM_SECTION_EMPTY_NAME_ERROR')) ?>'
	};
</script>