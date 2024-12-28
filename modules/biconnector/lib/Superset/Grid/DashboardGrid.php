<?php

namespace Bitrix\BIConnector\Superset\Grid;

use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Superset\Dashboard\UrlParameter;
use Bitrix\BIConnector\Superset\Grid\Row\Assembler\DashboardRowAssembler;
use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Engine\Response\Converter;
use Bitrix\Main\Filter\Filter;
use Bitrix\Main\Filter\Settings;
use Bitrix\Main\Grid\Column\Columns;
use Bitrix\Main\Grid\Grid;
use Bitrix\Main\Grid\Row\Rows;

/**
 * @method DashboardSettings getSettings()
 */
final class DashboardGrid extends Grid
{
	public function __construct(\Bitrix\Main\Grid\Settings $settings)
	{
		parent::__construct($settings);
		$this->getSettings()->setOrmFilter($this->getOrmFilter());
	}

	public function setSupersetAvailability(bool $isSupersetAvailable): void
	{
		$this->getSettings()->setSupersetAvailability($isSupersetAvailable);
	}

	protected function createColumns(): Columns
	{
		return new Columns(
			new \Bitrix\BIConnector\Superset\Grid\Column\Provider\DashboardDataProvider()
		);
	}

	protected function createRows(): Rows
	{
		$rowAssembler = new DashboardRowAssembler(
			[
				'TITLE',
				'STATUS',
				'CREATED_BY_ID',
				'OWNER_ID',
				'DATE_CREATE',
				'DATE_MODIFY',
				'SOURCE_ID',
				'TAGS',
				'SCOPE',
				'FILTER_PERIOD',
				'ID',
				'URL_PARAMS',
			],
			$this->getSettings()
		);

		return new Rows(
			$rowAssembler,
			new \Bitrix\BIConnector\Superset\Grid\Row\Action\DashboardDataProvider($this->getSettings())
		);
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new \Bitrix\BiConnector\Superset\Filter\Provider\DashboardDataProvider(
				new Settings(['ID' => $this->getId()])
			),
		);
	}

	public static function prepareRowData(Model\Dashboard $dashboard): array
	{
		$supersetController = new SupersetController(Integrator::getInstance());

		$settings = new DashboardSettings([
			'ID' => 'biconnector_superset_dashboard_grid',
			'IS_SUPERSET_AVAILABLE' => $supersetController->isExternalServiceAvailable(),
		]);

		$grid = new DashboardGrid($settings);

		$dashboardFields = $dashboard->toArray();
		$tagList = [];
		$tags = $dashboard->getOrmObject()->fillTags();
		foreach ($tags->getAll() as $tag)
		{
			$tagList[] = [
				'ID' => $tag->getId(),
				'TITLE' => $tag->getTitle(),
			];
		}
		$dashboardFields['TAGS'] = $tagList;
		$dashboardFields['SCOPE'] = ScopeService::getInstance()->getDashboardScopes($dashboard->getId());
		$urlService = new UrlParameter\Service($dashboard->getOrmObject());
		$dashboardFields['URL_PARAMS'] = array_map(
			static fn($param) => $param->title(),
			$urlService->getUrlParameters()
		);

		$dashboardFields['DETAIL_URL'] = $urlService->getEmbeddedUrl();
		$dashboardFields['HAS_ZONE_URL_PARAMS'] = $urlService->isExistScopeParams();
		$dashboardFields['IS_AVAILABLE_DASHBOARD'] = $dashboard->isAvailableDashboard();
		if ($dashboard->getOrmObject()->getSource())
		{
			$urlSourceService = new UrlParameter\Service($dashboard->getOrmObject()->getSource());
			$dashboardFields['SOURCE_DETAIL_URL'] = $urlSourceService->getEmbeddedUrl();
			$dashboardFields['SOURCE_HAS_ZONE_URL_PARAMS'] = $urlSourceService->isExistScopeParams();
		}

		$result = $grid->getRows()->prepareRows([$dashboardFields]);
		$result = current($result);
		if (is_array($result))
		{
			$converter = new Converter(Converter::OUTPUT_JSON_FORMAT);
			$result['actions'] = $converter->process($result['actions']);

			return $result;
		}

		return [];
	}
}
