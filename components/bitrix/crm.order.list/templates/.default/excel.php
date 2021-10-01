<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

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
$isStExportLastPage = (isset($arResult['STEXPORT_IS_LAST_PAGE']) && $arResult['STEXPORT_IS_LAST_PAGE'] === 'Y');


if ((!is_array($arResult['ORDER']) || count($arResult['ORDER']) <=0) && (!$isStExport || $isStExportFirstPage))
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
	foreach($arResult['SELECTED_HEADERS'] as $headerID)
	{
		if (isset($arHeaders[$headerID]) && $headerID === 'ENTITIES_LINKS')
		{
			$showProductRows = true;
		}
	}

	if (!$isStExport || $isStExportFirstPage)
	{
		?><meta http-equiv="Content-type" content="text/html;charset=<?=LANG_CHARSET?>" />
		<table border="1">
		<thead>
			<tr>
			<?php
			// Display headers
			foreach($arResult['SELECTED_HEADERS'] as $headerID)
			{
				$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
				if(!$arHead)
				{
					continue;
				}
				?>
				<th><?=$arHead['name']?></th>
				<?
			}
			?>
			</tr>
		</thead>
		<tbody>
		<?
	}

	$arPersonTypes = CCrmPaySystem::getPersonTypesList();
	$arPaySystems = array();
	foreach (array_keys($arPersonTypes) as $personTypeId)
	{
		$arPaySystems[$personTypeId] = CCrmPaySystem::GetPaySystemsListItems($personTypeId, true);
	}
	unset($personTypeId);

	foreach ($arResult['ORDER'] as $orderId => $orderFields)
	{
		$productRows = $showProductRows && isset($orderFields['PRODUCT_ROWS']) ? $orderFields['PRODUCT_ROWS'] : array();
		if(count($productRows) == 0)
		{
			$productRows[] = array();
		}
		$orderData = array();
		$personTypeId = $orderFields['PERSON_TYPE_ID'];
		$ufFields = $arResult['ORDER_UF'][$orderId];
		foreach($productRows as $productRow)
		{
			?><tr><?
			foreach($arResult['SELECTED_HEADERS'] as $headerID)
			{
				$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
				if(!$arHead)
				{
					continue;
				}

				$headerID = $arHead['id'];
				if(!isset($orderData[$headerID]))
				{
					switch($headerID)
					{
						case 'SOURCE':
							$orderData['SOURCE'] = isset($orderFields['SOURCE']) ? $orderFields['SOURCE'] : '';
							break;
						case 'USER':
							$orderData['USER'] = isset($orderFields['USER_FORMATTED_NAME']) ? $orderFields['USER_FORMATTED_NAME'] : '';
							break;
						case 'STATUS_ID':
							$statusID = !empty($orderFields['STATUS_ID']) ? $orderFields['STATUS_ID'] : '';
							$orderData['STATUS_ID'] = isset($arResult['STATUS_LIST'][$statusID]) ? $arResult['STATUS_LIST'][$statusID] : $statusID;
							break;
						case 'CURRENCY':
							$orderData['CURRENCY'] = CCrmCurrency::GetCurrencyName($orderFields['CURRENCY']);
							break ;
						case 'RESPONSIBLE_ID':
							$orderData['RESPONSIBLE_BY'] = $orderFields['RESPONSIBLE'];
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
							$orderData['PERSON_TYPE_ID'] = isset($orderFields['PERSON_TYPE_ID']) ? $orderFields['PERSON_TYPE_ID'] : '';
							break;
						case 'PAY_SYSTEM_ID':
							$orderData['PAY_SYSTEM_ID'] = htmlspecialcharsbx(trim($arPaySystems[$personTypeId][$orderFields['PAY_SYSTEM_ID']]));
							break;
						case 'CREATED_BY':
							$orderData['CREATED_BY'] = isset($orderFields['CREATED_BY_FORMATTED_NAME']) ? $orderFields['CREATED_BY_FORMATTED_NAME'] : '';
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

						case 'ACTIVITY_ID':
							$orderData['ACTIVITY_ID'] = isset($orderFields['C_ACTIVITY_SUBJECT']) ? $orderFields['C_ACTIVITY_SUBJECT'] : '';
							break;

						case 'EMP_DEDUCTED_ID':
							$orderData['EMP_DEDUCTED_ID'] = isset($orderFields['EMP_DEDUCTED_ID_FORMATTED_NAME']) ? $orderFields['EMP_DEDUCTED_ID_FORMATTED_NAME'] : '';
							break;

						default:
							$currentValue = $orderFields[$headerID];
							if (isset($ufFields[$headerID]))
							{
								$currentValue = $ufFields[$headerID];
							}

							if (is_array($currentValue))
							{
								$orderData[$headerID] = implode(', ', $currentValue);
							}
							else
							{
								$orderData[$headerID] = (string)($currentValue);
							}
					}
				}
				if(isset($orderData[$headerID]))
				{
					?><td><?=$orderData[$headerID]?></td><?
				}
			}
			?></tr><?
		}
	}
	if (!$isStExport || $isStExportLastPage)
	{
		?></tbody>
		</table><?
	}
}
