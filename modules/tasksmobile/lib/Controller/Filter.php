<?php

namespace Bitrix\TasksMobile\Controller;

use Bitrix\Main\Engine\ActionFilter\CloseSession;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Loader;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Socialnetwork\Component\WorkgroupList;
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
		/** @var \Bitrix\Tasks\Helper\Filter $filterInstance */
		$filterInstance = \Bitrix\Tasks\Helper\Filter::getInstance($this->getCurrentUser()->getId(), $groupId);
		if (method_exists(\Bitrix\Tasks\Helper\Filter::class, 'setRolePresetsEnabledForMobile'))
		{
			\Bitrix\Tasks\Helper\Filter::setRolePresetsEnabledForMobile(true);
		}
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
		if (method_exists(\Bitrix\Tasks\Helper\Filter::class, 'setRolePresetsEnabledForMobile'))
		{
			\Bitrix\Tasks\Helper\Filter::setRolePresetsEnabledForMobile(true);
		}
		$presets = $filterInstance->getPresets();
		$allPresets = $filterInstance->getAllPresets();
		foreach ($allPresets as $key => $preset)
		{
			if (!isset($preset['default']) && isset($presets[$key]))
			{
				$allPresets[$key]['default'] = $presets[$key]['default'];
			}
		}

		unset(
			$allPresets[Options::DEFAULT_FILTER],
			$allPresets[Options::TMP_FILTER]
		);

		$allPresets = array_map(
			static fn(string $key) => [
				'id' => $key,
				'name' => (string)$allPresets[$key]['name'],
				'default' => (bool)$allPresets[$key]['default'],
			],
			array_keys($allPresets)
		);

		return [
			'presets' => $allPresets,
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
			static fn($key) => [
				'id' => $key,
				'name' => $presets[$key]['name'],
				'fields' => ($presets[$key]['preparedFields'] ?? []),
			],
			array_keys($presets)
		);
	}
}
