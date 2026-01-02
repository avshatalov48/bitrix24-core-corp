<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use Bitrix\Tasks\Util\View;
use Bitrix\Main\Application;

if (!function_exists('renderNameColumn'))
{
	function renderNameColumn(array $data, array $arResult, bool $isActive): string
	{
		$flowId = (int) $data['flowId'];

		$flowName = HtmlFilter::encode($data['flowName']);
		$groupName = HtmlFilter::encode($data['groupName']);

		$dateLabel = $data['dateLabel'];
		$date = $data['date'];

		$groupLabel = $data['groupLabel'];

		$isPinned = $data['isPinned'] ? 'active' : 'by-hover';
		$pinText = $data['pinText'];
		$isPinnedBlock = <<<HTML
				<span title="$pinText" class="main-grid-cell-content-action main-grid-cell-content-action-pin main-grid-cell-content-action-$isPinned" onclick="BX.Tasks.Flow.Grid.pinFlow({$flowId})"></span>
			HTML
		;


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

		if ($data['demo'] && $data['editable'])
		{
			$isFeatureTrialable = Json::encode($arResult['isFeatureTrialable']);
			$nameClick = "BX.Tasks.Flow.EditForm.createInstance({ flowId: {$flowId}, isFeatureTrialable: '{$isFeatureTrialable}' })";
		}
		else
		{
			$nameClick = "BX.Tasks.Flow.ViewForm.showInstance({
				flowId: $flowId,
				bindElement: this,
			})";
		}

        return <<<HTML
            <div class="tasks-flow__list-cell tasks-flow__list-name tasks-flow__list-container">
                <div class="tasks-flow__list-info">
                    <div class="tasks-flow__list-name-wrapper">
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
                    $groupBlock
                </div>
                <div class="tasks-flow__list-name_info --link tasks-flow__list-pinned">
                    <span class="tasks-flow__list-name_info-title">
                    	$isPinnedBlock
                    </span>
                </div>
            </div>
        HTML;
    }
}