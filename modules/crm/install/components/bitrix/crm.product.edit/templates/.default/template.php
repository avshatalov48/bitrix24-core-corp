<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

global $APPLICATION;

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

// Product properties
foreach($arResult['PROPS'] as $propID => $arProp)
{
	if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
		&& !array_key_exists($arProp['USER_TYPE'], $arResult['PROP_USER_TYPES'])
	)
		continue;

	if (isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
		&& is_array($arResult['PROP_USER_TYPES'][$arProp['USER_TYPE']])
		&& $arProp['MULTIPLE'] == 'Y'
		&& array_key_exists('GetPublicEditHTMLMulty', $arResult['PROP_USER_TYPES'][$arProp['USER_TYPE']])
	)
	{
		$arProp['PROPERTY_USER_TYPE'] = $arResult['PROP_USER_TYPES'][$arProp['USER_TYPE']];
		$html = call_user_func_array($arResult['PROP_USER_TYPES'][$arProp['USER_TYPE']]['GetPublicEditHTMLMulty'],
			array(
				$arProp,
				$arResult['PROPS_FORM_DATA']['~'.$propID],
				array(
					'VALUE' => $propID,
					'DESCRIPTION' => '',
					'FORM_NAME' => 'form_'.$arResult['FORM_ID'],
					'MODE' => 'FORM_FILL',
				),
			));

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => $propID,
			'name' => $arProp['NAME'],
			'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
			'type' => 'custom',
			'value' => $html,
			'isTactile' => true
		);
	}
	else if (
		isset($arProp['USER_TYPE']) && !empty($arProp['USER_TYPE'])
		&& is_array($arResult['PROP_USER_TYPES'][$arProp['USER_TYPE']])
		&& array_key_exists('GetPublicEditHTML', $arResult['PROP_USER_TYPES'][$arProp['USER_TYPE']])
	)
	{
		$arProp['PROPERTY_USER_TYPE'] = $arResult['PROP_USER_TYPES'][$arProp['USER_TYPE']];
		if ($arProp['MULTIPLE'] == 'Y')
		{
			$html = '<table id="tbl'.$propID.'" style="width: 100%;">';
			foreach($arResult['PROPS_FORM_DATA']['~'.$propID] as $key => $value)
			{
				$html .= '<tr><td>'.call_user_func_array($arResult['PROP_USER_TYPES'][$arProp['USER_TYPE']]['GetPublicEditHTML'],
						array(
							$arProp,
							$value,
							array(
								'VALUE' => $propID.'['.$key.'][VALUE]',
								'DESCRIPTION' => '',
								'FORM_NAME' => 'form_'.$arResult['FORM_ID'],
								'MODE' => 'FORM_FILL',
								'COPY' => $arResult['COPY_ID'] > 0,
							),
						)).'</td></tr>';
			}
			$html .= '</table>';
			if ($arProp['USER_TYPE'] !== 'HTML')
				$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$propID.'\')" value="'.GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON').'">';

			$arResult['FIELDS']['tab_1'][] = array(
				'id' => $propID,
				'name' => $arProp['NAME'],
				'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
				'type' => 'custom',
				'value' => $html,
				'isTactile' => true
			);
		}
		else
		{
			foreach($arResult['PROPS_FORM_DATA']['~'.$propID] as $key => $value)
			{
				$html = call_user_func_array($arResult['PROP_USER_TYPES'][$arProp['USER_TYPE']]['GetPublicEditHTML'],
					array(
						$arProp,
						$value,
						array(
							'VALUE' => $propID.'['.$key.'][VALUE]',
							'DESCRIPTION' => '',
							'FORM_NAME' => 'form_'.$arResult['FORM_ID'],
							'MODE' => 'FORM_FILL',
						),
					));
				break;
			}

			$arResult['FIELDS']['tab_1'][] = array(
				'id' => $propID,
				'name' => $arProp['NAME'],
				'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
				'type' => 'custom',
				'value' => $html,
				'isTactile' => true
			);
		}
	}
	else if ($arProp['PROPERTY_TYPE'] == 'N')
	{
		if ($arProp['MULTIPLE'] == 'Y')
		{
			$html = '<table id="tbl'.$propID.'" style="width: 100%;">';
			foreach($arResult['PROPS_FORM_DATA'][$propID] as $key => $value)
				$html .= '<tr><td><input type="text" name="'.$propID.'['.$key.'][VALUE]" value="'.$value['VALUE'].'"></td></tr>';
			$html .= '</table>';
			$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$propID.'\')" value="'.GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON').'">';
		}
		else
		{
			foreach($arResult['PROPS_FORM_DATA'][$propID] as $key => $value)
				$html = '<input type="text" name="'.$propID.'['.$key.'][VALUE]" value="'.$value['VALUE'].'">';
		}

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => $propID,
			'name' => $arProp['NAME'],
			'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
			'type' => 'custom',
			'value' => $html,
			'isTactile' => true
		);
	}
	else if ($arProp['PROPERTY_TYPE'] == 'S')
	{
		$nCols = intval($arProp['COL_COUNT']);
		$nCols = ($nCols > 100) ? 100 : $nCols;
		if ($arProp['MULTIPLE'] == 'Y')
		{
			$html = '<table id="tbl'.$propID.'" style="width: 100%;">';
			if ($arProp['ROW_COUNT'] > 1)
			{
				foreach($arResult['PROPS_FORM_DATA'][$propID] as $key => $value)
				{
					$html .= '<tr><td><textarea name="'.$propID.'['.$key.'][VALUE]" rows="'.intval($arProp['ROW_COUNT']).'" cols="'.$nCols.'">'.$value['VALUE'].'</textarea></td></tr>';
				}
			}
			else
			{
				foreach($arResult['PROPS_FORM_DATA'][$propID] as $key => $value)
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
				foreach($arResult['PROPS_FORM_DATA'][$propID] as $key => $value)
				{
					$html = '<textarea name="'.$propID.'['.$key.'][VALUE]" rows="'.intval($arProp['ROW_COUNT']).'" cols="'.$nCols.'">'.$value['VALUE'].'</textarea>';
				}
			}
			else
			{
				foreach($arResult['PROPS_FORM_DATA'][$propID] as $key => $value)
				{
					$html = '<input type="text" name="'.$propID.'['.$key.'][VALUE]" value="'.$value['VALUE'].'" size="'.$nCols.'">';
				}
			}
		}
		unset($nCols);

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => $propID,
			'name' => $arProp['NAME'],
			'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
			'type' => 'custom',
			'value' => $html,
			'isTactile' => true
		);
	}
	else if ($arProp['PROPERTY_TYPE'] == 'L')
	{
		$items = array('' => GetMessage('CRM_PRODUCT_PROP_NO_VALUE'));
		$prop_enums = CIBlockProperty::GetPropertyEnum($arProp['ID']);
		while($ar_enum = $prop_enums->Fetch())
			$items[$ar_enum['ID']] = $ar_enum['VALUE'];

		$rowCount = 5;
		if (isset($arProp['ROW_COUNT']) && intval($arProp['ROW_COUNT']) > 0)
			$rowCount = intval($arProp['ROW_COUNT']);
		if ($arProp['MULTIPLE'] == 'Y')
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => $propID.'[]',
				'name' => $arProp['NAME'],
				'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
				'type' => 'list',
				'items' => $items,
				'value' => $arResult['PROPS_FORM_DATA'][$propID],
				'params' => array('size' => $rowCount, 'multiple' => 'multiple'),
				'isTactile' => true
			);
		}
		else
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => $propID,
				'name' => $arProp['NAME'],
				'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
				'type' => 'list',
				'items' => $items,
				'value' => $arResult['PROPS_FORM_DATA'][$propID],
				'isTactile' => true
			);
		}
	}
	else if ($arProp['PROPERTY_TYPE'] == 'F')
	{
		if ($arProp['MULTIPLE'] == 'Y')
		{
			$html = '<table id="tbl'.$propID.'" style="width: 100%;">';
			foreach($arResult['PROPS_FORM_DATA'][$propID] as $key => $value)
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
					'a_title' => GetMessage('CRM_PRODUCT_PROP_ENLARGE'),
					'download_text' => GetMessage('CRM_PRODUCT_PROP_DOWNLOAD'),
				));

				$html .= '</td></tr>';
			}
			$html .= '</table>';
			$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$propID.'\')" value="'.GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON').'">';

			$arResult['FIELDS']['tab_1'][] = array(
				'id' => $propID,
				'name' => $arProp['NAME'],
				'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
				'type' => 'custom',
				'value' => $html,
				'isTactile' => true
			);
		}
		else
		{
			foreach($arResult['PROPS_FORM_DATA'][$propID] as $key => $value)
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
					'a_title' => GetMessage('CRM_PRODUCT_PROP_ENLARGE'),
					'download_text' => GetMessage('CRM_PRODUCT_PROP_DOWNLOAD'),
				));


				$arResult['FIELDS']['tab_1'][] = array(
					'id' => $propID,
					'name' => $arProp['NAME'],
					'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
					'type' => 'custom',
					'value' => $html,
					'isTactile' => true
				);
			}
		}
	}
	else if ($arProp['PROPERTY_TYPE'] == 'G')
	{
		if ($arProp['IS_REQUIRED']=='Y')
			$items = array();
		else
			$items = array('' => GetMessage('CRM_PRODUCT_PROP_NO_VALUE'));

		$rsSections = CIBlockSection::GetTreeList(Array('IBLOCK_ID' => $arProp['LINK_IBLOCK_ID']));
		while($ar = $rsSections->GetNext())
			$items[$ar['ID']] = str_repeat(' . ', $ar['DEPTH_LEVEL']).$ar['~NAME'];

		if ($arProp['MULTIPLE'] == 'Y')
			$params = array('size' => 5, 'multiple' => 'multiple');
		else
			$params = array();

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => $propID.'[]',
			'name' => $arProp['NAME'],
			'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
			'type' => 'list',
			'items' => $items,
			'value' => $arResult['PROPS_FORM_DATA'][$propID],
			'params' => $params,
			'isTactile' => true
		);
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
		if (is_array($arResult['PROPS_FORM_DATA'][$propID]))
		{
			foreach($arResult['PROPS_FORM_DATA'][$propID] as $element_id)
				if ($element_id > 0 && array_key_exists($element_id, $items))
					$arValues[] = $items[$element_id].' ['.$element_id.']';
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
		$arResult['FIELDS']['tab_1'][] = array(
			'id' => $propID,
			'name' => $arProp['NAME'],
			'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
			'type' => 'custom',
			'value' => $html,
			'isTactile' => true
		);

	}
	else if ($arProp['MULTIPLE'] == 'Y')
	{
		$html = '<table id="tbl'.$propID.'" style="width: 100%;">';
		foreach($arResult['PROPS_FORM_DATA'][$propID] as $key => $value)
			$html .= '<tr><td><input type="text" name="'.$propID.'['.$key.'][VALUE]" value="'.$value['VALUE'].'"></td></tr>';
		$html .= '</table>';
		$html .= '<input type="button" onclick="addNewTableRow(\'tbl'.$propID.'\')" value="'.GetMessage('CRM_PRODUCT_PROP_ADD_BUTTON').'">';

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => $propID,
			'name' => $arProp['NAME'],
			'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
			'type' => 'custom',
			'value' => $html,
			'isTactile' => true
		);
	}
	else if (is_array($arResult['PROPS_FORM_DATA'][$propID]) && array_key_exists('VALUE', $arResult['PROPS_FORM_DATA'][$propID]))
	{
		$arResult['FIELDS']['tab_1'][] = array(
			'id' => $propID.'[VALUE]',
			'name' => $arProp['NAME'],
			'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
			'type' => 'text',
			'value' => $arResult['PROPS_FORM_DATA'][$propID]['VALUE'],
			'isTactile' => true
		);
	}
	else
	{
		$arResult['FIELDS']['tab_1'][] = array(
			'id' => $propID,
			'name' => $arProp['NAME'],
			'required' => $arProp['IS_REQUIRED']=='Y'? true: false,
			'type' => 'text',
			'isTactile' => true
		);
	}
}

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_1']
);

$arResult['CRM_CUSTOM_PAGE_TITLE'] =
	$arResult['PRODUCT_ID'] > 0
		? GetMessage('CRM_PRODUCT_NAV_TITLE_EDIT', array('#NAME#' => $arResult['PRODUCT']['NAME']))
		: GetMessage('CRM_PRODUCT_NAV_TITLE_ADD');

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);
$formCustomHtml = '<input type="hidden" name="product_id" value="'.$arResult['PRODUCT_ID'].'"/>'.
	'<input type="hidden" name="list_section_id" value="'.$arResult['LIST_SECTION_ID'].'"/>';
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'THEME_GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' => true,
			'back_url' => $arResult['BACK_URL'],
			'custom_html' => $formCustomHtml
		),
		'IS_NEW' => $arResult['PRODUCT_ID'] <= 0,
		'TITLE' => $arResult['CRM_CUSTOM_PAGE_TITLE'],
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'ENABLE_USER_FIELD_CREATION' => 'N',
		'DATA' => $arResult['PRODUCT'],
		'SHOW_SETTINGS' => 'Y',
		'SHOW_FORM_TAG' => 'Y'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
?>