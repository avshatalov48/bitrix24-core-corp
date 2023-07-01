<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(__DIR__.'/helper.php');
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if($helper->checkHasFatals())
{
	return;
}

$isMyTasks = $arResult['USER_ID'] === $arResult['OWNER_ID'];

$emptyMessage = '';
if (
	$isMyTasks
	|| \Bitrix\Tasks\Util\User::isAdmin()
	|| CTasks::IsSubordinate($arParams['USER_ID'], $arResult['USER_ID'])
)
{
	$emptyMessage = GetMessageJS('TASKS_COUNTER_EMPTY');
}


$readAllTitle = Loc::getMessage('TASKS_COUNTER_NEW_COMMENTS_READ_ALL_TITLE');
$readAllButton =
	'<a href="javascript:;" class="tasks-counter-counter-button" data-counter-id="new_comments" id="tasksCommentsReadAll">'
		."<span class=\"tasks-counter-counter-button-icon\" data-hint=\"{$readAllTitle}\" data-hint-no-icon></span>"
		."<span class=\"tasks-counter-counter-button-text\">{$readAllTitle}</span>"
	.'</a>'
;

$arResult['TEMPLATE_DATA'] = [];
$arResult['JS_DATA'] = [
	'userId' => $arResult['USER_ID'] ?? null,
	'ownerId' => $arResult['OWNER_ID'] ?? null,
	'groupId' => $arParams['GROUP_ID'] ?? null,
	'filterId' => $arParams['FILTER_ID'] ?? null,
	'roleId' => $arResult['ROLE'] ?? null,
	'showCounters' => $arResult['SHOW_COUNTERS'] ?? null,
	'counters' => $arResult['COUNTERS'] ?? null,
	'foreign_counters' => $arResult['FOREIGN_COUNTERS'] ?? null,
	'project_mode'	=> (isset($arParams['GROUP_ID']) && $arParams['GROUP_ID'] > 0),
	'templates' => [
		'empty' => '<span id="tasksSimpleCounters" class="tasks-page-name">#TEXT#</span>',
		'total' => '<span id="tasksSimpleCounters" class="tasks-counter-page-name">#TEXT#</span>',
		'foreign' => '<span id="tasksForeignCounters" class="tasks-counter-page-name">#TEXT#</span>',
		'counter' =>
			'<a href="javascript:;" class="tasks-counter-container" data-counter-id="#COUNTER_ID#" data-counter-value="#COUNTER#" data-counter-code="#COUNTER_CODE#">'
				.'<span class="tasks-counter ui-counter ui-counter-#CLASS#"><span class="ui-counter-inner">#COUNTER#</span></span>'
				.'<span class="tasks-counter-text">#TEXT#</span>'
			.'</a>'
			.'#BUTTON#',
	],
	'buttons' => [
		'new_comments' => ($isMyTasks ? $readAllButton : ''),
	],
	'classes' => [
		'total' => '',
		'expired' => 'danger',
		'new_comments' => 'success',
		'foreign_expired' => 'gray',
		'foreign_comments' => 'gray'
	],
	'messages' => [
		'empty' => $emptyMessage,
		'total' => ($arResult['USER_ID'] === $arResult['OWNER_ID']) ? GetMessageJS('TASKS_COUNTER_TOTAL') : GetMessageJS('TASKS_COUNTER_TOTAL_EMPL'),
		'foreign' => GetMessageJS('TASKS_COUNTER_FOREIGN'),
		'not_viewed_0' => GetMessageJS('TASKS_COUNTER_NEW_PLURAL_0'),
		'not_viewed_1' => GetMessageJS('TASKS_COUNTER_NEW_PLURAL_1'),
		'not_viewed_2' => GetMessageJS('TASKS_COUNTER_NEW_PLURAL_2'),
		'expired_0' => GetMessageJS('TASKS_COUNTER_EXPIRED_PLURAL_0'),
		'expired_1' => GetMessageJS('TASKS_COUNTER_EXPIRED_PLURAL_1'),
		'expired_2' => GetMessageJS('TASKS_COUNTER_EXPIRED_PLURAL_2'),
		'new_comments_0' => GetMessageJS('TASKS_COUNTER_NEW_COMMENTS_PLURAL_0'),
		'new_comments_1' => GetMessageJS('TASKS_COUNTER_NEW_COMMENTS_PLURAL_1'),
		'new_comments_2' => GetMessageJS('TASKS_COUNTER_NEW_COMMENTS_PLURAL_2'),
		'wait_ctrl_0' => GetMessageJS('TASKS_COUNTER_WAIT_CTRL_PLURAL_0'),
		'wait_ctrl_1' => GetMessageJS('TASKS_COUNTER_WAIT_CTRL_PLURAL_1'),
		'wait_ctrl_2' => GetMessageJS('TASKS_COUNTER_WAIT_CTRL_PLURAL_2'),
		'wo_deadline_0' => GetMessageJS('TASKS_COUNTER_WO_DEADLINE_PLURAL_0'),
		'wo_deadline_1' => GetMessageJS('TASKS_COUNTER_WO_DEADLINE_PLURAL_1'),
		'wo_deadline_2' => GetMessageJS('TASKS_COUNTER_WO_DEADLINE_PLURAL_2'),
		'expired_soon_0' => GetMessageJS('TASKS_COUNTER_EXPIRED_CANDIDATES_PLURAL_0'),
		'expired_soon_1' => GetMessageJS('TASKS_COUNTER_EXPIRED_CANDIDATES_PLURAL_1'),
		'expired_soon_2' => GetMessageJS('TASKS_COUNTER_EXPIRED_CANDIDATES_PLURAL_2'),
		'foreign_expired_0' => GetMessageJS('TASKS_COUNTER_EXPIRED_PLURAL_0'),
		'foreign_expired_1' => GetMessageJS('TASKS_COUNTER_EXPIRED_PLURAL_1'),
		'foreign_expired_2' => GetMessageJS('TASKS_COUNTER_EXPIRED_PLURAL_2'),
		'foreign_comments_0' => GetMessageJS('TASKS_COUNTER_NEW_COMMENTS_PLURAL_0'),
		'foreign_comments_1' => GetMessageJS('TASKS_COUNTER_NEW_COMMENTS_PLURAL_1'),
		'foreign_comments_2' => GetMessageJS('TASKS_COUNTER_NEW_COMMENTS_PLURAL_2'),
	],
];
