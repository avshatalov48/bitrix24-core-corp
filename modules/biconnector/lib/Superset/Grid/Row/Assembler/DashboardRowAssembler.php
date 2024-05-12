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
			new Field\CreatedByFieldAssembler([
				'CREATED_BY_ID',
			]),
			new Field\ActionFieldAssembler([
				'EDIT_URL',
			]),
			new Field\BasedOnFieldAssembler([
				'SOURCE_ID',
			]),
			new Field\FilterPeriodFieldAssembler([
				'FILTER_PERIOD',
			]),
			new Field\ExternalIdFieldAssembler([
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
