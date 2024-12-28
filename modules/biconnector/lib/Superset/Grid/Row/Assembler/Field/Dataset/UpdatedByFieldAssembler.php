<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dataset;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\UserFieldAssembler;

class UpdatedByFieldAssembler extends UserFieldAssembler
{
	protected function prepareColumn($value): string
	{
		return $this->prepareColumnWithParams(
			$value,
			'UPDATED_BY_ID',
			'BX.BIConnector.ExternalDatasetManager.Instance.handleUpdatedByClick'
		);
	}
}
