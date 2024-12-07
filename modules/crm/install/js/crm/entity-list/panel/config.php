<?php

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

if (\Bitrix\Main\Loader::includeModule('tasks'))
{
	$createTaskUri = new \Bitrix\Main\Web\Uri(
		\CComponentEngine::makePathFromTemplate(
			Option::get('tasks', 'paths_task_user_edit', ''),
			[
				'task_id' => 0,
				'user_id' => \Bitrix\Main\Web\Uri::urnEncode('#USER_ID#'),
			]
		)
	);

	$createTaskUri->addParams([
		'UF_CRM_TASK' => '#ENTITY_KEYS#',
		'TITLE' => Loc::getMessage('CRM_ENTITY_LIST_PANEL_CREATE_TASK_PREFIX'),
		'TAGS' => Loc::getMessage('CRM_ENTITY_LIST_PANEL_CREATE_TASK_TAG'),
	]);
}
else
{
	$createTaskUri = null;
}

if (\Bitrix\Main\Loader::includeModule('sender') && \Bitrix\Main\Loader::includeModule('crm'))
{
	$sender = [
		'letterAddUrl' => \Bitrix\Crm\Integration\Sender\GridPanel::getPathToAddLetter(),
		'segmentEditUrl' => \Bitrix\Crm\Integration\Sender\GridPanel::getPathToEditSegment(),
		'availableLetterCodes' => \Bitrix\Sender\Integration\Bitrix24\Service::getAvailableMailingCodes(),
	];
}
else
{
	$sender = [];
}

return [
	'css' => 'dist/panel.bundle.css',
	'js' => 'dist/panel.bundle.js',
	'rel' => [
		'crm_activity_planner',
		'main.core.collections',
		'main.core.events',
		'crm.merger.batchmergemanager',
		'crm.autorun',
		'ui.entity-selector',
		'ui.notification',
		'ui.dialogs.messagebox',
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'taskCreateUrl' => $createTaskUri?->getUri(),
		'sender' => $sender,
	],
];
