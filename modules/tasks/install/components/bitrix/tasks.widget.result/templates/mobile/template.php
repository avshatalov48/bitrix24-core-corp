<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

/**
 * @var $arParams []
 * @var $arResult []
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Internals\Task\Status;

Loc::loadMessages(__FILE__);

$commentsAsResult = [];
foreach ($arResult['RESULT_LIST'] as $result)
{
	$commentsAsResult[$result->getCommentId()] = $result->toArray(false);
}

?>
<div class="mobile-tasks-result-list-container" id="mobile-tasks-result-list-container-<?= $arParams['TASK_ID'] ?>">
	<div id="tasks-result-list-wrapper-<?= $arParams['TASK_ID'] ?>">
		<?php include($_SERVER["DOCUMENT_ROOT"].$this->getFolder().'/results.php'); ?>
	</div>
</div>

<script>
	BX.ready(function() {
		var result = BX.Tasks.ResultManager.getInstance()
			.initResult({
				taskId: <?= $arParams['TASK_ID'] ?>,
				isClosed: <?= in_array((int)$arResult['TASK_DATA']['STATUS'], [Status::SUPPOSEDLY_COMPLETED, Status::COMPLETED], true) ? 'true' : 'false'; ?>,
				comments: <?= CUtil::PhpToJsObject($commentsAsResult, false, false, true); ?>,
				context: 'task'
			});
	});

	BX.ready(function() {
		new BX.Tasks.TaskResultMobile(
			<?= $arParams['TASK_ID']; ?>,
			<?= $arParams['USER_ID']; ?>,
			{
				needTutorial: <?= $arResult['NEED_RESULT_TUTORIAL'] ? 'true' : 'false'; ?>,
				messages: {
					tutorialTitle: "<?= CUtil::JSEscape(Loc::getMessage('TASKS_RESULT_TUTORIAL_TITLE')) ?>",
					tutorialMessage: "<?= CUtil::JSEscape(Loc::getMessage('TASKS_RESULT_TUTORIAL_MESSAGE')) ?>",
				},
			}
		);
	});
</script>