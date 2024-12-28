<?php

namespace Bitrix\BIConnector\Superset\Filter\Provider;

use Bitrix\BIConnector\Superset\Dashboard\UrlParameter\ScopeMap;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Filter\EntityDataProvider;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetDashboardTable;

class DashboardDataProvider extends EntityDataProvider
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
			'OWNER_ID' => $this->createField('OWNER_ID', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_OWNER'),
				'default' => true,
				'type' => 'entity_selector',
				'partial' => true,
			]),
			'DATE_CREATE' => $this->createField('DATE_CREATE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_DATE_CREATE'),
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
			'DATE_MODIFY' => $this->createField('DATE_MODIFY', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_DATE_MODIFY'),
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
			'TYPE' => $this->createField('TYPE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_TYPE'),
				'default' => true,
				'type' => 'list',
				'partial' => true,
			]),
			'STATUS' => $this->createField('STATUS', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_STATUS'),
				'default' => true,
				'type' => 'list',
				'partial' => true,
			]),
			'ID' => $this->createField('ID', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_ID'),
				'default' => false,
				'type' => 'number',
				'partial' => true,
			]),
			'SOURCE_ID' => $this->createField('SOURCE_ID', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_SOURCE_ID'),
				'default' => false,
				'partial' => true,
				'type' => 'entity_selector',
			]),
			'TAGS' => $this->createField('TAGS.ID', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_TAGS'),
				'default' => true,
				'partial' => true,
				'type' => 'entity_selector',
			]),
			'SCOPE' => $this->createField('SCOPE.SCOPE_CODE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_SCOPE'),
				'default' => true,
				'partial' => true,
				'type' => 'entity_selector',
			]),
			'URL_PARAMS' => $this->createField('URL_PARAMS.CODE', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_URL_PARAMS'),
				'default' => true,
				'partial' => true,
				'type' => 'entity_selector',
			]),
			'CREATED_BY_ID' => $this->createField('CREATED_BY_ID', [
				'name' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_CREATED_BY'),
				'default' => false,
				'type' => 'entity_selector',
				'partial' => true,
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
					'SYSTEM' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TYPE_SYSTEM'),
					'MARKET' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TYPE_MARKET'),
					'CUSTOM' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TYPE_CUSTOM'),
				],
			];
		}

		if ($fieldID === 'STATUS')
		{
			return [
				'params' => [
					'multiple' => 'Y',
				],
				'items' => [
					SupersetDashboardTable::DASHBOARD_STATUS_READY => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_STATUS_READY'),
					SupersetDashboardTable::DASHBOARD_STATUS_DRAFT => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_STATUS_DRAFT'),
					SupersetDashboardTable::DASHBOARD_STATUS_LOAD => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_STATUS_LOAD'),
					SupersetDashboardTable::DASHBOARD_STATUS_FAILED => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_STATUS_FAILED'),
				],
			];
		}

		if ($fieldID === 'CREATED_BY_ID')
		{
			return $this->getUserEntitySelectorParams(
				$fieldID . '_filter',
				['fieldName' => $fieldID]
			);
		}

		if ($fieldID === 'OWNER_ID')
		{
			return $this->getUserEntitySelectorParams(
				$fieldID . '_filter',
				['fieldName' => $fieldID]
			);
		}

		if ($fieldID === 'SOURCE_ID')
		{
			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'filter',
						'entities' => [
							[
								'id' => 'biconnector-superset-dashboard',
								'dynamicLoad' => true,
								'dynamicSearch' => true,
							],
						],
					],
				],
			];
		}

		if ($fieldID === 'TAGS.ID')
		{
			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'biconnector-superset-dashboard-tag',
						'multiple' => 'Y',
						'entities' => [
							[
								'id' => 'biconnector-superset-dashboard-tag',
								'options' => ['filter' => true],
								'dynamicLoad' => true,
								'dynamicSearch' => true,
							],
						],
						'dropdownMode' => true,
						'compactView' => true,
					],
				],
			];
		}

		if ($fieldID === 'SCOPE.SCOPE_CODE')
		{
			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'biconnector-superset-scope',
						'multiple' => 'Y',
						'entities' => [
							[
								'id' => 'biconnector-superset-scope',
								'options' => ['filter' => true],
								'dynamicLoad' => true,
								'dynamicSearch' => true,
							],
						],
						'dropdownMode' => true,
						'compactView' => true,
						'showAvatars' => false,
						'height' => 200,
					],
				],
			];
		}

		if ($fieldID === 'URL_PARAMS.CODE')
		{
			$items = [];
			foreach (ScopeMap::getAvailableParameters() as $parameter)
			{
				$parameterScopes = ScopeMap::getParameterScopeCodes($parameter);

				if (in_array(ScopeMap::GLOBAL_SCOPE, $parameterScopes, true))
				{
					$supertitle = Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_PARAMS_SCOPE_SELECTOR_GLOBAL');
				}
				else
				{
					$copeNames = [];
					foreach ($parameterScopes as $parameterScope)
					{
						$copeNames[] = ScopeService::getInstance()->getScopeName($parameterScope);
					}

					$supertitle = implode(', ', $copeNames);
				}

				$items[] = [
					'id' => $parameter->code(),
					'entityId' => 'biconnector-superset-params',
					'title' => $parameter->title(),
					'supertitle' => $supertitle,
					'tabs' => 'params',
				];
				$preselectedItems[] = ['biconnector-superset-params', $parameter->code()];
			}

			return [
				'params' => [
					'multiple' => 'Y',
					'dialogOptions' => [
						'context' => 'filter-biconnector-superset-params',
						'multiple' => 'Y',
						'entities' => [
							[
								'id' => 'biconnector-superset-params',
							],
						],
						'tabs' => [
							[
								'id' => 'params',
								'title' => Loc::getMessage('BICONNECTOR_SUPERSET_DASHBOARD_GRID_FILTER_TITLE_URL_PARAMS'),
							]
						],
						'items' => $items,
						'preselectedItems' => $preselectedItems,
						'dropdownMode' => true,
						'compactView' => false,
						'showAvatars' => false,
						'height' => 200,
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