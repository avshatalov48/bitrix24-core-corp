<?php

use Bitrix\Iblock;
use Bitrix\Catalog\Access\AccessController;
use Bitrix\Catalog\Access\ActionDictionary;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CATALOG_MODULE_NOT_INSTALLED'));

	return;
}

/** @global CMain $APPLICATION */
/** @global CUser $USER */
global $USER, $APPLICATION;


if (
	!AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_READ)
	|| !AccessController::getCurrent()->check(ActionDictionary::ACTION_CATALOG_IMPORT_EXECUTION)
)
{
	global $APPLICATION;
	$APPLICATION->IncludeComponent(
		"bitrix:ui.info.error",
		"",
		[
			'TITLE' => GetMessage('CRM_IMPORT_ACCESS_RESTRICTED'),
		]
	);
	return;
}

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => 'ID'),
	array('id' => 'XML_ID', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_XML_ID')),
	array('id' => 'NAME', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_NAME')),
	array('id' => 'CODE', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_CODE')),
	array('id' => 'DESCRIPTION', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_DESCRIPTION')),
	array('id' => 'DESCRIPTION_TYPE', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_DESCRIPTION_TYPE')),
	array('id' => 'ACTIVE', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_ACTIVE')),
	array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_CURRENCY_ID')),
	array('id' => 'PRICE', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_PRICE')),
	array('id' => 'VAT_ID', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_VAT_ID')),
	array('id' => 'VAT_INCLUDED', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_VAT_INCLUDED')),
	array('id' => 'MEASURE', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_MEASURE')),
	array('id' => 'SECTION_ID', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_SECTION_ID')),
	array('id' => 'SORT', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_SORT')),
	array('id' => 'PREVIEW_PICTURE', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_PREVIEW_PICTURE')),
	array('id' => 'DETAIL_PICTURE', 'name' => GetMessage('CRM_PRODUCT_IMP_COL_DETAIL_PICTURE'))
);

$catalogID = isset($arParams['~CATALOG_ID']) ? intval($arParams['~CATALOG_ID']) : 0;
if ($catalogID <= 0)
{
	$catalogID = CCrmCatalog::EnsureDefaultExists();
}

$catalogDefaultImportLevels = intval(COption::GetOptionString('catalog', 'num_catalog_levels', '3'));
$catalogMaxImportLevels = 30;
$catalogImportLevels = isset($_POST['IMPORT_FILE_SECTION_LEVELS']) ?
	intval($_POST['IMPORT_FILE_SECTION_LEVELS']) : $catalogDefaultImportLevels;
if ($catalogImportLevels < 0)
	$catalogImportLevels = $catalogDefaultImportLevels;
if ($catalogImportLevels > $catalogMaxImportLevels)
	$catalogImportLevels = 30;

// Product properties
// <editor-fold defaultstate="collapsed" desc="Product properties">
$arPropUserTypeList = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'import');
$arProps = CCrmProductPropsHelper::GetProps($catalogID, $arPropUserTypeList, 'import');
CCrmProductPropsHelper::ListAddHeades($arPropUserTypeList, $arProps, $arResult['HEADERS']);
// </editor-fold>

$arRequireFields = Array();
$arRequireFields['NAME'] = GetMessage('CRM_PRODUCT_IMP_COL_NAME');

$arResult['LIST_SECTION_ID'] = isset($_REQUEST['list_section_id']) ? intval($_REQUEST['list_section_id']) : 0;
$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $arParams['PATH_TO_PRODUCT_LIST'], '?#section_id#');
$arParams['PATH_TO_PRODUCT_IMPORT'] = CrmCheckPath('PATH_TO_PRODUCT_IMPORT', $arParams['PATH_TO_PRODUCT_IMPORT'], $APPLICATION->GetCurPage().'?import');

if(isset($_REQUEST['getSample']) && $_REQUEST['getSample'] == 'csv')
{
	$APPLICATION->RestartBuffer();

	Header("Content-Type: application/force-download");
	Header("Content-Type: application/octet-stream");
	Header("Content-Type: application/download");
	Header("Content-Disposition: attachment;filename=products.csv");
	Header("Content-Transfer-Encoding: binary");

	// add UTF-8 BOM marker
	echo chr(239).chr(187).chr(191);

	$arPropertyListDemoCache = array();
	$arPropertyListDemoCacheUsedIndex = array();
	$nDemoStrings = 3;
	$arDemo = array();
	$yMapExamples = null;
	$yMapValues = null;
	$eCrmExamples = null;
	$eCrmValues = null;
	$moneyExamples = null;
	$moneyValues = null;
	for ($i = 0; $i < $nDemoStrings; $i++)
	{
		$arDemo[$i] = array(
			'ID' => '1001',
			'NAME' => ($i === 0) ? GetMessage('CRM_PRODUCT_IMP_SAMPLE_NAME') : '',
			'CODE' => ($i === 0) ? 'imported_product' : '',
			'DESCRIPTION' => ($i === 0) ? GetMessage('CRM_PRODUCT_IMP_SAMPLE_DESCRIPTION') : '',
			'ACTIVE' => ($i === 0) ? GetMessage('MAIN_YES') : '',
			'CURRENCY_ID' => ($i === 0) ? CCrmCurrency::GetBaseCurrencyID() : '',
			'PRICE' => ($i === 0) ? 120.00 : '',
			'SORT' => ($i === 0) ? 100 : ''
		);

		if ($i === 0)
		{
			$bVatMode = CCrmTax::isVatMode();
			if ($bVatMode)
			{
				$vatRateValue = null;
				$arVatRatesValues = CCrmVat::GetVatRatesListItems();
				unset($arVatRatesValues['']);
				$arVatRatesValues = array_values(array_unique($arVatRatesValues));
				$nVatRates = count($arVatRatesValues);
				if ($nVatRates > 0)
				{
					$randVatRateIndex = rand(0, $nVatRates);
					if ($randVatRateIndex < $nVatRates)
						$vatRateValue = $arVatRatesValues[$randVatRateIndex];
				}
				if ($vatRateValue !== null)
				{
					$arDemo[$i]['VAT_ID'] = $vatRateValue;
					$arDemo[$i]['VAT_INCLUDED'] = GetMessage('MAIN_NO');
				}
				unset($vatRateValue, $arVatRatesValues, $nVatRates, $randVatRateIndex);
			}

			$measureValue = null;
			$arMeasures = array();
			$measures = \Bitrix\Crm\Measure::getMeasures(100);
			if (is_array($measures))
			{
				foreach ($measures as $measure)
					$arMeasures[$measure['ID']] = $measure['SYMBOL'];
				unset($measure);
			}
			unset($measures);
			$arMeasures = array_values(array_unique($arMeasures));
			$nMeasures = count($arMeasures);
			if ($nMeasures > 0)
			{
				$randMeasureIndex = rand(0, $nMeasures);
				if ($randMeasureIndex < $nMeasures)
					$measureValue = $arMeasures[$randMeasureIndex];
			}
			if ($measureValue !== null)
				$arDemo[$i]['MEASURE'] = $measureValue;
			unset($measureValue, $arMeasures, $nMeasures, $randMeasureIndex);

			$arDemo[$i]['SECTION_ID_1'] = GetMessage('CRM_PRODUCT_IMP_SAMPLE_SECTION_ID');

			$arDemo[$i]['PREVIEW_PICTURE'] = 'http://localhost/files/preview_picture.jpg';
			$arDemo[$i]['DETAIL_PICTURE'] = 'http://localhost/files/detail_picture.jpg';
		}

		// demo values of properties
		foreach($arResult['HEADERS'] as $arField)
		{
			$currentKey = $arField['id'];
			if (mb_substr($currentKey, 0, 9) === 'PROPERTY_' && is_array($arProps) && !empty($arProps))
			{
				if (is_array($arProps[$currentKey])
					&& isset($arProps[$currentKey]['PROPERTY_TYPE'])
					&& array_key_exists('USER_TYPE', $arProps[$currentKey])
					&& ($i === 0
						|| (isset($arProps[$currentKey]['MULTIPLE']) && $arProps[$currentKey]['MULTIPLE'] === 'Y')))
				{
					$propID = intval(mb_substr($currentKey, 9));
					$propValue = null;

					if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
						&& $arProps[$currentKey]['USER_TYPE'] == '')
					{
						$propValue = GetMessage('CRM_PRODUCT_IMP_SAMPLE_STRING_VALUE');
						if (isset($arProps[$currentKey]['MULTIPLE']) && $arProps[$currentKey]['MULTIPLE'] === 'Y')
							$propValue .= ' '.($i + 1);
					}
					else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'N'
						&& $arProps[$currentKey]['USER_TYPE'] == '')
					{
						$propValue = round((doubleval(rand(10000, 99999)) / 100), 2);
					}
					else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'L'
						&& $arProps[$currentKey]['USER_TYPE'] == '')
					{
						if (!isset($arPropertyListDemoCache[$propID]))
						{
							$arDemoValues = array();
							$propListRes = CIBlockPropertyEnum::GetList(
								array('SORT' => 'ASC', 'VALUE' => 'ASC'),
								array('IBLOCK_ID' => $catalogID, 'PROPERTY_ID' => $propID)
							);
							while ($arDemoValue = $propListRes->Fetch())
								$arDemoValues[] = $arDemoValue['VALUE'];
							$arPropertyListDemoCache[$propID] = array_values(array_unique($arDemoValues));
							$arPropertyListDemoCacheUsedIndex[$propID] = array();
							unset($arDemoValues, $propListRes, $arDemoValue);
						}
						$arDemoValues = $arPropertyListDemoCache[$propID];
						$randValueIndex = null;
						$nValues = count($arDemoValues);
						if ($nValues > 0)
						{
							if ($nValues <= $nDemoStrings && isset($arDemoValues[$i]))
							{
								$randValueIndex = $i;
							}
							else
							{
								$randValueIndex = rand(0, $nValues - 1);
								$nAttempts = 3;
								for ($j = 0; $j < $nAttempts; $j++)
								{
									if (!in_array($randValueIndex, $arPropertyListDemoCacheUsedIndex[$propID], true))
										break;
									$randValueIndex = rand(0, $nValues - 1);
								}
								if ($j === $nAttempts)
								{
									$randValueIndex = null;
									$arPropertyListDemoCache[$propID] = array();
								}
								unset($nAttempts);
							}
						}
						if ($randValueIndex !== null)
						{
							$propValue = $arDemoValues[$randValueIndex];
							$arPropertyListDemoCacheUsedIndex[$propID][] = $randValueIndex;
						}
						unset($arDemoValues, $nValues, $randValueIndex);
					} else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'F'
						&& $arProps[$currentKey]['USER_TYPE'] == '')
					{
						$propValue = 'http://localhost/files/file';
						if (isset($arProps[$currentKey]['MULTIPLE']) && $arProps[$currentKey]['MULTIPLE'] === 'Y')
							$propValue .= ($i + 1);
						$fileExt = 'txt';
						if (isset($arProps[$currentKey]['FILE_TYPE']) && $arProps[$currentKey]['FILE_TYPE'] <> '')
						{
							$arFileExt = explode(',', $arProps[$currentKey]['FILE_TYPE']);
							$nFileExt = count($arFileExt);
							if ($nFileExt > 0)
							{
								$randFileExt = rand(0, $nFileExt - 1);
								$fileExt = mb_strtolower(trim($arFileExt[$randFileExt]));
							}
							unset($arFileExt, $nFileExt, $randFileExt);
							if ($fileExt == 'bmp')
							{
								$fileExt = 'png';
							}
						}
						$propValue .= '.'.$fileExt;
						unset($fileExt);
					} else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
						&& $arProps[$currentKey]['USER_TYPE'] === 'HTML')
					{
						$colors = array('red', 'green', 'blue');
						$tags = array(array('<b>', '</b>'), array('<b><i>', '</i></b>'), array('<b><u>', '</u></b>'));
						$index = 0;
						$items = explode(' ', GetMessage('CRM_PRODUCT_IMP_SAMPLE_STRING_VALUE'));
						$words = array();
						foreach ($items as $item)
						{
							$item = trim($item);
							if ($item !== '')
								$words[] = $item;
						}
						unset($items, $item);
						$nWords = count($words);
						if ($nWords > 0)
						{
							$propValue = '[html]';
							for ($j = 0; $j < $nWords; $j++)
							{
								$index = $j % 3;
								$propValue .= sprintf(($j > 0 ? ' ' : '').'<span style="color: %s;">%s%s%s</span>',
									$colors[$index], $tags[$index][0], $words[$j], $tags[$index][1]);
							}
							if (isset($arProps[$currentKey]['MULTIPLE']) && $arProps[$currentKey]['MULTIPLE'] === 'Y')
							{
								$index = $j % 3;
								$propValue .= sprintf(' <span style="color: %s;">%s%s%s</span>',
									$colors[$index], $tags[$index][0], '('.($i + 1).')', $tags[$index][1]);
							}
						}
						unset($index);
					}
					else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
						&& $arProps[$currentKey]['USER_TYPE'] === 'Date')
					{
						$propValue = ConvertTimeStamp(
							time() + ($i * 24 * 60 * 60) + CTimeZone::GetOffset(), 'SHORT', SITE_ID
						);
					}
					else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
						&& $arProps[$currentKey]['USER_TYPE'] === 'DateTime')
					{
						$propValue = ConvertTimeStamp(
							time() + ($i * (24 + $i) * 60 * 60) + CTimeZone::GetOffset(), 'FULL', SITE_ID
						);
					}
					else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
						&& $arProps[$currentKey]['USER_TYPE'] === 'employee')
					{
						if ($i === 0)
							$propValue = $USER->GetFormattedName(false, false);
					}
					else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
						&& $arProps[$currentKey]['USER_TYPE'] === 'map_yandex')
					{
						if ($yMapExamples === null)
						{
							$yMapExamples = array(
								'points' => array(
									'48.854736508712165,2.3518665185546386',
									'51.49636790381754,-0.11737626171878508',
									'55.745190814747545,37.63048150781247',
									'52.51775556923818,13.402417410156236',
									'35.68561910915888,139.8076923476562',
									'-16.499287616339927,-151.73556209521485',
									'19.58112023322279,-155.52202762500002',
									'23.105557353535882,-82.32951885938108',
									'-22.91494572104774,-43.180368606445306',
									'40.70728758735511,-73.99885857421876',
									'38.891736595443355,-77.02906855566405',
									'45.41320459583816,-75.69452205273438',
									'47.5945515503741,-122.3280592539062',
									'43.129472056417804,131.9186198906249',
									'59.924547182816774,10.759876679687498',
									'-37.82457663195642,144.96084329296878',
									'-37.82457663195642,144.96084329296878'
								),
								'vectors' => array(
									0 => array(0.1, 0.0),
									1 => array(0.07, 0.07),
									2 => array(0.0, 0.1),
									3 => array(-0.07, 0.07),
									4 => array(-0.1, 0.0),
									5 => array(-0.07, -0.07),
									6 => array(0.0, -0.1),
									7 => array(0.07, -0.07),
								)
							);
						}

						if ($i === 0)
						{
							if (!is_array($yMapValues))
								$yMapValues = array();
							if (!is_array($yMapValues[$currentKey]))
								$yMapValues[$currentKey] = array();
							$yMapValues[$currentKey][0] = $yMapExamples['points'][rand(0, count($yMapExamples['points']) - 1)];
							if (isset($arProps[$currentKey]['MULTIPLE']) && $arProps[$currentKey]['MULTIPLE'] === 'Y'
								&& $nDemoStrings > 1)
							{
								$index = rand(0, 7);
								$coords = array();
								for ($j = 1 ; $j < $nDemoStrings ; $j++)
								{
									$coords = explode(',', $yMapValues[$currentKey][$j - 1]);
									$coords[0] = doubleval($coords[0]) + $yMapExamples['vectors'][$index][1];
									$coords[1] = doubleval($coords[1]) + $yMapExamples['vectors'][$index++][0];
									if ($index >= 7)
										$index = 0;
									$yMapValues[$currentKey][$j] = sprintf('%.15F,%.15F', $coords[0], $coords[1]);
								}
								unset($coords);
							}

							$propValue = $yMapValues[$currentKey][$i];
						}
						else if (isset($arProps[$currentKey]['MULTIPLE']) && $arProps[$currentKey]['MULTIPLE'] === 'Y')
						{
							$propValue = $yMapValues[$currentKey][$i];
						}

						$propValue = $yMapValues[$currentKey][$i];
					}
					else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
						&& $arProps[$currentKey]['USER_TYPE'] === 'ECrm')
					{
						if ($eCrmExamples === null)
						{
							$eCrmExamples = array();
							$ownerPrefixList = array('L', 'C', 'CO', 'D');
							foreach ($ownerPrefixList as $ownerPrefix)
							{
								switch ($ownerPrefix)
								{
									case 'L':
										$res = CCrmLead::GetListEx(
											array('ID' => 'DESC'),
											array(),
											false,
											array('nTopCount' => 10),
											array('ID', 'TITLE')
										);
										while ($row = $res->Fetch())
										{
											$eCrmExamples[] = array(
												'prefix' => $ownerPrefix,
												'id' => $row['ID'],
												'name' => $row['TITLE']
											);
										}
										break;
									case 'C':
										$res = CCrmContact::GetListEx(
											array('ID' => 'DESC'),
											array('@CATEGORY_ID' => 0),
											false,
											array('nTopCount' => 10),
											array('ID', 'HONORIFIC', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO')
										);
										while ($row = $res->Fetch())
										{
											$formattedName = CCrmContact::PrepareFormattedName($row);
											$eCrmExamples[] = array(
												'prefix' => $ownerPrefix,
												'id' => $row['ID'],
												'name' => $formattedName
											);
										}
										break;
									case 'CO':
										$res = CCrmCompany::GetListEx(
											array('ID' => 'DESC'),
											array('@CATEGORY_ID' => 0),
											false,
											array('nTopCount' => 10),
											array('ID', 'TITLE')
										);
										while ($row = $res->Fetch())
										{
											$eCrmExamples[] = array(
												'prefix' => $ownerPrefix,
												'id' => $row['ID'],
												'name' => $row['TITLE']
											);
										}
										break;
									case 'D':
										$res = CCrmDeal::GetListEx(
											array('ID' => 'DESC'),
											array(),
											false,
											array('nTopCount' => 10),
											array('ID', 'TITLE')
										);
										while ($row = $res->Fetch())
										{
											$eCrmExamples[] = array(
												'prefix' => $ownerPrefix,
												'id' => $row['ID'],
												'name' => $row['TITLE']
											);
										}
										break;
								}
							}
							unset($ownerPrefixList, $ownerPrefix, $res, $row, $formattedName);
						}

						if ($i === 0 && !empty($eCrmExamples))
						{
							if (!is_array($eCrmValues))
								$eCrmValues = array();
							if (!is_array($eCrmValues[$currentKey]))
								$eCrmValues[$currentKey] = array();
							$maxIndex = count($eCrmExamples) - 1;
							$index = rand(0, $maxIndex);
							$tempExamples = $eCrmExamples;
							if (empty($tempExamples[$index]['name']))
							{
								$eCrmValues[$currentKey][0] =
									$tempExamples[$index]['prefix'].'_'.$tempExamples[$index]['id'];
							}
							else
							{
								$eCrmValues[$currentKey][0] =
									'['.$tempExamples[$index]['prefix'].']'.$tempExamples[$index]['name'];
							}

							if (isset($arProps[$currentKey]['MULTIPLE']) && $arProps[$currentKey]['MULTIPLE'] === 'Y'
								&& $nDemoStrings > 1)
							{
								array_splice($tempExamples, $index, 1, array());
								$maxIndex--;

								for ($j = 1 ; $j < $nDemoStrings ; $j++)
								{
									if ($maxIndex < 0)
										break;

									$index = rand(0, $maxIndex);
									if (empty($tempExamples[$index]['name']))
									{
										$eCrmValues[$currentKey][$j] =
											$tempExamples[$index]['prefix'].'_'.$tempExamples[$index]['id'];
									}
									else
									{
										$eCrmValues[$currentKey][$j] =
											'['.$tempExamples[$index]['prefix'].']'.$tempExamples[$index]['name'];
									}
									array_splice($tempExamples, $index, 1, array());
									$maxIndex--;
								}
							}
							unset($index, $maxIndex, $tempExamples);
						}

						if (isset($eCrmValues[$currentKey][$i]))
							$propValue = $eCrmValues[$currentKey][$i];
					}
					else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
						&& $arProps[$currentKey]['USER_TYPE'] === 'Money')
					{
						if ($moneyExamples === null)
						{
							$moneyExamples = array();

							$res = CCrmCurrency::GetList(array('SORT' => 'ASC'));
							$rowNum = 0;
							while ($row = $res->Fetch())
							{
								if (++$rowNum > 5)
									break;

								$value = (double)rand(100, 99999) / 100;
								$moneyExamples[] = $value.'|'.$row['CURRENCY'];
							}
							unset($res, $row, $rowNum, $value);
						}

						if ($i === 0 && !empty($moneyExamples))
						{
							if (!is_array($moneyValues))
								$moneyValues = array();
							if (!is_array($moneyValues[$currentKey]))
								$moneyValues[$currentKey] = array();
							$maxIndex = count($moneyExamples) - 1;
							$index = rand(0, $maxIndex);
							$tempExamples = $moneyExamples;
							$moneyValues[$currentKey][0] = $moneyExamples[$index];

							if (isset($arProps[$currentKey]['MULTIPLE']) && $arProps[$currentKey]['MULTIPLE'] === 'Y'
								&& $nDemoStrings > 1)
							{
								array_splice($tempExamples, $index, 1, array());
								$maxIndex--;

								for ($j = 1 ; $j < $nDemoStrings ; $j++)
								{
									if ($maxIndex < 0)
										break;

									$index = rand(0, $maxIndex);
									$moneyValues[$currentKey][$j] = $tempExamples[$index];
									array_splice($tempExamples, $index, 1, array());
									$maxIndex--;
								}
							}
							unset($index, $maxIndex, $tempExamples);
						}

						if (isset($moneyValues[$currentKey][$i]))
							$propValue = $moneyValues[$currentKey][$i];
					}
					else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'N'
						&& $arProps[$currentKey]['USER_TYPE'] === 'Sequence')
					{
						$propValue = $i;
					}

					if ($propValue !== null)
						$arDemo[$i][$currentKey] = $propValue;
				}
			}
		}

		// headers
		if ($i === 0)
		{
			foreach($arResult['HEADERS'] as $arField)
			{
				if ($arField['id'] === 'SECTION_ID')
				{
					for ($j = 1; $j <= $catalogImportLevels; $j++)
						echo '"' .
							str_replace('"', '""', GetMessage('CRM_PRODUCT_IMP_SECTION_HEADER', array('#LEVEL_NUM#' => $j))) .
							'";';
				} else
				{
					echo '"' . str_replace('"', '""', htmlspecialcharsback($arField['name'])) . '";';
				}
			}
			echo "\n";
		}

		foreach($arResult['HEADERS'] as $arField)
		{
			if ($arField['id'] === 'SECTION_ID')
			{
				for ($j = 1; $j <= $catalogImportLevels; $j++)
					echo isset($arDemo[$i][$arField['id'].'_'.$j]) ?
						'"' . str_replace('"', '""', $arDemo[$i][$arField['id'].'_'.$j]) . '";' : '"";';
			}
			else
			{
				echo isset($arDemo[$i][$arField['id']]) ?
					'"' . str_replace('"', '""', $arDemo[$i][$arField['id']]) . '";' : '"";';
			}
		}
		echo "\n";
	}
	unset($yMapExamples, $yMapValues, $eCrmExamples, $eCrmValues, $moneyExamples, $moneyValues);

	die();
}
else if (isset($_REQUEST['import']) && isset($_SESSION['CRM_IMPORT_FILE']))
{
	$APPLICATION->RestartBuffer();

	require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/classes/general/csv_data.php');

	$arVatRates = CCrmVat::GetVatRatesListItems();

	// measure list items
	$arMeasures = array('' => GetMessage('CRM_MEASURE_NOT_SELECTED'));
	$measures = \Bitrix\Crm\Measure::getMeasures(100);
	if (is_array($measures))
	{
		foreach ($measures as $measure)
			$arMeasures[$measure['ID']] = $measure['SYMBOL'];
		unset($measure);
	}
	unset($measures);

	$csvFile = new CCSVData();
	$csvFile->LoadFile($_SESSION['CRM_IMPORT_FILE']);
	$csvFile->SetFieldsType('R');
	$csvFile->SetPos($_SESSION['CRM_IMPORT_FILE_POS']);
	$csvFile->SetFirstHeader($_SESSION['CRM_IMPORT_FILE_FIRST_HEADER']);
	$csvFile->SetDelimiter($_SESSION['CRM_IMPORT_FILE_SEPARATOR']);

	$arResult = array(
		'import' => 0,
		'error' => 0,
		'error_data' => array()
	);

	$arProducts = array();
	$arMultipleProps = array();
	$arPropertyListCache = array();
	$arUserListCache = null;
	$sanitizer = null;

	$upperYes = mb_strtoupper(GetMessage('MAIN_YES'));

	$filePos = 0;

	$startTime = time();

	while($arData = $csvFile->Fetch())
	{
		$arResult['column'] = count($arData);
		$productIndex = '';

		$arProduct = array(
			'__CSV_DATA__' => array($arData)
		);

		foreach ($arData as $key => $data)
		{
			$prop = null;
			$propID = null;
			if (isset($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]) && !empty($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]))
			{
				$currentKey = mb_strtoupper($_SESSION['CRM_IMPORT_FILE_FIELD_'.$key]);
				$data = trim($data);

				if ($currentKey === 'ID')
				{
					$productIndex = $data;
					$data = null;
				}
				else if (mb_substr($currentKey, 0, 1) === '~' || $data === '')
				{
					continue;
				}
				else if ($currentKey === 'ACTIVE' || $currentKey === 'VAT_INCLUDED')
				{
					$dataUpper = mb_strtoupper($data);
					$data = ($dataUpper === $upperYes || $dataUpper === 'Y' || $dataUpper === 'YES'
						|| (is_numeric($dataUpper) && intval($dataUpper) > 0)) ? 'Y' : 'N';

					if ($currentKey === 'VAT_INCLUDED' && isset($arProduct['VAT_ID']) && $arProduct['VAT_ID'] <= 0)
						$data = 'N';
				}
				else if ($currentKey === 'CURRENCY_ID')
				{
					$currency = CCrmCurrency::GetByName($data);
					if(!$currency)
						$currency = CCrmCurrency::GetByID($data);
					$data = $currency ? $currency['CURRENCY'] : CCrmCurrency::GetBaseCurrencyID();
				}
				else if ($currentKey === 'PRICE')
				{
					$data = CCrmProductHelper::parseFloat($data, 2);
				}
				else if ($currentKey === 'VAT_ID')
				{
					$data = array_search($data, $arVatRates);
					$data = ($data === false) ? 0 : intval($data);

					if ($data <= 0 && isset($arProduct['VAT_INCLUDED']) && $arProduct['VAT_INCLUDED'] !== 'N')
						$arProduct['VAT_INCLUDED'] = 'N';
				}
				else if ($currentKey === 'MEASURE')
				{
					$data = array_search($data, $arMeasures);
					$data = ($data === false) ? null : intval($data);
				}
				else if (mb_substr($currentKey, 0, 11) === 'SECTION_ID_')
				{
					$data = trim(strval($data));
				}
				else if ($currentKey === 'SORT')
				{
					$data = intval($data);
				}
				else if ($currentKey === 'PREVIEW_PICTURE' || $currentKey === 'DETAIL_PICTURE')
				{
					if (CCrmUrlUtil::HasScheme($data) && CCrmUrlUtil::IsSecureUrl($data))
					{
						$data = CFile::MakeFileArray($data);
						if (is_array($data) && CFile::CheckImageFile($data) == '')
							$data = array_merge($data, array('MODULE_ID' => 'crm'));
						else
							$data = null;
					}
				}
				else if (mb_substr($currentKey, 0, 9) === 'PROPERTY_' && is_array($arProps) && !empty($arProps))
				{
					$propID = intval(mb_substr($currentKey, 9));

					if ($arProps[$currentKey]['MULTIPLE'] === 'Y' && !isset($arMultipleProps[$propID]))
						$arMultipleProps[$propID] = $key;

					if (is_array($arProps[$currentKey])
						&& isset($arProps[$currentKey]['PROPERTY_TYPE'])
						&& array_key_exists('USER_TYPE', $arProps[$currentKey]))
					{
						if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
							&& $arProps[$currentKey]['USER_TYPE'] == '')
						{
							$prop = array('VALUE' => $data);
						}
						else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'N'
							&& $arProps[$currentKey]['USER_TYPE'] == '')
						{
							$prop = array('VALUE' => CCrmProductHelper::parseFloat($data));
						}
						else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'L'
							&& $arProps[$currentKey]['USER_TYPE'] == '')
						{
							$propValueHash = md5($data);
							if (!isset($arPropertyListCache[$propID]))
							{
								$arPropertyListCache[$propID] = array();
								$propEnumRes = CIBlockPropertyEnum::GetList(
									array('SORT' => 'ASC', 'VALUE' => 'ASC'),
									array('IBLOCK_ID' => $catalogID, 'PROPERTY_ID' => $propID)
								);
								while ($propEnumValue = $propEnumRes->Fetch())
								{
									$arPropertyListCache[$propID][md5($propEnumValue['VALUE'])] = $propEnumValue['ID'];
								}
							}
							if (isset($arPropertyListCache[$propID][$propValueHash]))
								$prop = array('VALUE' => $arPropertyListCache[$propID][$propValueHash]);
						} else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'F'
							&& $arProps[$currentKey]['USER_TYPE'] == '')
						{
							if (CCrmUrlUtil::HasScheme($data) && CCrmUrlUtil::IsSecureUrl($data))
							{
								$data = CFile::MakeFileArray($data);
								$file = new CFile();
								if (is_array($data) && $file->CheckFile($data) == '')
									$prop = array('VALUE' => array_merge($data, array('MODULE_ID' => 'crm')));
								unset($file);
							}
						} else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
							&& $arProps[$currentKey]['USER_TYPE'] === 'HTML')
						{
							if (mb_strtoupper(mb_substr($data, 0, 6)) !== '[TEXT]')
							{
								$data = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($data);
							}
							$prop = array('VALUE' => $data);
						}
						else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
							&& $arProps[$currentKey]['USER_TYPE'] === 'Date')
						{
							if (CheckDateTime($data, FORMAT_DATE))
								$prop = array('VALUE' => $data);
							else
								$prop = null;
						}
						else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
							&& $arProps[$currentKey]['USER_TYPE'] === 'DateTime')
						{
							if (CheckDateTime($data, FORMAT_DATETIME))
								$prop = array('VALUE' => $data);
							else
								$prop = null;
						}
						else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
							&& $arProps[$currentKey]['USER_TYPE'] === 'employee')
						{
							$propValueHash = md5($data);
							if ($arUserListCache === null)
							{
								$arUserListCache = array();
								$rsUserList = CUser::GetList('ID', 'ASC');
								$site = new CSite();
								while ($arUser = $rsUserList->Fetch())
								{
									$propValue = [];
									$propValue[] = "(".$arUser["LOGIN"].") ".$arUser["NAME"]." ".$arUser["LAST_NAME"];
									$propValue[] = CUser::FormatName($site->GetNameFormat(false), $arUser, false, true);
									foreach ($propValue as $value)
									{
										$hash = md5($value);
										if (!isset($arUserListCache[$hash]))
										{
											$arUserListCache[$hash] = $arUser['ID'];
										}
									}
								}
								unset($rsUserList, $site, $arUser, $propValue, $value, $hash);
							}
							if (isset($arUserListCache[$propValueHash]))
							{
								$prop = array('VALUE' => $arUserListCache[$propValueHash]);
							}
							unset($propValueHash);
						}
						else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
							&& $arProps[$currentKey]['USER_TYPE'] === 'map_yandex')
						{
							$prop = array('VALUE' => $data);
						}
						else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
							&& $arProps[$currentKey]['USER_TYPE'] === 'ECrm')
						{
							$value = $data;
							CCrmProductPropsHelper::InternalizeCrmEntityValue($value, $arProps[$currentKey]);
							$prop = array('VALUE' => $value);
							unset($value);
						}
						else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'S'
							&& $arProps[$currentKey]['USER_TYPE'] === 'Money')
						{
							$prop = array('VALUE' => $data);
						}
						else if ($arProps[$currentKey]['PROPERTY_TYPE'] === 'N'
							&& $arProps[$currentKey]['USER_TYPE'] === 'Sequence')
						{
							$prop = array('VALUE' => intval($data));
						}
						/*else
						{
							$prop = array('VALUE' => $data);
						}*/
					}
					$data = null;
				}

				if ($data !== null)
					$arProduct[$currentKey] = $data;

				if ($prop !== null && $propID !== null)
				{
					if (!is_array($arProduct['PROPERTY_VALUES']))
						$arProduct['PROPERTY_VALUES'] = array();
					$arProduct['PROPERTY_VALUES'][$propID] = $prop;
				}
			}
		}

		$canBreak = true;

		if($productIndex !== '')
		{
			if(isset($arProducts[$productIndex]))
			{
				$canBreak = false;

				// merge multiple fields
				$arPrevProduct = $arProducts[$productIndex];
				$csvData = end($arProduct['__CSV_DATA__']);
				$prevCsvData = end($arPrevProduct['__CSV_DATA__']);
				foreach ($arMultipleProps as $propID => $csvKey)
				{
					$curValue = null;
					if (is_array($arProduct['PROPERTY_VALUES'])
						&& isset($arProduct['PROPERTY_VALUES'][$propID])
						&& is_array($arProduct['PROPERTY_VALUES'][$propID])
						&& isset($arProduct['PROPERTY_VALUES'][$propID]['VALUE'])
						&& $csvData[$csvKey] !== $prevCsvData[$csvKey])
					{
						$curValue = $arProduct['PROPERTY_VALUES'][$propID];
					}

					if ($curValue !== null)
					{
						if (!is_array($arPrevProduct['PROPERTY_VALUES']))
							$arPrevProduct['PROPERTY_VALUES'] = array();
						if (!is_array($arPrevProduct['PROPERTY_VALUES'][$propID]))
						{
							$arPrevProduct['PROPERTY_VALUES'][$propID] = array();
						}
						else if (isset($arPrevProduct['PROPERTY_VALUES'][$propID]['VALUE']))
						{
							$arPrevProduct['PROPERTY_VALUES'][$propID] =
								['n0' => $arPrevProduct['PROPERTY_VALUES'][$propID]];
						}

						$i = count($arPrevProduct['PROPERTY_VALUES'][$propID]);
						$arPrevProduct['PROPERTY_VALUES'][$propID]['n'.$i] = $curValue;
						unset($i);
					}
				}
				$arPrevProduct['__CSV_DATA__'] = array_merge($arPrevProduct['__CSV_DATA__'], $arProduct['__CSV_DATA__']);
				$arProduct = $arPrevProduct;

				unset($arProducts[$productIndex]);
			}
		}
		else
		{
			$productIndex = uniqid();
		}

		if($canBreak && (count($arProducts) >= 20 || (time() - $startTime) > 10))
			break;

		$arProducts[$productIndex] = $arProduct;
		$filePos = $csvFile->GetPos();
	}

	$sectionHelper = new CCrmProductSectionHelper($catalogID);
	foreach($arProducts as $arProduct)
	{
		// import sections and make SECTION_ID
		$arProductSections = array();
		foreach ($arProduct as $key => $value)
		{
			if (mb_substr($key, 0, 11) === 'SECTION_ID_')
			{
				$strLevel = mb_substr($key, 11);
				if ($strLevel !== false && $strLevel <> '')
				{
					$sectionLevel = intval($strLevel);
					if ($sectionLevel > 0)
					{
						$sectionLevel--;
						$sectionName = $value;
						$arProductSections[$sectionLevel] = $sectionName;
					}
					unset($sectionName, $sectionLevel);
				}
				unset($strLevel, $arProduct[$key]);
			}
		}

		$row = null;
		$xmlId = '';
		$productId = 0;
		if (isset($arProduct['XML_ID']))
		{
			$xmlId = strval($arProduct['XML_ID']);
			$xmlId = mb_substr($xmlId, 0, 256);

			if ($xmlId <> '')
			{
				$res = CCrmProduct::GetList(['ID' => 'DESC'], ['=XML_ID' => $xmlId], ['ID'], ['nTopCount' => 1]);
				if (is_object($res))
				{
					$row = $res->Fetch();
					if (is_array($row))
					{
						$productId = (int)$row['ID'];
					}
				}
			}
			else
			{
				unset($arProduct['XML_ID']);
			}
		}
		unset($res, $row, $xmlId);

		if ($productId <= 0 || (is_array($arProductSections) && !empty($arProductSections)))
		{
			$arProduct['SECTION_ID'] = $sectionHelper->ImportSectionArray($arProductSections);
		}

		if (isset($arProduct['DESCRIPTION_TYPE'])
			&& $arProduct['DESCRIPTION_TYPE'] === 'html'
			&& isset($arProduct['DESCRIPTION'])
			&& is_string($arProduct['DESCRIPTION'])
			&& $arProduct['DESCRIPTION'] !== '')
		{
			$arProduct['DESCRIPTION'] = \Bitrix\Crm\Format\TextHelper::sanitizeHtml($arProduct['DESCRIPTION']);
		}

		$err = null;
		if ($productId > 0)
		{
			if (is_array($arProduct['PROPERTY_VALUES']) && !empty($arProduct['PROPERTY_VALUES']))
			{
				$res = CIBlockElement::GetProperty(
					$catalogID,
					$productId,
					array(
						'sort' => 'asc',
						'id' => 'asc',
						'enum_sort' => 'asc',
						'value_id' => 'asc',
					),
					array(
						'ACTIVE' => 'Y',
						'EMPTY' => 'N',
						'CHECK_PERMISSIONS' => 'N'
					)
				);
				$oldPropertyValues = [];
				$propertyValues = [];
				while ($row = $res->Fetch())
				{
					if ($row['PROPERTY_TYPE'] === Iblock\PropertyTable::TYPE_FILE)
					{
						if (isset($row['ID'])
							&& isset($row['MULTIPLE']) && $row['MULTIPLE'] === 'Y'
							&& isset($row['PROPERTY_VALUE_ID']))
						{
							$propertyId = (int)$row['ID'];
							$propertyValueId = (int)$row['PROPERTY_VALUE_ID'];
							$propertyValues[$propertyId][$propertyValueId] = ['del' => 'Y'];
						}
					}
					else
					{
						$propertyId = (int)$row['ID'];
						$propertyValueId = (int)$row['PROPERTY_VALUE_ID'];
						if (!isset($oldPropertyValues[$propertyId]))
						{
							$oldPropertyValues[$propertyId] = [];
						}
						$oldPropertyValues[$propertyId][$propertyValueId] = [
							'VALUE' => $row['VALUE'],
							'DESCRIPTION' => $row['DESCRIPTION']
						];
					}
				}
				unset($res, $row, $propertyId, $propertyValueId);

				foreach ($arProduct['PROPERTY_VALUES'] as $propertyId => $newPropertyValues)
				{
					if (isset($propertyValues[$propertyId]))
					{
						$arProduct['PROPERTY_VALUES'][$propertyId] = $propertyValues[$propertyId];
						foreach ($newPropertyValues as $propertyValueId => $propertyValue)
						{
							$arProduct['PROPERTY_VALUES'][$propertyId][$propertyValueId] = $propertyValue;
						}
					}
				}
				unset($propertyId, $newPropertyValues, $propertyValueId, $propertyValue);

				if (!empty($oldPropertyValues))
				{
					foreach (array_keys($oldPropertyValues) as $propertyId)
					{
						if (!isset($arProduct['PROPERTY_VALUES'][$propertyId]))
						{
							$arProduct['PROPERTY_VALUES'][$propertyId] = $oldPropertyValues[$propertyId];
						}
					}
				}
				unset($oldPropertyValues);
			}

			if (!CCrmProduct::Update($productId, $arProduct))
			{
				$err = CCrmProduct::GetLastError();
				if (!isset($err[0]))
				{
					$err = GetMessage('CRM_PRODUCT_UPDATE_UNKNOWN_ERROR');
				}
			}
		}
		else
		{
			if (!CCrmProduct::Add($arProduct))
			{
				$err = CCrmProduct::GetLastError();
				if (!isset($err[0]))
				{
					$err = GetMessage('CRM_PRODUCT_ADD_UNKNOWN_ERROR');
				}
			}
		}
		if ($err !== null)
		{
			$arResult['error']++;
			$arResult['error_data'][] = array(
				'message' => CCrmComponentHelper::encodeErrorMessage((string)$err),
				'data' => $arProduct['__CSV_DATA__']
			);
		}
		else if (!empty($arProduct))
		{
			$arResult['import']++;
		}
	}

	$_SESSION['CRM_IMPORT_FILE_POS'] = $filePos;
	$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'] = false;

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arResult);
	die();
}

