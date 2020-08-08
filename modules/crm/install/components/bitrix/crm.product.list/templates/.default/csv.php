<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

/** @var array $arResult */

$isStExport = (isset($arResult['STEXPORT_MODE']) && $arResult['STEXPORT_MODE'] === 'Y');
$isStExportFirstPage = (isset($arResult['STEXPORT_IS_FIRST_PAGE']) && $arResult['STEXPORT_IS_FIRST_PAGE'] === 'Y');
$isStExportLastPage = (isset($arResult['STEXPORT_IS_LAST_PAGE']) && $arResult['STEXPORT_IS_LAST_PAGE'] === 'Y');
$sectionDepth = isset($arResult['SECTION_MAX_DEPTH']) ? (int)$arResult['SECTION_MAX_DEPTH'] : 0;

if ((!is_array($arResult['PRODUCTS']) || count($arResult['PRODUCTS']) <= 0) && (!$isStExport || $isStExportFirstPage))
{
	echo(GetMessage('ERROR_PRODUCT_LIST_IS_EMPTY'));
}
else
{
	// Build up associative array of headers
	$arHeaders = array();
	foreach ($arResult['HEADERS'] as $arHead)
	{
		$arHeaders[$arHead['id']] = $arHead;
	}

	if (!$isStExport || $isStExportFirstPage)
	{
		// Display headers
		foreach($arResult['SELECTED_HEADERS'] as $headerId)
		{
			$arHead = isset($arHeaders[$headerId]) ? $arHeaders[$headerId] : null;
			if($arHead)
			{
				switch ($headerId)
				{
					case 'SECTION_ID':
						if ($sectionDepth > 0)
						{
							for ($pathIndex = 1 ; $pathIndex <= $sectionDepth; $pathIndex++)
							{
								$columnTitle = GetMessage(
									'CRM_PRODUCT_EXP_COLUMN_SECTION_ID',
									['#LEVEL_NUM#' => $pathIndex]
								);
								echo '"', str_replace('"', '""', $columnTitle), '";';
								unset($columnTitle);
							}
							unset($pathIndex);
						}
						break;
					default:
						echo '"', str_replace('"', '""', htmlspecialcharsback($arHead['name'])),'";';
				}

				switch ($headerId)
				{
					case 'PRICE':
						echo '"', str_replace('"', '""', GetMessage('CRM_PRODUCT_EXP_COLUMN_CURRENCY_ID')),'";';
						break;
					case 'DESCRIPTION':
						echo '"', str_replace('"', '""', GetMessage('CRM_PRODUCT_EXP_COLUMN_DESCRIPTION_TYPE')),'";';
						break;
				}
			}
		}
		echo "\n";
	}

	// Display data
	foreach ($arResult['PRODUCTS'] as $productId => $arProduct)
	{
		$additionalRowsCount = 0;
		$additionalRows = [];
		$productIdIndex = $colIndex = 1;
		foreach($arResult['SELECTED_HEADERS'] as $headerId)
		{
			$arHead = isset($arHeaders[$headerId]) ? $arHeaders[$headerId] : null;
			if(!$arHead)
			{
				continue;
			}

			$headerId = $arHead['id'];

			$isProperty = isset($arResult['PROPS'][$headerId]);
			if ($isProperty)
			{
				$value = '';
				if (is_array($arResult['PROPERTY_VALUES'][$productId])
					&& array_key_exists($headerId, $arResult['PROPERTY_VALUES'][$productId]))
				{
					$propertyValue = $arResult['PROPERTY_VALUES'][$productId][$headerId];
					if (is_array($propertyValue))
					{
						$propertyValueCount = count($propertyValue);
						if ($propertyValueCount > 1)
						{
							$additionalRowsCount = max($additionalRowsCount, $propertyValueCount - 1);
							$rowIndex = -1;
							foreach ($propertyValue as $additionalValue)
							{
								if (++$rowIndex === 0)
								{
									continue;
								}

								if (!isset($additionalRows[$rowIndex]))
								{
									$additionalRows[$rowIndex] = [$productIdIndex => $productId];
								}
								$additionalRows[$rowIndex][$colIndex] = $additionalValue;
							}
							unset($additionalValue);
						}
						unset($propertyValueCount);

						$value = $propertyValue[0];
					}
					else
					{
						$value = $propertyValue;
					}
					unset($propertyValue);
				}
				echo '"', str_replace('"', '""', $value), '";';
				$colIndex++;
			}
			else
			{
				switch ($headerId)
				{
					case 'MEASURE':
						$value = '';
						if ($arProduct[$headerId] > 0 && isset($arResult['MEASURE_LIST_ITEMS'][$arProduct[$headerId]]))
						{
							$value = $arResult['MEASURE_LIST_ITEMS'][$arProduct[$headerId]];
						}
						echo '"', str_replace('"', '""', $value), '";';
						$colIndex++;
						break;
					case 'SECTION_ID':
						if ($sectionDepth > 0)
						{
							for ($pathIndex = 0; $pathIndex < $sectionDepth; $pathIndex++)
							{
								$value = '';
								if (isset($arProduct['SECTION_PATH'][$pathIndex]['NAME'])
									&& is_string($arProduct['SECTION_PATH'][$pathIndex]['NAME'])
									&& $arProduct['SECTION_PATH'][$pathIndex]['NAME'] <> '')
								{
									$value = $arProduct['SECTION_PATH'][$pathIndex]['NAME'];
								}
								echo '"', str_replace('"', '""', $value), '";';
								$colIndex++;
							}
							unset($pathIndex);
						}
						break;
					case 'VAT_ID':
						$value = '';
						$vatId = isset($arProduct[$headerId]) ? (int)$arProduct[$headerId] : 0;
						if ($vatId > 0)
						{
							if (isset($arResult['VAT_RATE_LIST_ITEMS'][$vatId]))
							{
								$value = $arResult['VAT_RATE_LIST_ITEMS'][$vatId];
							}
						}
						unset($vatId);
						echo '"', str_replace('"', '""', $value), '";';
						$colIndex++;
						break;
					case 'VAT_INCLUDED':
					case 'ACTIVE':
						$value = $arProduct[$headerId] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
						echo '"', str_replace('"', '""', $value), '";';
						$colIndex++;
						break;
					case 'PREVIEW_PICTURE':
					case 'DETAIL_PICTURE':
						$value = '';
						if (isset($arProduct['~'.$headerId]) && $arProduct['~'.$headerId] > 0)
						{
							$productFile = new CCrmProductFile($productId, $headerId, (int)$arProduct['~'.$headerId]);
							$value = $productFile->GetPublicLink(
								[
									/*'url_template' => $arParams['PATH_TO_PRODUCT_FILE'],*/
									'url_params' => [/*'download' => 'y'*/]
								]
							);
							unset($productFile);
						}
						echo '"', str_replace('"', '""', $value), '";';
						$colIndex++;
						break;
					default:
						{
							if (is_array($arProduct[$headerId]))
							{
								$value = implode(', ', $arProduct[$headerId]);
							}
							else
							{
								$value = strval($arProduct[$headerId]);
							}
							echo '"', str_replace('"', '""', htmlspecialcharsback($value)), '";';
							$colIndex++;
						}
				}

				switch ($headerId)
				{
					case 'PRICE':
						$value = CCrmCurrency::GetCurrencyName($arProduct['CURRENCY_ID']);
						echo '"', str_replace('"', '""', htmlspecialcharsback($value)), '";';
						$colIndex++;
						break;
					case 'DESCRIPTION':
						$value = $arProduct['DESCRIPTION_TYPE'];
						echo '"', str_replace('"', '""', htmlspecialcharsback($value)), '";';
						$colIndex++;
						break;
				}
			}
		}
		$columnsNumber = $colIndex - 1;
		unset($colIndex);

		echo "\n";

		if (!empty($additionalRows))
		{
			foreach ($additionalRows as $row)
			{
				$prevColIndex = 1;
				foreach ($row as $colIndex => $value)
				{
					$emptyColumns = $colIndex - $prevColIndex - 1;
					for ($i = 0; $i < $emptyColumns; $i++)
					{
						echo '"";';
					}
					echo '"', str_replace('"', '""', $value), '";';

					$prevColIndex = $colIndex;
				}

				if ($columnsNumber > $colIndex)
				{
					$emptyColumns = $columnsNumber - $colIndex;
					for ($i = 0; $i < $emptyColumns; $i++)
					{
						echo '"";';
					}
				}

				echo "\n";
			}
		}
	}
}
