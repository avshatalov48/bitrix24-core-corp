<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Socialnetwork\Component\WorkgroupList;
use Bitrix\Tasks\Helper\Filter as TasksFilter;
use Bitrix\Tasks\Internals\Project\Provider;

Loader::requireModule('socialnetwork');

class Filter extends Controller
{
	public function configureActions()
	{
		return [
			'getTaskListPresets' => [
				'+prefilters' => [new CloseSession()],
			],
			'getProjectListPresets' => [
				'+prefilters' => [new CloseSession()],
			],
			'getScrumListPresets' => [
				'+prefilters' => [new CloseSession()],
			],
			'getSearchBarPresets' => [
				'+prefilters' => [new CloseSession()],
			],
		];
	}

	public function getTaskListPresetsAction(int $groupId = 0): array
	{
		$filterInstance = TasksFilter::getInstance($this->getCurrentUser()->getId(), $groupId);
		if (method_exists(TasksFilter::class, 'setRolePresetsEnabledForMobile'))
		{
			TasksFilter::setRolePresetsEnabledForMobile(true);
		}
		$filterOptions = $filterInstance->getOptions();
		$presets = $filterInstance->getAllPresets();
		$defaultPresetKey = $filterInstance->getDefaultPresetKey();

		foreach (array_keys($presets) as $id)
		{
			$filterSettings = ($filterOptions->getFilterSettings($id) ?? $filterOptions->getDefaultPresets()[$id]);
			$sourceFields = $filterInstance->getFilters();
			$presets[$id]['preparedFields'] = Options::fetchFieldValuesFromFilterSettings($filterSettings, [], $sourceFields);
		}

		return $this->preparePresetsForOutput($presets, $defaultPresetKey);
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
		$filterInstance = (
			TasksFilter::getInstance($this->getCurrentUser()->getId(), $groupId)->setGanttMode(false)
		);
		if (method_exists(TasksFilter::class, 'setRolePresetsEnabledForMobile'))
		{
			TasksFilter::setRolePresetsEnabledForMobile(true);
		}

		$presets = $filterInstance->getAllPresets();
		$defaultPresetKey = $filterInstance->getDefaultPresetKey();

		return [
			'presets' => $this->preparePresetsForOutput($presets, $defaultPresetKey),
			'counters' => [],
		];
	}

	private function preparePresetsForOutput(array $presets, $defaultPresetKey = null): array
	{
		unset(
			$presets[Options::DEFAULT_FILTER],
			$presets[Options::TMP_FILTER]
		);

		if ($defaultPresetKey)
		{
			$presets[$defaultPresetKey]['default'] = true;
		}

		return array_map(
			static fn($key) => [
				'id' => $key,
				'name' => $presets[$key]['name'],
				'fields' => ($presets[$key]['preparedFields'] ?? []),
				'default' => (bool)$presets[$key]['default'],
			],
			array_keys($presets)
		);
	}
}
