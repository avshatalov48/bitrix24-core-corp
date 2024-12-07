<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Util\View;

if (!function_exists('renderNameColumn'))
{
	function renderNameColumn(array $data, array $arResult, bool $isActive): string
	{
		$flowId = (int) $data['flowId'];

		$flowName = HtmlFilter::encode($data['flowName']);
		$groupName = HtmlFilter::encode($data['groupName']);

		$dateLabel = $data['dateLabel'];
		$date = $data['date'];

		$teamLabel = $data['teamLabel'];
		$team = $data['team'];

		$groupLabel = $data['groupLabel'];

		$groupBlock = <<<HTML
			<div class="tasks-flow__list-name_info">
				<span class="tasks-flow__list-name_info-title">$groupLabel</span>
				<span class="tasks-flow__list-name_info-text">$groupName</span>
			</div>
		HTML;

		if (!$data['hidden'])
		{
			$uri = new Uri(
				CComponentEngine::makePathFromTemplate(
					$arResult['pathToGroupTasks'], ['group_id' => $data['groupId']]
				)
			);

			$uri->addParams([View::STATE_PARAMETER => $data['view']]);

			$uri = $uri->getUri();

			$groupBlock = <<<HTML
				<div class="tasks-flow__list-name_info --link">
					<span class="tasks-flow__list-name_info-title">$groupLabel</span>
					<a 
						class="tasks-flow__list-name_info-link" 
						title="$groupName" 
						onclick="BX.SidePanel.Instance.open('$uri')"
						data-id="tasks-flow-list-name-group-$flowId"
					>
						$groupName
					</a>
				</div>
			HTML;
		}

		$isFeatureEnabled = $arResult['isFeatureEnabled'] ? 'Y' : 'N';

		if ($data['demo'] && $data['editable'])
		{
			$nameClick = 'BX.Tasks.Flow.EditForm.createInstance({ flowId: ' . $flowId . ' })';
		}
		else
		{
			$nameClick = "BX.Tasks.Flow.ViewForm.showInstance({
				flowId: $flowId,
				bindElement: this,
				isFeatureEnabled: '$isFeatureEnabled'
			})";
		}

		if ($data['demo'])
		{
			$teamContentBlock = <<<HTML
				<span class="tasks-flow__list-name_info-text">$team</span>
			HTML;
		}
		else
		{
			$teamContentBlock = <<<HTML
				<span 
					class="tasks-flow__list-name_info-link"
					title="$team"
					onclick="{BX.Tasks.Flow.Grid.showTeam('$flowId', this)}"
					data-id="tasks-flow-list-name-team-$flowId"
				>
					$team
				</span>
			HTML;
		}

		return <<<HTML
			<div class="tasks-flow__list-cell tasks-flow__list-name">
				<div>
					<span 
						class="tasks-flow__list-name_flow-name"
						onclick="$nameClick"
						data-id="tasks-flow-list-name-$flowId"
					>
						$flowName
					</span>
				</div>
				<div class="tasks-flow__list-name_info">
					<span class="tasks-flow__list-name_info-title">$dateLabel</span>
					<span class="tasks-flow__list-name_info-text">$date</span>
				</div>
				<div class="tasks-flow__list-name_info --link">
					<span class="tasks-flow__list-name_info-title">
						$teamLabel
					</span>
					$teamContentBlock
				</div>
				$groupBlock
			</div>
		HTML;
	}
}
