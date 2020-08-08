<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/*$file = trim(preg_replace("'[\\\\/]+'", '/', (dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/result_modifier.php')));
__IncludeLang($file);*/
IncludeModuleLangFile(__FILE__);

$isInExportMode = (isset($arResult['IS_EXPORT_MODE']) && $arResult['IS_EXPORT_MODE'] === 'Y');
$exportType = isset($arResult['EXPORT_TYPE']) ? $arResult['EXPORT_TYPE'] : '';

$arArrays = array();
$arElements = array();
$arSections = array();
$arCrmElements = array();

foreach ($arResult['PROPERTY_VALUES'] as $productID => $arProperties)
{
	foreach ($arProperties as $propID => $propValue)
	{
		$arProp = $arResult['PROPS'][$propID];

		if ($arProp['PROPERTY_TYPE'] == 'F')
		{
			if (is_array($propValue))
			{
				foreach ($propValue as $valueKey => $file)
				{
					if ($isInExportMode)
					{
						$productFile = new CCrmProductFile($productID, $propID, (int)$file);
						$propValue[$valueKey] = $productFile->GetPublicLink(
							[
								/*'url_template' => $arParams['~PATH_TO_PRODUCT_FILE'],*/
								'url_params' => [/*'download' => 'y'*/]
							]
						);
						unset($productFile);
					}
					else
					{
						$obFile = new CCrmProductFile(
							$productID,
							$propID,
							$file
						);

						$obFileControl = new CCrmProductFileControl($obFile, $propID);

						$propValue[$valueKey] = '<nobr>'.$obFileControl->GetHTML(
							array(
								'show_input' => false,
								'max_size' => 102400,
								'max_width' => 50,
								'max_height' => 50,
								'url_template' => $arParams['~PATH_TO_PRODUCT_FILE'],
								'a_title' => GetMessage('CRM_PRODUCT_PROP_ENLARGE'),
								'download_text' => GetMessage('CRM_PRODUCT_PROP_DOWNLOAD'),
							)
						).'</nobr>';
					}
				}
			}
			else
			{
				if ($isInExportMode)
				{
					$productFile = new CCrmProductFile($productID, $propID, (int)$propValue);
					$propValue = $productFile->GetPublicLink(
						[
							'url_template' => $arParams['~PATH_TO_PRODUCT_FILE'],
							'url_params' => [/*'download' => 'y'*/]
						]
					);
					unset($productFile);
				}
				else
				{
					$obFile = new CCrmProductFile(
						$productID,
						$propID,
						$propValue
					);

					$obFileControl = new CCrmProductFileControl($obFile, $propID);

					$propValue = '<nobr>'.$obFileControl->GetHTML(
						array(
							'show_input' => false,
							'max_size' => 102400,
							'max_width' => 50,
							'max_height' => 50,
							'url_template' => $arParams['~PATH_TO_PRODUCT_FILE'],
							'a_title' => GetMessage('CRM_PRODUCT_PROP_ENLARGE'),
							'download_text' => GetMessage('CRM_PRODUCT_PROP_DOWNLOAD'),
						)
					).'</nobr>';
				}
			}
		}
		else if ($arProp['PROPERTY_TYPE'] == 'E')
		{
			if (is_array($propValue))
			{
				foreach ($propValue as $valueKey => $id)
				{
					if ($id > 0)
						$arElements[] = &$arResult['PROPERTY_VALUES'][$productID][$propID][$valueKey];
				}
				$arArrays[$productID.'_'.$propID] = &$arResult['PROPERTY_VALUES'][$productID][$propID];
			}
			else if ($propValue > 0)
			{
				$arElements[] = &$arResult['PROPERTY_VALUES'][$productID][$propID];
			}
			continue;
		}
		else if ($arProp['PROPERTY_TYPE'] == 'G')
		{
			if (is_array($propValue))
			{
				foreach ($propValue as $valueKey => $id)
				{
					if ($id > 0)
						$arSections[] = &$arResult['PROPERTY_VALUES'][$productID][$propID][$valueKey];
				}
				$arArrays[$productID.'_'.$propID] = &$arResult['PROPERTY_VALUES'][$productID][$propID];
			}
			else if ($propValue > 0)
			{
				$arSections[] = &$arResult['PROPERTY_VALUES'][$productID][$propID];
			}
			continue;
		}
		else if ($isInExportMode && $exportType === 'csv' && $arProp['PROPERTY_TYPE'] === 'S'
			&& isset($arProp['USER_TYPE']) && $arProp['USER_TYPE'] === 'ECrm')
		{
			$isStringValue = (is_string($propValue) && $propValue <> '');
			if ($isStringValue || is_array($propValue))
			{
				if ($isStringValue)
				{
					$newPropValue = explode(',', $propValue);
					if (is_array($newPropValue))
					{
						$arResult['PROPERTY_VALUES'][$productID][$propID] = $propValue = $newPropValue;
					}
					else
					{
						unset($arResult['PROPERTY_VALUES'][$productID][$propID]);
					}
					unset($newPropValue);
				}

				$ownerPrefixList = array('L', 'C', 'CO', 'D');
				$regExp = '/('.implode('|', $ownerPrefixList).')_(\d+)/'.BX_UTF_PCRE_MODIFIER;
				foreach ($propValue as $valueKey => $value)
				{
					$matches = [];
					if (preg_match($regExp, $value, $matches))
					{
						$type = $matches[1];
						$id = (int)$matches[2];
						if ($id > 0)
						{
							if (!is_array($arCrmElements[$type]))
							{
								$arCrmElements[$type] = [];
							}
							$arResult['PROPERTY_VALUES'][$productID][$propID][$valueKey] = $id;
							$arCrmElements[$type][] = &$arResult['PROPERTY_VALUES'][$productID][$propID][$valueKey];
						}
					}
				}
				unset($ownerPrefixList, $regExp, $valueKey, $value, $matches);
			}
			unset($isStringValue);
			continue;
		}

		$arResult['PROPERTY_VALUES'][$productID][$propID] = $propValue;

		if (is_array($propValue))
		{
			if (count($propValue) > 1)
				$arArrays[$productID.'_'.$propID] = &$arResult['PROPERTY_VALUES'][$productID][$propID];
			else
				$arResult['PROPERTY_VALUES'][$productID][$propID] = $propValue[0];
		}
	}
}

