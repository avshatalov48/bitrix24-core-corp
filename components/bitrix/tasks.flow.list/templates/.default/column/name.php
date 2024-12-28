<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Text\HtmlFilter;
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

		$isFeatureEnabled = $arResult['isFeatureEnabled'] ? 'Y' : 'N';

		if ($data['demo'] && $data['editable'])
		{
			$nameClick = 'BX.Tasks.Flow.EditForm.createInstance({ flowId: ' . $flowId . ' })';
		}
		else
		{
			$flowUri = new Uri(
				CComponentEngine::makePathFromTemplate(
					$arResult['pathToFlows'],
				)
			);
			$host = Application::getInstance()->getContext()->getRequest()->getServer()->getHttpHost();

			$flowUri->addParams(['ID_numsel' => 'exact']);
			$flowUri->addParams(['ID_from' => $flowId]);
			$flowUri->addParams(['ID_to' => $flowId]);
			$flowUri->addParams(['apply_filter' => 'Y']);
			$flowUri->setHost($host);
			$url = $flowUri->getUri();

			$nameClick = "BX.Tasks.Flow.ViewForm.showInstance({
				flowId: $flowId,
				bindElement: this,
				isFeatureEnabled: '$isFeatureEnabled',
				flowUrl: '$url',
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