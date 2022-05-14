<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
/** @var boolean $isProject */
/** @var boolean $isScrumProject */

Loc::loadMessages(__FILE__);

?>
<div class="socialnetwork-group-create-ex__create--switch-scrum <?= ($isScrumProject ? '--scrum' : '') ?>">
	<div class="socialnetwork-group-create-ex__content-block --space-bottom">
		<?php

		$defaultKey = (
			isset($arResult['ScrumSprintDurationValues'][$arResult['POST']['SCRUM_SPRINT_DURATION']])
				? $arResult['POST']['SCRUM_SPRINT_DURATION']
				: $arResult['ScrumSprintDurationDefaultKey']
		);
		$defaultValue = $arResult['ScrumSprintDurationValues'][$defaultKey];

		?>
		<div class="socialnetwork-group-create-ex__text --s ui-ctl-label-text"><?= Loc::getMessage('SONET_GCE_T_SCRUM_SPRINT_DURATION') ?></div>
		<div class="ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown" data-role="soc-net-dropdown" data-items="<?= htmlspecialcharsbx(Json::encode($arResult['ScrumSprintDurationValues'])) ?>" data-value="<?= htmlspecialcharsbx($arResult['POST']['SCRUM_SPRINT_DURATION']) ?>">
			<div class="ui-ctl-after ui-ctl-icon-angle"></div>
			<div class="ui-ctl-element"><?= htmlspecialcharsEx($defaultValue) ?></div>
			<input type="hidden" name="SCRUM_SPRINT_DURATION" value="<?= htmlspecialcharsbx($defaultKey) ?>">
		</div>

	</div>
	<div class="socialnetwork-group-create-ex__content-block --space-bottom"><?php

		$defaultKey = (
			isset($arResult['ScrumTaskResponsible'][$arResult['POST']['SCRUM_TASK_RESPONSIBLE']])
				? $arResult['POST']['SCRUM_TASK_RESPONSIBLE']
				: array_key_first($arResult['ScrumTaskResponsible'])
		);
		$defaultValue = $arResult['ScrumTaskResponsible'][$defaultKey];

		?>
		<div class="socialnetwork-group-create-ex__text --s ui-ctl-label-text"><?= Loc::getMessage('SONET_GCE_T_SCRUM_TASK_RESPONSIBLE') ?></div>
		<div class="ui-ctl ui-ctl-after-icon ui-ctl-w100 ui-ctl-dropdown" data-role="soc-net-dropdown" data-items="<?= htmlspecialcharsbx(Json::encode($arResult['ScrumTaskResponsible'])) ?>" data-value="<?= htmlspecialcharsbx($arResult['POST']['SCRUM_TASK_RESPONSIBLE']) ?>">
			<div class="ui-ctl-after ui-ctl-icon-angle"></div>
			<div class="ui-ctl-element"><?= htmlspecialcharsEx($defaultValue) ?></div>
			<input type="hidden" name="SCRUM_TASK_RESPONSIBLE" value="<?= htmlspecialcharsbx($defaultKey) ?>">
		</div>
	</div>
</div><?php
