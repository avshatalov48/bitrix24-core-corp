<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var \CBitrixComponent $component */

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."flexible-layout");

if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

// preloading some css files
$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/crm.product.section.tree/templates/.default/style.css');
$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/crm.product.section.crumbs/templates/.default/style.css');
$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/crm.interface.grid/templates/flat/bitrix/main.interface.grid/.default/style.css');
$APPLICATION->SetAdditionalCSS('/bitrix/components/bitrix/crm.product.search.dialog/templates/.default/bitrix/catalog.product.search/.default/style.css');

$APPLICATION->AddHeadScript('/bitrix/js/crm/crm.js');
$APPLICATION->AddHeadScript('/bitrix/js/main/dd.js');

$bCanAddProduct = $arResult['CAN_ADD_PRODUCT'];
if ($bCanAddProduct)
	$APPLICATION->AddHeadScript($this->GetFolder().'/product_create.js');
$readOnly = !isset($arResult['READ_ONLY']) || $arResult['READ_ONLY']; //Only READ_ONLY access by defaul
$bInitEditable = ((isset($arResult['INIT_EDITABLE']) ? $arResult['INIT_EDITABLE'] : false) && !$readOnly);
$bHideModeButton = ((isset($arResult['HIDE_MODE_BUTTON']) ? $arResult['HIDE_MODE_BUTTON'] : false) || $readOnly);
$enableCustomProducts = $arResult['ENABLE_CUSTOM_PRODUCTS'];
$containerID = $arResult['PREFIX'].'_container';
$currencyText = CCrmViewHelper::getCurrencyText($arResult['CURRENCY_ID']);
$nProductRows = count($arResult['PRODUCT_ROWS']);
$additionalClasses = $dataTabs = "";
if ($arResult['ALLOW_TAX'] && $arResult['ENABLE_TAX'] && $arResult['ENABLE_DISCOUNT'])
{
	$dataTabs = 'all';
	$additionalClasses = " crm-items-list-tax crm-items-list-sale";
}
else if ($arResult['ALLOW_TAX'] && $arResult['ENABLE_TAX'])
{
	$dataTabs = 'tax';
	$additionalClasses = " crm-items-list-tax";
}
else if ($arResult['ENABLE_DISCOUNT'])
{
	$dataTabs = 'sale';
	$additionalClasses = " crm-items-list-sale";
}

// Product properties
$arPropUserTypeList = &$arResult['PRODUCT_PROPS_USER_TYPES'];
$visibleFields =
	is_array($arResult['PRODUCT_CREATE_DLG_VISIBLE_FIELDS'])
		? $arResult['PRODUCT_CREATE_DLG_VISIBLE_FIELDS'] : array();
