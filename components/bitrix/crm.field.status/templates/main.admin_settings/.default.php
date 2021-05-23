<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arResult
 */
$name = $arResult['additionalParameters']['NAME'];
?>

<tr>
	<td>
		<?= Loc::getMessage('USER_TYPE_CRM_ENTITY_TYPE') ?>:
	</td>
	<td>
		<?= SelectBoxFromArray(
			$name.'[ENTITY_TYPE]',
			$arResult['arr'],
			$arResult['value']
		) ?>
	</td>
</tr>