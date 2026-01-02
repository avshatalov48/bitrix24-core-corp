<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\BIConnector\Access\AccessController;
use Bitrix\BIConnector\Access\ActionDictionary;
use Bitrix\BIConnector\ExternalSource\DatasetManager;
use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model\SupersetUserTable;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Superset\ActionFilter;
use Bitrix\BIConnector\Superset\Grid\UnusedElementsGrid;
use Bitrix\Intranet;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main;
use Bitrix\Main\Grid\Settings;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type;
use Bitrix\Main\Web\Uri;
use Bitrix\UI\Toolbar\Facade\Toolbar;

Loader::includeModule('biconnector');

class UnusedElementsComponent extends CBitrixComponent implements Controllerable, Main\Errorable
{
	use Main\ErrorableImplementation;

	private UnusedElementsGrid $grid;

	private const ELEMENT_TYPE_CHART = 'chart';
	private const ELEMENT_TYPE_DATASET = 'dataset';

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new Main\ErrorCollection();
	}

	public function configureActions()
	{
		$filters = [
			new ActionFilter\BIConstructorAccess(),
			new ActionFilter\WorkspaceAnalyticAccess(),
		];

		if (Loader::includeModule('intranet'))
		{
			$filters[] = new Intranet\ActionFilter\IntranetUser();
		}

		return [
			'delete' => [
				'+prefilters' => $filters,
			],
		];
	}

	public function executeComponent()
	{
		$this->prepareGrid();
		$this->prepareFilters();
		$this->grid->processRequest();
		$this->loadRows();
		if (empty($this->grid->getOrmFilter()))
		{
			$this->initStub();
		}

		$this->arResult['GRID'] = $this->grid;
		$this->includeComponentTemplate();
	}

	private function prepareGrid(): void
	{
		$settings = [
			'ID' => 'biconnector_unused_elements_grid',
			'SHOW_ROW_CHECKBOXES' => true,
			'SHOW_ROW_ACTIONS_MENU' => true,
			'SHOW_SELECTED_COUNTER' => true,
			'SHOW_CHECK_ALL_CHECKBOXES' => true,
			'SHOW_TOTAL_COUNTER' => true,
			'EDITABLE' => true,
			'SHOW_ACTION_PANEL' => true,
		];
		$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
		$removeButton = $snippet->getRemoveButton();
		$settings['ACTION_PANEL'] = [
			'GROUPS' => [
				[
					'ITEMS' => [
						$removeButton,
					],
				],
			]
		];

		$grid = new UnusedElementsGrid(
			new Settings($settings)
		);
		$this->grid = $grid;
	}

	private function prepareFilters(): void
	{
		$filter = $this->grid->getFilter();
		if ($filter)
		{
			$options = \Bitrix\Main\Filter\Component\ComponentParams::get(
				$this->grid->getFilter(),
				[
					'GRID_ID' => $this->grid->getId(),
				],
			);
		}
		else
		{
			$options = [
				'FILTER_ID' => $this->grid->getId(),
			];
		}

		Toolbar::addFilter($options);
	}

	private function loadRows(): void
	{
		$integrator = Bitrix\BIConnector\Integration\Superset\Integrator\Integrator::getInstance();
		$pagination = $this->grid->getPagination();
		$page = $pagination?->getCurrentPage();
		$pageSize = $pagination?->getPageSize();
		$order = $this->grid->getOrmOrder();
		$filter = $this->normalizeDatetimeInFilter($this->grid->getOrmFilter());

		$result = $integrator->getUnusedElements([
			'page' => $page - 1,
			'pageSize' => $pageSize,
			'order' => $order,
			'filter' => $filter,
		]);
		if ($result->hasErrors())
		{
			$this->grid->setRawRows([]);

			return;
		}

		$data = $result->getData();
		if (empty($data))
		{
			$this->grid->setRawRows([]);

			return;
		}

		$count = $data['count'];
		$this->grid->getPagination()?->setRecordCount($count);

		$elements = $data['unusedElements'];
		$elements = $this->mapClientIdsToOwners($elements);

		$rows = [];
		foreach ($elements as $element)
		{
			$rows[] = [
				'TITLE' => $element['name'],
				'TYPE' => $element['type'],
				'EXTERNAL_ID' => $element['external_id'],
				'OWNERS' => $element['owners'] ?? [],
				'DESCRIPTION' => $element['description'],
				'DATE_CREATE' => $element['created_on'] ? $this->parseDateFromIntegratorResponse($element['created_on']) : null,
				'DATE_UPDATE' => $element['changed_on'] ? $this->parseDateFromIntegratorResponse($element['changed_on']) : null,
				'OPEN_URL' => $element['open_url'],
			];
		}
		$this->grid->setRawRows($rows);
	}

	/*
	 * for backwards compatibility, can be removed later
	 */
	private function parseDateFromIntegratorResponse(string|int $date): Type\DateTime
	{
		if (is_numeric($date))
		{
			return Type\DateTime::createFromTimestamp((int)$date);
		}

		return new Type\DateTime($date, 'Y-m-d H:i:s');
	}

	/**
	 * Since Superset accepts the date values in the filter as d.m.Y H:i:s
	 * and the filter API returns the filter values in the site-wide culture format
	 * we have to format the dates before sending them to Superset
	 *
	 * @param array $filter
	 * @return array
	 */
	private function normalizeDatetimeInFilter(array $filter): array
	{
		$normalizedFilter = $filter;

		foreach ($normalizedFilter as $key => $value)
		{
			$fieldName = \CSqlUtil::GetFilterOperation($key)['FIELD'];
			if (in_array($fieldName, ['DATE_CREATE', 'DATE_UPDATE']))
			{
				$normalizedFilter[$key] = (new Type\DateTime($value))->format('d.m.Y H:i:s');
			}
		}

		return $normalizedFilter;
	}

	/**
	 * Replace client ids from the proxy response with portal users' ids
	 *
	 * @param array $elements
	 * @return array
	 */
	private function mapClientIdsToOwners(array $elements): array
	{
		$result = $elements;
		$clientIds = [];

		foreach ($elements as $element)
		{
			foreach ($element['owners'] as $owner)
			{
				if (!in_array($owner, $clientIds, true))
				{
					$clientIds[] = $owner;
				}
			}
		}

		$userIds = SupersetUserTable::getList([
			'select' => ['USER_ID', 'CLIENT_ID'],
			'filter' => ['=CLIENT_ID' => $clientIds],
		])->fetchAll();

		$userIds = array_column($userIds, 'USER_ID', 'CLIENT_ID');

		foreach ($result as $elementKey => $element)
		{
			foreach ($element['owners'] as $ownerKey => $owner)
			{
				$result[$elementKey]['owners'][$ownerKey] = (int)$userIds[$owner];
			}
		}

		return $result;
	}

	private function initStub(): void
	{
		if ((int)$this->grid->getPagination()?->getRecordCount() !== 0)
		{
			return;
		}

		$this->arResult['GRID_STUB'] = $this->getStub();
	}

	private function getStub(): ?string
	{
		$iconPath = '/bitrix/images/biconnector/workspace-analytic-grid/empty-state-icon.svg';
		$title = Loc::getMessage('BI_UNUSED_ELEMENTS_STUB_TITLE');

		return <<<HTML
			<div class="biconnector-dataset-grid-stub-container">
				<div class="biconnector-dataset-grid-stub-logo">
					<img src="{$iconPath}" alt="No unused elements">
				</div>
				<div class="main-grid-empty-block-title">
					{$title}
				</div>
			</div>
		HTML;
	}

	public function deleteAction(array $elements): ?bool
	{
		foreach ($elements as $element)
		{
			$elementType = $element['elementType'] ?? null;
			if ($elementType !== self::ELEMENT_TYPE_CHART && $elementType !== self::ELEMENT_TYPE_DATASET)
			{
				$this->errorCollection[] = new Main\Error("Wrong element type '{$elementType}'");

				return null;
			}

			$elementId = (int)($element['elementId'] ?? 0);
			if ($elementId <= 0)
			{
				$this->errorCollection[] = new Main\Error("Wrong element ID {$element['elementId']}");

				return null;
			}
		}

		$datasetElements = array_filter($elements, static fn($item) => $item['elementType'] === self::ELEMENT_TYPE_DATASET);
		$datasetExternalIds = array_column($datasetElements, 'elementId');

		$deleteAllElementsPermission = AccessController::getCurrent()->check(ActionDictionary::ACTION_BIC_DELETE_ALL_UNUSED_ELEMENTS);
		if (!$deleteAllElementsPermission)
		{
			$currentUserId = (int)Main\Engine\CurrentUser::get()->getId();

			if (!empty($datasetExternalIds))
			{
				$supersetDatasetsResult = Integrator::getInstance()->getDatasetList($datasetExternalIds);
				if ($supersetDatasetsResult->hasErrors())
				{
					$this->errorCollection->add($supersetDatasetsResult->getErrors());
				}

				$supersetDatasets = $supersetDatasetsResult->getData();
				$supersetDatasets = $this->mapClientIdsToOwners($supersetDatasets);

				foreach ($supersetDatasets as $dataset)
				{
					if (!in_array($currentUserId, $dataset['owners'], true))
					{
						$this->errorCollection[] = new Main\Error(Loc::getMessage('BI_UNUSED_ELEMENTS_NO_RIGHTS_SINGLE'));
					}
				}
			}

			$chartElements = array_filter($elements, static fn($item) => $item['elementType'] === self::ELEMENT_TYPE_CHART);
			$chartExternalIds = array_column($chartElements, 'elementId');

			if (!empty($chartExternalIds))
			{
				$supersetChartsResult = Integrator::getInstance()->getChartList($chartExternalIds);
				if ($supersetChartsResult->hasErrors())
				{
					$this->errorCollection->add($supersetChartsResult->getErrors());
				}

				$supersetCharts = $supersetChartsResult->getData();
				$supersetCharts = $this->mapClientIdsToOwners($supersetCharts);

				foreach ($supersetCharts as $chart)
				{
					if (!in_array($currentUserId, $chart['owners'], true))
					{
						$this->errorCollection[] = new Main\Error(Loc::getMessage('BI_UNUSED_ELEMENTS_NO_RIGHTS_SINGLE'));
					}
				}
			}

			if ($this->hasErrors())
			{
				if (count($elements) > 1)
				{
					$this->errorCollection->clear();
					$this->errorCollection[] = new Main\Error(
						Loc::getMessage('BI_UNUSED_ELEMENTS_NO_RIGHTS_MULTIPLE'),
					);
				}

				return null;
			}
		}

		$workspaceDatasets = \Bitrix\BIConnector\ExternalSource\DatasetManager::getList(['=EXTERNAL_ID' => $datasetExternalIds]);
		foreach ($workspaceDatasets as $workspaceDataset)
		{
			$deleteResult = DatasetManager::delete($workspaceDataset->getId());
			if (!$deleteResult->isSuccess())
			{
				$this->errorCollection->add($deleteResult->getErrors());

				return null;
			}
			$elements = array_filter($elements, static function($item) use ($workspaceDataset) {
				return
					!($item['elementType'] === self::ELEMENT_TYPE_DATASET
					&& (int)$item['elementId'] === $workspaceDataset->getExternalId())
				;
			});
		}

		if ($elements)
		{
			$response = Integrator::getInstance()->deleteUnusedElements($elements);
			if ($response->hasErrors())
			{
				$this->errorCollection->add($response->getErrors());

				return null;
			}
		}

		return true;
	}

	public function getOpenUrlAction(string $openUrl): ?string
	{
		$loginUrl = (new SupersetController(Integrator::getInstance()))->getLoginUrl();

		if ($loginUrl)
		{
			$url = new Uri($loginUrl);
			$url->addParams([
				'next' => $openUrl,
			]);

			return $url->getLocator();
		}

		return $openUrl;
	}
}
