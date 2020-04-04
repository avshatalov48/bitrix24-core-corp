<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('DisableEventsCheck', true);

/** @var array $arResult */

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
		// Display headers
		foreach($arResult['SELECTED_HEADERS'] as $headerID)
		{
			$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
			if($arHead)
			{
				echo '"', str_replace('"', '""', $arHead['name']),'";';
			}
		}
		if ($isRequisiteMultiline)
		{
			foreach (array_keys($rqHeaders) as $rqHeaderId)
			{
				if (isset($rqHeaders[$rqHeaderId]))
				{
					echo '"', str_replace('"', '""', $rqHeaders[$rqHeaderId]['name']),'";';
				}
			}
		}
		echo "\n";
	}

	// Display data
	foreach ($arResult['COMPANY'] as $i => &$arCompany)
	{
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
				{
					$result = $arResult['COMPANY_TYPE_LIST'][$arCompany['COMPANY_TYPE']];
					break;
				}
				case 'EMPLOYEES':
				{
					$result = $arResult['EMPLOYEES_LIST'][$arCompany['EMPLOYEES']];
					break;
				}
				case 'INDUSTRY':
				{
					$result = $arResult['INDUSTRY_LIST'][$arCompany['INDUSTRY']];
					break;
				}
				case 'CURRENCY_ID':
				{
					$result = CCrmCurrency::GetCurrencyName($arCompany['CURRENCY_ID']);
					break;
				}
				case 'CREATED_BY':
				{
					$result = $arCompany['CREATED_BY_FORMATTED_NAME'];
					break;
				}
				case 'MODIFY_BY':
				{
					$result = $arCompany['MODIFY_BY_FORMATTED_NAME'];
					break;
				}
				case 'IS_MY_COMPANY':
				{
					$result = $arCompany['IS_MY_COMPANY'] === 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO');
					break;
				}
				default:
				{
					if(isset($arResult['COMPANY_UF'][$i]) && isset($arResult['COMPANY_UF'][$i][$headerID]))
					{
						$result = $arResult['COMPANY_UF'][$i][$headerID];
					}
					elseif (is_array($arCompany[$headerID]))
					{
						$result = implode(', ', $arCompany[$headerID]);
					}
					else
					{
						$result = strval($arCompany[$headerID]);
					}
				}
			}

			echo '"', str_replace('"', '""', htmlspecialcharsback($result)), '";';
		}
		if ($isRequisiteMultiline)
		{
			$rqCount = 0;
			if (is_array($arResult['STEXPORT_RQ_DATA'][$i]))
				$rqCount = count($arResult['STEXPORT_RQ_DATA'][$i]);
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
						$rqRow[] = '';
					$rqRows[] = $rqRow;
				}
				$rqCount = 1;
			}

			$rowIndex = 0;
			while ($rqCount--)
			{
				if ($rowIndex > 0)
				{
					echo "\n";
					foreach ($arResult['SELECTED_HEADERS'] as $headerId)
					{
						if(isset($arHeaders[$headerId]))
						{
							echo '"";';
						}
					}
				}

				if (is_array($rqRows[$rowIndex]))
				{
					foreach ($rqRows[$rowIndex] as $rqValue)
					{
						echo '"', str_replace('"', '""', $rqValue), '";';
					}
				}

				$rowIndex++;
			}
		}
		echo "\n";
	}
}