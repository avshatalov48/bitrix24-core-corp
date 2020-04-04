<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

	$arResult['ENTITY_ID'] 	= $arParams['ENTITY_ID'];
	$arResult['ELEMENT_ID'] = IntVal($arParams['ELEMENT_ID']);
	$arResult['TYPE_ID'] 	= $arParams['TYPE_ID'];
	$arResult['READ_ONLY'] = isset($arParams['READ_ONLY']) ? $arParams['READ_ONLY'] : false;

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

	$this->IncludeComponentTemplate();
?>