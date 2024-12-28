<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\UI\Extension;
use Bitrix\Tasks\Flow\FlowFeature;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Uri;

if (!function_exists('renderFlow'))
{
	function renderFlow(array $record, array $flows): void
	{
		$flowId = (int)($record['FROM_VALUE'] ?? null);
		if ($flowId > 0)
		{
			renderLink($flowId, $flows);
		}

		renderArrow();

		$flowId = (int)$record['TO_VALUE'];
		if ($flowId > 0)
		{
			renderLink($flowId, $flows);
		}
	}

	function renderLink(int $flowId, array $flows): void
	{
		$name = $flows[$flowId]['name'] ?? '';
		$pathToFlows = $flows['pathToFlows'] ?? '';
		if ('' === $name)
		{
			?><span><?= Loc::getMessage('TASKS_LOG_HIDDEN_VALUE') ?></span><?php
			return;
		}

		$flowUri = new Uri(
			CComponentEngine::makePathFromTemplate(
				$pathToFlows,
			)
		);
		$host = Application::getInstance()->getContext()->getRequest()->getServer()->getHttpHost();
		$flowUri->addParams(['ID_numsel' => 'exact']);
		$flowUri->addParams(['ID_from' => $flowId]);
		$flowUri->addParams(['ID_to' => $flowId]);
		$flowUri->addParams(['apply_filter' => 'Y']);
		$flowUri->setHost($host);

		$url = $flowUri->getUri();

		Extension::load(['tasks.flow.view-form']);

		$isFeatureEnabled = FlowFeature::isFeatureEnabled() ? 'Y' : 'N';

		$onClick = "
			BX.Tasks.Flow.ViewForm.showInstance({
				flowId: {$flowId},
				bindElement: this,
				isFeatureEnabled: '{$isFeatureEnabled}',
				flowUrl: '{$url}',
			})
		";

		?><a class="tasks-log-pseudo-link" onclick="<?= $onClick ?>"><?= HtmlFilter::encode($name) ?></a><?php
	}

	function renderArrow(): void
	{
		?><span class="task-log-arrow">&rarr;</span><?php
	}
}
