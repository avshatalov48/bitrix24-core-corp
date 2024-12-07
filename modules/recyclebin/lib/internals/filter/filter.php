<?php

namespace Bitrix\Recyclebin\Internals\Filter;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\Filter\DateType;
use Bitrix\Main\UI\Filter\Options;
use Bitrix\Recyclebin\Internals\User;

final class Filter
{
	private string $filterId;
	private ?string $moduleId;
	private ?string $entityType;
	private string $userId;
	private ?Options $options = null;
	private ?array $modulesList = null;
	private ?DataPreparer $dataPreparer = null;
	private array $customPresets;
	private array $entityTypes;

	public function __construct(string $filterId, array $params)
	{
		$this->filterId = $filterId;
		$this->moduleId = $params['moduleId'] ?? null;
		$this->entityType = $params['entityType'] ?? null;
		$this->modulesList = $params['modulesList'] ?? null;
		$this->userId = $params['userId'] ?? User::getCurrentUserId();
		$this->customPresets = $params['customPresets'] ?? [];
		$this->entityTypes = $params['entityTypes'] ?? [];
	}

	public function getPreparedFields(): array
	{
		$filter = [];

		if ($this->getFieldData('FIND'))
		{
			$filter['*%NAME'] = $this->getFieldData('FIND');
		}

		if ($this->moduleId)
		{
			$filter['=MODULE_ID'] = $this->moduleId;
		}

		if ($this->entityType)
		{
			$filter['=ENTITY_TYPE'] = $this->entityType;
		}

		if (!User::isSuper())
		{
			$filter['=USER_ID'] = $this->userId;
		}

		if ($this->getFieldData('FILTER_APPLIED', false) !== true)
		{
			return $filter;
		}

		$this->getDataPreparer()->prepareFilterFields($filter, $this->getFields());

		return $filter;
	}

	public function getFields(): array
	{
		$list = [
			'ENTITY_ID' => [
				'id' => 'ENTITY_ID',
				'name' => Loc::getMessage('RECYCLEBIN_FILTER_COLUMN_ENTITY_ID'),
				'type' => 'number',
			],
			'NAME' => [
				'id' => 'NAME',
				'name' => Loc::getMessage('RECYCLEBIN_FILTER_COLUMN_NAME'),
				'type' => 'string',
				'default' => true,
			],
			'TIMESTAMP' => [
				'id' => 'TIMESTAMP',
				'name' => Loc::getMessage('RECYCLEBIN_FILTER_COLUMN_TIMESTAMP'),
				'type' => 'date',
				'exclude' => [
					DateType::TOMORROW,
					DateType::PREV_DAYS,
					DateType::NEXT_DAYS,
					DateType::NEXT_WEEK,
					DateType::NEXT_MONTH
				],
			],
		];

		if (User::isSuper())
		{
			$list['USER_ID'] = [
				'id' => 'USER_ID',
				'name' => Loc::getMessage('RECYCLEBIN_FILTER_COLUMN_USER_ID'),
				'params' => ['multiple' => 'Y'],
				'type' => 'custom_entity',
				'selector' => [
					'TYPE' => 'user',
					'DATA' => [
						'ID' => 'user',
						'FIELD_ID' => 'USER_ID',
					],
				],
			];
		}

		if (!$this->moduleId)
		{
			$list['MODULE_ID'] = [
				'id' => 'MODULE_ID',
				'name' => Loc::getMessage('RECYCLEBIN_FILTER_COLUMN_MODULE_ID'),
				'params' => ['multiple' => 'Y'],
				'type' => 'list',
				'items' => $this->modulesList,
			];
		}

		if (!$this->entityType)
		{
			$list['ENTITY_TYPE'] = [
				'id'     => 'ENTITY_TYPE',
				'name'   => Loc::getMessage('RECYCLEBIN_FILTER_COLUMN_ENTITY_TYPE'),
				'params' => ['multiple' => 'Y'],
				'type'   => 'list',
				'items'  => $this->entityTypes ?? [],
			];
		}

		return $list;
	}

	private function getFieldData($field, $default = null): mixed
	{
		return $this->getDataPreparer()->getFieldData($field, $default);
	}

	private function getOptions(): Options
	{
		if ($this->options === null)
		{
			$this->options = new Options($this->filterId);
		}

		return $this->options;
	}

	private function getDataPreparer(): DataPreparer
	{
		if ($this->dataPreparer === null)
		{
			$data = $this->getOptions()->getFilter($this->getFields());
			$this->dataPreparer = new DataPreparer($data);
		}

		return $this->dataPreparer;
	}

	public function getPresets(): array
	{
		$presets = [
			'preset_today' => [
				'name'    => Loc::getMessage('RECYCLEBIN_FILTER_PRESET_CURRENT_DAY'),
				'default' => false,
				'fields'  => [
					'TIMESTAMP_datesel' => DateType::CURRENT_DAY,
				],
			],
			'preset_week'  => [
				'name'    => Loc::getMessage('RECYCLEBIN_FILTER_PRESET_CURRENT_WEEK'),
				'default' => false,
				'fields'  => [
					'TIMESTAMP_datesel' => DateType::CURRENT_WEEK,
				],
			],
			'preset_month' => [
				'name'    => Loc::getMessage('RECYCLEBIN_FILTER_PRESET_CURRENT_MONTH'),
				'default' => false,
				'fields'  => [
					'TIMESTAMP_datesel' => DateType::CURRENT_MONTH,
				],
			],
		];

		$hasDefault = false;
		if (!empty($this->customPresets))
		{
			foreach ($this->customPresets as $customPreset)
			{
				if (isset($customPreset['default']) && $customPreset['default'] === true)
				{
					$hasDefault = true;
					break;
				}
			}

			$presets = array_merge($presets, $this->customPresets);
		}

		if (!$hasDefault)
		{
			$presets['preset_month']['default'] = true;
		}

		return $presets;
	}
}
