<?php

namespace Bitrix\Tasks\Flow\Filter;

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Flow\Provider\UserProvider;

class Filter
{
	public const MY_PRESET = 'filter_flow_my';
	public const ACTIVE_PRESET = 'filter_flow_active';

	private static ?self $instance = null;

	private int $userId;

	private Main\UI\Filter\Options $filterOptions;
	private UserProvider $userProvider;

	public static function getInstance(int $userId): static
	{
		if (self::$instance === null)
		{
			self::$instance = new static($userId);
		}

		return self::$instance;
	}

	public static function getAvailablePresets(): array
	{
		return [
			self::MY_PRESET => Loc::getMessage('TASKS_FLOW_FILTER_PRESET_MY'),
			self::ACTIVE_PRESET => Loc::getMessage('TASKS_FLOW_FILTER_PRESET_ACTIVE'),
		];
	}

	private function __construct(int $userId)
	{
		$this->userId = $userId;

		$this->init();
	}

	public function getId(): string
	{
		return 'TASKS_FLOW_FILTER_ID';
	}

	public function getCurrentFilterValues(): array
	{
		return $this->filterOptions->getFilter($this->getFieldArrays());
	}

	public function isUserFilterApplied(): bool
	{
		$currentPreset = $this->filterOptions->getCurrentFilterId();
		$isDefaultPreset = ($this->filterOptions->getDefaultFilterId() === $currentPreset);
		$additionalFields = $this->filterOptions->getAdditionalPresetFields($currentPreset);
		$isSearchStringEmpty = ($this->filterOptions->getSearchString() === '');

		return (
			!$isSearchStringEmpty
			|| !$isDefaultPreset
			|| !empty($additionalFields)
		);
	}

	public function getPresets(): array
	{
		$presets = [];

		$user = current($this->userProvider->getUsersInfo([$this->userId]));
		if ($user)
		{
			$presets[self::MY_PRESET] = [
				'name' => Loc::getMessage('TASKS_FLOW_FILTER_PRESET_MY'),
				'fields' => [
					'CREATOR_ID' => $this->userId,
					'CREATOR_ID_label' => $user->toArray()['name'],
					'OWNER_ID' => $this->userId,
					'OWNER_ID_label' => $user->toArray()['name'],
				],
				'default' => false,
			];
		}

		$presets[self::ACTIVE_PRESET] = [
			'name' => Loc::getMessage('TASKS_FLOW_FILTER_PRESET_ACTIVE'),
			'fields' => [
				'ACTIVE' => 'Y',
			],
			'default' => false,
		];

		return $presets;
	}

	public function getFieldArrays(): array
	{
		$filter = [];

		$filter['ID'] = [
			'id' => 'ID',
			'name' => Loc::getMessage('TASKS_FLOW_FILTER_ID'),
			'type' => 'number',
		];

		$filter['GROUP_ID'] = [
			'id' => 'GROUP_ID',
			'name' => Loc::getMessage('TASKS_FLOW_FILTER_GROUP_ID'),
			'type' => 'entity_selector',
			'params' => [
				'multiple' => 'Y',
				'dialogOptions' => [
					'context' => 'TASKS_FLOW_FILTER_GROUP_ID',
					'entities' => [
						[
							'id' => 'project',
							'options' => [
								'dynamicLoad' => true,
								'dynamicSearch' => true,
							],
						],
					]
				],
			],
			'default' => true,
		];

		$filter['CREATOR_ID'] = [
			'id' => 'CREATOR_ID',
			'name' => Loc::getMessage('TASKS_FLOW_FILTER_CREATOR_ID'),
			'type' => 'entity_selector',
			'params' => [
				'multiple' => 'Y',
				'dialogOptions' => [
					'context' => 'TASKS_FLOW_FILTER_CREATOR_ID',
					'entities' => [
						[
							'id' => 'user',
							'options' => [
								'inviteEmployeeLink' => false
							],
						],
					]
				],
			],
			'default' => true,
		];

		$filter['OWNER_ID'] = [
			'id' => 'OWNER_ID',
			'name' => Loc::getMessage('TASKS_FLOW_FILTER_OWNER_ID'),
			'type' => 'entity_selector',
			'params' => [
				'multiple' => 'Y',
				'dialogOptions' => [
					'context' => 'TASKS_FLOW_FILTER_OWNER_ID',
					'entities' => [
						[
							'id' => 'user',
							'options' => [
								'inviteEmployeeLink' => false
							],
						],
					]
				],
			],
			'default' => true,
		];

		$filter['EFFICIENCY'] = [
			'id' => 'EFFICIENCY',
			'name' => Loc::getMessage('TASKS_FLOW_FILTER_EFFICIENCY'),
			'type' => 'number',
			'default' => true,
		];

		$filter['ACTIVE'] = [
			'id' => 'ACTIVE',
			'name' => Loc::getMessage('TASKS_FLOW_FILTER_ACTIVE'),
			'type' => 'checkbox',
			'default' => false,
		];

		$filter['PROBLEM'] = [
			'id' => 'PROBLEM',
			'name' => Loc::getMessage('TASKS_FLOW_FILTER_PROBLEM'),
			'type' => 'list',
			'items' => $this->getAllowedCategories(),
		];

		return $filter;
	}

	private function getAllowedCategories(): array
	{
		$result = [];

		$categories = [
			\CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
			\CTaskListState::VIEW_TASK_CATEGORY_NEW_COMMENTS,
		];

		foreach ($categories as $category)
		{
			$result[$category] = \CTaskListState::getTaskCategoryName($category);
		}

		return $result;
	}

	private function init()
	{
		$this->userProvider = new UserProvider();

		$this->filterOptions = new Main\UI\Filter\Options($this->getId(), $this->getPresets());
	}
}