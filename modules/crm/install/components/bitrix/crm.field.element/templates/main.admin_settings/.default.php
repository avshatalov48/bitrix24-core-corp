<?php

if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arResult
 */

$name = $arResult['additionalParameters']['NAME'];
?>

<tr valign="top">
	<td><?= Loc::getMessage('USER_TYPE_CRM_ENTITY_TYPE') ?>:</td>
	<td>
		<?php
		$isFirst = true;
		foreach ($arResult['entities'] as $entityType => $isChecked):
			if (!$isFirst)
			{
				echo '<br />';
			}
			$isFirst = false;
			?>
			<input
				type="checkbox"
				name="<?= $name ?>[<?= $entityType ?>]"
				value="Y"
				<?= ($isChecked === 'Y' ? 'checked="checked"' : '') ?>
			>
			<?= \Bitrix\Main\Text\HtmlFilter::encode($arResult['titles'][$entityType]) ?>
		<?php endforeach;?>
	</td>
</tr>