<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Text\HtmlFilter;
?>

<title><?=HtmlFilter::encode($arResult['Title'])?></title>
<meta http-equiv="Content-Type" content="text/html; charset='<?=LANG_CHARSET?>">

<table border="1">
	<thead>
	<tr>
		<?php foreach ($arResult['HEADERS'] as $header):?>
			<th><?=HtmlFilter::encode($header['name']);?></th>
		<?php endforeach;?>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($arResult['ROWS'] as $row):?>
		<tr>
			<?php foreach ($row['columns'] as $value):?>
				<td>
					<?php if (count($row) == 1):?><b><?php endif;?>
						<?=HtmlFilter::encode($value);?>
						<?php if (count($row) == 1):?></b><?php endif;?>
				</td>
			<?php endforeach;?>
		</tr>
	<?php endforeach;?>
	</tbody>
</table>