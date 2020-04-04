<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(dirname(__FILE__).'/helper.php');
$arParams =& $helper->getComponent()->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if($helper->checkHasFatals())
{
	return;
}

// you may parse some additional template parameters
//$this->__component->tryParseURIParameter($arParams['PARAM1'], '');
//$this->__component->tryParseIntegerParameter($arParams['PARAM2'], 0, true);
//$this->__component->tryParseBooleanParameter($arParams['PARAM3'], false);

$arResult['TEMPLATE_DATA'] = array(
	// contains data generated in result_modifier.php
);
$arResult['JS_DATA'] = array(
	'userId'=> $arParams['USER_ID'],
	'groupId'=> $arParams['GROUP_ID'],
	'filterId'=> $arParams['FILTER_ID'],
	'counters'=>$arResult['COUNTERS'],
	'templates'=>array(
		'empty'=>'<span class="tasks-page-name">#TEXT#</span>',
		'total'=>'<!--<span class="tasks-counter-total">#COUNTER#</span>-->'
				 .'<span class="tasks-counter-page-name">#TEXT#</span>',
		'counter'=>'<a href="javascript:;" class="tasks-counter-container #CLASS#" 
						data-counter-id="#COUNTER_ID#" 
						data-counter-value="#COUNTER#"
						data-counter-code="#COUNTER_CODE#">'
					.'		<span class="tasks-counter-inner">'
					.'			<span class="tasks-counter-number">#COUNTER#</span>'
					.'			<span class="tasks-counter-text">#TEXT#</span>'
					.'		</span>'
					.'	</a>',
	),
	'classes'=>array(
		'total'=>'',
		'wo_deadline'=>'',
		'expired'=>'task-status-text-color-overdue',
		'expired_soon'=>'task-status-text-color-waiting',
		'not_viewed'=>'task-status-text-color-new',
		'wait_ctrl'=>'task-status-text-color-waiting',
	),
	'messages'=>array(
		'empty'=>GetMessageJS('TASKS_COUNTER_EMPTY'),

		'total'=>GetMessageJS('TASKS_COUNTER_TOTAL'),

		'not_viewed_0'=>GetMessageJS('TASKS_COUNTER_NEW_PLURAL_0'),
		'not_viewed_1'=>GetMessageJS('TASKS_COUNTER_NEW_PLURAL_1'),
		'not_viewed_2'=>GetMessageJS('TASKS_COUNTER_NEW_PLURAL_2'),

		'expired_0'=>GetMessageJS('TASKS_COUNTER_EXPIRED_PLURAL_0'),
		'expired_1'=>GetMessageJS('TASKS_COUNTER_EXPIRED_PLURAL_1'),
		'expired_2'=>GetMessageJS('TASKS_COUNTER_EXPIRED_PLURAL_2'),

		'wait_ctrl_0'=>GetMessageJS('TASKS_COUNTER_WAIT_CTRL_PLURAL_0'),
		'wait_ctrl_1'=>GetMessageJS('TASKS_COUNTER_WAIT_CTRL_PLURAL_1'),
		'wait_ctrl_2'=>GetMessageJS('TASKS_COUNTER_WAIT_CTRL_PLURAL_2'),

		'wo_deadline_0'=>GetMessageJS('TASKS_COUNTER_WO_DEADLINE_PLURAL_0'),
		'wo_deadline_1'=>GetMessageJS('TASKS_COUNTER_WO_DEADLINE_PLURAL_1'),
		'wo_deadline_2'=>GetMessageJS('TASKS_COUNTER_WO_DEADLINE_PLURAL_2'),

		'expired_soon_0'=>GetMessageJS('TASKS_COUNTER_EXPIRED_CANDIDATES_PLURAL_0'),
		'expired_soon_1'=>GetMessageJS('TASKS_COUNTER_EXPIRED_CANDIDATES_PLURAL_1'),
		'expired_soon_2'=>GetMessageJS('TASKS_COUNTER_EXPIRED_CANDIDATES_PLURAL_2'),
	)
);