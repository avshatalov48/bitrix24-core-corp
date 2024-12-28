<?php

use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Tasks\Flow\Integration\AI\Control\AdviceService;
use Bitrix\Tasks\Flow\Integration\AI\Control\CollectedDataService;
use Bitrix\Tasks\Flow\Integration\AI\Copilot\RequestSender;
use Bitrix\Tasks\Flow\Integration\AI\Event\EfficiencyListener;

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/tasks/lang.php");

// all common phrases place here
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

$moduleRoot = $_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/tasks";

require_once($moduleRoot."/tools.php");
require_once __DIR__.'/autoload.php';

CJSCore::RegisterExt('task-popups', array(
	'js' => '/bitrix/js/tasks/task-popups.js',
	'css' => '/bitrix/js/tasks/css/task-popups.css',
	'rel' => ['ui.design-tokens'],
));

$eventManager = EventManager::getInstance();

$eventManager->addEventHandler('tasks', 'onFlowEfficiencyChanged', static function (Event $event): EventResult {
	return (new EfficiencyListener())->onFlowEfficiencyChanged($event);
});

$eventManager->addEventHandler('tasks', 'onFlowDataCollected', static function (Event $event): EventResult {
	return (new RequestSender())->onFlowDataCollected($event);
});

$eventManager->addEventHandler('tasks', 'onAfterTasksFlowDelete', static function (Event $event): EventResult {
	/** @var AdviceService $adviceService */
	$adviceService = ServiceLocator::getInstance()->get('tasks.flow.copilot.advice.service');

	return $adviceService->onFlowDeleted($event);
});

$eventManager->addEventHandler('tasks', 'onAfterTasksFlowDelete', static function (Event $event): EventResult {
	/** @var CollectedDataService $collectedDataService */
	$collectedDataService = ServiceLocator::getInstance()->get('tasks.flow.copilot.collected.data.service');

	return $collectedDataService->onFlowDeleted($event);
});

require_once($moduleRoot."/include/asset.php");
