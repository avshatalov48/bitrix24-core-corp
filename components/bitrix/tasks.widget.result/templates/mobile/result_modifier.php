<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2023 Bitrix
 */

/**
 * @var $arParams []
 * @var $arResult []
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$userId = $arParams['USER_ID'];
$taskId = $arParams['TASK_ID'];

$resultTutorial = new \Bitrix\Tasks\Internals\Marketing\OneOff\ResultTutorial($userId);
$isTutorialEnable = $resultTutorial->isEnabled();

$requireResult = \Bitrix\Tasks\Internals\Task\Result\ResultManager::requireResult($taskId);

$members = $arParams['ACCOMPLICES'];
$members[] = $arParams['RESPONSIBLE'];

$arResult['NEED_RESULT_TUTORIAL'] = $isTutorialEnable && $requireResult && in_array($userId, $members);