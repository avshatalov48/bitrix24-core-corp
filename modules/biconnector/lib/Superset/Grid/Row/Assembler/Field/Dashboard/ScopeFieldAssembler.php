<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\BIConnector\Superset\Scope\ScopeService;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

/**
 * @method DashboardSettings getSettings()
 */
class ScopeFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value)
	{
		if (!$value['SCOPE'])
		{
			return '';
		}

		$scopeNames = array_filter(ScopeService::getInstance()->getScopeNameList($value['SCOPE']));
		if (count($scopeNames) > 4)
		{
			$scopeNamesToShow = array_slice($scopeNames, 0, 3);
			$scopeNamesToHide = array_slice($scopeNames, 3);

			$shownPart = htmlspecialcharsbx(
				implode(', ', $scopeNamesToShow)
				. ' ',
			);
			$hiddenPart = htmlspecialcharsbx(implode(', ', $scopeNamesToHide));

			$result = Loc::getMessage(
				'BICONNECTOR_SUPERSET_DASHBOARD_GRID_SCOPES',
				[
					'#FIRST_SCOPES#' => $shownPart,
					'[hint]' => <<<HTML
						<span
							data-hint='{$hiddenPart}' 
							data-hint-no-icon
							data-hint-interactivity
							data-hint-center
							class="biconnector-grid-scope-hint-more"
						>
						HTML,
					'#COUNT#' => count($scopeNamesToHide),
					'[/hint]' => '</span>',
				]
			);
		}
		else
		{
			$result = htmlspecialcharsbx(implode(', ', $scopeNames));
		}

		return $result;
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