$strError = '';
$arResult['STEP'] = isset($_POST['step'])? intval($_POST['step']): 1;
if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	if (isset($_POST['next']))
	{
		if ($arResult['STEP'] == 1)
		{
			if ($_FILES['IMPORT_FILE']['error'] > 0)
				ShowError(GetMessage('CRM_PRODUCT_IMP_CSV_NF_ERROR'));
			elseif (($strError = CFile::CheckFile($_FILES['IMPORT_FILE'], 0, false, 'csv,txt')) == '')
			{
				$arFields = Array(''=>'');
				$arFieldsUpper = Array();
				foreach($arResult['HEADERS'] as $arField)
				{
					if ($arField['id'] === 'SECTION_ID')
					{
						for ($i = 1; $i <= $catalogImportLevels; $i++)
						{
							$arFields[$arField['id'].'_'.$i] =
								GetMessage('CRM_PRODUCT_IMP_SECTION_HEADER', array('#LEVEL_NUM#' => $i));
							$arFieldsUpper[$arField['id'].'_'.$i] =
								mb_strtoupper(GetMessage('CRM_PRODUCT_IMP_SECTION_HEADER', array('#LEVEL_NUM#' => $i)));
						}
					}
					else
					{
						//echo '"'.$arField['name'].'";';
						$fieldName = htmlspecialcharsback($arField['name']);
						$arFields[$arField['id']] = $fieldName;
						$arFieldsUpper[$arField['id']] = mb_strtoupper($fieldName);
						if ($arField['mandatory'] == 'Y')
						{
							$arRequireFields[$arField['id']] = $fieldName;
						}
						unset($fieldName);
					}
				}

				if (isset($_SESSION['CRM_IMPORT_FILE']))
					unset($_SESSION['CRM_IMPORT_FILE']);

				$sTmpFilePath = CTempFile::GetDirectoryName(12, 'crm');
				CheckDirPath($sTmpFilePath);
				$_SESSION['CRM_IMPORT_FILE_SKIP_EMPTY'] = isset($_POST['IMPORT_FILE_SKIP_EMPTY']) && $_POST['IMPORT_FILE_SKIP_EMPTY'] == 'Y'? true: false;
				$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'] = isset($_POST['IMPORT_FILE_FIRST_HEADER']) && $_POST['IMPORT_FILE_FIRST_HEADER'] == 'Y'? true: false;
				$_SESSION['CRM_IMPORT_FILE'] = $sTmpFilePath.md5($_FILES['IMPORT_FILE']['tmp_name']).'.tmp';
				$_SESSION['CRM_IMPORT_FILE_POS'] = 0;
				move_uploaded_file($_FILES['IMPORT_FILE']['tmp_name'], $_SESSION['CRM_IMPORT_FILE']);
				@chmod($_SESSION['CRM_IMPORT_FILE'], BX_FILE_PERMISSIONS);

				if (isset($_POST['IMPORT_FILE_ENCODING']))
				{
					$fileEncoding = $_POST['IMPORT_FILE_ENCODING'];

					if ($fileEncoding == '_' && isset($_POST['hidden_file_import_encoding']))
					{
						$fileEncoding = $_POST['hidden_file_import_encoding'];
					}

					if($fileEncoding !== '' && $fileEncoding !== '_' && $fileEncoding !== mb_strtolower(SITE_CHARSET))
					{
						$fileHandle = fopen($_SESSION['CRM_IMPORT_FILE'], 'rb');
						$fileContents = fread($fileHandle, filesize($_SESSION['CRM_IMPORT_FILE']));
						fclose($fileHandle);

						//HACK: Remove UTF-8 BOM
						if($fileEncoding === 'utf-8' && mb_substr($fileContents, 0, 3) === "\xEF\xBB\xBF")
						{
							$fileContents = mb_substr($fileContents, 3);
						}

						$fileContents = \Bitrix\Main\Text\Encoding::convertEncoding($fileContents, $fileEncoding, SITE_CHARSET);

						$fileHandle = fopen($_SESSION['CRM_IMPORT_FILE'], 'wb');
						fwrite($fileHandle, $fileContents);
						fclose($fileHandle);
					}
				}

				if ($_POST['IMPORT_FILE_SEPARATOR'] == 'semicolon')
					$_SESSION['CRM_IMPORT_FILE_SEPARATOR'] = ';';
				elseif ($_POST['IMPORT_FILE_SEPARATOR'] == 'comma')
					$_SESSION['CRM_IMPORT_FILE_SEPARATOR'] = ',';
				elseif ($_POST['IMPORT_FILE_SEPARATOR'] == 'tab')
					$_SESSION['CRM_IMPORT_FILE_SEPARATOR'] = "\t";
				elseif ($_POST['IMPORT_FILE_SEPARATOR'] == 'space')
					$_SESSION['CRM_IMPORT_FILE_SEPARATOR'] = ' ';

				require_once($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/classes/general/csv_data.php');

				$csvFile = new CCSVData();
				$csvFile->LoadFile($_SESSION['CRM_IMPORT_FILE']);
				$csvFile->SetFieldsType('R');
				$csvFile->SetFirstHeader(false);
				$csvFile->SetDelimiter($_SESSION['CRM_IMPORT_FILE_SEPARATOR']);

				$iRow = 1;
				$arHeader = Array();
				$arRows = Array();
				while($arData = $csvFile->Fetch())
				{
					if ($iRow == 1)
					{
						foreach($arData as $key => $value):
							if ($_SESSION['CRM_IMPORT_FILE_SKIP_EMPTY'] && empty($value))
								continue;
							if ($_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'])
								$arHeader[$key] = empty($value)? GetMessage('CRM_COLUMN_HEADER').' '.($key+1): trim($value);
							else
								$arHeader[$key] = GetMessage('CRM_COLUMN_HEADER').' '.($key+1);
						endforeach;
						if (!$_SESSION['CRM_IMPORT_FILE_FIRST_HEADER'])
							foreach($arHeader as $key => $value)
								$arRows[$iRow][$key] = $arData[$key];
					}
					else
						foreach($arHeader as $key => $value)
							$arRows[$iRow][$key] = $arData[$key];

					if ($iRow > 5)
						break;

					$iRow++;
				}
				$_SESSION['CRM_IMPORT_FILE_HEADERS'] = $arHeader;

				$arResult['FIELDS']['tab_2'] = array();
				if (count($arRequireFields)>0)
				{
					ob_start();
					?>
					<div class="crm_import_require_fields">
						<p><?=GetMessage('CRM_REQUIRE_FIELDS')?>: <b><?=implode('</b>, <b>', $arRequireFields)?></b>.</p>
						<p><?=GetMessage(
								'CRM_PRODUCT_IMPORT_HINT_XML_ID_01',
								array('#XML_ID_TITLE#' => '<b>'.$arFields['XML_ID'].'</b>')
							)?><br><?=GetMessage('CRM_PRODUCT_IMPORT_HINT_XML_ID_02')?></p>
					</div>
					<?
					$sVal = ob_get_contents();
					ob_end_clean();
					$arResult['FIELDS']['tab_2'][] = array(
						'id' => 'IMPORT_REQUIRE_FIELDS',
						'name' => "",
						'colspan' => true,
						'type' => 'custom',
						'value' => $sVal
					);
				}

				foreach ($arHeader as $key => $value)
				{
					$arResult['FIELDS']['tab_2'][] = array(
						'id' => 'IMPORT_FILE_FIELD_'.$key,
						'name' => $value,
						'items' => $arFields,
						'type' => 'list',
						'value' => isset($arFields[mb_strtoupper($value)])? mb_strtoupper($value): array_search(mb_strtoupper($value), $arFieldsUpper),
					);
				}
				$arResult['FIELDS']['tab_2'][] = array(
					'id' => 'IMPORT_ASSOC_EXAMPLE',
					'name' => GetMessage('CRM_SECTION_IMPORT_ASSOC_EXAMPLE'),
					'type' => 'section'
				);
				ob_start();
				?>
				<div id="crm_import_example" class="crm_import_example">
					<table cellspacing="0" cellpadding="0" class="crm_import_example_table">
						<tr>
							<?foreach ($arHeader as $key => $value):?>
								<th><?=htmlspecialcharsbx($value)?></th>
							<?endforeach;?>
						</tr>
						<?foreach ($arRows as $arRow):?>
							<tr>
							<?foreach ($arRow as $row):?>
								<td><?=htmlspecialcharsbx($row)?></td>
							<?endforeach;?>
							</tr>
						<?endforeach;?>
					</table>
				</div>
				<script>
					windowSizes = BX.GetWindowSize(document);
					if (windowSizes.innerWidth > 1024)
						BX('crm_import_example').style.width = '870px';
					if (windowSizes.innerWidth > 1280)
						BX('crm_import_example').style.width = '1065px';
				</script>
				<?
				$sVal = ob_get_contents();
				ob_end_clean();
				$arResult['FIELDS']['tab_2'][] = array(
					'id' => 'IMPORT_ASSOC_EXAMPLE_TABLE',
					'name' => "",
					'colspan' => true,
					'type' => 'custom',
					'value' => $sVal
				);
				if (count($arHeader) == 1)
					ShowError(GetMessage('CRM_CSV_SEPARATOR_ERROR'));
				else
					$arResult['STEP'] = 2;
			}
			else
				ShowError($strError);

		}
		else if ($arResult['STEP'] == 2)
		{
			$arResult['FIELDS']['tab_3'] = array();

			$arConfig = Array();
			foreach ($_POST as $key => $value)
				if(mb_strpos($key, 'IMPORT_FILE_FIELD_') !== false)
					$_SESSION['CRM_'.$key] = $value;

			ob_start();
			?>
				<div class="crm_import_entity"><?=GetMessage('CRM_IMPORT_FINISH')?>: <span id="crm_import_entity">0</span> <span id="crm_import_entity_progress"><img src="/bitrix/components/bitrix/crm.contact.import/templates/.default/images/wait.gif" align="absmiddle"></span></div>
				<div id="crm_import_error" class="crm_import_error"><?=GetMessage('CRM_IMPORT_ERROR')?>: <span id="crm_import_entity_error">0</span></div>
				<div id="crm_import_example" class="crm_import_example" style="display:none">
					<table cellspacing="0" cellpadding="0" class="crm_import_example_table" id="crm_import_example_table">
						<tbody id="crm_import_example_table_body">
						<tr>
							<?foreach ($_SESSION['CRM_IMPORT_FILE_HEADERS'] as $key => $value):?>
								<th><?=htmlspecialcharsbx($value)?></th>
							<?endforeach;?>
						</tr>
						</tbody>
					</table>
				</div>
				<script>
					windowSizes = BX.GetWindowSize(document);
					BX('crm_import_example').style.height = "44px";
					if (windowSizes.innerWidth > 1024)
						BX('crm_import_example').style.width = '870px';
					if (windowSizes.innerWidth > 1280)
						BX('crm_import_example').style.width = '1065px';
					crmImportAjax('<?=$APPLICATION->GetCurPage()?>?import');
				</script>
			<?
			$sVal = ob_get_contents();
			ob_end_clean();
			$arResult['FIELDS']['tab_3'][] = array(
				'id' => 'IMPORT_FINISH',
				'name' => "",
				'colspan' => true,
				'type' => 'custom',
				'value' => $sVal
			);
			$arResult['STEP'] = 3;
		}
		else if ($arResult['STEP'] == 3)
		{
			@unlink($_SESSION['CRM_IMPORT_FILE']);
			foreach ($_SESSION as $key => $value)
				if(mb_strpos($key, 'CRM_IMPORT_FILE') !== false)
					unset($_SESSION[$key]);

			LocalRedirect(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_LIST'],
					array('section_id' => isset($arResult['LIST_SECTION_ID']) ? $arResult['LIST_SECTION_ID'] : 0)
				)
			);
		}
		else
			$arResult['STEP'] = 1;
	}
	else if (isset($_POST['previous']))
	{
		@unlink($_SESSION['CRM_IMPORT_FILE']);
		foreach ($_SESSION as $key => $value)
			if(mb_strpos($key, 'CRM_IMPORT_FILE') !== false)
				unset($_SESSION[$key]);

		$arResult['STEP'] = 1;
	}
	else if (isset($_POST['cancel']))
	{
		@unlink($_SESSION['CRM_IMPORT_FILE']);
		foreach ($_SESSION as $key => $value)
			if(mb_strpos($key, 'CRM_IMPORT_FILE') !== false)
				unset($_SESSION[$key]);

		LocalRedirect(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_LIST'],
				array('section_id' => isset($arResult['LIST_SECTION_ID']) ? $arResult['LIST_SECTION_ID'] : 0)
			)
		);

	}
}

