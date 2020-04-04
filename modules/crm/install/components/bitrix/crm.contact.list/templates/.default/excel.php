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
$isStExportLastPage = (isset($arResult['STEXPORT_IS_LAST_PAGE']) && $arResult['STEXPORT_IS_LAST_PAGE'] === 'Y');

if ((!is_array($arResult['CONTACT']) || count($arResult['CONTACT']) <= 0) && (!$isStExport || $isStExportFirstPage))
{
	echo(GetMessage('ERROR_CONTACT_IS_EMPTY'));
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
		?><meta http-equiv="Content-type" content="text/html;charset=<?echo LANG_CHARSET?>" />
		<table border="1">
		<thead>
			<tr><?
			// Display headers
			foreach($arResult['SELECTED_HEADERS'] as $headerID)
			{
				$arHead = isset($arHeaders[$headerID]) ? $arHeaders[$headerID] : null;
				if($arHead):
					?><th><?=$arHead['name']?></th><?
				endif;
			}
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

	foreach ($arResult['CONTACT'] as $i => &$arContact)
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
				case 'TYPE_ID':
					$result = isset($arContact['TYPE_ID']) ? $arResult['TYPE_LIST'][$arContact['TYPE_ID']] : '';
					break;
				case 'HONORIFIC':
					$result = isset($arResult['HONORIFIC'][$arContact['HONORIFIC']])
						? $arResult['HONORIFIC'][$arContact['HONORIFIC']] : '';
					break;
				case 'SOURCE_ID':
					$result = isset($arContact['SOURCE_ID']) ? $arResult['SOURCE_LIST'][$arContact['SOURCE_ID']] : '';
					break;
				case 'COMPANY_ID':
					$result = isset($arContact['COMPANY_TITLE']) ? $arContact['COMPANY_TITLE'] : '';
					break;
				case 'CREATED_BY':
					$result = isset($arContact['CREATED_BY_FORMATTED_NAME']) ? $arContact['CREATED_BY_FORMATTED_NAME'] : '';
					break;
				case 'MODIFY_BY':
					$result = isset($arContact['MODIFY_BY_FORMATTED_NAME']) ? $arContact['MODIFY_BY_FORMATTED_NAME'] : '';
					break;
				case 'EXPORT':
					$result = isset($arContact['EXPORT']) ? $arResult['EXPORT_LIST'][$arContact['EXPORT']] : '';
					break;
				case 'COMMENTS':
					$result = isset($arContact['COMMENTS']) ? htmlspecialcharsback($arContact['COMMENTS']) : '';
					break;
				default:
					if(isset($arResult['CONTACT_UF'][$i]) && isset($arResult['CONTACT_UF'][$i][$headerID])):
						$result = $arResult['CONTACT_UF'][$i][$headerID];
					elseif(is_array($arContact[$headerID])):
						$result = implode(', ', $arContact[$headerID]);
					else:
						$result = strval($arContact[$headerID]);
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