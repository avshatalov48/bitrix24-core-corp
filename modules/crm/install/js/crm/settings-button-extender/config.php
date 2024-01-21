<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$createTimeAliases = [];
$isAIEnabledInGlobalSettings = false;
$allAIOperationTypes = [];
$transcribeAIOperationType = 0;
$langAdditional = [];

if (\Bitrix\Main\Loader::includeModule('crm'))
{
	$container = \Bitrix\Crm\Service\Container::getInstance();

	$map = $container->getTypesMap();
	foreach ($map->getFactories() as $factory)
	{
		$createTimeAliases[$factory->getEntityTypeId()] =
			$factory->getEntityFieldNameByMap(\Bitrix\Crm\Item::FIELD_NAME_CREATED_TIME)
		;
	}

	$isAIEnabledInGlobalSettings = \Bitrix\Crm\Integration\AI\AIManager::isEnabledInGlobalSettings();
	if (\Bitrix\Crm\Integration\AI\AIManager::isAiCallAutomaticProcessingAllowed())
	{
		$allAIOperationTypes = \Bitrix\Crm\Integration\AI\AIManager::getAllOperationTypes();
		$transcribeAIOperationType = \Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording::TYPE_ID;
	}

	$langAdditional['CRM_COMMON_COPILOT'] = $container->getLocalization()->loadMessages()['CRM_COMMON_COPILOT'] ?? null;
}

return [
	'css' => 'dist/settings-button-extender.bundle.css',
	'js' => 'dist/settings-button-extender.bundle.js',
	'rel' => [
		'crm.activity.todo-notification-skip-menu',
		'crm.activity.todo-ping-settings-menu',
		'main.core.events',
		'main.core',
		'crm.kanban.restriction',
		'crm.kanban.sort',
		'main.popup',
	],
	'skip_core' => false,
	'settings' => [
		'createTimeAliases' => $createTimeAliases,
		'isAIEnabledInGlobalSettings' => $isAIEnabledInGlobalSettings,
		'allAIOperationTypes' => $allAIOperationTypes,
		'transcribeAIOperationType' => $transcribeAIOperationType,
	],
	'lang_additional' => $langAdditional,
];
