<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Source;

use Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Base\UserFieldAssembler;
use Bitrix\Main\Localization\Loc;

class CreatedByFieldAssembler extends UserFieldAssembler
{
	protected function prepareColumn($value): string
	{
		if (empty($value['CREATED_BY_ID']))
		{
			$authorText = Loc::getMessage('BICONNECTOR_SUPERSET_SOURCE_GRID_CREATED_BY_SYSTEM');

			return "<span class=\"biconnector-grid-market-cell\">
				<img src=\"/bitrix/images/biconnector/superset-dashboard-grid/icon-type-system.png\" width='24' height='24' alt=\"{$authorText}\"> 
				<span class=\"biconnector-grid-username\">{$authorText}</span>
			</span>";
		}

		return $this->prepareColumnWithParams(
			$value,
			'CREATED_BY_ID',
			'BX.BIConnector.ExternalSourceManager.Instance.handleCreatedByClick'
		);
	}
}
