<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/*$file = trim(preg_replace("'[\\\\/]+'", '/', (dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/result_modifier.php')));
__IncludeLang($file);*/
IncludeModuleLangFile(__FILE__);

$arArrays = array();
$arElements = array();
$arSections = array();

foreach ($arResult['PROPERTY_VALUES'] as $propID => $propValue)
{
	$arProp = $arResult['PROPS'][$propID];

	if ($arProp['PROPERTY_TYPE'] == 'F')
	{
		if (is_array($propValue))
		{
			foreach ($propValue as $valueKey => $file)
			{
				$obFile = new CCrmProductFile(
					$arResult['PRODUCT_ID'],
					$propID,
					$file
				);

				$obFileControl = new CCrmProductFileControl($obFile, $propID);

				$propValue[$valueKey] = '<nobr>'.$obFileControl->GetHTML(array(
						'show_input' => false,
						'max_size' => 102400,
						'max_width' => 150,
						'max_height' => 150,
						'url_template' => $arParams['PATH_TO_PRODUCT_FILE'],
						'a_title' => GetMessage('CRM_PRODUCT_PROP_ENLARGE'),
						'download_text' => GetMessage('CRM_PRODUCT_PROP_DOWNLOAD'),
					)).'</nobr>';
			}
		}
		else
		{
			$obFile = new CCrmProductFile(
				$arResult['PRODUCT_ID'],
				$propID,
				$propValue
			);

			$obFileControl = new CCrmProductFileControl($obFile, $propID);

			$propValue = '<nobr>'.$obFileControl->GetHTML(array(
					'show_input' => false,
					'max_size' => 102400,
					'max_width' => 150,
					'max_height' => 150,
					'url_template' => $arParams['PATH_TO_PRODUCT_FILE'],
					'a_title' => GetMessage('CRM_PRODUCT_PROP_ENLARGE'),
					'download_text' => GetMessage('CRM_PRODUCT_PROP_DOWNLOAD'),
				)).'</nobr>';
		}
	}
	else if ($arProp['PROPERTY_TYPE'] == 'E')
	{
		if (is_array($propValue))
		{
			foreach ($propValue as $valueKey => $id)
			{
				if ($id > 0)
					$arElements[] = &$arResult['PROPERTY_VALUES'][$propID][$valueKey];
			}
			$arArrays[$propID] = &$arResult['PROPERTY_VALUES'][$propID];
		}
		else if ($propValue > 0)
		{
			$arElements[] = &$arResult['PROPERTY_VALUES'][$propID];
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
					$arSections[] = &$arResult['PROPERTY_VALUES'][$propID][$valueKey];
			}
			$arArrays[$propID] = &$arResult['PROPERTY_VALUES'][$propID];
		}
		else if ($propValue > 0)
		{
			$arSections[] = &$arResult['PROPERTY_VALUES'][$propID];
		}
		continue;
	}

	$arResult['PROPERTY_VALUES'][$propID] = $propValue;

	if (is_array($propValue))
	{
		if (count($propValue) > 1)
			$arArrays[$propID] = &$arResult['PROPERTY_VALUES'][$propID];
		else
			$arResult['PROPERTY_VALUES'][$propID] = $propValue[0];
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

foreach ($arArrays as $i => $ar)
	$arArrays[$i] = implode('&nbsp;/<br>', $ar);
