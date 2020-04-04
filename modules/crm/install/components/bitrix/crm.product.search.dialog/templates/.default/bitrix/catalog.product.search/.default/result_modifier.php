<?php
use Bitrix\Iblock;
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
	die();

/** @var array $arParams */
/** @var array $arResult */

IncludeModuleLangFile(__FILE__);

$priceTypeId = intval(CCrmProduct::getSelectedPriceTypeId());

$props = array();
if (is_array($arResult['PROPS']))
{
	foreach ($arResult['PROPS'] as $propIndex => $prop)
	{
		if ((!isset($prop['USER_TYPE'])
				|| empty($prop['USER_TYPE'])
				|| (is_array($prop['PROPERTY_USER_TYPE'])
					&& array_key_exists('GetPublicViewHTML', $prop['PROPERTY_USER_TYPE']))
			)
			&& $prop['PROPERTY_TYPE'] !== 'G')
		{
			$props[intval($prop['ID'])] = &$arResult['PROPS'][$propIndex];
		}
	}
}

$arResult['PUBLIC_PROPS'] = &$props;

function isPublicHeaderItem($headerId, $priceTypeId, &$propsInfo)
{
	$headerId = trim(strval($headerId));
	$priceTypeId = intval($priceTypeId);
	if ($headerId === '')
		return false;

	if (in_array($headerId, array('BALANCE', 'CODE', 'EXTERNAL_ID', 'SHOW_COUNTER', 'SHOW_COUNTER_START', 'EXPAND',
		'PREVIEW_TEXT', 'QUANTITY', 'ACTION'), true))
	{
		return false;
	}

	$matches = array();
	if (preg_match('/^PRICE(\d+)$/', $headerId, $matches))
	{
		if ($priceTypeId !== intval($matches[1]))
			return false;
	}

	if (is_array($propsInfo) && count($propsInfo) > 0)
	{
		$matches = array();
		if (preg_match('/^PROPERTY_(\d+)$/', $headerId, $matches))
		{
			$propIndex = intval($matches[1]);
			if (!isset($propsInfo[$propIndex]))
				return false;
		}
	}

	return true;
}

if (is_array($arResult['HEADERS']))
{
	$newHeaders = array();

	foreach ($arResult['HEADERS'] as $header)
	{
		if (!isPublicHeaderItem($header['id'], $priceTypeId, $props))
			continue;

		$newHeader = array();
		if (isset($header['id']))
			$newHeader['id'] = $header['id'];
		if (isset($header['content']))
		{
			$matches = array();
			if (preg_match('/^PRICE(\d+)$/', $header['id'], $matches))
			{
				$newHeader['name'] = GetMessage('CRM_COLUMN_PRODUCT_PRICE');
			}
			else
			{
				$newHeader['name'] = $header['content'];
			}
		}
		if (isset($header['sort']))
			$newHeader['sort'] = $header['sort'];
		if (isset($header['default']))
			$newHeader['default'] = $header['default'];
		if (isset($header['align']))
			$newHeader['align'] = $header['align'];
		$newHeaders[] = $newHeader;
	}

	$arResult['HEADERS'] = $newHeaders;
}



// Properties values
$arArrays = array();
$arElements = array();
$arSections = array();

$arProducts = is_array($arResult['PRODUCTS']) ? $arResult['PRODUCTS'] : array();

$elementsNamesCache = array();
$sectionsNamesCache = array();

if (!empty($arProducts) && !empty($props))
{
	$iblockData = array();
	$itemList = array();
	$iblockProperties = array();
	foreach (array_keys($arProducts) as $index)
	{
		$item = $arProducts[$index];
		if (!isset($iblockData[$item['IBLOCK_ID']]))
			$iblockData[$item['IBLOCK_ID']] = array();
		$iblockData[$item['IBLOCK_ID']][$item['ID']] = $item['ID'];
		$arProducts[$index]['PROPERTIES'] = array();
		$arProducts[$index]['DISPLAY_PROPERTIES'] = array();
		$itemList[$item['ID']] = &$arProducts[$index];
		unset($index);
	}
	unset($index);

	foreach ($props as $row)
	{
		if (!isset($iblockProperties[$row['IBLOCK_ID']]))
			$iblockProperties[$row['IBLOCK_ID']] = array();
		$iblockProperties[$row['IBLOCK_ID']][$row['ID']] = $row['ID'];
	}
	unset($row);

	if (!empty($iblockData))
	{
		foreach ($iblockData as $iblockId => $itemIds)
		{
			if (empty($iblockProperties[$iblockId]))
				continue;
			CIBlockElement::GetPropertyValuesArray(
				$itemList,
				$iblockId,
				array(
					'IBLOCK_ID' => $iblockId,
					'ID' => $itemIds
				),
				array(
					'ID' => $iblockProperties[$iblockId]
				)
			);
		}
		unset($iblockId, $itemIds);
	}
}

