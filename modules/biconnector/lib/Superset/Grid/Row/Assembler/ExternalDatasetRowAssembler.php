<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler;

use Bitrix\BIConnector\Superset\Grid\Settings\ExternalDatasetSettings;
use Bitrix\Main\Grid\Row\RowAssembler;
use Bitrix\Main\Grid\Row\Assembler\Field\StringFieldAssembler;

class ExternalDatasetRowAssembler extends RowAssembler
{
	private ?ExternalDatasetSettings $settings;

	public function __construct(array $visibleColumnIds, ExternalDatasetSettings $settings = null)
	{
		$this->settings = $settings;

		parent::__construct($visibleColumnIds);
	}

	protected function prepareFieldAssemblers(): array
	{
		return [
			new Field\Dataset\IdFieldAssembler(
				[
					'ID',
				]),
			new Field\Dataset\TypeFieldAssembler(
				[
					'TYPE',
				],
			),
			new Field\Dataset\NameFieldAssembler(
				[
					'NAME',
				],
			),
			new Field\Dataset\SourceFieldAssembler(
				[
					'SOURCE',
				],
				$this->settings,
			),
			new StringFieldAssembler(
				[
					'DESCRIPTION',
				],
			),
			new Field\Base\DateFieldAssembler(
				[
					'DATE_CREATE',
				],
			),
			new Field\Base\DateFieldAssembler(
				[
					'DATE_UPDATE',
				],
			),
			new Field\Dataset\CreatedByFieldAssembler(
				[
					'CREATED_BY_ID',
				],
				$this->settings,
			),
			new Field\Dataset\UpdatedByFieldAssembler(
				[
					'UPDATED_BY_ID',
				],
				$this->settings,
			),
		];
	}
}
