<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();?>

<html>
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
		<?
		foreach($arResult['HEADERS'] as $arHeader)
		{
			if($arHeader['id'] === 'ACTION')
				continue;

			?><td><?=$arHeader['name'];?></td><?
		}
		?>
	</tr>
	</thead>
	<tbody>
	<?
	foreach($arResult["ELEMENTS_ROWS"] as $arRow)
	{
		?>
		<tr>
			<?
			foreach($arResult['HEADERS'] as $arHeader)
			{
				if($arHeader['id'] === 'ACTION')
					continue;
				?>
				<td>
					<?
					if(isset($arRow["columns"][$arHeader['id']]))
					{
						echo $arRow["columns"][$arHeader['id']];
					}
					elseif(isset($arRow["data"][$arHeader['id']]))
					{
						echo $arRow["data"][$arHeader['id']];
					}
					?>
				</td>
				<?
			}
			?>
		</tr>
		<?
	}
	?>
	</tbody>
</table>
</body>
</html>