if (count($arElements))
{
	$rsElements = CIBlockElement::GetList(array(), array('=ID' => $arElements), false, false, array('ID', 'NAME', 'DETAIL_PAGE_URL'));
	$arr = array();
	while($ar = $rsElements->GetNext())
		$arr[$ar['ID']] = $ar['NAME'];

	foreach ($arElements as $i => $el)
		if (isset($arr[$el]))
			$arElements[$i] = $arr[$el];
}

if (count($arSections))
{
	$rsSections = CIBlockSection::GetList(array(), array('=ID' => $arSections));
	$arr = array();
	while($ar = $rsSections->GetNext())
		$arr[$ar['ID']] = $ar['NAME'];

	foreach ($arSections as $i => $el)
		if (isset($arr[$el]))
			$arSections[$i] = $arr[$el];
}

if (count($arCrmElements))
{
	foreach ($arCrmElements as $type => $ids)
	{
		$arr = [];
		switch ($type)
		{
			case 'L':
				$res = CCrmLead::GetListEx([], ['ID' => $ids], false, [], ['ID', 'TITLE']);
				while ($row = $res->Fetch())
				{
					$arr[$type][$row['ID']] = $row['TITLE'];
				}
				break;
			case 'C':
				$res = CCrmContact::GetListEx(
					[], ['ID' => $ids], false, [],
					['ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME']
				);
				while ($row = $res->Fetch())
				{
					$formattedName = CCrmContact::PrepareFormattedName($row);
					$arr[$type][$row['ID']] = $formattedName;
				}
				unset($formattedName);
				break;
			case 'CO':
				$res = CCrmCompany::GetListEx([], ['ID' => $ids], false, [], ['ID', 'TITLE']);
				while ($row = $res->Fetch())
				{
					$arr[$type][$row['ID']] = $row['TITLE'];
				}
				break;
			case 'D':
				$res = CCrmDeal::GetListEx([], ['ID' => $ids], false, [], array('ID', 'TITLE'));
				while ($row = $res->Fetch())
				{
					$arr[$type][$row['ID']] = $row['TITLE'];
				}
				break;
		}
		unset($res, $row);
		
		foreach ($ids as $i => $id)
		{
			if (isset($arr[$type][$id]))
			{
				$arCrmElements[$type][$i] = '['.$type.']'.$arr[$type][$id];
			}
			else
			{
				$arCrmElements[$type][$i] = $type.'_'.$id;
			}
		}
		unset($i, $id);
	}
	unset($type, $ids);
}

if (!$isInExportMode)
{
	foreach ($arArrays as $i => $ar)
	{
		$arArrays[$i] = implode('&nbsp;/<br>', $ar);
	}
}
