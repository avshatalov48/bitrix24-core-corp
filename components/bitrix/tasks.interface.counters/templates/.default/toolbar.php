<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/* @var $arParams array */
/* @var $arResult array */

use Bitrix\Main\Localization\Loc;

\Bitrix\Main\UI\Extension::load('tasks.viewed');
?>

<div class="task-interface-toolbar--item --visible" data-role="tasks-component-counters">
	<div></div>
</div>
<script>
	BX.message(<?= \CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)) ?>);
	BX.ready(function() {
		var counters = new BX.Tasks.Counters.Counters({
			renderTo: document.querySelector('[data-role="tasks-component-counters"]'),
			filterId: '<?= CUtil::JSEscape($arParams['GRID_ID']) ?>',
			role: '<?= CUtil::JSEscape($arParams['ROLE']) ?>',
			userId: <?= (int)$arParams['USER_ID'] ?>,
			targetUserId: <?= (int)$arParams['TARGET_USER_ID'] ?>,
			groupId: <?= (int)$arParams['GROUP_ID'] ?>,
			counterTypes:  <?= CUtil::PhpToJSObject($arParams['COUNTERS']) ?>,
			counters:  <?= CUtil::PhpToJSObject($arResult['COUNTERS']) ?>,
			signedParameters: <?=CUtil::PhpToJSObject($this->getComponent()->getSignedParameters()) ?>
		});
		counters.render();
	});
</script>