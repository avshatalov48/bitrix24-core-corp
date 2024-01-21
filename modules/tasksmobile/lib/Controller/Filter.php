<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Socialnetwork\Component\WorkgroupList;
use Bitrix\Tasks\Internals\Counter;
use Bitrix\Tasks\Internals\Counter\CounterDictionary;
use Bitrix\Tasks\Internals\Counter\Role;
use Bitrix\Tasks\Internals\Project\Provider;

Loader::requireModule('socialnetwork');

class Filter extends Controller
{
	public function getTaskListPresetsAction(int $groupId = 0): array
	{
		/** @var \Bitrix\Tasks\Helper\Filter $filterInstance */
		$filterInstance = \Bitrix\Tasks\Helper\Filter::getInstance($this->getCurrentUser()->getId(), $groupId);
		$filterOptions = $filterInstance->getOptions();
		$presets = $filterInstance->getAllPresets();

		foreach (array_keys($presets) as $id)
		{
			$filterSettings = ($filterOptions->getFilterSettings($id) ?? $filterOptions->getDefaultPresets()[$id]);
			$sourceFields = $filterInstance->getFilters();
			$presets[$id]['preparedFields'] = Options::fetchFieldValuesFromFilterSettings($filterSettings, [], $sourceFields);
		}

		return $this->preparePresetsForOutput($presets);
	}

	public function getProjectListPresetsAction(): array
	{
		$provider = new Provider($this->getCurrentUser()->getId(), WorkgroupList::MODE_TASKS_PROJECT);

		return $this->preparePresetsForOutput($provider->getPresets());
	}

	public function getScrumListPresetsAction(): array
	{
		$provider = new Provider($this->getCurrentUser()->getId(), WorkgroupList::MODE_TASKS_SCRUM);

		return $this->preparePresetsForOutput($provider->getPresets());
	}

	public function getSearchBarPresetsAction(int $groupId = 0): array
	{
		/** @var \Bitrix\Tasks\Helper\Filter $filterInstance */
		$filterInstance = \Bitrix\Tasks\Helper\Filter::getInstance($this->getCurrentUser()->getId(), $groupId);
		$presets = $filterInstance->getAllPresets();
		unset(
			$presets[Options::DEFAULT_FILTER],
			$presets[Options::TMP_FILTER]
		);

		$presets = array_map(
			static fn (string $key) => [
				'id' => $key,
				'name' => (string)$presets[$key]['name'],
				'default' => (bool)$presets[$key]['default'],
			],
			array_keys($presets)
		);

		return [
			'presets' => $presets,
			'counters' => [],
		];
	}

	private function preparePresetsForOutput(array $presets): array
	{
		unset(
			$presets[Options::DEFAULT_FILTER],
			$presets[Options::TMP_FILTER]
		);

		return array_map(
			static fn ($key) => [
				'id' => $key,
				'name' => $presets[$key]['name'],
				'fields' => ($presets[$key]['preparedFields'] ?? []),
			],
			array_keys($presets)
		);
	}
}
