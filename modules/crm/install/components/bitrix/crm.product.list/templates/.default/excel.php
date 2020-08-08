<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponentTemplate $this
 * @global \CMain $APPLICATION
 * @global \CUser $USER
 * @global \CDatabase $DB
 */

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
		?><meta http-equiv="Content-type" content="text/html;charset=<?echo LANG_CHARSET?>" />
		<table border="1">
		<thead>
			<tr><?
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
									?><th><?=htmlspecialcharsbx($columnTitle)?></th><?
									unset($columnTitle);
								}
								unset($pathIndex);
							}
							break;
						default:
							?><th><?=$arHead['name']?></th><?

					}

					switch ($headerId)
					{
						case 'PRICE':
							?><th><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_EXP_COLUMN_CURRENCY_ID'))?></th><?
							break;
						case 'DESCRIPTION':
							?><th><?=htmlspecialcharsbx(GetMessage('CRM_PRODUCT_EXP_COLUMN_DESCRIPTION_TYPE'))?></th><?
							break;
					}
				}
			}
		?></tr>
		</thead>
		<tbody><?
	}

	// Display data
	foreach ($arResult['PRODUCTS'] as $productId => $arProduct)
	{
		?><tr><?
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
				$propertyInfo = $arResult['PROPS'][$headerId];
				$propertyType = '';
				if (isset($propertyInfo['PROPERTY_TYPE']))
				{
					$propertyType .= $propertyInfo['PROPERTY_TYPE'];
				}
				if ($propertyType != '' && isset($propertyInfo['USER_TYPE']) && $propertyInfo['USER_TYPE'] != '')
				{
					$propertyType .= ':'.$propertyInfo['USER_TYPE'];
				}
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
								switch ($propertyType)
								{
									case 'S:HTML':
									case 'S:ECrm':
										break;
									default:
										$additionalValue = htmlspecialcharsbx($additionalValue);
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
				switch ($propertyType)
				{
					case 'S:HTML':
					case 'S:ECrm':
						break;
					default:
						$value = htmlspecialcharsbx($value);
				}
				?><td><?=$value?></td><?
				$colIndex++;
				unset($propertyInfo, $propertyType);
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
						?><td><?=htmlspecialcharsbx($value)?></td><?

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
								?><td><?=htmlspecialcharsbx($value)?></td><?
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
						?><td><?=htmlspecialcharsbx($value)?></td><?
						$colIndex++;
						break;
					case 'VAT_INCLUDED':
					case 'ACTIVE':
						$value = $arProduct[$headerId] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
						?><td><?=htmlspecialcharsbx($value)?></td><?
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
						?><td><?=htmlspecialcharsbx(htmlspecialcharsbx($value))?></td><?
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
							?><td><?=$value?></td><?
							$colIndex++;
						}
				}

				switch ($headerId)
				{
					case 'PRICE':
						$value = CCrmCurrency::GetCurrencyName($arProduct['CURRENCY_ID']);
						?><td><?=htmlspecialcharsbx($value)?></td><?
						$colIndex++;
						break;
					case 'DESCRIPTION':
						$value = $arProduct['DESCRIPTION_TYPE'];
						?><td><?=htmlspecialcharsbx($value)?></td><?
						$colIndex++;
						break;
				}
			}
		}
		$columnsNumber = $colIndex - 1;
		unset($colIndex);

		if (!empty($additionalRows))
		{
			foreach ($additionalRows as $row)
			{
				?></tr><tr><?
				$prevColIndex = 1;
				foreach ($row as $colIndex => $value)
				{
					$emptyColumns = $colIndex - $prevColIndex - 1;
					for ($i = 0; $i < $emptyColumns; $i++)
					{
						?><td></td><?
					}
					?><td><?=$value?></td><?

					$prevColIndex = $colIndex;
				}

				if ($columnsNumber > $colIndex)
				{
					$emptyColumns = $columnsNumber - $colIndex;
					for ($i = 0; $i < $emptyColumns; $i++)
					{
						?><td></td><?
					}
				}
			}
		}
		?></tr><?
	}
	if (!$isStExport || $isStExportLastPage)
	{
		?></tbody></table><?
	}
}
