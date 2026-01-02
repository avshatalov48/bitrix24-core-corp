<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Source\DescriptionFieldAssembler;
use Bitrix\BIConnector\Superset\Grid\Settings\ExternalSourceSettings;
use Bitrix\Main\Grid\Row\RowAssembler;

class ExternalSourceRowAssembler extends RowAssembler
{
	private ?ExternalSourceSettings $settings;

	public function __construct(array $visibleColumnIds, ExternalSourceSettings $settings = null)
	{
		$this->settings = $settings;

		parent::__construct($visibleColumnIds);
	}

	protected function prepareFieldAssemblers(): array
	{
		return [
			new Field\Source\NameFieldAssembler(
				[
					'TITLE',
				],
			),
			new Field\Source\SourceTypeFieldAssembler(
				[
					'TYPE',
				],
			),
			new Field\Source\ActiveFieldAssembler(
				[
					'ACTIVE',
				],
			),
			new Field\Base\DateFieldAssembler(
				[
					'DATE_CREATE',
				],
			),
			new Field\Source\CreatedByFieldAssembler(
				[
					'CREATED_BY_ID',
				],
				$this->settings,
			),
			new DescriptionFieldAssembler(
				[
					'DESCRIPTION',
				],
			),
		];
	}
}