$bMultipleListType = false;
foreach($arResult['PRODUCT_PROPS'] as $propID => $arProp)
{
	if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
		&& !array_key_exists($arProp['USER_TYPE'], $arPropUserTypeList)
	)
		continue;

	$bMultipleListType = ($arProp['PROPERTY_TYPE'] === 'L' && $arProp['MULTIPLE'] === 'Y' && empty($arProp['USER_TYPE']));
	$skip = !CCrmProductHelper::IsFieldVisible($bMultipleListType ? $propID.'[]' : $propID, $visibleFields);
	$defaultValue = array(
		'n0' => array(
			'VALUE' => $arProp['DEFAULT_VALUE'],
			'DESCRIPTION' => '',
		)
	);
	if ($arProp['MULTIPLE'] == 'Y')
	{
		if (is_array($arProp['DEFAULT_VALUE']) || strlen($arProp['DEFAULT_VALUE']))
			$defaultValue['n1'] = array('VALUE' => '', 'DESCRIPTION' => '');
	}

	if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
		&& is_array($arPropUserTypeList[$arProp['USER_TYPE']])
		&& $arProp['MULTIPLE'] == 'Y'
		&& array_key_exists('GetPublicEditHTMLMulty', $arPropUserTypeList[$arProp['USER_TYPE']])
	)
	{
		$arProp['PROPERTY_USER_TYPE'] = $arPropUserTypeList[$arProp['USER_TYPE']];
		$html = call_user_func_array(
			$arPropUserTypeList[$arProp['USER_TYPE']]['GetPublicEditHTMLMulty'],
			array(
				$arProp,
				$defaultValue,
				array(
					'VALUE' => $propID,
					'DESCRIPTION' => '',
					'FORM_NAME' => $arResult['PRODUCT_CREATE_DLG_SETTINGS']['formId'],
					'MODE' => 'FORM_FILL',
				),
			)
		);

		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
			'textCode' => $propID,
			'type' => 'custom',
			'value' => $html,
			'skip' => $skip ? 'Y' : 'N',
			'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
		);
	}
	else if (
		isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
		&& is_array($arPropUserTypeList[$arProp['USER_TYPE']])
		&& array_key_exists('GetPublicEditHTML', $arPropUserTypeList[$arProp['USER_TYPE']])
	)
	{
		$arProp['PROPERTY_USER_TYPE'] = $arPropUserTypeList[$arProp['USER_TYPE']];
		if ($arProp['MULTIPLE'] == 'Y')
		{
			$html = '<table id="tbl'.$propID.'">';
			foreach($defaultValue as $key => $value)
			{
				$html .= '<tr><td>'.call_user_func_array($arPropUserTypeList[$arProp['USER_TYPE']]['GetPublicEditHTML'],
						array(
							$arProp,
							$value,
							array(
								'VALUE' => $propID.'['.$key.'][VALUE]',
								'DESCRIPTION' => '',
								'FORM_NAME' => $arResult['PRODUCT_CREATE_DLG_SETTINGS']['formId'],
								'MODE' => 'FORM_FILL',
								'COPY' => $arResult['COPY_ID'] > 0,
							),
						)).'</td></tr>';
			}
			$html .= '</table>';
			if ($arProp['USER_TYPE'] !== 'HTML')
				$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$propID.'\')" value="'.GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON').'">';

			$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
			$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
				'textCode' => $propID,
				'type' => 'custom',
				'value' => $html,
				'skip' => $skip ? 'Y' : 'N',
				'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
			);
		}
		else
		{
			foreach($defaultValue as $key => $value)
			{
				$html = call_user_func_array($arPropUserTypeList[$arProp['USER_TYPE']]['GetPublicEditHTML'],
					array(
						$arProp,
						$value,
						array(
							'VALUE' => $propID.'['.$key.'][VALUE]',
							'DESCRIPTION' => '',
							'FORM_NAME' => $arResult['PRODUCT_CREATE_DLG_SETTINGS']['formId'],
							'MODE' => 'FORM_FILL',
						),
					));
				break;
			}
			$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
			$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
				'textCode' => $propID,
				'type' => 'custom',
				'value' => $html,
				'skip' => $skip ? 'Y' : 'N',
				'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
			);
		}
	}
	else if ($arProp['PROPERTY_TYPE'] == 'N')
	{
		if ($arProp['MULTIPLE'] == 'Y')
		{
			$html = '<table id="tbl'.$propID.'">';
			foreach($defaultValue as $key => $value)
				$html .= '<tr><td><input type="text" name="'.$propID.'['.$key.'][VALUE]" value="'.$value['VALUE'].'"></td></tr>';
			$html .= '</table>';
			$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$propID.'\')" value="'.GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON').'">';
		}
		else
		{
			foreach($defaultValue as $key => $value)
				$html = '<input type="text" name="'.$propID.'['.$key.'][VALUE]" value="'.$value['VALUE'].'">';
		}

		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
			'textCode' => $propID,
			'type' => 'custom',
			'value' => $html,
			'skip' => $skip ? 'Y' : 'N',
			'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
		);
	}
	else if ($arProp['PROPERTY_TYPE'] == 'S')
	{
		$nCols = intval($arProp['COL_COUNT']);
		$nCols = ($nCols > 100) ? 100 : $nCols;
		if ($arProp['MULTIPLE'] == 'Y')
		{
			$html = '<table id="tbl'.$propID.'">';
			if ($arProp['ROW_COUNT'] > 1)
			{
				foreach($defaultValue as $key => $value)
				{
					$html .= '<tr><td><textarea name="'.$propID.'['.$key.'][VALUE]" rows="'.intval($arProp['ROW_COUNT']).'" cols="'.$nCols.'">'.$value['VALUE'].'</textarea></td></tr>';
				}
			}
			else
			{
				foreach($defaultValue as $key => $value)
				{
					$html .= '<tr><td><input type="text" name="'.$propID.'['.$key.'][VALUE]" value="'.$value['VALUE'].'"></td></tr>';
				}
			}
			$html .= '</table>';
			$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$propID.'\')" value="'.GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON').'">';
		}
		else
		{
			if ($arProp['ROW_COUNT'] > 1)
			{
				foreach($defaultValue as $key => $value)
				{
					$html = '<textarea name="'.$propID.'['.$key.'][VALUE]" rows="'.intval($arProp['ROW_COUNT']).'" cols="'.$nCols.'">'.$value['VALUE'].'</textarea>';
				}
			}
			else
			{
				foreach($defaultValue as $key => $value)
				{
					$html = '<input type="text" name="'.$propID.'['.$key.'][VALUE]" value="'.$value['VALUE'].'" size="'.$nCols.'">';
				}
			}
		}
		unset($nCols);

		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
			'textCode' => $propID,
			'type' => 'custom',
			'value' => $html,
			'skip' => $skip ? 'Y' : 'N',
			'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
		);
	}
	else if ($arProp['PROPERTY_TYPE'] == 'L')
	{
		$items = array('' => GetMessage('CRM_PRODUCT_PROP_NO_VALUE'));
		$prop_enums = CIBlockProperty::GetPropertyEnum($arProp['ID']);
		$defaultValue = '';
		while($ar_enum = $prop_enums->Fetch())
		{
			$items[$ar_enum['ID']] = $ar_enum['VALUE'];
			if ('Y' === $ar_enum['DEF'])
			{
				if ($defaultValue === '')
					$defaultValue = array($ar_enum['ID']);
				else if (is_array($defaultValue))
					$defaultValue[] = $ar_enum['ID'];
			}
		}
		if ($arProp['MULTIPLE'] == 'Y')
		{
			$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID.'[]'] = $arProp['NAME'];
			$rowCount = 5;
			if (isset($arProp['ROW_COUNT']) && intval($arProp['ROW_COUNT']) > 0)
				$rowCount = intval($arProp['ROW_COUNT']);
			$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
				'textCode' => $propID.'[]',
				'type' => 'select',
				'value' => $defaultValue,
				'items' => CCrmViewHelper::prepareSelectItemsForJS($items),
				'skip' => $skip ? 'Y' : 'N',
				'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N',
				'params' => array('size' => $rowCount, 'multiple' => 'multiple')
			);
			unset($rowCount);
		}
		else
		{
			$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
			$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
				'textCode' => $propID,
				'type' => 'select',
				'value' => $defaultValue,
				'items' => CCrmViewHelper::prepareSelectItemsForJS($items),
				'skip' => $skip ? 'Y' : 'N',
				'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
			);
		}
	}
	else if ($arProp['PROPERTY_TYPE'] == 'F')
	{
		if ($arProp['MULTIPLE'] == 'Y')
		{
			$html = '<table id="tbl'.$propID.'">';
			foreach($defaultValue as $key => $value)
			{
				$html .= '<tr><td>';

				$obFile = new CCrmProductFile(
					$arResult['PRODUCT_ID'],
					$propID,
					$value['VALUE']
				);

				$obFileControl = new CCrmProductFileControl($obFile, $propID.'['.$key.'][VALUE]');

				$html .= $obFileControl->GetHTML(array(
					'max_size' => 102400,
					'max_width' => 150,
					'max_height' => 150,
					'url_template' => $arParams['~PATH_TO_PRODUCT_FILE'],
					'a_title' => GetMessage('CRM_PRODUCT_FILE_ENLARGE'),
					'download_text' => GetMessage('CRM_PRODUCT_FILE_DOWNLOAD'),
				));

				$html .= '</td></tr>';
			}
			$html .= '</table>';
			$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$propID.'\')" value="'.GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON').'">';

			$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
			$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
				'textCode' => $propID,
				'type' => 'custom',
				'value' => $html,
				'skip' => $skip ? 'Y' : 'N',
				'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
			);
		}
		else
		{
			foreach($defaultValue as $key => $value)
			{
				$obFile = new CCrmProductFile(
					$arResult['PRODUCT_ID'],
					$propID,
					$value['VALUE']
				);

				$obFileControl = new CCrmProductFileControl($obFile, $propID.'['.$key.'][VALUE]');

				$html = $obFileControl->GetHTML(array(
					'max_size' => 102400,
					'max_width' => 150,
					'max_height' => 150,
					'url_template' => $arParams['~PATH_TO_PRODUCT_FILE'],
					'a_title' => GetMessage('CRM_PRODUCT_FILE_ENLARGE'),
					'download_text' => GetMessage('CRM_PRODUCT_FILE_DOWNLOAD'),
				));

				$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
				$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
					'textCode' => $propID,
					'type' => 'custom',
					'value' => $html,
					'skip' => $skip ? 'Y' : 'N',
					'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
				);
			}
		}
	}
	else if ($arProp['PROPERTY_TYPE'] == 'G')
	{
	}
	else if ($arProp['PROPERTY_TYPE'] == 'E')
	{
		if ($arProp['IS_REQUIRED']=='Y')
			$items = array();
		else
			$items = array('' => GetMessage('CRM_PRODUCT_PROP_NO_VALUE'));

		$rsElements = CIBlockElement::GetList(array('NAME' => 'ASC'), array('IBLOCK_ID' => $arProp['LINK_IBLOCK_ID']), false, false, array('ID', 'NAME'));
		while($ar = $rsElements->Fetch())
			$items[$ar['ID']] = $ar['NAME'];

		ob_start();

		$arValues = array();
		if (is_array($defaultValue))
		{
			foreach(array_keys($defaultValue) as $key)
				if ($key > 0 && array_key_exists($key, $items))
					$arValues[] = $items[$key].' ['.$key.']';
		}
		?><input type="hidden" name="<?echo $propID?>[]" value=""><? //This will emulate empty input
		$control_id = $APPLICATION->IncludeComponent(
			'bitrix:main.lookup.input',
			'elements',
			array(
				'INPUT_NAME' => $propID,
				'INPUT_NAME_STRING' => 'inp_'.$propID,
				'INPUT_VALUE_STRING' => implode("\n", $arValues),
				'START_TEXT' => GetMessage('CRM_PRODUCT_PROP_START_TEXT'),
				'MULTIPLE' => $arProp['MULTIPLE'],
				//These params will go throught ajax call to ajax.php in template
				'IBLOCK_TYPE_ID' => $arResult['CATALOG_TYPE_ID'],
				'IBLOCK_ID' => $arProp['LINK_IBLOCK_ID'],
				'SOCNET_GROUP_ID' => '',
			), $component, array('HIDE_ICONS' => 'Y')
		);

		$name = $APPLICATION->IncludeComponent(
			'bitrix:main.tree.selector',
			'elements',
			array(
				'INPUT_NAME' => $propID,
				'ONSELECT' => 'jsMLI_'.$control_id.'.SetValue',
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
		?><a href="javascript:void(0)" onclick="<?=$name?>.SetValue([]); <?=$name?>.Show()"><?echo GetMessage('CRM_PRODUCT_PROP_CHOOSE_ELEMENT')?></a><?

		$html = ob_get_contents();
		ob_end_clean();

		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
			'textCode' => $propID,
			'type' => 'custom',
			'value' => $html,
			'skip' => $skip ? 'Y' : 'N',
			'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
		);
	}
	else if ($arProp['MULTIPLE'] == 'Y')
	{
		$html = '<table id="tbl'.$propID.'">';
		foreach($defaultValue as $key => $value)
			$html .= '<tr><td><input type="text" name="'.$propID.'['.$key.'][VALUE]" value="'.$value['VALUE'].'"></td></tr>';
		$html .= '</table>';
		$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$propID.'\')" value="'.GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON').'">';

		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
			'textCode' => $propID,
			'type' => 'custom',
			'value' => $html,
			'skip' => $skip ? 'Y' : 'N',
			'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
		);
	}
	else if (is_array($defaultValue) && array_key_exists('VALUE', $defaultValue))
	{
		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID.'[VALUE]'] = $arProp['NAME'];
		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
			'textCode' => $propID.'[VALUE]',
			'type' => 'text',
			'value' => $defaultValue['VALUE'],
			'skip' => $skip ? 'Y' : 'N',
			'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
		);
	}
	else
	{
		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['messages'][$propID] = $arProp['NAME'];
		$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'][] = array(
			'textCode' => $propID,
			'type' => 'text',
			'skip' => $skip ? 'Y' : 'N',
			'required' => $arProp['IS_REQUIRED']=='Y'? 'Y' : 'N'
		);
	}
}
unset($bMultipleListType);

