<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

use Bitrix\Tasks\Internals\Task\Status;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */

if (
	!isset($arParams['SHOW_RESULT_FIELD'])
	|| $arParams['SHOW_RESULT_FIELD'] !== 'N'
)
{
	$APPLICATION->IncludeComponent(
		'bitrix:tasks.widget.result.field',
		'',
		[],
		$this->getComponent(),
		[
			'HIDE_ICONS' => 'Y',
		]
	);
}

$commentsAsResult = [];
foreach ($arResult['RESULT_LIST'] as $result)
{
	$commentsAsResult[$result->getCommentId()] = $result->toArray(false);
}

?>
<div class="tasks-result-list-container" id="tasks-result-list-container-<?= $arParams['TASK_ID'] ?>">
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

		new BX.Tasks.TaskResult(<?= $arParams['TASK_ID']; ?>);
	});
</script>