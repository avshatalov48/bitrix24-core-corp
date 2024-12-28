<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dataset;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\UserFieldAssembler;

class CreatedByFieldAssembler extends UserFieldAssembler
{
	protected function prepareColumn($value): string
	{
		return $this->prepareColumnWithParams(
			$value,
			'CREATED_BY_ID',
			'BX.BIConnector.ExternalDatasetManager.Instance.handleCreatedByClick'
		);
	}
}
