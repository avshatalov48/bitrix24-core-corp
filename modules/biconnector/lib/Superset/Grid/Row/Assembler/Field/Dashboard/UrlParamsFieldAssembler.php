<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

/**
 * @method DashboardSettings getSettings()
 */
class UrlParamsFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value)
	{
		$urlParams = $value['URL_PARAMS'];

		if (!$value['URL_PARAMS'])
		{
			return '';
		}

		if (count($urlParams) > 4)
		{
			$urlParamsToShow = array_slice($urlParams, 0, 3);
			$shownPart = htmlspecialcharsbx(implode(', ', $urlParamsToShow));

			$urlParamsToHide = array_slice($urlParams, 3);
			$hiddenPart = htmlspecialcharsbx(implode(', ', $urlParamsToHide));

			$shownPart = Loc::getMessage(
				'BICONNECTOR_SUPERSET_DASHBOARD_URL_PARAMS_SHORT_PHRASE',
				[
					'#FIRST_PARAMETERS#' => $shownPart,
					'[hint]' => "
						<span
							data-hint='{$hiddenPart}' 
							data-hint-no-icon
							data-hint-interactivity
							data-hint-center
							class='biconnector-grid-scope-hint-more'
						>"
					,
					'#COUNT#' => count($urlParamsToHide),
					'[/hint]' => '</span>',
				]
			);
		}
		else
		{
			$shownPart = htmlspecialcharsbx(implode(', ', $urlParams));
		}

		return $shownPart;
	}

	protected function prepareRow(array $row): array
	{
		if (empty($this->getColumnIds()))
		{
			return $row;
		}

		$row['columns'] ??= [];
		foreach ($this->getColumnIds() as $columnId)
		{
			$row['columns'][$columnId] = $this->prepareColumn($row['data']);
		}

		return $row;
	}
}