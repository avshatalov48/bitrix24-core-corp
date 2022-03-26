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
$APPLICATION->RestartBuffer();
// hack. any '.default' customized template should contain 'excel' page
Header('Content-Type: application/vnd.ms-excel');
Header('Content-Disposition: attachment;filename=crm_items.xls');
Header('Content-Type: application/octet-stream');
Header('Content-Transfer-Encoding: binary');

if ($arResult['FIRST_EXPORT_PAGE'])
{
?><html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?=LANG_CHARSET;?>">
	<style>
		.number0 {mso-number-format:0;}
		.number2 {mso-number-format:Fixed;}
	</style>
	<title></title>
</head>
<body>
<table border="1">
	<thead>
		<tr>
			<?php
		array_map(function($header) {
			?>
			<th><?=htmlspecialcharsbx($header['name'])?></th>
			<?php
		}, $arResult['HEADERS']);
		?></tr>
	</thead>
	<tbody><?php
}

foreach ($arResult['ITEMS'] as $item)
{
	?>
		<tr><?php
	array_map(function($header) use ($item) {
		?>
			<td><?=($item[$header['id']] ?? '')?></td>
		<?php
	}, $arResult['HEADERS']);
	?></tr>
	<?php
}

if ($arResult['LAST_EXPORT_PAGE'])
{
?>
	</tbody>
</table>
</body>
</html><?php
}
