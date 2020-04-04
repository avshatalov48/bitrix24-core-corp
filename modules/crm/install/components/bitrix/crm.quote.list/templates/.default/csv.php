<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

/**
 * @var array $arParams
 * @var array $arResult
 * @var \CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CDatabase $DB
 */

$isStExport = (isset($arResult['STEXPORT_MODE']) && $arResult['STEXPORT_MODE'] === 'Y');
$isStExportFirstPage = (isset($arResult['STEXPORT_IS_FIRST_PAGE']) && $arResult['STEXPORT_IS_FIRST_PAGE'] === 'Y');

if ((!is_array($arResult['QUOTE']) || count($arResult['QUOTE']) <= 0) && (!$isStExport || $isStExportFirstPage))
{
	echo GetMessage('ERROR_QUOTE_IS_EMPTY_2');
}
else
{
	// Build up associative array of headers
	$arHeaders = array();
	foreach ($arResult['HEADERS'] as $arHead)
	{
		$arHeaders[$arHead['id']] = $arHead;
	}

	// Special logic for PRODUCT_ROWS and ENTITIES_LINKS headers: expand product in 3 columns
	$showProductRows = false;
	foreach ($arResult['SELECTED_HEADERS'] as $headerID)
	{
		if (isset($arHeaders[$headerID]) && $headerID === 'PRODUCT_ID')
		{
			$showProductRows = true;
		}
	}


	if (!$isStExport || $isStExportFirstPage)
	{
		// Display headers
		foreach ($arResult['SELECTED_HEADERS'] as $headerID)
		{
			$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
			if (!$arHead)
			{
				continue;
			}

			// Special logic for PRODUCT_ROWS and ENTITIES_LINKS headers: expand product in 3 columns
			if ($headerID === 'PRODUCT_ID')
			{
				echo '"', GetMessage('CRM_COLUMN_PRODUCT_NAME'), '";';
				echo '"', GetMessage('CRM_COLUMN_PRODUCT_PRICE'), '";';
				echo '"', GetMessage('CRM_COLUMN_PRODUCT_QUANTITY'), '";';
			}
			elseif ($headerID === 'ENTITIES_LINKS')
			{
				echo '"', GetMessage('CRM_COLUMN_LEAD_ID'), '";';
				echo '"', GetMessage('CRM_COLUMN_DEAL_ID'), '";';
				echo '"', GetMessage('CRM_COLUMN_COMPANY_ID'), '";';
				echo '"', GetMessage('CRM_COLUMN_CONTACT_ID'), '";';
			}
			else
			{
				echo '"', str_replace('"', '""', $arHead['name']), '";';
			}
		}
		echo "\n";
	}

	// Display data
	foreach ($arResult['QUOTE'] as $i => &$arQuote)
	{
		// Serialize each product row as quote with single product
		$productRows = $showProductRows && isset($arQuote['PRODUCT_ROWS']) ? $arQuote['PRODUCT_ROWS'] : array();
		if(count($productRows) == 0)
		{
			// Quote has no product rows (or they are not displayed) - we have to create dummy for next loop by product rows only
			$productRows[] = array();
		}

		$quoteData = array();
		foreach($productRows as $productRow)
		{
			foreach($arResult['SELECTED_HEADERS'] as $headerID)
			{
				$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
				if(!$arHead)
				{
					continue;
				}

				$headerID = $arHead['id'];
				if ($headerID === 'PRODUCT_ID')
				{
					// Special logic for PRODUCT_ROWS: expand product in 3 columns
					echo '"', isset($productRow['PRODUCT_NAME']) ? str_replace('"', '""', $productRow['PRODUCT_NAME']) : '', '";';
					echo '"', CCrmProductRow::GetPrice($productRow, ''), '";';
					echo '"', CCrmProductRow::GetQuantity($productRow, ''), '";';

					continue;
				}
				if ($headerID === 'ENTITIES_LINKS')
				{
					// Special logic for ENTITIES_LINKS: expand in 3 columns
					echo ($arQuote['LEAD_TITLE'] != '') ? '"'.str_replace('"', '""', htmlspecialcharsback($arQuote['LEAD_TITLE'])).'";' : ';';
					echo ($arQuote['DEAL_TITLE'] != '') ? '"'.str_replace('"', '""', htmlspecialcharsback($arQuote['DEAL_TITLE'])).'";' : ';';
					echo ($arQuote['COMPANY_TITLE'] != '') ? '"'.str_replace('"', '""', htmlspecialcharsback($arQuote['COMPANY_TITLE'])).'";' : ';';
					echo ($arQuote['CONTACT_FORMATTED_NAME'] != '') ? '"'.str_replace('"', '""', htmlspecialcharsback($arQuote['CONTACT_FORMATTED_NAME'])).'";' : ';';

					continue;
				}
				if ($headerID === 'OPPORTUNITY')
				{
					// Special logic for OPPORTUNITY: replace it by product row sum if it specified
					if(isset($productRow['ID']) && intval($productRow['ID']) > 0)
					{
						echo '"', round(CCrmProductRow::GetPrice($productRow) * CCrmProductRow::GetQuantity($productRow), 2), '";';
					}
					else
					{
						echo '"', isset($arQuote['OPPORTUNITY']) ? strval($arQuote['OPPORTUNITY']) : '', '";';
					}

					continue;
				}

				if(!isset($quoteData[$headerID]))
				{
					switch($headerID)
					{
						case 'STATUS_ID':
						{
							$statusID = !empty($arQuote['STATUS_ID']) ? $arQuote['STATUS_ID'] : '';
							$quoteData['STATUS_ID'] = isset($arResult['STATUS_LIST'][$statusID]) ? $arResult['STATUS_LIST'][$statusID] : $statusID;
							break;
						}
						/*case 'STATE_ID':
						{
							$stateID = !empty($arQuote['STATE_ID']) ? $arQuote['STATE_ID'] : '';
							$quoteData['STATE_ID'] = isset($arResult['STATE_LIST'][$stateID]) ? $arResult['STATE_LIST'][$stateID] : $stateID;
							break;
						}*/
						/*case 'TYPE_ID':
						{
							$typeID = !empty($arQuote['TYPE_ID']) ? $arQuote['TYPE_ID'] : '';
							$quoteData['TYPE_ID'] = isset($arResult['TYPE_LIST'][$typeID]) ? $arResult['TYPE_LIST'][$typeID] : $typeID;
							break;
						}*/
						case 'CURRENCY_ID':
						{
							$quoteData['CURRENCY_ID'] = CCrmCurrency::GetCurrencyName($arQuote['CURRENCY_ID']);
							break;
						}
						/*case 'EVENT_ID':
						{
							$eventID = !empty($arQuote['EVENT_ID']) ? $arQuote['EVENT_ID'] : '';
							$quoteData['EVENT_ID'] = isset($arResult['EVENT_LIST'][$eventID]) ? $arResult['EVENT_LIST'][$eventID] : $eventID;
							break;
						}*/
						case 'COMPANY_ID':
						{
							$quoteData['COMPANY_ID'] = isset($arQuote['COMPANY_TITLE']) ? $arQuote['COMPANY_TITLE'] : '';
							break;
						}
						case 'LEAD_ID':
						{
							$quoteData['LEAD_ID'] = isset($arQuote['LEAD_TITLE']) ? $arQuote['LEAD_TITLE'] : '';
							break;
						}
						case 'DEAL_ID':
						{
							$quoteData['DEAL_ID'] = isset($arQuote['DEAL_TITLE']) ? $arQuote['DEAL_TITLE'] : '';
							break;
						}
						case 'CONTACT_ID':
						{
							$quoteData['CONTACT_ID'] = isset($arQuote['CONTACT_FULL_NAME']) ? $arQuote['CONTACT_FULL_NAME'] : '';
							break;
						}
						case 'MYCOMPANY_ID':
						{
							$quoteData['MYCOMPANY_ID'] = isset($arQuote['MYCOMPANY_TITLE']) ? $arQuote['MYCOMPANY_TITLE'] : '';
							break;
						}
						case 'CLOSED':
						{
							$closed = !empty($arQuote['CLOSED']) ? $arQuote['CLOSED'] : 'N';
							$quoteData['CLOSED'] = isset($arResult['CLOSED_LIST'][$closed]) ? $arResult['CLOSED_LIST'][$closed] : $closed;
							break;
						}
						default:
						{
							if(isset($arResult['QUOTE_UF'][$i]) && isset($arResult['QUOTE_UF'][$i][$headerID]))
							{
								$quoteData[$headerID] = $arResult['QUOTE_UF'][$i][$headerID];
							}
							elseif (is_array($arQuote[$headerID]))
							{
								$quoteData[$headerID] = implode(', ', $arQuote[$headerID]);
							}
							else
							{
								$quoteData[$headerID] = strval($arQuote[$headerID]);
							}
						}
					}
				}

				if(isset($quoteData[$headerID]))
				{
					echo '"', str_replace('"', '""', htmlspecialcharsback($quoteData[$headerID])), '";';
				}
			}
			echo "\n";
		}
	}
}