foreach ($arProducts as $productID => $arItems)
{
	if (is_array($arItems['PRICES']) && isset($arItems['PRICES'][$priceTypeId]))
	{
		if (is_array($arItems['PRICES'][$priceTypeId])
			&& isset($arItems['PRICES'][$priceTypeId]['PRICE']))
		{
			$price = $arItems['PRICES'][$priceTypeId]['PRICE'];
			if (isset($arItems['PRICES'][$priceTypeId]['CURRENCY']))
			{
				$currencyId = $arItems['PRICES'][$priceTypeId]['CURRENCY'];
				$arResult['PRODUCTS'][$productID]['PRICE'.$priceTypeId] = CCrmCurrency::MoneyToString($price, $currencyId);
			}
			else
			{
				$arResult['PRODUCTS'][$productID]['PRICE'.$priceTypeId] = number_format($price, 2, '.', '');
			}
		}
		else
		{
			$arResult['PRODUCTS'][$productID]['PRICE'.$priceTypeId] = $arItems['PRICES'][$priceTypeId];
		}
	}

	if (!empty($arItems['PROPERTIES']) && is_array($arItems['PROPERTIES']))
	{
		foreach ($arItems['PROPERTIES'] as $property)
		{
			if (!isset($props[$property['ID']]))
				continue;
			$viewValues = array();

			$property['USER_TYPE'] = (string)$property['USER_TYPE'];

			$userType = ($property['USER_TYPE'] !== '' ? CIBlockProperty::GetUserType($property['USER_TYPE']) : array());

			if ($property['MULTIPLE'] == 'N' || !is_array($property['VALUE']))
				$valueIdList = array($property['PROPERTY_VALUE_ID']);
			else
				$valueIdList = $property['PROPERTY_VALUE_ID'];

			if (isset($userType['GetPublicViewHTML']))
			{
				if ($property['MULTIPLE'] == 'N' || !is_array($property['~VALUE']))
					$valueList = array($property['~VALUE']);
				else
					$valueList = $property['~VALUE'];
			}
			else
			{
				if ($property['MULTIPLE'] == 'N' || !is_array($property['VALUE']))
					$valueList = array($property['VALUE']);
				else
					$valueList = $property['VALUE'];
			}

			foreach ($valueList as $valueIndex => $value)
			{
				if (isset($userType['GetPublicViewHTML']))
				{
					$viewValues[] = call_user_func_array(
						$userType['GetPublicViewHTML'],
						array(
							$property,
							array(
								'VALUE' => $value
							),
							array()
						));
				}
				else
				{
					switch ($property['PROPERTY_TYPE'])
					{
						case Iblock\PropertyTable::TYPE_SECTION:
							$value = (int)$value;
							if ($value > 0)
							{
								if (!isset($sectionsNamesCache[$value]))
								{
									$sectionsNamesCache[$value] = '';
									$sectionsIterator = Iblock\SectionTable::getList(array(
										'select' => array('ID', 'NAME'),
										'filter' => array('=ID' => $value)
									));
									if ($section = $sectionsIterator->fetch())
										$sectionsNamesCache[$value] = $section['NAME'];
									unset($section, $sectionsIterator);
								}
								if ($sectionsNamesCache[$value] !== '')
									$viewValues[] = $sectionsNamesCache[$value];
							}
							break;
						case Iblock\PropertyTable::TYPE_ELEMENT:
							$value = (int)$value;
							if ($value > 0)
							{
								if (!isset($elementsNamesCache[$value]))
								{
									$rsElement = CIBlockElement::GetList(
										array(),
										array('ID' => $value, 'SHOW_HISTORY' => 'Y'),
										false,
										false,
										array("ID", "IBLOCK_ID", "NAME")
									);
									$element = $rsElement->Fetch();
									$elementsNamesCache[$value] = $element ? $element['NAME'] : '';
								}
								if ($elementsNamesCache[$value] !== '')
									$viewValues[] = $elementsNamesCache[$value];
							}
							break;
						case Iblock\PropertyTable::TYPE_FILE:
							$tmp = CFileInput::Show(
								'NO_FIELDS['.$valueIdList[$valueIndex].']',
								$value,
								array(
									'IMAGE' => 'Y',
									'PATH' => false,
									'FILE_SIZE' => false,
									'DIMENSIONS' => false,
									'IMAGE_POPUP' => false,
									'MAX_SIZE' => array('W' => 50, 'H' => 50),
									'MIN_SIZE' => array('W' => 1, 'H' => 1),
								),
								array(
									'upload' => false,
									'medialib' => false,
									'file_dialog' => false,
									'cloud' => false,
									'del' => false,
									'description' => false,
								)
							);
							$viewValues[] = preg_replace('!<script[^>]*>.*</script>!isU','', $tmp);
							unset($tmp);
							break;
						case Iblock\PropertyTable::TYPE_LIST:
						case Iblock\PropertyTable::TYPE_NUMBER:
						case Iblock\PropertyTable::TYPE_STRING:
						default:
							$viewValues[] = $value;
							break;
					}
				}
			}
			unset($value, $valueList, $valueIdList);
			unset($userType);

			if (!empty($viewValues))
				$arResult['PRODUCTS'][$productID]['DISPLAY_PROPERTIES'][$property['ID']] = $viewValues;
			unset($viewValues);
		}
		unset($propValue);
	}
}