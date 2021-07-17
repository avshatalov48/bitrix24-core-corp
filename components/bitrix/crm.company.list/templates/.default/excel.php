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
$isRequisiteMultiline = ($isStExport && isset($arResult['STEXPORT_REQUISITE_MULTILINE'])
	&& $arResult['STEXPORT_REQUISITE_MULTILINE'] === 'Y');
$isStExportFirstPage = (isset($arResult['STEXPORT_IS_FIRST_PAGE']) && $arResult['STEXPORT_IS_FIRST_PAGE'] === 'Y');
$isStExportLastPage = (isset($arResult['STEXPORT_IS_LAST_PAGE']) && $arResult['STEXPORT_IS_LAST_PAGE'] === 'Y');

if ((!is_array($arResult['COMPANY']) || count($arResult['COMPANY']) <= 0) && (!$isStExport || $isStExportFirstPage))
{
	echo(GetMessage('ERROR_COMPANY_IS_EMPTY'));
}
else
{
	// Build up associative array of headers
	$arHeaders = array();
	foreach ($arResult['HEADERS'] as $arHead)
	{
		$arHeaders[$arHead['id']] = $arHead;
	}
	$rqHeaders = array();
	if ($isRequisiteMultiline)
	{
		foreach ($arResult['STEXPORT_RQ_HEADERS'] as $rqHead)
		{
			$rqHeaders[$rqHead['id']] = $rqHead;
		}
	}

	if (!$isStExport || $isStExportFirstPage)
	{
		?><meta http-equiv="Content-type" content="text/html;charset=<?= LANG_CHARSET?>" />
		<table border="1">
		<thead>
			<tr><?
			// Display headers
			foreach($arResult['SELECTED_HEADERS'] as $headerID):
				$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
				if($arHead):
					?><th><?=$arHead['name']?></th><?
				endif;
			endforeach;
			if ($isRequisiteMultiline)
			{
				foreach (array_keys($rqHeaders) as $rqHeaderId)
				{
					if (isset($rqHeaders[$rqHeaderId]))
					{
						?><th><?=htmlspecialcharsbx($rqHeaders[$rqHeaderId]['name'])?></th><?
					}
				}
			}
			?></tr>
		</thead>
		<tbody><?
	}

	foreach ($arResult['COMPANY'] as $i => &$arCompany)
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
			$result = '';

			switch($headerID)
			{
				case 'COMPANY_TYPE':
					$result = isset($arCompany['COMPANY_TYPE']) ? $arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']] : '';
					break;
				case 'EMPLOYEES':
					$result = isset($arCompany['EMPLOYEES']) ? $arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']] : '';
					break;
				case 'INDUSTRY':
					$result = isset($arCompany['INDUSTRY']) ? $arResult['INDUSTRY_LIST'][$arCompany['INDUSTRY']] : '';
					break;
				case 'CURRENCY_ID':
					$result = isset($arCompany['CURRENCY_ID']) ? CCrmCurrency::GetEncodedCurrencyName($arCompany['CURRENCY_ID']) : '';
					break;
				case 'CREATED_BY':
					$result = isset($arCompany['CREATED_BY_FORMATTED_NAME']) ? $arCompany['CREATED_BY_FORMATTED_NAME'] : '';
					break;
				case 'MODIFY_BY':
					$result = isset($arCompany['MODIFY_BY_FORMATTED_NAME']) ? $arCompany['MODIFY_BY_FORMATTED_NAME'] : '';
					break;
				case 'COMMENTS':
					$result = isset($arCompany['COMMENTS']) ? htmlspecialcharsback($arCompany['COMMENTS']) : '';
					break;
				case 'IS_MY_COMPANY':
					$result = $arCompany['IS_MY_COMPANY'] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
					break;
				default:
					if(isset($arResult['COMPANY_UF'][$i]) && isset($arResult['COMPANY_UF'][$i][$headerID])):
						$result = $arResult['COMPANY_UF'][$i][$headerID];
					elseif (is_array($arResult['COMPANY'][$i][$headerID])):
						$result = implode(', ', $arCompany[$headerID]);
					else:
						$result = $arCompany[$headerID];
					endif;
			}
			?><td><?=$result?></td><?
		}
		if ($isRequisiteMultiline)
		{
			$rqCount = 0;
			if (is_array($arResult['STEXPORT_RQ_DATA'][$i]))
			{
				$rqCount = count($arResult['STEXPORT_RQ_DATA'][$i]);
			}
			if ($rqCount > 0)
			{
				$rqRows = $arResult['STEXPORT_RQ_DATA'][$i];
			}
			else
			{
				// fill empty row
				$rqRows = array();
				$colCount = count($rqHeaders);
				if ($colCount > 0)
				{
					$rqRow = array();
					while ($colCount--)
					{
						$rqRow[] = '';
					}
					$rqRows[] = $rqRow;
				}
				$rqCount = 1;
			}

			$rowIndex = 0;
			while ($rqCount--)
			{
				if ($rowIndex > 0)
				{
					?></tr><tr><?
					foreach ($arResult['SELECTED_HEADERS'] as $headerId)
					{
						if(isset($arHeaders[$headerId]))
						{
							?><td></td><?
						}
					}
				}

				if (is_array($rqRows[$rowIndex]))
				{
					foreach ($rqRows[$rowIndex] as $rqValue)
					{
						?><td><?= htmlspecialcharsbx($rqValue) ?></td><?
					}
				}

				$rowIndex++;
			}
		}
		?></tr><?
	}
	if (!$isStExport || $isStExportLastPage)
	{
		?></tbody></table><?
	}
}