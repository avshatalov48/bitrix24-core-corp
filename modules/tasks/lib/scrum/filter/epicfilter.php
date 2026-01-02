<?php

namespace Bitrix\Tasks\Scrum\Filter;

use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Filter\Trait\FilterApplied;
use Bitrix\Tasks\Integration\Socialnetwork\Internals\Registry\GroupRegistry;

class EpicFilter
{
	use FilterApplied;

	public const PRESET_MY = 'tasks-scrum-epic-preset-my';
	private int $groupId;
	private int $userId;
	private Main\UI\Filter\Options $filterOptions;

	public function __construct(int $userId, int $groupId)
	{
		$this->userId = $userId;
		$this->groupId = $groupId;

		$this->init();
	}

	public function getId(): string
	{
		return 'EntityEpicsGrid_' . $this->groupId;
	}

	public function getPresets(): array
	{
		return [
			self::PRESET_MY => [
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_FILTER_PRESET_MY'),
				'fields' => [
					'CREATED_BY' => $this->userId,
				],
				'default' => false,
			]
		];
	}

	public function getFields(): array
	{
		return [
			[
				'id' => 'CREATED_BY',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_FILTER_AUTHOR'),
				'type' => 'entity_selector',
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'hideOnSelect' => false,
						'context' => 'TASKS_SCRUM_EPIC_FILTER_CREATED_BY',
						'entities' => [
							[
								'id' => 'user',
								'options' => [
									'inviteEmployeeLink' => false,
									'userId' => $this->getGroupUserIds(),
								],
							],
						]
					],
				],
				'default' => true,
			],
			[
				'id' => 'NAME',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_FILTER_NAME_SHORT'),
				'default' => true,
			],
			[
				'id' => 'DESCRIPTION',
				'name' => Loc::getMessage('TASKS_SCRUM_EPIC_FILTER_DESCRIPTION_SHORT'),
				'default' => true,
			],
		];
	}

	public function getFilterFields(): array
	{
		$filterData = $this->getCurrentFilterValues();

		$filterFields = [];

		$filterConfig = [
			'FIND' => [
				'field' => '%NAME',
			],
			'DESCRIPTION' => [
				'field' => '%DESCRIPTION',
			],
			'NAME' => [
				'field' => 'NAME',
			],
			'CREATED_BY' => [
				'field' => 'CREATED_BY',
			],
		];

		foreach ($filterConfig as $field => $config)
		{
			if (!empty($filterData[$field]))
			{
				$filterFields[$config['field']] = $filterData[$field];
			}
		}

		return $filterFields;
	}

	public function getCurrentFilterValues(): array
	{
		return $this->filterOptions->getFilter($this->getFields());
	}

	private function getGroupUserIds(): array
	{
		return GroupRegistry::getInstance()?->get($this->groupId)?->getUserMemberIds() ?? [];
	}

	private function init(): void
	{
		$this->filterOptions = new Main\UI\Filter\Options($this->getId());
	}
}