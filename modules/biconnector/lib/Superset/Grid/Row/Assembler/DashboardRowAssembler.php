<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler;

use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\Main\Grid\Row\RowAssembler;

class DashboardRowAssembler extends RowAssembler
{
	private ?DashboardSettings $settings;

	public function __construct(array $visibleColumnIds, DashboardSettings $settings = null)
	{
		$this->settings = $settings;

		parent::__construct($visibleColumnIds);
	}

	protected function prepareFieldAssemblers(): array
	{
		return [
			new Field\Dashboard\NameFieldAssembler([
				'TITLE',
			]),
			new Field\Dashboard\StatusFieldAssembler(
				[
					'STATUS',
				],
				$this->settings,
			),
			new Field\Dashboard\CreatedByFieldAssembler(
				[
					'CREATED_BY_ID',
				],
				$this->settings,
			),
			new Field\Dashboard\OwnerFieldAssembler(
				[
					'OWNER_ID',
				],
				$this->settings,
			),
			new Field\Dashboard\TagFieldAssembler(
				[
					'TAGS',
				],
				$this->settings,
			),
			new Field\Dashboard\ScopeFieldAssembler([
				'SCOPE',
			]),
			new Field\Dashboard\UrlParamsFieldAssembler([
				'URL_PARAMS',
			]),
			new Field\Dashboard\BasedOnFieldAssembler([
				'SOURCE_ID',
			]),
			new Field\Dashboard\FilterPeriodFieldAssembler([
				'FILTER_PERIOD',
			]),
			new Field\Dashboard\IdFieldAssembler([
				'ID',
			]),
			new Field\Base\DateFieldAssembler([
				'DATE_CREATE',
			]),
			new Field\Base\DateFieldAssembler([
				'DATE_MODIFY',
			]),
		];
	}
}