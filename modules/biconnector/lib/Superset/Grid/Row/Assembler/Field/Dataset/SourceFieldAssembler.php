<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dataset;

use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Web\Json;

class SourceFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value): ?string
	{
		if (!$value)
		{
			return null;
		}

		$id = (int)$value['ID'];
		$title = htmlspecialcharsbx($value['TITLE']);

		$ormFilter = $this->getSettings()->getOrmFilter();
		$isFiltered = isset($ormFilter['SOURCE.ID'], $id) && in_array($id, $ormFilter['SOURCE.ID']);

		$event = Json::encode([
			'ID' => $id,
			'TITLE' => $title,
			'IS_FILTERED' => $isFiltered,
		]);

		$clickHandler = 'BX.BIConnector.ExternalDatasetManager.Instance.handleSourceClick';

		$selector = 'biconnector-grid-username-cell';
		$closeIcon = '';
		if ($isFiltered)
		{
			$selector .= ' biconnector-grid-username-cell-active';
			$closeIcon = '<div class="biconnector-grid-filter-close ui-icon-set --cross-20"></div>';
		}

		return <<<HTML
				<span
					class="$selector"
					onclick='{$clickHandler}($event)'
				>
					<span class="biconnector-grid-username">$title</span>
					$closeIcon
				</span>
			HTML;
	}
}
