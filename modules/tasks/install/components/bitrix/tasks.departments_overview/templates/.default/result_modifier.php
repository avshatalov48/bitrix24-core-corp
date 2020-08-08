<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Tasks\Util;

// create template controller with js-dependency injections
$arResult['HELPER'] = $helper = require(dirname(__FILE__).'/helper.php');
$arParams =& $helper->getComponent(
)->arParams; // make $arParams the same variable as $this->__component->arParams, as it really should be

if ($helper->checkHasFatals())
{
	return;
}

//region TITLE
$sTitle = $sTitleShort = GetMessage("TASKS_MANAGE_TITLE");
$APPLICATION->SetPageProperty("title", $sTitle);
$APPLICATION->SetTitle($sTitleShort);
//endregion TITLE

$arResult['JS_DATA'] = [
	'filterId' => $arParams['FILTER_ID'],
	'taskLimitExceeded' => $arResult['TASK_LIMIT_EXCEEDED'],
	'pathToTasks' => $arParams['PATH_TO_TASKS'],
];

function prepareEffective($row, $arParams)
{
	$color = $row['EFFECTIVE'] < 50 ? '#D2000D' : '#333';

	$url = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_TASKS'].'effective/',
		array('user_id' => $row['ID'])
	);

	return '<a href="'.
		   $url.'" style="color:'.
		   $color.
		   ';">'.
		   (int)$row['EFFECTIVE'].
		   '%</span>';
}

function prepareCounter($counter)
{
	$sub = '';
	if ($counter['NOTICE'] > 0)
	{
		$sub = '<span class="tasks-manage-counter-notify-digit">'.$counter['NOTICE'].'</span>';
	}
	return '<span class="tasks-manage-counter">
				<span class="tasks-manage-counter-total">
					<a href="'.$counter['URL'].'">'.$counter['ALL'].'</a>
				</span>
				<span class="tasks-manage-counter-notify">'.$sub.'</span>	
			</span>';
}

function prepareDepartments($row, $result)
{
	$list = [];
	$departments = $result['DEPARTMENTS'];

	if ($row['UF_DEPARTMENT'])
	{
		foreach ($row['UF_DEPARTMENT'] as $departmentId)
		{
			$list[] = '<a href="javascript:;" class="js-id-department" data-id="'.
					  $departmentId.
					  '">'.
					  htmlspecialcharsbx($departments[$departmentId]['NAME']).
					  '</a>';
		}
	}

	return join(', ', $list);
}

function prepareRow($row, $arParams, $arResult)
{
	$resultRow = array(
		'ID' => $row['ID'],
		'NAME' => prepareTaskRowUserBaloonHtml($row, $arParams),
		'DEPARTMENTS' => prepareDepartments($row, $arResult),

		'EFFECTIVE' => prepareEffective($row, $arParams),
		'RESPONSIBLE' => prepareCounter($arResult['COUNTERS'][$row['ID']]['RESPONSIBLE']),
		'ORIGINATOR' => prepareCounter($arResult['COUNTERS'][$row['ID']]['ORIGINATOR']),
		'ACCOMPLICE' => prepareCounter($arResult['COUNTERS'][$row['ID']]['ACCOMPLICE']),
		'AUDITOR' => prepareCounter($arResult['COUNTERS'][$row['ID']]['AUDITOR']),
	);

	return $resultRow;
}

function prepareTaskRowUserBaloonHtml($user, $arParams)
{
	$user['AVATAR'] = \Bitrix\Tasks\UI::getAvatar($user['PERSONAL_PHOTO'], 100, 100);
	$user['IS_EXTERNAL'] = Util\User::isExternalUser($user['ID']);
	$user['URL'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_PROFILE'],
		array("user_id" => $user['ID'])
	);

	$userAvatar = 'tasks-grid-avatar-empty';
	if ($user['AVATAR'])
	{
		$userAvatar = '';
	}

	$userName = '<span class="tasks-grid-avatar  '.$userAvatar.' " 
			'.($user['AVATAR'] ? 'style="background-image: url(\''.$user['AVATAR'].'\')"' : '').'></span>';

	$userName .= '<span class="tasks-grid-username-inner">'.
				 htmlspecialcharsbx(\Bitrix\Tasks\Util\User::formatName($user)).
				 '</span>';

	return '<div class="tasks-grid-username-wrapper">
				<a href="'.htmlspecialcharsbx($user['URL']).'" class="tasks-grid-username">'.$userName.'</a>
			</div>';
}

$arResult['ROWS'] = [];

foreach ($arResult['GRID']['DATA'] as $row)
{
	$rowItem = [
		"id" => $row["ID"],
		'columns' => prepareRow($row, $arParams, $arResult),
	];

	$arResult['ROWS'][] = $rowItem;
}

function prepareSummaryCounter($counter)
{
	$sub = '';
	if ($counter['NOTICE'] > 0)
	{
		$sub = '<span class="tasks-manage-counter-notify-digit">'.$counter['NOTICE'].'</span>';
	}

	return '<span class="tasks-manage-counter">
		<span class="tasks-manage-counter-total-bottom">'.$counter['ALL'].'</span>
		<span class="tasks-manage-counter-notify">'.$sub.'</span>
	</span>';
}

$rowItem = [
	"id" => 'summary',
	'columns' => [
		'ID' => $row['ID'],
		'NAME' => '<strong>'.GetMessage('TASKS_ROW_SUMMARY').'</strong>',

		'EFFECTIVE' => '<strong>'.round($arResult['SUMMARY']['EFFECTIVE']).'%'.'</strong>',
		'RESPONSIBLE' => prepareSummaryCounter($arResult['SUMMARY']['RESPONSIBLE']),
		'ORIGINATOR' => prepareSummaryCounter($arResult['SUMMARY']['ORIGINATOR']),
		'ACCOMPLICE' => prepareSummaryCounter($arResult['SUMMARY']['ACCOMPLICE']),
		'AUDITOR' => prepareSummaryCounter($arResult['SUMMARY']['AUDITOR']),
	]
];

$arResult['ROWS'][] = $rowItem;