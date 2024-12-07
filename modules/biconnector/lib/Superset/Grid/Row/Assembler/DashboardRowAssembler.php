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
			new Field\NameFieldAssembler([
				'TITLE',
			]),
			new Field\StatusFieldAssembler(
				[
					'STATUS',
				],
				$this->settings,
			),
			new Field\CreatedByFieldAssembler(
				[
					'CREATED_BY_ID',
				],
				$this->settings,
			),
			new Field\OwnerFieldAssembler(
				[
					'OWNER_ID',
				],
				$this->settings,
			),
			new Field\ActionFieldAssembler([
				'EDIT_URL',
			]),
			new Field\TagFieldAssembler(
				[
					'TAGS',
				],
				$this->settings,
			),
			new Field\ScopeFieldAssembler([
				'SCOPE',
			]),
			new Field\UrlParamsFieldAssembler([
				'URL_PARAMS',
			]),
			new Field\BasedOnFieldAssembler([
				'SOURCE_ID',
			]),
			new Field\FilterPeriodFieldAssembler([
				'FILTER_PERIOD',
			]),
			new Field\IdFieldAssembler([
				'ID',
			]),
			new Field\DateFieldAssembler([
				'DATE_CREATE',
			]),
			new Field\DateFieldAssembler([
				'DATE_MODIFY',
			]),
		];
	}
}
