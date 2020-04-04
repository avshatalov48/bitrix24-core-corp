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

if ((!is_array($arResult['ORDER']) || count($arResult['ORDER']) <= 0) && (!$isStExport || $isStExportFirstPage))
{
	echo GetMessage('ERROR_ORDER_IS_EMPTY_2');
}
else
{
	// Build up associative array of headers
	$arHeaders = array();
	foreach ($arResult['HEADERS'] as $arHead)
	{
		$arHeaders[$arHead['id']] = $arHead;
	}

	// Special logic for ENTITIES_LINKS headers: expand in 3 columns
	$showProductRows = false;
	foreach ($arResult['SELECTED_HEADERS'] as $headerID)
	{
		if (isset($arHeaders[$headerID]) && $headerID === 'ENTITIES_LINKS')
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
			echo '"'.$arHead['name'].'";';
		}
		echo "\n";
	}

	$arPersonTypes = \CCrmPaySystem::getPersonTypesList();
	$arPaySystems = array();
	foreach (array_keys($arPersonTypes) as $personTypeId)
	{
		$arPaySystems[$personTypeId] = \CCrmPaySystem::GetPaySystemsListItems($personTypeId, true);
	}
	unset($personTypeId);
	foreach ($arResult['ORDER'] as $i => &$orderFields)
	{
		// Serialize each product row as invoice with single product
		$productRows = $showProductRows && isset($orderFields['PRODUCT_ROWS']) ? $orderFields['PRODUCT_ROWS'] : array();
		if (count($productRows) == 0)
		{
			// Invoice has no product rows (or they are not displayed) - we have to create dummy for next loop by product rows only
			$productRows[] = array();
		}
		$orderData = array();
		$personTypeId = $orderFields['PERSON_TYPE_ID'];
		foreach ($productRows as $productRow)
		{
			foreach ($arResult['SELECTED_HEADERS'] as $headerID)
			{
				$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
				if (!$arHead)
				{
					continue;
				}

				$headerID = $arHead['id'];
				if (!isset($orderData[$headerID]))
				{
					switch ($arHead['id'])
					{
						case 'SOURCE':
							$orderData['SOURCE'] = htmlspecialcharsbx(trim($arPersonTypes[$orderFields['SOURCE']]));
							break;
						case 'USER':
							$orderData['USER_ID'] = isset($orderFields['USER_FORMATTED_NAME']) ? $orderFields['USER_FORMATTED_NAME'] : '';
							break;
						case 'CREATED_BY':
							$orderData['CREATED_BY'] = isset($orderFields['CREATED_BY_FORMATTED_NAME']) ? $orderFields['CREATED_BY_FORMATTED_NAME'] : '';
							break;
						case 'EMP_PAYED_ID':
							$orderData['EMP_PAYED_ID'] = isset($orderFields['EMP_PAYED_ID_FORMATTED_NAME']) ? $orderFields['EMP_PAYED_ID_FORMATTED_NAME'] : '';
							break;
						case 'EMP_CANCELED_ID':
							$orderData['EMP_CANCELED_ID'] = isset($orderFields['EMP_CANCELED_ID_FORMATTED_NAME']) ? $orderFields['EMP_CANCELED_ID_FORMATTED_NAME'] : '';
							break;
						case 'EMP_STATUS_ID':
							$orderData['EMP_STATUS_ID'] = isset($orderFields['EMP_STATUS_ID_FORMATTED_NAME']) ? $orderFields['EMP_STATUS_ID_FORMATTED_NAME'] : '';
							break;
						case 'EMP_ALLOW_DELIVERY_ID':
							$orderData['EMP_ALLOW_DELIVERY_ID'] = isset($orderFields['EMP_ALLOW_DELIVERY_ID_FORMATTED_NAME']) ? $orderFields['EMP_ALLOW_DELIVERY_ID_FORMATTED_NAME'] : '';
							break;
						case 'EMP_DEDUCTED_ID':
							$orderData['EMP_ALLOW_DELIVERY_ID'] = isset($orderFields['EMP_DEDUCTED_ID_FORMATTED_NAME']) ? $orderFields['EMP_DEDUCTED_ID_FORMATTED_NAME'] : '';
							break;
						case 'STATUS_ID':
							$statusID = !empty($orderFields['STATUS_ID']) ? $orderFields['STATUS_ID'] : '';
							$orderData['STATUS_ID'] = isset($arResult['STATUS_LIST'][$statusID]) ? $arResult['STATUS_LIST'][$statusID] : $statusID;
							break;
						case 'CURRENCY':
							$orderData['CURRENCY'] = CCrmCurrency::GetCurrencyName($orderFields['CURRENCY']);
							break ;
						case 'RESPONSIBLE':
							$orderData['RESPONSIBLE_ID'] = $orderFields['RESPONSIBLE_BY'];
							break ;
						case 'DATE_INSERT':
						case 'DATE_PAYED':
						case 'DATE_CANCELED':
						case 'DATE_ALLOW_DELIVERY':
						case 'DATE_DEDUCTED':
						case 'DATE_STATUS':
						case 'DATE_UPDATE':
							$site = new CSite();
							if (!empty($orderFields[$arHead['id']]))
								$orderData[$arHead['id']] = htmlspecialcharsbx(FormatDate('SHORT', MakeTimeStamp($orderFields[$arHead['id']], $site->GetDateFormat('FULL'))));
							else
								$orderData[$arHead['id']] = '';
							unset($site);
							break;
						case 'PERSON_TYPE_ID':
							$orderData['PERSON_TYPE_ID'] = htmlspecialcharsbx(trim($arPersonTypes[$orderFields['PERSON_TYPE_ID']]));
							break;
						case 'PAY_SYSTEM_ID':
							$orderData['PAY_SYSTEM_ID'] = htmlspecialcharsbx(trim($arPaySystems[$personTypeId][$orderFields['PAY_SYSTEM_ID']]));
							break;
						case 'PROPS':
							$preparedProps = [];
							if (is_array($orderFields[$headerID]))
							{
								foreach ($orderFields[$headerID] as $groupProperty)
								{
									$groupItems = $groupProperty['ITEMS'];
									if (!empty($groupItems) && is_array($groupItems))
									{
										foreach ($groupItems as $property)
										{
											if ($property['VALUE'] !== '')
											{
												$preparedProps[] = "{$property['NAME']}: {$property['VALUE']}";
											}
										}
									}
								}
							}
							$orderData[$headerID] = !empty($preparedProps) ? implode(', ', $preparedProps) : '';
							break;
						case 'PAYMENT':
							$paymentsData = [];
							if (is_array($orderFields[$headerID]))
							{
								foreach ($orderFields[$headerID] as $payment)
								{
									$paymentsData[] = "{$payment['PAY_SYSTEM_NAME']} ({$payment['SUM']})";
								}
							}
							$orderData[$headerID] = !empty($paymentsData) ? implode(', ', $paymentsData) : '';
							break;
						case 'SHIPMENT':
							$shipmentData = [];
							if (is_array($orderFields[$headerID]))
							{
								foreach ($orderFields[$headerID] as $shipment)
								{
									$shipmentData[] = "{$shipment['DELIVERY_NAME']} ({$shipment['PRICE_DELIVERY']})";
								}
							}
							$orderData[$headerID] = !empty($shipmentData) ? implode(', ', $shipmentData) : '';
							break;
						case 'BASKET':
							$preparedBasket = [];
							if (is_array($orderFields[$headerID]))
							{
								foreach ($orderFields[$headerID] as $basketItem)
								{
									$preparedBasket[] = "[{$basketItem['PRODUCT_ID']}] {$basketItem['NAME']} - {$basketItem['QUANTITY']} ({$basketItem['PRICE']})";
								}
							}
							$orderData[$headerID] = !empty($preparedBasket) ? implode(', ', $preparedBasket) : '';
							break;
						default:
							if (is_array($orderFields[$headerID]))
								$orderData[$headerID] = implode(', ', $orderFields[$headerID]);
							else
								$orderData[$headerID] = strval($orderFields[$headerID]);
					}
				}
				if (isset($orderData[$headerID]))
				{
					echo ($orderData[$headerID] != '') ? '"'.str_replace('"', '""', htmlspecialcharsback($orderData[$headerID])).'";' : ';';
				}
			}
			echo "\n";
		}
	}
}
