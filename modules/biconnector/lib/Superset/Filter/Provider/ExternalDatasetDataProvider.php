<?php

namespace Bitrix\BIConnector\Superset\Filter\Provider;

use Bitrix\BIConnector\ExternalSource;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI;

class ExternalDatasetDataProvider extends EntityDataProvider
{
	public function __construct(protected Settings $settings)
	{
	}

	public function getSettings(): Settings
	{
		return $this->settings;
	}

	protected function getFieldName($fieldID)
	{
		return $fieldID;
	}

	public function prepareFields(): array
	{
		$result = [
			'TYPE' => $this->createField('TYPE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_FILTER_TITLE_TYPE'),
				'default' => true,
				'type' => 'list',
				'partial' => true,
			]),
			'CREATED_BY_ID' => $this->createField('CREATED_BY_ID', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_FILTER_TITLE_CREATED_BY'),
				'default' => true,
				'type' => 'entity_selector',
				'partial' => true,
			]),
			'UPDATED_BY_ID' => $this->createField('UPDATED_BY_ID', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_FILTER_TITLE_UPDATED_BY'),
				'default' => false,
				'type' => 'entity_selector',
				'partial' => true,
			]),
			'DATE_CREATE' => $this->createField('DATE_CREATE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_FILTER_TITLE_DATE_CREATE'),
				'default' => true,
				'type' => 'date',
				'time' => true,
				'data' => [
					'exclude' => [
						UI\Filter\DateType::TOMORROW,
						UI\Filter\DateType::NEXT_DAYS,
						UI\Filter\DateType::NEXT_WEEK,
						UI\Filter\DateType::NEXT_MONTH,
					],
				],
			]),
			'DATE_UPDATE' => $this->createField('DATE_UPDATE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_FILTER_TITLE_DATE_UPDATE'),
				'default' => false,
				'type' => 'date',
				'time' => true,
				'data' => [
					'exclude' => [
						UI\Filter\DateType::TOMORROW,
						UI\Filter\DateType::NEXT_DAYS,
						UI\Filter\DateType::NEXT_WEEK,
						UI\Filter\DateType::NEXT_MONTH,
					],
				],
			]),
			'ID' => $this->createField('ID', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_FILTER_TITLE_ID'),
				'default' => false,
				'type' => 'number',
				'partial' => true,
			]),
		];

		if (ExternalSource\SourceManager::isExternalConnectionsAvailable())
		{
			$result['SOURCE'] = $this->createField('SOURCE.ID', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_FILTER_TITLE_SOURCE'),
				'default' => false,
				'type' => 'entity_selector',
				'partial' => true,
			]);
		}

		return $result;
	}

	public function prepareFieldData($fieldID): ?array
	{
		if ($fieldID === 'TYPE')
		{
			$items = [
				ExternalSource\Type::Csv->value => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_FILTER_TYPE_CSV'),
			];

			if (ExternalSource\SourceManager::is1cConnectionsAvailable())
			{
				$items[ExternalSource\Type::Source1C->value] = Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_FILTER_TYPE_1C');
			}

			return [
				'params' => [
					'multiple' => 'N',
				],
				'items' => $items,
			];
		}

		if ($fieldID === 'CREATED_BY_ID')
		{
			return $this->getUserEntitySelectorParams(
				$fieldID . '_filter',
				['fieldName' => $fieldID]
			);
		}

		if ($fieldID === 'UPDATED_BY_ID')
		{
			return $this->getUserEntitySelectorParams(
				$fieldID . '_filter',
				['fieldName' => $fieldID]
			);
		}

		if ($fieldID === 'SOURCE.ID')
		{
			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'height' => 200,
						'context' => 'filter-biconnector-external-connection',
						'entities' => [
							[
								'id' => 'biconnector-external-connection',
								'options' => ['filter' => true],
								'dynamicLoad' => true,
								'dynamicSearch' => true,
							],
						],
						'tabs' => [
							[
								'id' => 'connections',
								'title' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_DATASET_GRID_FILTER_TITLE_SOURCE'),
							],
						],
						'showAvatars' => true,
						'dropdownMode' => true,
					],
				],
			];
		}

		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function prepareFilterValue(array $rawFilterValue): array
	{
		$rawFilterValue = parent::prepareFilterValue($rawFilterValue);

		if (!empty($rawFilterValue['FIND']))
		{
			if (!empty($rawFilterValue['NAME']))
			{
				$rawFilterValue['NAME'] = [
					$rawFilterValue['NAME'],
					"%{$rawFilterValue['FIND']}%",
				];
			}
			else
			{
				$rawFilterValue['NAME'] = "%{$rawFilterValue['FIND']}%";
			}
		}

		return $rawFilterValue;
	}
}
