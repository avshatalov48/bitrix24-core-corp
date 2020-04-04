<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

if (!empty($arResult["FIELDS_FORMATTED"]))
{
	if ($arResult["FORMAT"] == "table")
	{
		?><table class="crm-feed-info-table"><?
	}

	foreach($arResult["FIELDS_FORMATTED"] as $value)
	{
		if ($arResult["FORMAT"] == "table")
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
				array(
					"<tr>", 
					"</tr>",
					'<td class="crm-feed-info-left-cell">',
					'<td class="crm-feed-info-right-cell">',
					'<td colspan="2" class="crm-feed-info-right-cell">',
					'</td>',
				),
				$value
			);
		}
	}

	if ($arResult["FORMAT"] == "table")
	{
		?></table><?
	}	
}
?>

