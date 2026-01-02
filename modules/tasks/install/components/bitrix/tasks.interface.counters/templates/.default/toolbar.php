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

\Bitrix\Main\UI\Extension::load([
	'tasks.viewed',
	'ui.analytics',
	'ui.counterpanel',
]);
?>

<div class="ui-actions-bar__panel" data-role="tasks-component-counters"></div>
<script>
	BX.message(<?= \CUtil::PhpToJSObject(Loc::loadLanguageFile(__FILE__)) ?>);

	const counters = new BX.Tasks.Counters.Counters({
		renderTo: document.querySelector('[data-role="tasks-component-counters"]'),
		filterId: '<?= CUtil::JSEscape($arParams['GRID_ID']) ?>',
		role: '<?= CUtil::JSEscape($arParams['ROLE']) ?>',
		userId: <?= (int)$arParams['USER_ID'] ?>,
		targetUserId: <?= (int)$arParams['TARGET_USER_ID'] ?>,
		groupId: <?= (int)$arParams['GROUP_ID'] ?>,
		counterTypes: <?= CUtil::PhpToJSObject($arParams['COUNTERS']) ?>,
		counters: <?= CUtil::PhpToJSObject($arResult['COUNTERS']) ?>,
		signedParameters: <?=CUtil::PhpToJSObject($this->getComponent()->getSignedParameters()) ?>
	});

	counters.render();

	BX.ready(function() {
		counters.connectWithFilter();
	});
</script>

<?php
$shouldShowClue = $arResult['SHOULD_SHOW_COMMENT_COUNTER_AHA'] ?? false;
if ($shouldShowClue)
{
	\Bitrix\Main\UI\Extension::load([
		'tasks.clue',
	]);
}
?>

<script>
	const shouldShowClue = <?=($shouldShowClue? 'true': 'false')?>;
	BX.ready(function() {
		if (shouldShowClue)
		{
			const commentsCounter = document.querySelector('.ui-counter.ui-counter-success');
			if (BX.Type.isDomNode(commentsCounter))
			{
				const clue = new BX.Tasks.Clue({
					id: `tasks_comment_counter`,
					autoSave: true,
				});
				clue.show(BX.Tasks.Clue.SPOT.COMMENT_COUNTER, commentsCounter);
			}
		}
	});
</script>