// order fields by settings
if (is_array($visibleFields) && count($visibleFields) > 0
	&& is_array($arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'])
	&& count($arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields']) > 0)
{
	$fields = $arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'];
	$fieldsIndex = array();
	foreach ($fields as $index => $field)
	{
		$fieldsIndex[$field['textCode']] = array(
			'index' => $index,
			'ordered' => false
		);
	}
	$orderedFields = array();
	foreach ($visibleFields as $fieldName)
	{
		if (isset($fieldsIndex[$fieldName]))
		{
			$orderedFields[] = $fields[$fieldsIndex[$fieldName]['index']];
			$fieldsIndex[$fieldName]['ordered'] = true;
		}
	}
	foreach ($fieldsIndex as $index)
	{
		if ($index['ordered'] === false)
		{
			$orderedFields[] = $fields[$index['index']];
		}
	}
	$arResult['PRODUCT_CREATE_DLG_SETTINGS']['fields'] = $orderedFields;
	unset($fields, $fieldsIndex, $index, $field, $orderedFields, $fieldName);
}
?>
<div id="<?=$containerID?>" class="crm-items-list-wrap<?=$additionalClasses?>" data-tabs="<?=$dataTabs?>"><?
$choiceProductBtnID = $arResult['PREFIX'].'_select_product_button';
$addProductBtnID = $arResult['PREFIX'].'_add_product_button';
$modeBtnID = $arResult['PREFIX'].'_edit_rows_button';
$addRowBtnID = $arResult['PREFIX'].'_add_row_button';
//$buttonContainerID = $arResult['PREFIX'].'_product_button_container';
?>  <div class="crm-items-table-top-bar"><span id="crm-l-space" class="<?= $arResult['ALLOW_TAX'] ? 'crm-items-table-bar-l' : 'crm-items-table-bar-l-wtax' ?>"><span id="<?=$choiceProductBtnID?>" class="webform-small-button"<?= ($arResult['INVOICE_MODE']) ? ' style="display: none;"' : '' ?>><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?=htmlspecialcharsbx(GetMessage('CRM_FF_CHOISE_3'))?></span><span class="webform-small-button-right"></span></span><?
	if ($bCanAddProduct):
		?><span id="<?=$addProductBtnID?>" class="webform-small-button"<?= ($arResult['INVOICE_MODE']) ? ' style="display: none;"' : '' ?>><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?=htmlspecialcharsbx(GetMessage('CRM_FF_ADD_CUSTOM_1'))?></span><span class="webform-small-button-right"></span></span><?
	endif;
?></span><span class="crm-items-table-tab crm-items-table-sale" id="crm-top-sale-tab" style="<?= $nProductRows === 0 ? 'display: none;' : '' ?>"><span class="crm-items-table-tab-inner"><input class="crm-items-checkbox" id="crm-top-sale-checkbox" type="checkbox"<?= $arResult['ENABLE_DISCOUNT'] ? ' checked="checked"' : '' ?>/><label class="crm-items-label" for="crm-top-sale-checkbox"><?=GetMessage('CRM_PRODUCT_SHOW_DISCOUNT')?></label></span></span><span class="crm-items-table-tab-spacer" id="crm-top-spacer" style="<?= $nProductRows === 0 ? 'display: none;' : '' ?>"></span><?
if($arResult['ALLOW_TAX']):
	?><span class="crm-items-table-tab crm-items-table-tax" id="crm-top-tax-tab" style="<?= $nProductRows === 0 ? 'display: none;' : '' ?>"><span class="crm-items-table-tab-inner"><input class="crm-items-checkbox" id="crm-top-tax-checkbox"  type="checkbox"<?= $arResult['ENABLE_TAX'] ? ' checked="checked"' : '' ?>/><label class="crm-items-label" for="crm-top-tax-checkbox"><?=GetMessage('CRM_PRODUCT_SHOW_TAX')?></label></span></span><?
endif;
if($arResult['ENABLE_MODE_CHANGE']):
	?><span class="crm-items-table-bar-r"><span id="<?=$modeBtnID?>" class="webform-small-button"<?= ($bInitEditable || $nProductRows === 0 || $arResult['INVOICE_MODE']) ? ' style="display: none;"' : '' ?>><span class="webform-small-button-left"></span><span class="webform-small-button-text"><?= htmlspecialcharsbx(GetMessage($bInitEditable ? 'CRM_PRODUCT_ROW_BTN_EDIT_F' : 'CRM_PRODUCT_ROW_BTN_EDIT') )?></span><span class="webform-small-button-right"></span></span></span><?
endif;
?>  </div><?
$productContainerID = $arResult['PREFIX'].'_product_table';
$priceTitleId = $arResult['PREFIX'].'_price_title';
$jsEventsManagerId = 'PageEventsManager_'.$arResult['COMPONENT_ID'];
?>
	<table id="<?= $productContainerID ?>" class="crm-items-table" style="<?= $nProductRows === 0 ? 'display: none;' : '' ?>">
		<thead>
			<tr class="crm-items-table-header">
				<td class="crm-item-cell crm-item-name"><span class="crm-item-cell-text"><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_COL_TTL_NAME'))?></span></td>
				<td class="crm-item-cell crm-item-price"><span id="<?=$priceTitleId?>" class="crm-item-cell-text"><?=GetMessage('CRM_PRODUCT_ROW_COL_TTL_PRICE', array('#CURRENCY#' => " ($currencyText)"))?></span></td>
				<td class="crm-item-cell crm-item-qua" ><span class="crm-item-cell-text"><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_COL_TTL_QUANTITY'))?></span></td>
				<td class="crm-item-cell crm-item-unit"><span class="crm-item-cell-text"><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_COL_TTL_MEASURE'))?></span></td>
				<td class="crm-item-cell crm-item-sale"><span class="crm-item-cell-text"><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_COL_TTL_DISCOUNT_RATE'))?></span></td>
				<td class="crm-item-cell crm-item-sum-sale"><span class="crm-item-cell-text"><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_COL_TTL_DISCOUNT'))?></span></td>
				<?if($arResult['ALLOW_TAX']):?>
				<td class="crm-item-cell crm-item-spacer"></td>
				<td class="crm-item-cell crm-item-tax"><span class="crm-item-cell-text"><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_COL_TTL_TAX'))?></span></td>
				<td class="crm-item-cell crm-item-tax-included"><span class="crm-item-cell-text"><?=GetMessage('CRM_PRODUCT_ROW_COL_TTL_TAX_INCLUDED')?></span></td>
				<td class="crm-item-cell crm-item-tax-sum"><span class="crm-item-cell-text"><?=GetMessage('CRM_PRODUCT_ROW_COL_TTL_TAX_SUM')?></span></td>
				<?endif;?>
				<td class="crm-item-cell crm-item-total"><span class="crm-item-cell-text"><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_ROW_COL_TTL_SUM'))?></span></td>
				<td class="crm-item-cell crm-item-move"><span class="crm-item-cell-text"></span></td>
			</tr>
		</thead>
		<tbody>
		<?
		$defaultMeasure = \Bitrix\Crm\Measure::getDefaultMeasure();
		$defaultTax = CCrmTax::GetDefaultVatRateInfo();
		$measures = \Bitrix\Crm\Measure::getMeasures(100);
		$productTotalContainerID = $arResult['PREFIX'].'_product_sum_total_container';
		$rowIdPrefix = $arResult['PREFIX'].'_product_row_';
		$productEditorCfg = array(
			'sessid' => bitrix_sessid(),
			'serviceUrl'=> '/bitrix/components/bitrix/crm.product_row.list/ajax.php?'.bitrix_sessid_get(),
			'productSearchUrl'=> '/bitrix/components/bitrix/crm.product.list/list.ajax.php?'.bitrix_sessid_get(),
			'pathToProductShow' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_SHOW']),
			'pathToProductEdit' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_EDIT']),
			'ownerType' => $arResult['OWNER_TYPE'],
			'ownerID' => $arResult['OWNER_ID'],
			'permissionEntityType' => $arResult['PERMISSION_ENTITY_TYPE'],
			'invoiceMode' => $arResult['INVOICE_MODE'],
			'currencyID' => $arResult['CURRENCY_ID'],
			'locationID' => $arResult['LOCATION_ID'],
			'currencyFormat' => $arResult['CURRENCY_FORMAT'],
			'formID' => $arResult['FORM_ID'],
			'productRowsTabID' => $arResult['TAB_ID'],
			'containerID' => $containerID,
			'productContainerID' => $productContainerID,
			'productTotalContainerID' => $productTotalContainerID,
			'choiceBtnID' => $choiceProductBtnID,
			'addBtnID' => $addProductBtnID,
			'productCreateDialogSettings' =>
				isset($arResult['PRODUCT_CREATE_DLG_SETTINGS']) ? $arResult['PRODUCT_CREATE_DLG_SETTINGS'] : null,
			'modeBtnID' => $modeBtnID,
			'addRowBtnID' => $addRowBtnID,
			'canAddProduct' => $bCanAddProduct,
			'taxValueID' => $arResult['PREFIX'].'_tax_value',
			'hideModeButton' => $bHideModeButton,
			'dataFieldName' => $arResult['PRODUCT_DATA_FIELD_NAME'],
			'readOnly' => $readOnly,
			'initEditable' => $bInitEditable,
			'enableRawCatalogPricing' => $arResult['ENABLE_RAW_CATALOG_PRICING'],
			'defaultMeasure' => $defaultMeasure,
			'measures' => $measures,
			'priceTitleId' => $priceTitleId,
			'discountTypeText' => array(
				\Bitrix\Crm\Discount::UNDEFINED => '-',
				\Bitrix\Crm\Discount::PERCENTAGE => '%',
				\Bitrix\Crm\Discount::MONETARY => $currencyText
			),
			'siteId' => $arResult['SITE_ID'],
			'clientSelectorId' => $arResult['CLIENT_SELECTOR_ID'],
			'clientTypeName' => $arResult['CLIENT_TYPE_NAME'],
			'productFields' => array(
				'PRODUCT_NAME',
				'PRICE',
				'QUANTITY',
				'MEASURE',
				'DISCOUNT',
				'DISCOUNT_SUBTOTAL',
				'TAX_RATE',
				'TAX_INCLUDED',
				'TAX_SUM',
				'SUM'
			),
			'rowIdPrefix' => $rowIdPrefix,
			'items' => array(),
			'jsEventsManagerId' => $jsEventsManagerId,
			'initLayout' => $arResult['INIT_LAYOUT']
		);

		$productEditorCfg['enableSubmitWithoutLayout'] = $arResult['ENABLE_SUBMIT_WITHOUT_LAYOUT'];

		$productEditorCfg['hideTaxIncludedColumn'] = $arResult['HIDE_TAX_INCLUDED_COLUMN'];
		$productEditorCfg['hideAllTaxes'] = $arResult['HIDE_ALL_TAXES'];
		$productEditorCfg['allowTax'] = $arResult['ALLOW_TAX'];
		$productEditorCfg['taxUniform'] = $arResult['PRODUCT_ROW_TAX_UNIFORM'];
		$productEditorCfg['defaultTax'] = $defaultTax;
		$productEditorCfg['allowLDTax'] = $arResult['ALLOW_LD_TAX'];
		$taxes = array();
		if($arResult['ALLOW_TAX'])
		{
			$productEditorCfg['taxes'] = $taxes = CCrmTax::GetVatRateInfos();
		}
		$taxRatesOrig = array();
		foreach ($taxes as $tax)
			$taxRatesOrig[] = $tax['VALUE'];

		$productEditorCfg['enableTax'] = $arResult['ENABLE_TAX'];
		$productEditorCfg['enableDiscount'] = $arResult['ENABLE_DISCOUNT'];

		for ($i = 0; $i < $nProductRows; $i++)
		{
			$row = $arResult['PRODUCT_ROWS'][$i];
			$rowID = $rowIdPrefix.strval($i);
			$productID = intval($row['PRODUCT_ID']);
			$productName = isset($row['PRODUCT_NAME']) ? $row['PRODUCT_NAME'] : '';
			if($productName === '')
			{
				$productName = $productID > 0 && isset($row['ORIGINAL_PRODUCT_NAME'])
					? $row['ORIGINAL_PRODUCT_NAME'] : "[{$productID}]";
			}

			$fixedProductName = '';
			if ($productName == "OrderDelivery" || $productName == "OrderDiscount")
			{
				$fixedProductName = $productName;
				if ($productName == "OrderDelivery")
					$productName = GetMessage("CRM_PRODUCT_ROW_DELIVERY");
				elseif ($productName == "OrderDiscount")
					$productName = GetMessage("CRM_PRODUCT_ROW_DISCOUNT");
			}

			$productEditorCfg['items'][] =
				array(
					'rowID' => $rowID,
					'settings' => array(
						'ID' => $row['ID'],
						'PRODUCT_ID' => strval($productID),
						'PRODUCT_NAME' => $productName,
						'QUANTITY' => $row['QUANTITY'],
						'DISCOUNT_TYPE_ID' => $row['DISCOUNT_TYPE_ID'],
						'DISCOUNT_RATE' => $row['DISCOUNT_RATE'],
						'DISCOUNT_SUM' => $row['DISCOUNT_SUM'],
						'PRICE' => $row['PRICE'],
						'PRICE_EXCLUSIVE' => $row['PRICE_EXCLUSIVE'],
						'PRICE_NETTO' => $row['PRICE_NETTO'],
						'PRICE_BRUTTO' => $row['PRICE_BRUTTO'],
						'TAX_RATE' => $row['TAX_RATE'],
						'TAX_INCLUDED' => $row['TAX_INCLUDED'] === 'Y',
						'CUSTOMIZED' => $row['CUSTOMIZED'] === 'Y',
						'MEASURE_CODE' => $row['MEASURE_CODE'],
						'MEASURE_NAME' => $row['MEASURE_NAME'],
						'SORT' => ($i + 1) * 10,
						'FIXED_PRODUCT_NAME' => $fixedProductName
					)
				);

			// PRODUCT_NAME
			$htmlValues = array();
			$htmlValues['PRODUCT_NAME'] = htmlspecialcharsbx($productName);

			// PRICE
			$htmlValues['PRICE'] = number_format(
				($arResult['ALLOW_TAX'] && $arResult['ENABLE_TAX']) ? $row['PRICE_NETTO'] : $row['PRICE_BRUTTO'],
				2, '.', ''
			);

			// QUANTITY
			$htmlValues['QUANTITY'] = rtrim(rtrim(number_format($row['QUANTITY'], 4, '.', ''), '0'), '.');

			// MEASURE
			$measureSelectedCode = $measureSelectedSymbol = null;
			foreach ($measures as $measure)
			{
				if ($measureSelectedCode === null)
				{
					$measureSelectedCode = $measure['CODE'];
					$measureSelectedSymbol = $measure['SYMBOL'];
				}
				if (is_array($defaultMeasure) && isset($defaultMeasure['CODE']) && $measure['CODE'] === $defaultMeasure['CODE'])
				{
					$measureSelectedCode = $measure['CODE'];
					$measureSelectedSymbol = $measure['SYMBOL'];
				}
				if ($measure['CODE'] === $row['MEASURE_CODE'])
				{
					$measureSelectedCode = $measure['CODE'];
					$measureSelectedSymbol = $measure['SYMBOL'];
					break;
				}
			}
			unset($measure);
			$htmlValues['~MEASURE_SELECTED_CODE'] = $measureSelectedCode;
			$htmlValues['MEASURE_SELECTED_SYMBOL'] = htmlspecialcharsbx($measureSelectedSymbol);
			unset($measureSelectedCode, $measureSelectedSymbol);

			// DISCOUNT
			$discountValue = '';

			/*if ($row['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::MONETARY)
				$discountValue = number_format(doubleval($row['DISCOUNT_SUM']), 2, '.', '');
			else if ($row['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::PERCENTAGE)
				$discountValue = rtrim(rtrim(number_format(doubleval($row['DISCOUNT_RATE']), 2, '.', ''), '0'), '.');*/
			$discountValue = rtrim(rtrim(number_format(doubleval($row['DISCOUNT_RATE']), 2, '.', ''), '0'), '.');

			$htmlValues['DISCOUNT'] = $discountValue;
			unset($discountValue);

			// DISCOUNT TYPE
			/*$htmlValues['DISCOUNT_TYPE_TEXT'] = ($row['DISCOUNT_TYPE_ID'] === \Bitrix\Crm\Discount::MONETARY) ? $currencyText : '%';*/
			$htmlValues['DISCOUNT_TYPE_TEXT'] = '%';

			// DISCOUNT SUM
			$discountSubtotalValue = 0.0;
			$discountSubtotalValue = doubleval($row['QUANTITY']) * doubleval($row['DISCOUNT_SUM']);
			$htmlValues['DISCOUNT_SUBTOTAL'] = number_format($discountSubtotalValue, 2, '.', '');
			unset($discountSubtotalValue);

			// TAX RATE
			$taxRateSelected = round(doubleval($row['TAX_RATE']), 2);
			$taxRates = $taxRatesOrig;
			$taxRates[] = 0;
			$taxRates[] = $taxRateSelected;
			$taxRates = array_unique($taxRates);
			sort($taxRates, SORT_NUMERIC);
			$htmlValues['TAX_RATES'] = $taxRates;
			$htmlValues['TAX_RATE'] = $taxRateSelected;
			unset($taxRateSelected, $taxRates, $tax);

			// TAX INCLUDED
			$htmlValues['TAX_INCLUDED'] = ($row['TAX_INCLUDED'] === 'Y');

			// SUM
			$htmlValues['SUM'] = number_format($row['PRICE'] * $row['QUANTITY'], 2, '.', '');
			$className = (($i + 1) % 2) === 0 ? "crm-items-table-even-row" : "crm-items-table-odd-row";
			?>

			<tr id="<?=$rowID?>" class="<?=$className?>">
				<td class="crm-item-cell crm-item-name">
					<span class="crm-item-cell-text"<?= ($bInitEditable && empty($fixedProductName)) ? '' : ' style="display: none;"' ?>>
						<span class="crm-table-name-left">
							<span class="crm-item-move-btn"></span><span id="<?= ($rowID.'_NUM') ?>" class="crm-item-num"><?=($i+1).'.'?></span>
						</span>
						<span class="crm-item-inp-wrap">
							<input id="<?=$rowID.'_PRODUCT_NAME'?>" class="crm-item-name-inp" type="text" value="<?=$htmlValues['PRODUCT_NAME']?>" autocomplete="off"/><span class="crm-item-inp-btn<? echo ($productID > 0) ? ' crm-item-inp-arrow' : ($bCanAddProduct ? ' crm-item-inp-plus' : ''); ?>"></span>
						</span>
					</span>
					<span class="crm-item-cell-view"<?= ($bInitEditable && empty($fixedProductName)) ? ' style="display: none;"' : '' ?>>
						<span class="crm-table-name-left">
							<span class="crm-item-move-btn view-mode"></span><span id="<?= ($rowID.'_NUM_v') ?>" class="crm-item-num"><?=($i+1).'.'?></span>
						</span>
						<span class="crm-item-txt-wrap">
							<div id="<?=$rowID.'_PRODUCT_NAME_v'?>" class="crm-item-name-txt"><?=$htmlValues['PRODUCT_NAME']?></div>
						</span>
					</span>
				</td>
				<td class="crm-item-cell crm-item-price">
					<span class="crm-item-cell-text"<?= $bInitEditable ? '' : ' style="display: none;"' ?>>
						<input id="<?=$rowID.'_PRICE'?>" type="text" class="crm-item-table-inp" value="<?=$htmlValues['PRICE']?>"/>
					</span>
					<span class="crm-item-cell-view"<?= $bInitEditable ? ' style="display: none;"' : '' ?>>
						<div id="<?=$rowID.'_PRICE_v'?>" class="crm-item-table-txt"><?=$htmlValues['PRICE']?></div>
					</span>
				</td>
				<td class="crm-item-cell crm-item-qua">
					<span class="crm-item-cell-text"<?= $bInitEditable ? '' : ' style="display: none;"' ?>>
						<input id="<?=$rowID.'_QUANTITY'?>" type="text" class="crm-item-table-inp" value="<?=$htmlValues['QUANTITY']?>"/>
					</span>
					<span class="crm-item-cell-view"<?= $bInitEditable ? ' style="display: none;"' : '' ?>>
						<div id="<?=$rowID.'_QUANTITY_v'?>" class="crm-item-table-txt"><?=$htmlValues['QUANTITY']?></div>
					</span>
				</td>
				<td class="crm-item-cell crm-item-unit">
					<span class="crm-item-cell-text"<?= $bInitEditable ? '' : ' style="display: none;"' ?>>
						<select id="<?=$rowID.'_MEASURE'?>" class="crm-item-table-select">
							<?php
							foreach ($measures as $measure)
							{
								echo '<option value="'.htmlspecialcharsbx($measure['CODE']).'"'.
									($measure['CODE'] === $htmlValues['~MEASURE_SELECTED_CODE'] ? ' selected="selected"' : '').'>'.
									htmlspecialcharsbx($measure['SYMBOL']).'</option>'.PHP_EOL;
							}
							unset($measure);
							?>
						</select>
					</span>
					<span class="crm-item-cell-view"<?= $bInitEditable ? ' style="display: none;"' : '' ?>>
						<div id="<?=$rowID.'_MEASURE_v'?>" class="crm-item-table-txtl"><?=$htmlValues['MEASURE_SELECTED_SYMBOL']?></div>
					</span>
				</td>
				<td class="crm-item-cell crm-item-sale">
					<span class="crm-item-cell-text"<?= $bInitEditable ? '' : ' style="display: none;"' ?>>
						<input id="<?=$rowID.'_DISCOUNT'?>" type="text" class="crm-item-table-inp" value="<?=$htmlValues['DISCOUNT']?>"/><span class="crm-item-sale-text-wrap"><?
								if ($arResult['INVOICE_MODE']) :
							?><span class="crm-item-sale-text"><?=$htmlValues['DISCOUNT_TYPE_TEXT']?></span><?
								else :
							?><a href="#" class="crm-item-sale-text"><?=$htmlValues['DISCOUNT_TYPE_TEXT']?></a><?
								endif;
							?></span>
					</span>
					<span class="crm-item-cell-view"<?= $bInitEditable ? ' style="display: none;"' : '' ?>>
						<div id="<?=$rowID.'_DISCOUNT_v'?>" class="crm-item-table-txt"><?=$htmlValues['DISCOUNT']?></div><span class="crm-item-sale-text-wrap"><span class="crm-item-sale-text"><?=$htmlValues['DISCOUNT_TYPE_TEXT']?></span></span>
					</span>
				</td>
				<td class="crm-item-cell crm-item-sum-sale">
					<span class="crm-item-cell-text"<?= $bInitEditable ? '' : ' style="display: none;"' ?>>
						<input id="<?=$rowID.'_DISCOUNT_SUBTOTAL'?>" type="text" class="crm-item-table-inp" value="<?=$htmlValues['DISCOUNT_SUBTOTAL']?>"/>
					</span>
					<span class="crm-item-cell-view"<?= $bInitEditable ? ' style="display: none;"' : '' ?>>
						<div id="<?=$rowID.'_DISCOUNT_SUBTOTAL_v'?>" class="crm-item-table-txt"><?=$htmlValues['DISCOUNT_SUBTOTAL']?></div>
					</span>
				</td>
				<?if($arResult['ALLOW_TAX']):?>
				<td class="crm-item-cell crm-item-spacer"></td>
				<td class="crm-item-cell crm-item-tax">
					<span class="crm-item-cell-text"<?= $bInitEditable ? '' : ' style="display: none;"' ?>>
						<select id="<?=$rowID.'_TAX_RATE'?>" class="crm-item-table-select">
							<?php
							foreach ($htmlValues['TAX_RATES'] as $taxRate)
							{
								echo '<option value="'.htmlspecialcharsbx($taxRate).'"'.
									($taxRate === $htmlValues['TAX_RATE'] ? ' selected="selected"' : '').'>'.
									htmlspecialcharsbx($taxRate.'%').'</option>'.PHP_EOL;
							}
							unset($taxRate);
							?>
						</select>
					</span>
					<span class="crm-item-cell-view"<?= $bInitEditable ? ' style="display: none;"' : '' ?>>
						<div id="<?=$rowID.'_TAX_RATE_v'?>" class="crm-item-table-txtl"><?=htmlspecialcharsbx($htmlValues['TAX_RATE'].'%')?></div>
					</span>
				</td>
				<td class="crm-item-cell crm-item-tax-included">
					<span class="crm-item-cell-text"<?= $bInitEditable ? '' : ' style="display: none;"' ?>>
						<input id="<?=$rowID.'_TAX_INCLUDED'?>" type="checkbox"<?= $htmlValues['TAX_INCLUDED'] ? ' checked="checked"' : '' ?>/>
					</span>
					<span class="crm-item-cell-view"<?= $bInitEditable ? ' style="display: none;"' : '' ?>>
						<div id="<?=$rowID.'_TAX_INCLUDED_v'?>" class="crm-item-table-txt"><?=htmlspecialcharsbx($htmlValues['TAX_INCLUDED'] ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'))?></div>
					</span>
				</td>
				<td class="crm-item-cell crm-item-tax-sum">
					<span class="crm-item-cell-text"<?= $bInitEditable ? '' : ' style="display: none;"' ?>>
						<div id="<?=$rowID.'_TAX_SUM'?>" class="crm-item-table-txt">0.0</div>
					</span>
					<span class="crm-item-cell-view"<?= $bInitEditable ? ' style="display: none;"' : '' ?>>
						<div id="<?=$rowID.'_TAX_SUM_v'?>" class="crm-item-table-txt">0.0</div>
					</span>
				</td>
				<?endif;?>
				<td class="crm-item-cell crm-item-total">
					<span class="crm-item-cell-text"<?= $bInitEditable ? '' : ' style="display: none;"' ?>>
						<input id="<?=$rowID.'_SUM'?>" type="text" value="<?=$htmlValues['SUM']?>" class="crm-item-table-inp"/>
					</span>
					<span class="crm-item-cell-view"<?= $bInitEditable ? ' style="display: none;"' : '' ?>>
						<div id="<?=$rowID.'_SUM_v'?>" class="crm-item-table-txt"><?=$htmlValues['SUM']?></div>
					</span>
				</td>
				<td class="crm-item-cell crm-item-move"><span class="crm-item-del"<?= $bInitEditable ?  '' : ' style="display: none;"' ?>></span></td>
			</tr>
		<?}?>
		</tbody>
	</table>
	<?
	if ($enableCustomProducts):
	?><div class="crm-items-add-row-wrap"><a id="<?=$addRowBtnID?>" class="crm-items-add-row" href="#"<?= $readOnly ? ' style="display: none;"' : '' ?>><?=GetMessage('CRM_PRODUCT_ROW_ADD_ROW')?></a></div><?
	endif;    // if ($enableCustomProducts):
	?>
	<!-- example row -->
	<table style="display: none;">
		<tbody>
		<tr id="<?= ($rowIdPrefix.'#N#') ?>" style="display: none;">
			<td class="crm-item-cell crm-item-name">
					<span class="crm-item-cell-text">
						<span class="crm-table-name-left">
							<span class="crm-item-move-btn"></span><span id="<?= ($rowIdPrefix.'#N#_NUM') ?>" class="crm-item-num"></span>
						</span>
						<span class="crm-item-inp-wrap">
							<input id="<?= ($rowIdPrefix.'#N#_PRODUCT_NAME') ?>" class="crm-item-name-inp" type="text" value=""  autocomplete="off"/><span class="crm-item-inp-btn"></span>
						</span>
					</span>
					<span class="crm-item-cell-view">
						<span class="crm-table-name-left">
							<span class="crm-item-move-btn view-mode"></span><span id="<?= ($rowIdPrefix.'#N#_NUM_v') ?>" class="crm-item-num"></span>
						</span>
						<span class="crm-item-txt-wrap">
							<div id="<?= ($rowIdPrefix.'#N#_PRODUCT_NAME_v') ?>" class="crm-item-name-txt"></div>
						</span>
					</span>
			</td>
			<td class="crm-item-cell crm-item-price">
					<span class="crm-item-cell-text">
						<input id="<?= ($rowIdPrefix.'#N#_PRICE') ?>" type="text" class="crm-item-table-inp" value="0.00"/>
					</span>
					<span class="crm-item-cell-view">
						<div id="<?= ($rowIdPrefix.'#N#_PRICE_v') ?>" class="crm-item-table-txt">0.00</div>
					</span>
			</td>
			<td class="crm-item-cell crm-item-qua">
					<span class="crm-item-cell-text">
						<input id="<?= ($rowIdPrefix.'#N#_QUANTITY') ?>" type="text" class="crm-item-table-inp" value="0"/>
					</span>
					<span class="crm-item-cell-view">
						<div id="<?= ($rowIdPrefix.'#N#_QUANTITY_v') ?>" class="crm-item-table-txt">0</div>
					</span>
			</td>
			<td class="crm-item-cell crm-item-unit">
					<span class="crm-item-cell-text">
						<select id="<?= ($rowIdPrefix.'#N#_MEASURE') ?>" class="crm-item-table-select">
							<?php
							$selectedMeasureCode = null;
							if (is_array($defaultMeasure) && isset($defaultMeasure['CODE']))
							{
								$selectedMeasureCode = $defaultMeasure['CODE'];
							}
							else
							{
								if (is_array($measures) && count($measures) > 0 && isset($measures[0]['CODE']))
									$selectedMeasureCode = $measures[0]['CODE'];
							}
							foreach ($measures as $measure)
							{
								echo '<option value="'.htmlspecialcharsbx($measure['CODE']).'"'.
									($selectedMeasureCode !== null && $measure['CODE'] === $selectedMeasureCode ? ' selected="selected"' : '').'>'.
									htmlspecialcharsbx($measure['SYMBOL']).'</option>'.PHP_EOL;
							}
							unset($selectedMeasureCode, $measure);
							?>
						</select>
					</span>
					<span class="crm-item-cell-view">
						<div id="<?= ($rowIdPrefix.'#N#_MEASURE_v') ?>" class="crm-item-table-txtl"><?=$htmlValues['MEASURE_SELECTED_SYMBOL']?></div>
					</span>
			</td>
			<td class="crm-item-cell crm-item-sale">
					<span class="crm-item-cell-text">
						<input id="<?= ($rowIdPrefix.'#N#_DISCOUNT') ?>" type="text" class="crm-item-table-inp" value="0"/><span class="crm-item-sale-text-wrap"><?
							if ($arResult['INVOICE_MODE']) :
								?><span class="crm-item-sale-text">%</span><?
							else :
								?><a href="#" class="crm-item-sale-text">%</a><?
							endif;
							?></span>
					</span>
					<span class="crm-item-cell-view">
						<div id="<?= ($rowIdPrefix.'#N#_DISCOUNT_v') ?>" class="crm-item-table-txt">0</div><span class="crm-item-sale-text-wrap"><span class="crm-item-sale-text">%</span></span>
					</span>
			</td>
			<td class="crm-item-cell crm-item-sum-sale">
					<span class="crm-item-cell-text">
						<input id="<?= ($rowIdPrefix.'#N#_DISCOUNT_SUBTOTAL') ?>" type="text" class="crm-item-table-inp" value="0.00"/>
					</span>
					<span class="crm-item-cell-view">
						<div id="<?= ($rowIdPrefix.'#N#_DISCOUNT_SUBTOTAL_v') ?>" class="crm-item-table-txt">0.00</div>
					</span>
			</td>
			<?if($arResult['ALLOW_TAX']):?>
				<td class="crm-item-cell crm-item-spacer"></td>
				<td class="crm-item-cell crm-item-tax">
					<span class="crm-item-cell-text">
						<select id="<?= ($rowIdPrefix.'#N#_TAX_RATE') ?>" class="crm-item-table-select">
							<?php
							$taxRates = $taxRatesOrig;
							$taxRates[] = 0.00;
							$taxRates = array_unique($taxRates);
							sort($taxRates, SORT_NUMERIC);
							$selectedTaxValue = 0.00;
							if (is_array($defaultTax) && isset($defaultTax['VALUE']))
							{
								$selectedTaxValue = $defaultTax['VALUE'];
							}
							foreach ($taxRates as $taxRate)
							{
								echo '<option value="'.htmlspecialcharsbx($taxRate).'"'.
									($selectedTaxValue !== null && $taxRate === $selectedTaxValue ? ' selected="selected"' : '').'>'.
									htmlspecialcharsbx($taxRate.'%').'</option>'.PHP_EOL;
							}
							unset($selectedTaxValue, $taxRate);
							?>
						</select>
					</span>
					<span class="crm-item-cell-view">
						<div id="<?= ($rowIdPrefix.'#N#_TAX_RATE_v') ?>" class="crm-item-table-txtl"><?=htmlspecialcharsbx((isset($htmlValues['TAX_RATES'][0]) ? $htmlValues['TAX_RATES'][0] : 0).'%')?></div>
					</span>
				</td>
				<td class="crm-item-cell crm-item-tax-included">
					<span class="crm-item-cell-text">
						<input id="<?= ($rowIdPrefix.'#N#_TAX_INCLUDED') ?>" type="checkbox"/>
					</span>
					<span class="crm-item-cell-view">
						<div id="<?= ($rowIdPrefix.'#N#_TAX_INCLUDED_v') ?>" class="crm-item-table-txt"><?=htmlspecialcharsbx(GetMessage('MAIN_NO'))?></div>
					</span>
				</td>
				<td class="crm-item-cell crm-item-tax-sum">
					<span class="crm-item-cell-text">
						<div id="<?= ($rowIdPrefix.'#N#_TAX_SUM') ?>" class="crm-item-table-txt">0.00</div>
					</span>
					<span class="crm-item-cell-view">
						<div id="<?= ($rowIdPrefix.'#N#_TAX_SUM_v') ?>" class="crm-item-table-txt">0.00</div>
					</span>
				</td>
			<?endif;?>
			<td class="crm-item-cell crm-item-total">
					<span class="crm-item-cell-text">
						<input id="<?= ($rowIdPrefix.'#N#_SUM') ?>" type="text" value="0.00" class="crm-item-table-inp"/>
					</span>
					<span class="crm-item-cell-view">
						<div id="<?= ($rowIdPrefix.'#N#_SUM_v') ?>" class="crm-item-table-txt">0.00</div>
					</span>
			</td>
			<td class="crm-item-cell crm-item-move"><span class="crm-item-del"<?= $bInitEditable ?  '' : ' style="display: none;"' ?>></span></td>
		</tr>
		</tbody>
	</table><?
	$bShowDiscount = $arResult['ENABLE_DISCOUNT'];
	$bShowTax = (!$arResult['HIDE_ALL_TAXES'] && ($arResult['ALLOW_LD_TAX'] || ($arResult['ALLOW_TAX'] && $arResult['ENABLE_TAX'])));
	$bDiscountExists = false;
	$bTaxExists = false;
	?>
	<div id="<?=$productTotalContainerID?>" class="crm-view-table-total" style="<?= $nProductRows === 0 ? 'display: none;' : '' ?>">
		<div class="crm-view-table-total-inner">
			<table><tbody>
				<tr class="crm-view-table-total-value"<?= $bShowDiscount ? '' : ' style="display: none;"' ?>>
					<td><nobr><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_TOTAL_BEFORE_DISCOUNT'))?>:</nobr></td>
					<td><?$productEditorCfg['TOTAL_BEFORE_DISCOUNT_ID'] = $arResult['PREFIX'].'_total_before_discount';?>
						<strong id="<?=htmlspecialcharsbx($productEditorCfg['TOTAL_BEFORE_DISCOUNT_ID'])?>" class="crm-view-table-total-value"><?=CCrmCurrency::MoneyToString($arResult['TOTAL_BEFORE_DISCOUNT'], $arResult['CURRENCY_ID'])?></strong>
					</td>
				</tr>
				<tr class="crm-view-table-total-value"<?= $bShowDiscount ? '' : ' style="display: none;"' ?>>
					<td><nobr><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_TOTAL_DISCOUNT'))?>:</nobr></td>
					<td><?
						$productEditorCfg['TOTAL_DISCOUNT_ID'] = $arResult['PREFIX'].'_total_discount';
						if (round(doubleval($arResult['TOTAL_DISCOUNT']), 2) !== 0.0)
							$bDiscountExists = true;
						?>
						<strong id="<?=htmlspecialcharsbx($productEditorCfg['TOTAL_DISCOUNT_ID'])?>" class="crm-view-table-total-value"><?=CCrmCurrency::MoneyToString($arResult['TOTAL_DISCOUNT'], $arResult['CURRENCY_ID'])?></strong>
					</td>
				</tr><?
				$productEditorTaxList = array();
				if ($arResult['ALLOW_TAX'] || $arResult['ALLOW_LD_TAX']):
				?>
				<tr class="crm-view-table-total-value"<?= $bShowTax ? '' : ' style="display: none;"' ?>>
					<td><nobr><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_TOTAL_BEFORE_TAX'))?>:</nobr></td>
					<td><?$productEditorCfg['TOTAL_BEFORE_TAX_ID'] = $arResult['PREFIX'].'_total_before_tax';?>
						<strong id="<?=htmlspecialcharsbx($productEditorCfg['TOTAL_BEFORE_TAX_ID'])?>" class="crm-view-table-total-value"><?=CCrmCurrency::MoneyToString($arResult['TOTAL_BEFORE_TAX'], $arResult['CURRENCY_ID'])?></strong>
					</td>
				</tr>
				<?
				endif;
				if($arResult['ALLOW_TAX']):
					$productEditorTaxList[] = array(
						'TAX_NAME' => GetMessage('CRM_PRODUCT_TOTAL_BEFORE_TAX'),
						'TAX_VALUE' => CCrmCurrency::MoneyToString($arResult['TOTAL_BEFORE_TAX'], $arResult['CURRENCY_ID'])
					);
					if (round(doubleval($arResult['TOTAL_TAX']), 2) !== 0.0)
						$bTaxExists = true;
					?>
				<tr class="crm-view-table-total-value crm-tax-value"<?= $bShowTax ? '' : ' style="display: none;"' ?>>
					<td><nobr><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_TOTAL_TAX'))?>:</nobr></td>
					<td>
						<strong id="<?=htmlspecialcharsbx($productEditorCfg['taxValueID'])?>" class="crm-view-table-total-value"><?=CCrmCurrency::MoneyToString($arResult['TOTAL_TAX'], $arResult['CURRENCY_ID'])?></strong>
					</td>
				</tr><?
				elseif ($arResult['ALLOW_LD_TAX']):
					$taxList = isset($arResult['TAX_LIST']) ? $arResult['TAX_LIST'] : array();
					if (!is_array($arResult['TAX_LIST']) || count($arResult['TAX_LIST']) === 0)
					{
						$taxList = array(
							array(
								'NAME' => GetMessage('CRM_PRODUCT_TOTAL_TAX'),
								'TAX_VALUE' => CCrmCurrency::MoneyToString(0.0, $arResult['CURRENCY_ID'])
							)
						);
					}
					$i = 0;
					foreach ($taxList as $taxInfo):
					$productEditorTaxList[] = array(
						'TAX_NAME' => sprintf(
							"%s%s%s",
							($taxInfo["IS_IN_PRICE"] == "Y") ? GetMessage('CRM_PRODUCT_TAX_INCLUDING')." " : "",
							$taxInfo["NAME"],
							(/*$vat <= 0 &&*/ $taxInfo["IS_PERCENT"] == "Y")
								? sprintf(' (%s%%)', roundEx($taxInfo["VALUE"], $arResult['TAX_LIST_PERCENT_PRECISION']))
								: ""
						),
						'TAX_VALUE' => CCrmCurrency::MoneyToString(
								$taxInfo['VALUE_MONEY'], $arResult['CURRENCY_ID']
							)
					);
					if (round(doubleval($taxInfo['VALUE_MONEY']), 2) !== 0.0)
						$bTaxExists = true;

					?>
				<tr class="crm-view-table-total-value crm-tax-value"<?= $bShowTax ? '' : ' style="display: none;"' ?>>
					<td><nobr><?= htmlspecialcharsbx($productEditorTaxList[$i]['TAX_NAME']) ?>:</nobr></td>
					<td>
						<strong <?php echo ($i === 0) ? 'id="'.htmlspecialcharsbx($productEditorCfg['taxValueID']).'" ' : ''; ?>class="crm-view-table-total-value"><?= CCrmCurrency::MoneyToString($taxInfo['VALUE_MONEY'], $arResult['CURRENCY_ID']) ?></strong>
					</td>
				</tr><?
					$i++;
					endforeach;
					$productEditorCfg['LDTaxes'] = $productEditorTaxList;
					if(isset($arResult['TAX_LIST_PERCENT_PRECISION']))
						$productEditorCfg['taxListPercentPrecision'] = $arResult['TAX_LIST_PERCENT_PRECISION'];
				endif;?>
				<tr class="crm-view-table-total-value">
					<td><nobr><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_SUM_TOTAL'))?>:</nobr></td>
					<td>
						<?$productEditorCfg['SUM_TOTAL_ID'] = $arResult['PREFIX'].'_sum_total';?>
						<strong id="<?=htmlspecialcharsbx($productEditorCfg['SUM_TOTAL_ID'])?>" class="crm-view-table-total-value"><?=CCrmCurrency::MoneyToString($arResult['TOTAL_SUM'], $arResult['CURRENCY_ID'])?></strong>
					</td>
				</tr>
				<?
				$productEditorCfg['_discountExistsInit'] = $bDiscountExists;
				$productEditorCfg['_taxExistsInit'] = $bTaxExists;
				?>
			</tbody></table>
		</div>
	</div>
	<input type="hidden" name="<?=htmlspecialcharsbx($arResult['PRODUCT_DATA_FIELD_NAME'])?>" value="" />
	<input type="hidden" name="<?=htmlspecialcharsbx($arResult['PRODUCT_DATA_FIELD_NAME'].'_SETTINGS')?>" value="" />
</div>
<script type="text/javascript">
BX.CrmProductEditorMessages =
{
	editButtonTitle: "<?= GetMessageJS('CRM_EDIT_BTN_TTL')?>",
	deleteButtonTitle: "<?= GetMessageJS('CRM_DEL_BTN_TTL')?>",
	deletionConfirm: "<?= GetMessageJS('CRM_PRODUCT_ROW_DELETION_CONFIRM')?>",
	addCustomProductDlgTitle: "<?=GetMessageJS('CRM_ADD_CUSTOM_PRODUCT_DLG_TTL')?>",
	crmProductRowBtnEdit: "<?=GetMessageJS('CRM_PRODUCT_ROW_BTN_EDIT')?>",
	crmProductRowBtnAdd: "<?=GetMessageJS('CRM_PRODUCT_ROW_BTN_ADD')?>",
	crmProductRowBtnEditF: "<?=GetMessageJS('CRM_PRODUCT_ROW_BTN_EDIT_F')?>",
	yes: "<?=GetMessageJS('MAIN_YES')?>",
	no: "<?=GetMessageJS('MAIN_NO')?>",
	saving: "<?=GetMessageJS('CRM_PRODUCT_ROW_SAVING')?>",
	priceTitleText: "<?=GetMessageJS('CRM_PRODUCT_ROW_COL_TTL_PRICE')?>",
	openProductCard: "<?=GetMessageJS('CRM_PRODUCT_ROW_OPEN_PRODUCT_CARD')?>",
	createProduct: "<?=GetMessageJS('CRM_PRODUCT_ROW_CREATE_PRODUCT')?>",
	productSearchDialogTitle: "<?=GetMessageJS('CRM_PRODUCT_SEARCH_DLG_TITLE')?>"
};

BX.CrmProductEditorErrors =
{
	"PERMISSION_DENIED": "<?= GetMessageJS('CRM_PERMISSION_DENIED_ERROR')?>",
	"INVALID_REQUEST_ERROR": "<?= GetMessageJS('CRM_INVALID_REQUEST_ERROR')?>",
	"CUSTOM_PRODUCT_NAME_NOT_ASSIGNED": "<?= GetMessageJS('CRM_CUSTOM_PRODUCT_NAME_NOT_ASSIGNED_ERROR')?>"
};

BX.ready(
	function()
	{
		var editor = BX.CrmProductEditor.create(
			"<?=$arResult['ID']?>",
			<?=CUtil::PhpToJSObject($productEditorCfg)?>
		);

		var dlgID = CRM.Set(
			BX("<?=CUtil::JSEscape($choiceProductBtnID)?>"),
			"<?=CUtil::JSEscape($choiceProductBtnID)?>",
			"",
			<?=CUtil::PhpToJsObject(CCrmProductHelper::PreparePopupItems($arResult['CURRENCY_ID'], 50, $arResult['ENABLE_RAW_CATALOG_PRICING']))?>,
			false,
			false,
			["product"],
			{
				ok: "<?=htmlspecialcharsbx(GetMessageJS('CRM_FF_OK'))?>",
				cancel: "<?=htmlspecialcharsbx(GetMessageJS('CRM_FF_CANCEL'))?>",
				close: "<?=htmlspecialcharsbx(GetMessageJS('CRM_FF_CLOSE'))?>",
				wait: "<?=htmlspecialcharsbx(GetMessageJS('CRM_FF_SEARCH'))?>",
				noresult: "<?=htmlspecialcharsbx(GetMessageJS('CRM_FF_NO_RESULT'))?>",
				add : "<?=htmlspecialcharsbx(GetMessageJS('CRM_FF_CHOISE_2'))?>",
				edit : "<?=htmlspecialcharsbx(GetMessageJS('CRM_FF_CHANGE'))?>",
				search : "<?=htmlspecialcharsbx(GetMessageJS('CRM_FF_SEARCH'))?>",
				last : "<?=htmlspecialcharsbx(GetMessageJS('CRM_FF_LAST'))?>"
			},
			true
		);
		if(typeof(obCrm[dlgID]) !== "undefined")
		{
			obCrm[dlgID].AddOnSaveListener(BX.delegate(editor.handleProductChoice, editor));
			obCrm[dlgID].AddOnBeforeSearchListener(BX.delegate(editor.handleBeforeSearch, editor));

			editor.registerProductDialogId(dlgID);
		}

		/*if (editor._settings['enableDiscount'])
		{
			var sale = BX('crm-top-sale-checkbox');
			if (sale)
				BX.CrmProductEditor.onAddColumn.apply(sale, [editor, true]);
		}
		if (editor._settings['enableTax'])
		{
			var tax = BX('crm-top-tax-checkbox');
			if (tax)
				BX.CrmProductEditor.onAddColumn.apply(tax, [editor, true]);
		}*/
		BX.bind(BX('crm-top-tax-checkbox'), 'click', function(){BX.CrmProductEditor.arrangeColumns.apply(this, [editor]);});
		BX.bind(BX('crm-top-sale-checkbox'), 'click', function(){BX.CrmProductEditor.arrangeColumns.apply(this, [editor]);});
	}
);

BX.namespace("BX.Crm");
BX.Crm["<?=$jsEventsManagerId?>"] = BX.Crm.PageEventsManagerClass.create({id: "<?=$arResult['COMPONENT_ID']?>"});

</script>