$arResult['FORM_ID'] = 'CRM_PRODUCT_IMPORT';

$arResult['FIELDS']['tab_1'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE',
	'name' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE'),
	'params' => array(),
	'type' => 'file',
	'required' => true
);
$arResult['IMPORT_FILE'] = 'IMPORT_FILE';

$encodings = array(
	'_' => GetMessage('CRM_PRODUCT_IMP_FIELD_AUTO_DETECT_ENCODING'),
	'ascii' => 'ASCII',
	'UTF-8' => 'UTF-8',
	'UTF-16' => 'UTF-16',
	'windows-1251' => 'Windows-1251',
	'Windows-1252' => 'Windows-1252',
	'iso-8859-1' => 'ISO-8859-1',
	'iso-8859-2' => 'ISO-8859-2',
	'iso-8859-3' => 'ISO-8859-3',
	'iso-8859-4' => 'ISO-8859-4',
	'iso-8859-5' => 'ISO-8859-5',
	'iso-8859-6' => 'ISO-8859-6',
	'iso-8859-7' => 'ISO-8859-7',
	'iso-8859-8' => 'ISO-8859-8',
	'iso-8859-9' => 'ISO-8859-9',
	'iso-8859-10' => 'ISO-8859-10',
	'iso-8859-13' => 'ISO-8859-13',
	'iso-8859-14' => 'ISO-8859-14',
	'iso-8859-15' => 'ISO-8859-15',
	'koi8-r' => 'KOI8-R'
);

