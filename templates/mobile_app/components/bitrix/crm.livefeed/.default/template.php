<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!empty($arResult["FIELDS_FORMATTED"]))
{
	if ($arResult["FORMAT"] == "table")
	{
		?><table class="crm-feed-info-table"><?
	}

	if ($arResult["FORMAT"] == "table")
	{
		$arReplace = array(
			"<tr>", 
			"</tr>",
			'<td class="crm-feed-info-left-cell">',
			'<td class="crm-feed-info-right-cell">',
			'<td colspan="2" class="crm-feed-info-right-cell">',
			'</td>',
		);
	}
	else
	{
		$arReplace = array(
			"<div>", 
			"</div>",
			"",
			"",
			"",
			"",
		);
	}

	foreach($arResult["FIELDS_FORMATTED"] as $value)
	{
		echo str_replace(
			array(
				"#row_begin#", 
				"#row_end#",
				"#cell_begin_left#",
				"#cell_begin_right#",
				"#cell_begin_colspan2#",
				"#cell_end#"
			),
			$arReplace,
			$value
		);
	}

	if ($arResult["FORMAT"] == "table")
	{
		?></table><?
	}	
}
?>