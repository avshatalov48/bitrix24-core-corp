<?php

namespace Bitrix\BIConnector\Superset\Filter\Provider;

use Bitrix\BIConnector\Superset\Grid\ExternalSourceRepository;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI;

class ExternalSourceDataProvider extends EntityDataProvider
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
		return [
			'TYPE' => $this->createField('TYPE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_FILTER_TITLE_TYPE'),
				'default' => true,
				'type' => 'list',
				'partial' => true,
			]),
			'ACTIVE' => $this->createField('ACTIVE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_FILTER_TITLE_ACTIVE'),
				'default' => true,
				'type' => 'list',
				'partial' => true,
			]),
			'DATE_CREATE' => $this->createField('DATE_CREATE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_FILTER_TITLE_DATE_CREATE'),
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
//			'CREATED_BY_ID' => $this->createField('CREATED_BY_ID', [
//				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_FILTER_TITLE_CREATED_BY'),
//				'default' => true,
//				'type' => 'entity_selector',
//				'partial' => true,
//			]),
		];
	}

	public function prepareFieldData($fieldID): ?array
	{
		if ($fieldID === 'TYPE')
		{
			$filterItemsList = [];
			foreach (ExternalSourceRepository::getStaticSourceList() as $source)
			{
				$filterItemsList[$source['CODE']] = $source['NAME'];
			}
			return [
				'params' => [
					'multiple' => 'N',
				],
				'items' => $filterItemsList,
			];
		}

		if ($fieldID === 'ACTIVE')
		{
			return [
				'params' => [
					'multiple' => 'N',
				],
				'items' => [
					'Y' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_FILTER_TITLE_ACTIVE_SUCCESS'),
					'N' => Loc::getMessage('BICONNECTOR_SUPERSET_EXTERNAL_SOURCE_GRID_FILTER_TITLE_ACTIVE_ERROR'),
				],
			];
		}

//		if ($fieldID === 'CREATED_BY_ID')
//		{
//			return $this->getUserEntitySelectorParams(
//				$fieldID . '_filter',
//				['fieldName' => $fieldID]
//			);
//		}

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
			if (!empty($rawFilterValue['TITLE']))
			{
				$rawFilterValue['TITLE'] = [
					$rawFilterValue['TITLE'],
					"%{$rawFilterValue['FIND']}%",
				];
			}
			else
			{
				$rawFilterValue['TITLE'] = "%{$rawFilterValue['FIND']}%";
			}
		}

		return $rawFilterValue;
	}
}