$siteEncoding = mb_strtolower(SITE_CHARSET);
$arResult['ENCODING_SELECTOR_ID'] = 'import_file_encoding';
$arResult['HIDDEN_FILE_IMPORT_ENCODING'] = 'hidden_file_import_encoding';

foreach (array_keys($encodings) as $key)
{
	if ($key !== '_')
		$arResult['CHARSETS'][] = $key;
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_ENCODING',
	'name' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE_ENCODING'),
	'params' => array('id' => $arResult['ENCODING_SELECTOR_ID']),
	'items' => $encodings,
	'type' => 'list',
	'value' => '_'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_EXAMPLE',
	'name' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE_EXAMPLE'),
	'params' => array(),
	'type' => 'label',
	'value' => '<a href="?getSample=csv&ncc=1">'.GetMessage('CRM_DOWNLOAD').'</a>'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_FORMAT',
	'name' => GetMessage('CRM_SECTION_IMPORT_FILE_FORMAT'),
	'type' => 'section'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_SEPARATOR',
	'name' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE_SEPARATOR'),
	'items' => Array(
		'semicolon' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE_SEPARATOR_SEMICOLON'),
		'comma' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE_SEPARATOR_COMMA'),
		'tab' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE_SEPARATOR_TAB'),
		'space' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE_SEPARATOR_SPACE'),
	),
	'type' => 'list',
	'value' => isset($_POST['IMPORT_FILE_SEPARATOR'])? $_POST['IMPORT_FILE_SEPARATOR']: 'semicolon'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_FIRST_HEADER',
	'name' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE_FIRST_HEADER'),
	'type' => 'checkbox',
	'value' => isset($_POST['IMPORT_FILE_FIRST_HEADER']) && $_POST['IMPORT_FILE_FIRST_HEADER'] == 'N'? 'N': 'Y'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_SKIP_EMPTY',
	'name' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE_SKIP_EMPTY'),
	'type' => 'checkbox',
	'value' => isset($_POST['IMPORT_FILE_SKIP_EMPTY']) && $_POST['IMPORT_FILE_SKIP_EMPTY'] == 'N'? 'N': 'Y'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IMPORT_FILE_SECTION_LEVELS',
	'name' => GetMessage('CRM_PRODUCT_IMP_FIELD_FILE_SECTION_LEVELS'),
	'type' => 'text',
	'params' => array('size' => 2),
	'value' => $catalogImportLevels
);

for ($i = 1; $i <= 3; $i++):
	if ($arResult['STEP'] != $i)
		$arResult['FIELDS']['tab_'.$i] = array();
endfor;

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.product/include/nav.php');

?>
