<?php

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$isStExport = (isset($arResult['STEXPORT_MODE']) && $arResult['STEXPORT_MODE'] === 'Y');
$isFirstPage = $arResult['IS_FIRST_PAGE'] === 'Y';
$isLastPage = $arResult['IS_LAST_PAGE'] === 'Y';

if ($isFirstPage): ?>
<html>
	<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html;charset=<?= LANG_CHARSET ?>">
	</head>
	<body>
	<table border="1">
		<thead>
			<tr>
				<?php foreach ($arResult['HEADERS'] as $key => $header): ?>
					<th><?= $header['content'] ?></th>
					<?php if ($key === 'SUM'): ?>
					<th><?= Loc::getMessage('CRM_COLUMN_CURRENCY') ?></th>
					<?php endif; ?>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
<?php endif; ?>

<?php foreach ($arResult['ENTRIES'] as $entry): ?>
			<tr>
				<?php foreach ($arResult['HEADERS'] as $fieldName => $header): ?>
					<td><?= $entry[$fieldName] ?></td>
					<?php if ($fieldName === 'SUM'): ?>
					<td><?= $entry['CURRENCY'] ?></td>
					<?php endif; ?>
				<?php endforeach; ?>
			</tr>
<?php endforeach; ?>

<?php if ($isLastPage): ?>
		</tbody>
	</table>
	</body>
</html>
<?php endif; ?>