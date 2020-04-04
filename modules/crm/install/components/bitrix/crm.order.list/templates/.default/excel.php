<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);


if (!is_array($arResult['DEAL']) || !($USERS_CNT = count($arResult['DEAL']))):
	echo(GetMessage('ERROR_DEAL_IS_EMPTY_2'));
else:
	?><meta http-equiv="Content-type" content="text/html;charset=<?=LANG_CHARSET?>" />
	<table border="1">
	<thead>
		<tr><?
	// Build up associative array of headers
	$arHeaders = array();
	foreach ($arResult['HEADERS'] as $arHead)
		$arHeaders[$arHead['id']] = $arHead;

	$showProductRows = false;
	// Display headers
	foreach($arResult['SELECTED_HEADERS'] as $headerID):
		$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
		if(!$arHead)
			continue;

		// Special logic for PRODUCT_ROWS headers: expand product in 3 columns
		if($headerID === 'PRODUCT_ID'):
			$showProductRows = true;
			?><th><?=htmlspecialcharsbx(GetMessage('CRM_COLUMN_PRODUCT_NAME'))?></th><?
			?><th><?=htmlspecialcharsbx(GetMessage('CRM_COLUMN_PRODUCT_PRICE'))?></th><?
			?><th><?=htmlspecialcharsbx(GetMessage('CRM_COLUMN_PRODUCT_QUANTITY'))?></th><?
		else:
			?><th><?=$arHead['name']?></th><?
		endif;
	endforeach;
		?></tr>
	</thead>
	<tbody><?
	foreach ($arResult['DEAL'] as $i => &$arDeal):
		// Serialize each product row as deal with single product
		$productRows = $showProductRows && isset($arDeal['PRODUCT_ROWS']) ? $arDeal['PRODUCT_ROWS'] : array();
		$hasProducts = !empty($productRows);
		if(!$hasProducts)
		{
			// Deal has no product rows (or they are not displayed) - we have to create dummy for next loop by product rows only
			$productRows[] = array();
		}

		$dealData = array();
		foreach($productRows as $productRow):
		?><tr><?
			foreach($arResult['SELECTED_HEADERS'] as $headerID):
				$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
				if(!$arHead)
					continue;

				$headerID = $arHead['id'];
				if($headerID === 'PRODUCT_ID'):
					// Special logic for PRODUCT_ROWS: expand product in 3 columns
					?><td><?=isset($productRow['PRODUCT_NAME']) ? htmlspecialcharsbx($productRow['PRODUCT_NAME']) : ''?></td><?
					?><td><?=CCrmProductRow::GetPrice($productRow, '')?></td><?
					?><td><?=CCrmProductRow::GetQuantity($productRow, '')?></td><?
					continue;
				elseif($headerID === 'OPPORTUNITY'):
					// Special logic for OPPORTUNITY: replace it by product row sum if it specified
					if($hasProducts):
						?><td><?=round(CCrmProductRow::GetPrice($productRow) * CCrmProductRow::GetQuantity($productRow), 2)?></td><?
					else:
						?><td><?=isset($arDeal['OPPORTUNITY']) ? strval($arDeal['OPPORTUNITY']) : ''?></td><?
					endif;
					continue;
				endif;


				if(!isset($dealData[$headerID])):
					switch($arHead['id']):
						case 'CATEGORY_ID':
							$categoryID = !empty($arDeal['CATEGORY_ID']) ? $arDeal['CATEGORY_ID'] : 0;
							$dealData['CATEGORY_ID'] = isset($arDeal['DEAL_CATEGORY_NAME']) ? $arDeal['DEAL_CATEGORY_NAME'] : $categoryID;
							break;
						case 'STAGE_ID':
							$stageID = !empty($arDeal['STAGE_ID']) ? $arDeal['STAGE_ID'] : '';
							$dealData['STAGE_ID'] = isset($arDeal['DEAL_STAGE_NAME']) ? $arDeal['DEAL_STAGE_NAME'] : $stageID;
							break;
						case 'STATE_ID':
							$stateID = !empty($arDeal['STATE_ID']) ? $arDeal['STATE_ID'] : '';
							$dealData['STATE_ID'] = isset($arResult['STATE_LIST'][$stateID]) ? $arResult['STATE_LIST'][$stateID] : $stateID;
							break;
						case 'TYPE_ID':
							$typeID = !empty($arDeal['TYPE_ID']) ? $arDeal['TYPE_ID'] : '';
							$dealData['TYPE_ID'] = isset($arResult['TYPE_LIST'][$typeID]) ? $arResult['TYPE_LIST'][$typeID] : $typeID;
							break;
						case 'CURRENCY_ID':
							$dealData['CURRENCY_ID'] = CCrmCurrency::GetCurrencyName($arDeal['CURRENCY_ID']);
							break ;
						case 'EVENT_ID':
							$eventID = !empty($arDeal['EVENT_ID']) ? $arDeal['EVENT_ID'] : '';
							$dealData['EVENT_ID'] = isset($arResult['EVENT_LIST'][$eventID]) ? $arResult['EVENT_LIST'][$eventID] : $eventID;
							break;
						case 'COMPANY_ID':
							$dealData['COMPANY_ID'] = isset($arDeal['COMPANY_TITLE']) ? $arDeal['COMPANY_TITLE'] : '';
							break;
						case 'CONTACT_ID':
							$dealData['CONTACT_ID'] = isset($arDeal['CONTACT_FORMATTED_NAME']) ? $arDeal['CONTACT_FORMATTED_NAME'] : '';
							break;
						case 'CREATED_BY':
							$dealData['CREATED_BY'] = isset($arDeal['CREATED_BY_FORMATTED_NAME']) ? $arDeal['CREATED_BY_FORMATTED_NAME'] : '';
							break;
						case 'MODIFY_BY':
							$dealData['MODIFY_BY'] = isset($arDeal['MODIFY_BY_FORMATTED_NAME']) ? $arDeal['MODIFY_BY_FORMATTED_NAME'] : '';
							break;
						case 'CLOSED':
							$closed = !empty($arDeal['CLOSED']) ? $arDeal['CLOSED'] : 'N';
							$dealData['CLOSED'] = isset($arResult['CLOSED_LIST'][$closed]) ? $arResult['CLOSED_LIST'][$closed] : $closed;
							break;
						case 'COMMENTS':
							$dealData['COMMENTS'] = isset($arDeal['COMMENTS']) ? htmlspecialcharsback($arDeal['COMMENTS']) : '';
							break;
						default:
							if(isset($arResult['DEAL_UF'][$i]) && isset($arResult['DEAL_UF'][$i][$headerID]))
								$dealData[$headerID] = $arResult['DEAL_UF'][$i][$headerID];
							elseif (is_array($arDeal[$headerID]))
								$dealData[$headerID] = implode(', ', $arDeal[$headerID]);
							else
								$dealData[$headerID] = strval($arDeal[$headerID]);
					endswitch;
				endif;
				if(isset($dealData[$headerID])):
					?><td><?=$dealData[$headerID]?></td><?
				endif;
			endforeach;
		?></tr><?
		endforeach;
	endforeach;
	?></tbody>
	</table><?
endif;
