<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

$createTimeAliases = [];
$isAIEnabledInGlobalSettings = false;
$isAIHasPackages = false;
$allAIOperationTypes = [];
$transcribeAIOperationType = 0;

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
	$isAIHasPackages = \Bitrix\Crm\Integration\AI\AIManager::isBaasServiceHasPackage();

	if ($isAIHasPackages && \Bitrix\Crm\Integration\AI\AIManager::isAiCallAutomaticProcessingAllowed())
	{
		$allAIOperationTypes = \Bitrix\Crm\Integration\AI\AIManager::getAllOperationTypes();
		$transcribeAIOperationType = \Bitrix\Crm\Integration\AI\Operation\TranscribeCallRecording::TYPE_ID;
	}
}

return [
	'css' => 'dist/settings-button-extender.bundle.css',
	'js' => 'dist/settings-button-extender.bundle.js',
	'rel' => [
		'crm.activity.todo-notification-skip-menu',
		'crm.activity.todo-ping-settings-menu',
		'crm.kanban.restriction',
		'crm.kanban.sort',
		'main.core.events',
		'main.popup',
		'ui.entity-selector',
		'main.core',
	],
	'skip_core' => false,
	'settings' => [
		'createTimeAliases' => $createTimeAliases,
		'isAIEnabledInGlobalSettings' => $isAIEnabledInGlobalSettings,
		'isAIHasPackages' => $isAIHasPackages,
		'allAIOperationTypes' => $allAIOperationTypes,
		'transcribeAIOperationType' => $transcribeAIOperationType,
		'aiDisabledSliderCode' => \Bitrix\Crm\Integration\AI\Operation\Scenario::FILL_FIELDS_SCENARIO_OFF_SLIDER_CODE,
		'aiPackagesEmptySliderCode' => \Bitrix\Crm\Integration\AI\AIManager::AI_PACKAGES_EMPTY_SLIDER_CODE,
	],
];
