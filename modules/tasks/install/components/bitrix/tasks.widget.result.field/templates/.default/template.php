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

\Bitrix\Main\UI\Extension::load('tasks.result');

/** @var array $arParams */
/** @var array $arResult */

$classList = [
	'tasks-feed-add-post-flag',
];
if (
	isset($arParams['HIDDEN'])
	&& $arParams['HIDDEN'] === 'Y'
)
{
	$classList[] = '--hidden';
}

$this->SetViewTarget('mpf_extra_buttons', 100);
?>
<label class="<?= implode(' ', $classList) ?>">
	<input type="hidden" name="IS_TASK_RESULT_FORM" value="Y">
	<input type="checkbox" class="tasks-feed-add-post-flag-checkbox" id="IS_TASK_RESULT" name="IS_TASK_RESULT" value="Y">
	<span type="checkbox" class="tasks-feed-add-post-flag-label"><?= \Bitrix\Main\Localization\Loc::getMessage('TASKS_COMMENT_RESULT_FIELD') ?></span>
</label>
<?php
$this->EndViewTarget();
