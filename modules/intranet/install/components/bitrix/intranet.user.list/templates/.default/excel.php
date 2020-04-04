<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

Header('Content-Disposition: attachment; filename="users.xls"');
Header('Content-Type: application/force-download; name="users.xls"');

// add UTF-8 BOM marker
if (defined('BX_UTF') && BX_UTF)
{
	echo chr(239).chr(187).chr(191);
}

$headersList = array();
foreach ($arResult['HEADERS'] as $header)
{
	if (in_array($header['id'], $arResult['GRID_COLUMNS']))
	{
		$headersList[$header['id']] = $header;
	}
}

?><meta http-equiv="Content-type" content="text/html;charset=<?=LANG_CHARSET?>" />
<table border="1">
	<thead>
	<tr><?
		foreach($headersList as $header)
		{
			?><th><?=$header['name']?></th><?
		}
	?></tr>
	</thead>
	<tbody><?

	foreach($arResult['ROWS'] as $row)
	{
		$itemData = (isset($row['data']) && is_array($row['data']) ? $row['data'] : []);

		?><tr><?
		foreach($headersList as $header)
		{

			if (isset($itemData[$header['id']]))
			{
				?><td><?=$itemData[$header['id']]?></td><?
			}
			else
			{
				?><td>&nbsp;</td><?
			}
		}
		?></tr><?
	}
	?></tbody>
</table><?

