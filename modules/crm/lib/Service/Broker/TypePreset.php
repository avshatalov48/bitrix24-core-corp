<?php

namespace Bitrix\Crm\Service\Broker;

use Bitrix\Crm\Service;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\EventResult;
use Bitrix\Main\IO\Path;
use Bitrix\Main\Localization\Loc;

class TypePreset
{
	public const COLLECT_EVENT_NAME = 'onCollectTypePresets';

	/**
	 * @return Service\TypePreset[]
	 */
	public function getList(): array
	{
		return array_merge($this->getPredefinedPresets(), $this->collectPresetsByEvent());
	}

	/**
	 * @return Service\TypePreset[]
	 */
	public function getPredefinedPresets(): array
	{
		$presets = [];
		foreach ($this->getPredefinedData() as $data)
		{
			$presets[] = new Service\TypePreset($data['fields'], $data['data']);
		}

		return $presets;
	}

	protected function getPredefinedData(): array
	{
		$iconsPath = \CComponentEngine::makeComponentPath('bitrix:crm.type.detail');
		$iconsPath = getLocalPath(Path::combine('components', $iconsPath, 'templates', '.default', 'images'));

		return [
			[
				'fields' => [
					'id' => 'bitrix:list',
					'title' => Loc::getMessage('CRM_TYPE_PRESET_LIST_TITLE'),
					'category' => 'main',
					'icon' => Path::combine($iconsPath, 'preset-list.svg'),
				],
				'data' => [
					'title' => Loc::getMessage('CRM_TYPE_PRESET_LIST_TITLE'),
					'isDocumentsEnabled' => true,
					'isUseInUserfieldEnabled' => true,
					'isSetOpenPermissions' => true,
				],
			],
			[
				'fields' => [
					'id' => 'bitrix:full',
					'title' => Loc::getMessage('CRM_TYPE_PRESET_FULL_TITLE'),
					'category' => 'main',
					'icon' => Path::combine($iconsPath, 'preset-full.svg'),
				],
				'data' => [
					'title' => Loc::getMessage('CRM_TYPE_PRESET_FULL_TITLE'),
					'isCategoriesEnabled' => true,
					'isStagesEnabled' => true,
					'isBeginCloseDatesEnabled' => true,
					'isClientEnabled' => true,
					'isLinkWithProductsEnabled' => true,
					'isMycompanyEnabled' => true,
					'isDocumentsEnabled' => true,
					'isSourceEnabled' => true,
					'isUseInUserfieldEnabled' => true,
					'isObserversEnabled' => true,
					'isRecyclebinEnabled' => true,
					'isAutomationEnabled' => true,
					'isBizProcEnabled' => true,
					'isSetOpenPermissions' => true,
					'isCountersEnabled' => true
				],
			],
			[
				'fields' => [
					'id' => 'bitrix:empty',
					'title' => Loc::getMessage('CRM_TYPE_PRESET_EMPTY_TITLE'),
					'category' => 'main',
					'icon' => Path::combine($iconsPath, 'preset-empty.svg'),
				],
				'data' => [
				],
			],
//			[
//				'fields' => [
//					'id' => 'bitrix:disabled',
//					'title' => 'Disabled preset',
//					'category' => 'second',
//					'disabled' => true,
//				],
//				'data' => [
//
//				],
//			],
		];
	}

	/**
	 * @return Service\TypePreset[]
	 */
	public function collectPresetsByEvent(): array
	{
		$presets = [];

		$event = new Event('crm', static::COLLECT_EVENT_NAME);
		EventManager::getInstance()->send($event);
		$results = $event->getResults();
		if (empty($results) || !is_array($results))
		{
			return $presets;
		}
		foreach ($results as $eventResult)
		{
			if ($eventResult->getType() !== EventResult::SUCCESS)
			{
				continue;
			}

			$eventPresets = $eventResult->getParameters()['presets'] ?? [];
			foreach ($eventPresets as $preset)
			{
				if (
					is_array($preset)
					&& isset($preset['fields'], $preset['data'])
					&& is_array($preset['fields'])
					&& is_array($preset['data'])
				)
				{
					$presets[] = new Service\TypePreset($preset['fields'], $preset['data']);
				}
			}
		}

		return $presets;
	}

	public function getCategories(): array
	{
		return [
			'main' => [
//				'title' => 'Main Category',
			],
//			'second' => [
//				'title' => 'Another Category',
//			],
		];
	}
}
