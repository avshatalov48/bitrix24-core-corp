<?php

declare(strict_types=1);

namespace Bitrix\Tasks\Flow\Integration\AI;

use Bitrix\AI\Context;
use Bitrix\AI\Engine;
use Bitrix\AI\Quality;
use Bitrix\AI\Tuning;
use Bitrix\Main\Entity;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Tasks\Integration\AI\Settings;

final class FlowSettings extends Settings
{
	public const TUNING_CODE_FLOWS_TEXT_ENGINE = 'tasks_flows_text_generate_engine';
	private const TUNING_CODE_FLOWS_GROUP = 'tasks_flows_copilot';
	private const TUNING_CODE_FLOWS_TEXT = 'tasks_flows_allow_text_generate';
	private const HELPDESK_ID = 23501672;

	public static function isQueueMode(): bool
	{
		if (!Loader::includeModule('ai'))
		{
			return false;
		}

		if (!ModuleManager::isModuleInstalled('bitrix24'))
		{
			return true;
		}

		return (Option::get('ai', 'queue_mode', 'N') === 'Y') && (Option::get('ai', 'force_queue', 'N') === 'Y');
	}

	public static function isFlowsTextAvailable(): bool
	{
		if (!self::checkEngineAvailable(self::TEXT_CATEGORY))
		{
			return false;
		}

		$item = (new Tuning\Manager())->getItem(self::TUNING_CODE_FLOWS_TEXT);

		return $item ? $item->getValue() : true;
	}

	public static function onTuningLoad(): Entity\EventResult
	{
		$result = new Entity\EventResult();

		$items = [];
		$groups = [];

		$isFlowCopilotFeatureOn = (bool)(Option::get('tasks', FlowCopilotFeature::FEATURE, true));
		if (!$isFlowCopilotFeatureOn)
		{
			$result->modifyFields([
				'items' => $items,
				'groups' => $groups,
			]);

			return $result;
		}

		if (Engine::getByCategory(self::TEXT_CATEGORY, Context::getFake()))
		{
			$groups[self::TUNING_CODE_FLOWS_GROUP] = [
				'title' => Loc::getMessage('TASKS_AI_SETTINGS_FLOWS_COPILOT_TITLE'),
				'description' => Loc::getMessage('TASKS_AI_SETTINGS_FLOWS_COPILOT_DESC'),
				'helpdesk' => self::HELPDESK_ID,
			];

			$items[self::TUNING_CODE_FLOWS_TEXT] = [
				'group' => self::TUNING_CODE_FLOWS_GROUP,
				'title' => Loc::getMessage('TASKS_AI_SETTINGS_FLOWS_COPILOT_TITLE'),
				'header' => Loc::getMessage('TASKS_AI_SETTINGS_ALLOW_FLOWS_COPILOT_DESC'),
				'type' => Tuning\Type::BOOLEAN,
				'default' => true,
				'sort' => 10,
			];

			$quality = new Quality(
				[
					Quality::QUALITIES['give_advice'],
				],
			);

			$items[self::TUNING_CODE_FLOWS_TEXT_ENGINE] = array_merge(
				Tuning\Defaults::getProviderSelectFieldParams(Engine::CATEGORIES['text'], $quality),
				[
					'group' => self::TUNING_CODE_FLOWS_GROUP,
					'title' => Loc::getMessage('TASKS_AI_SETTINGS_FLOWS_COPILOT_ENGINE_DESC'),
					'sort' => 20,
				],
			);
		}

		$result->modifyFields([
			'items' => $items,
			'groups' => $groups,
		]);

		return $result;
	}
}
