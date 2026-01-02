<?php

namespace Bitrix\BIConnector\Superset\Filter\Provider;

use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI;

class UnusedElementsFilterProvider extends EntityDataProvider
{
	public function __construct(protected Settings $settings)
	{
	}

	public function getSettings(): Settings
	{
		return $this->settings;
	}

	public function prepareFields(): array
	{
		return [
			'TYPE' => $this->createField('TYPE', [
				'name' => Loc::getMessage('BI_UNUSED_ELEMENTS_FILTER_TYPE') ?? '',
				'default' => true,
				'type' => 'list',
				'partial' => true,
			]),
			'DATE_CREATE' => $this->createField('DATE_CREATE', [
				'name' => Loc::getMessage('BI_UNUSED_ELEMENTS_FILTER_DATE_CREATE') ?? '',
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
				'name' => Loc::getMessage('BI_UNUSED_ELEMENTS_FILTER_DATE_UPDATE') ?? '',
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
		];
	}

	public function prepareFieldData($fieldID): ?array
	{
		if ($fieldID === 'TYPE')
		{
			return [
				'params' => [
					'multiple' => 'N',
				],
				'items' => [
					'chart' => Loc::getMessage('BI_UNUSED_ELEMENTS_FILTER_TYPE_CHART'),
					'dataset' => Loc::getMessage('BI_UNUSED_ELEMENTS_FILTER_TYPE_DATASET'),
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
			$rawFilterValue['TITLE'] = $rawFilterValue['FIND'];
		}

		return $rawFilterValue;
	}
}
