<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var $APPLICATION \CMain */
/** @var array $arResult */
/** @var \CBitrixComponent $component */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Bitrix\Main\UI\Extension;

$messages = Loc::loadLanguageFile(__FILE__);

Extension::load(['ui.buttons', 'ui.fonts.opensans']);

$containerId = 'tasks-scrum-epic-' . $arResult['mode'] . '-selector';

?>
<div id="<?= $containerId ?>" class="tasks-scrum-epic-selector"></div>

<script>
	BX.message(<?= Json::encode($messages) ?>);

	(new BX.Tasks.Scrum.EpicSelector({
		groupId: '<?= $arResult['groupId'] ?>',
		taskId: '<?= $arResult['taskId'] ?>',
		epic: <?= Json::encode($arResult['epic']) ?>,
		canEdit: '<?= $arResult['canEdit'] ?>',
		mode: '<?= $arResult['mode'] ?>',
		inputName: '<?= $arResult['inputName'] ?>'
	}))
		.renderTo(document.getElementById('<?= $containerId ?>'))
	;
</script>