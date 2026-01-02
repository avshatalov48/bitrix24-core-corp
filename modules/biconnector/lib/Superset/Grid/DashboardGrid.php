<?php

namespace Bitrix\BIConnector\Superset\Grid;

use Bitrix\BIConnector\Integration\Superset\Integrator\Integrator;
use Bitrix\BIConnector\Integration\Superset\Model;
use Bitrix\BIConnector\Integration\Superset\SupersetController;
use Bitrix\BIConnector\Superset\Grid\Row\Assembler\DashboardRowAssembler;
use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
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
	public const SUPERSET_DASHBOARD_GRID_ID = 'biconnector_superset_dashboard_grid';

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
			new \Bitrix\BIConnector\Superset\Grid\Column\Provider\DashboardDataProvider(),
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
				'GROUPS',
				'FILTER_PERIOD',
				'ID',
				'URL_PARAMS',
			],
			$this->getSettings(),
		);

		return new Rows(
			$rowAssembler,
			new \Bitrix\BIConnector\Superset\Grid\Row\Action\Dashboard\DashboardActionDataProvider($this->getSettings()),
		);
	}

	protected function createFilter(): ?Filter
	{
		return new Filter(
			$this->getId(),
			new \Bitrix\BiConnector\Superset\Filter\Provider\DashboardDataProvider(
				new Settings(['ID' => $this->getId()]),
			),
		);
	}

	public static function prepareDashboardRowData(Model\Dashboard $dashboard, array $additionalOptions = []): array
	{
		$supersetController = new SupersetController(Integrator::getInstance());

		$settings = new DashboardSettings([
			'ID' => self::SUPERSET_DASHBOARD_GRID_ID,
			'IS_SUPERSET_AVAILABLE' => $supersetController->isExternalServiceAvailable(),
		]);

		$grid = new self($settings);

		$dashboardFields = $supersetController
			->getUnionDashboardGroupRepository()
			->getDashboardRow($dashboard, $additionalOptions)
		;

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
