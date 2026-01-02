<?php

namespace Bitrix\BIConnector\Superset\Grid\Row\Assembler\Field\Dashboard;

use Bitrix\BIConnector\Superset\Grid\Settings\DashboardSettings;
use Bitrix\Main\Grid\Row\FieldAssembler;
use Bitrix\Main\Localization\Loc;

/**
 * @method DashboardSettings getSettings()
 */
class GroupFieldAssembler extends FieldAssembler
{
	protected function prepareColumn($value)
	{
		if (empty($value) || !is_array($value))
		{
			return '';
		}

		$availableGroupIds = $this->getSettings()->getAvailableGroupIds();
		if (empty($availableGroupIds))
		{
			return '';
		}
		$groups = array_filter(
			$value,
			static fn($item) => in_array($item['ID'], $availableGroupIds, true)
		);
		$groupNames = array_column($groups, 'NAME');

		if (count($groupNames) > 3)
		{
			$groupsToShow = array_slice($groupNames, 0, 2);
			$groupsToHide = array_slice($groupNames, 2);

			$shownPart = htmlspecialcharsbx(
				implode(', ', $groupsToShow)
				. ' ',
			);
			$hiddenPart = htmlspecialcharsbx(implode(', ', $groupsToHide));

			$result = Loc::getMessage(
				'BICONNECTOR_SUPERSET_DASHBOARD_GRID_GROUPS',
				[
					'#FIRST_GROUPS#' => $shownPart,
					'[hint]' => <<<HTML
						<span
							data-hint='{$hiddenPart}' 
							data-hint-no-icon
							data-hint-interactivity
							data-hint-center
							class="biconnector-grid-scope-hint-more"
						>
						HTML,
					'#COUNT#' => count($groupsToHide),
					'[/hint]' => '</span>',
				]
			);
		}
		else
		{
			$result = htmlspecialcharsbx(implode(', ', $groupNames));
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
			$row['columns'][$columnId] = $this->prepareColumn($row['data']['GROUPS']);
		}

		return $row;
	}
}
