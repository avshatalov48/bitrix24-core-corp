<?php

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * @var $arParams
 * @var $arResult
 */

use Bitrix\Main\UI;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Uri;

UI\Extension::load([
	'ui.tooltip',
	'ui.link',
	'ui.urlpreview',
	'ui.icons.b24',
]);

$taskViewUri = new Uri($arParams['URL']);
$taskViewUri->addParams([
	'ta_sec' => 'comment',
	'ta_el' => 'title_click',
]);

?>
<div class="task-preview">
	<div class="task-preview-header"><?php
		$style = (!empty($arResult['TASK']['CREATED_BY_PHOTO']) ? 'background-image: url('. Uri::urnEncode($arResult['TASK']['CREATED_BY_PHOTO']) .');' : '');
		?><span class="ui-icon ui-icon-common-user task-preview-header-icon" title="<?= htmlspecialcharsbx($arResult['TASK']['CREATED_BY_FORMATTED']) ?>">
			<i style="<?= $style ?>"></i>
		</span>
		<span class="task-preview-header-title">
			<a id="a_<?= htmlspecialcharsbx($arResult['TASK']['CREATED_BY_UNIQID']) ?>" href="<?= htmlspecialcharsbx($arResult["TASK"]["CREATED_BY_PROFILE"]) ?>" target="_blank" bx-tooltip-user-id="<?= htmlspecialcharsbx($arResult['TASK']['CREATED_BY']) ?>">
				<?= htmlspecialcharsbx($arResult['TASK']['CREATED_BY_FORMATTED']) ?>
			</a>
		</span><?php

		if ((int)$arResult['TASK']['RESPONSIBLE_ID'] > 0)
		{
			?><span class="urlpreview__icon-destination"></span><?php
			$style = (!empty($arResult['TASK']['RESPONSIBLE_PHOTO']) ? 'background-image: url('. Uri::urnEncode($arResult['TASK']['RESPONSIBLE_PHOTO']) .');' : '');
			?><span class="ui-icon ui-icon-common-user task-preview-header-icon" title="<?= htmlspecialcharsbx($arResult['TASK']['RESPONSIBLE_FORMATTED']) ?>">
				<i style="<?= $style ?>"></i>
			</span>
			<span class="task-preview-header-title">
				<a id="a_<?= htmlspecialcharsbx($arResult['TASK']['RESPONSIBLE_UNIQID']) ?>" href="<?= htmlspecialcharsbx($arResult['TASK']['RESPONSIBLE_PROFILE']) ?>" target="_blank" bx-tooltip-user-id="<?= htmlspecialcharsbx($arResult['TASK']['RESPONSIBLE_ID']) ?>">
					<?= htmlspecialcharsbx($arResult['TASK']['RESPONSIBLE_FORMATTED']) ?>
				</a>
			</span><?php
		}

		?><a class="urlpreview__time" href="<?= htmlspecialcharsbx($taskViewUri->getUri()) ?>">
			<?= htmlspecialcharsbx($arResult['TASK']['CREATED_DATE_FORMATTED']) ?>
		</a>
	</div>
	<div class="task-preview-info">
		<a href="<?= htmlspecialcharsbx($taskViewUri->getUri()) ?>" target="_blank" class="ui-link ui-link-dashed"><?= htmlspecialcharsbx($arResult['TASK']['TITLE']) ?></a><br>
		<?= Loc::getMessage('TASKS_STATUS_' . $arResult['TASK']['REAL_STATUS']) ?><br>

		<?php
		if (!empty($arResult['TASK']['DEADLINE']))
		{
			?><?= Loc::getMessage('TASKS_DEADLINE')?>: <?= FormatDateFromDB($arResult['TASK']['DEADLINE'], 'SHORT') ?><br><?php
		}

		if (!empty($arResult['TASK']['CLOSED_DATE']))
		{
			?><?= Loc::getMessage('TASKS_CLOSED_DATE') ?>: <?= FormatDateFromDB($arResult['TASK']['CLOSED_DATE'], 'SHORT') ?><br><?php
		}
	?></div>
</div>
