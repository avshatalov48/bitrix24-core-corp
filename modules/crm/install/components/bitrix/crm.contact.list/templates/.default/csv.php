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
$isRequisiteMultiline = ($isStExport && isset($arResult['STEXPORT_REQUISITE_MULTILINE'])
	&& $arResult['STEXPORT_REQUISITE_MULTILINE'] === 'Y');
$isStExportFirstPage = (isset($arResult['STEXPORT_IS_FIRST_PAGE']) && $arResult['STEXPORT_IS_FIRST_PAGE'] === 'Y');

if ((!is_array($arResult['CONTACT']) || count($arResult['CONTACT']) <= 0) && (!$isStExport || $isStExportFirstPage))
{
	echo GetMessage('ERROR_CONTACT_IS_EMPTY');
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
		foreach ($arResult['SELECTED_HEADERS'] as $headerID)
		{
			$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
			if ($arHead)
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
	foreach ($arResult['CONTACT'] as $i => &$arContact)
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
				case 'HONORIFIC':
				{
					$result = isset($arResult['HONORIFIC'][$arContact['HONORIFIC']])
						? $arResult['HONORIFIC'][$arContact['HONORIFIC']] : '';
					break;
				}
				case 'TYPE_ID':
				{
					$result = $arResult['TYPE_LIST'][$arContact['TYPE_ID']];
					break;
				}
				case 'SOURCE_ID':
				{
					$result = $arResult['SOURCE_LIST'][$arContact['SOURCE_ID']];
					break;
				}
				case 'COMPANY_ID':
				{
					$result = $arResult['CONTACT'][$i]['COMPANY_TITLE'];
					break;
				}
				case 'EXPORT':
				{
					$result = $arResult['EXPORT_LIST'][$arContact['EXPORT']];
					break;
				}
				case 'CREATED_BY':
				{
					$result = $arContact['CREATED_BY_FORMATTED_NAME'];
					break;
				}
				case 'MODIFY_BY':
				{
					$result = $arContact['MODIFY_BY_FORMATTED_NAME'];
					break;
				}
				default:
				{
					if(isset($arResult['CONTACT_UF'][$i]) && isset($arResult['CONTACT_UF'][$i][$headerID]))
					{
						$result = $arResult['CONTACT_UF'][$i][$headerID];
					}
					elseif (is_array($arContact[$headerID]))
					{
						$result = implode(', ', $arContact[$headerID]);
					}
					else
					{
						$result = strval($arContact[$headerID]);
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