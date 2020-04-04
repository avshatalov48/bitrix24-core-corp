<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

	if (!CModule::IncludeModule('crm'))
	{
		ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
		return;
	}

	$arResult['ENTITY_ID'] 	= isset($arParams['ENTITY_ID']) ? $arParams['ENTITY_ID'] : '';
	$arResult['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
	$arResult['TYPE_ID'] = isset($arParams['TYPE_ID']) ? strval($arParams['TYPE_ID']) : '';
	$arResult['FM_MNEMONIC'] = 'FM';

	if (isset($arParams['FM_MNEMONIC']) && !empty($arParams['FM_MNEMONIC']))
		$arResult['FM_MNEMONIC'] = $arParams['FM_MNEMONIC'];

	if($arResult['TYPE_ID'] === '')
	{
		ShowError(GetMessage('CRM_FIELD_MULTI_EDIT_TYPE_ID_NOT_DEFINED'));
		return;
	}

	$arResult['SKIP_VALUES'] = Array();
	if (isset($arParams['SKIP_VALUES']) && !empty($arParams['SKIP_VALUES']))
		$arResult['SKIP_VALUES'] = $arParams['SKIP_VALUES'];

	$ar = CCrmFieldMulti::GetEntityTypeList($arResult['TYPE_ID'], false);
	foreach($ar as $valueType => $value)
	{
		if (in_array($valueType, $arResult['SKIP_VALUES']))
			continue;

		$arResult['TYPE_BOX']['REFERENCE'][] = $value;
		$arResult['TYPE_BOX']['REFERENCE_ID'][] = $valueType;
	}

	$arResult['VALUES'] = Array();
	if (isset($arParams['VALUES'][$arParams['TYPE_ID']])
	&& !empty($arParams['VALUES'][$arParams['TYPE_ID']]))
	{
		foreach ($arParams['VALUES'][$arParams['TYPE_ID']] as $ID => $arValue)
		{
			if ((substr($ID, 0, 1) == 'n') && $arValue['VALUE'] == '')
				continue;
			$arResult['VALUES'][$ID]['ID'] = $ID;
			$arResult['VALUES'][$ID]['VALUE'] = $arValue['VALUE'];
			$arResult['VALUES'][$ID]['VALUE_TYPE'] = $arValue['VALUE_TYPE'];
			$arResult['VALUES'][$ID]['COMPLEX_ID'] = $arParams['TYPE_ID'].'_'.$arValue['VALUE_TYPE'];
			$arResult['VALUES'][$ID]['COMPLEX_NAME'] = CCrmFieldMulti::GetEntityNameByComplex($arParams['TYPE_ID'].'_'.$arValue['VALUE_TYPE']);
			$arResult['VALUES'][$ID]['TEMPLATE'] =  CCrmFieldMulti::GetTemplateByComplex($arParams['TYPE_ID'].'_'.$arValue['VALUE_TYPE'], $arValue['VALUE']);
		}
	}
	elseif ($arResult['ELEMENT_ID'] > 0)
	{
		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => $arResult['ENTITY_ID'], 'ELEMENT_ID' => $arResult['ELEMENT_ID'], 'TYPE_ID' =>  $arResult['TYPE_ID'])
		);
		while($ar = $res->Fetch())
		{
			$arResult['VALUES'][$ar['ID']]['ID'] = $ar['ID'];
			$arResult['VALUES'][$ar['ID']]['VALUE'] = $ar['VALUE'];
			$arResult['VALUES'][$ar['ID']]['VALUE_TYPE'] = $ar['VALUE_TYPE'];
			$arResult['VALUES'][$ar['ID']]['COMPLEX_ID'] = $ar['COMPLEX_ID'];
			$arResult['VALUES'][$ar['ID']]['COMPLEX_NAME'] = CCrmFieldMulti::GetEntityNameByComplex($ar['COMPLEX_ID']);
			$arResult['VALUES'][$ar['ID']]['TEMPLATE'] =  CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);
		}
	}

	if(isset($arParams['EDITOR_ID']) && is_string($arParams['EDITOR_ID']) && $arParams['EDITOR_ID'] !== '')
	{
		$arResult['EDITOR_ID'] = $arParams['EDITOR_ID'];
	}
	$this->IncludeComponentTemplate();
?>
