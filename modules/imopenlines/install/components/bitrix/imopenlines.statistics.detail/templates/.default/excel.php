<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if ($arResult['STEXPORT_IS_FIRST_PAGE'])
{
	?><html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?=LANG_CHARSET;?>">
		<style>
			.number0 {mso-number-format:0;}
			.number2 {mso-number-format:Fixed;}
		</style>
	</head>
	<body>
	<table border="1">
		<thead>
		<tr>
			<?php
			foreach($arResult['SELECTED_HEADERS'] as $arHeader)
			{
				if($arHeader['id'] === 'ACTION')
				{
					continue;
				}

				?><td><?=$arHeader['name']?></td><?php
			}
			?>
		</tr>
		</thead>
		<tbody><?php
}

foreach($arResult['ELEMENTS_ROWS'] as $arRow)
{
	?><tr><?php
		foreach($arResult['SELECTED_HEADERS'] as $arHeader)
		{
			if($arHeader['id'] === 'ACTION')
			{
				continue;
			}
			?><td><?php
			if(isset($arRow['columns'][$arHeader['id']]))
			{
				echo $arRow['columns'][$arHeader['id']];
			}
			elseif(isset($arRow['data'][$arHeader['id']]))
			{
				echo $arRow['data'][$arHeader['id']];
			}
			?></td><?php
		}
	?></tr><?php
}

if ($arResult['STEXPORT_IS_LAST_PAGE'])
{?>
		</tbody>
	</table>
	</body>
</html><?php
}